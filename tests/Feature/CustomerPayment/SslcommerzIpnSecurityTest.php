<?php

declare(strict_types=1);

namespace Tests\Feature\CustomerPayment;

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\FinancialYear;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VoucherType;
use App\Services\CustomerPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The SSLCommerz IPN is an unauthenticated endpoint. It must work without a
 * session, and it must never post money on the strength of the request body.
 */
class SslcommerzIpnSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const TRAN_ID = 'CUS-1-abcdef0123456789';

    private CustomerPaymentService $service;

    private Company $company;

    private Customer $customer;

    private User $companyUser;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'sslcommerz.store_id' => 'test_store',
            'sslcommerz.store_password' => 'test_pass',
            'sslcommerz.sandbox_mode' => true,
        ]);

        $this->service = app(CustomerPaymentService::class);

        $this->company = Company::factory()->create(['status' => true]);
        $this->companyUser = User::factory()->create(['company_id' => $this->company->id]);

        $this->customer = Customer::query()->create([
            'company_id' => $this->company->id,
            'customer_code' => 'SA-SSL-001',
            'name' => 'SSL Customer',
            'phone' => '01700000000',
            'email' => 'ssl@example.com',
            'customer_type' => 'Individual',
            'credit_limit' => 0,
            'opening_balance' => 0,
            'balance_type' => 'Receivable',
            'is_active' => true,
        ]);

        FinancialYear::query()->create([
            'company_id' => $this->company->id,
            'year_name' => '2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'is_closed' => false,
        ]);

        VoucherType::query()->create([
            'company_id' => $this->company->id,
            'code' => 'RV',
            'name' => 'Receipt Voucher',
            'nature' => VoucherType::NATURE_RECEIPT,
            'prefix' => 'RV',
            'last_number' => 0,
            'is_active' => true,
            'status' => 1,
        ]);

        Account::query()->create([
            'company_id' => $this->company->id,
            'account_code' => '1101',
            'account_name' => 'Test Bank Account',
            'account_type' => Account::TYPE_ASSET,
            'balance_type' => Account::BALANCE_DEBIT,
            'nature' => Account::NATURE_BANK,
            'level' => 1,
            'is_system' => false,
            'is_active' => true,
            'opening_balance' => 0,
        ]);

        Account::query()->create([
            'company_id' => $this->company->id,
            'account_code' => '1003',
            'account_name' => 'Accounts Receivable',
            'account_type' => Account::TYPE_ASSET,
            'balance_type' => Account::BALANCE_DEBIT,
            'nature' => Account::NATURE_CUSTOMER,
            'level' => 1,
            'is_system' => true,
            'is_active' => true,
            'opening_balance' => 0,
        ]);
    }

    private function pendingSslPayment(float $amount = 500.00): CustomerPayment
    {
        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $this->customer->customer_code,
            amount: $amount,
            paymentMethod: 'sslcommerz',
        );

        $payment->update(['transaction_reference' => self::TRAN_ID]);

        return $payment->refresh();
    }

    private function fakeValidation(array $remote): void
    {
        Http::fake([
            '*validationserverAPI.php*' => Http::response($remote),
        ]);
    }

    private function validRemote(array $override = []): array
    {
        return array_merge([
            'status' => 'VALID',
            'tran_id' => self::TRAN_ID,
            'currency' => 'BDT',
            'amount' => '500.00',
        ], $override);
    }

    private function ipn(array $override = [])
    {
        return $this->post(route('customer-payment.sslcommerz.ipn'), array_merge([
            'status' => 'VALID',
            'tran_id' => self::TRAN_ID,
            'val_id' => 'VAL123456',
            'bank_tran_id' => 'BANK-1',
            'card_type' => 'VISA-Dutch Bangla',
        ], $override));
    }

    public function test_valid_ipn_without_any_session_confirms_and_posts_one_balanced_voucher(): void
    {
        $payment = $this->pendingSslPayment();
        $this->fakeValidation($this->validRemote());

        // No actingAs(): the gateway server has no session.
        $this->ipn()->assertOk()->assertJson(['success' => true]);

        $payment->refresh();

        $this->assertSame('received', $payment->status);
        $this->assertNotNull($payment->voucher_id);
        $this->assertSame(self::TRAN_ID, $payment->transaction_reference);
        $this->assertSame('VAL123456', $payment->metadata['val_id']);
        $this->assertSame(1, Transaction::query()->count());

        $voucher = Transaction::query()->findOrFail($payment->voucher_id);

        $this->assertSame(0, bccomp((string) $voucher->total_debit, (string) $voucher->total_credit, 4));
        $this->assertSame(0, bccomp((string) $voucher->total_debit, '500', 4));
    }

    public function test_forged_ipn_that_the_gateway_does_not_validate_posts_nothing(): void
    {
        $payment = $this->pendingSslPayment();
        $this->fakeValidation(['status' => 'INVALID_TRANSACTION']);

        $this->ipn()->assertStatus(422);

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertNull($payment->voucher_id);
        $this->assertSame(0, Transaction::query()->count());
        $this->assertSame(0, LedgerEntry::query()->count());
    }

    public function test_amount_mismatch_between_gateway_and_payment_is_rejected(): void
    {
        $payment = $this->pendingSslPayment(500.00);
        $this->fakeValidation($this->validRemote(['amount' => '1.00']));

        $this->ipn()->assertStatus(422);

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_transaction_id_mismatch_between_gateway_and_callback_is_rejected(): void
    {
        $payment = $this->pendingSslPayment();
        $this->fakeValidation($this->validRemote(['tran_id' => 'CUS-9-someoneelse']));

        $this->ipn()->assertStatus(422);

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_unknown_transaction_id_returns_404_without_calling_the_gateway(): void
    {
        Http::fake();

        $this->ipn(['tran_id' => 'CUS-0-doesnotexist'])->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_ipn_cannot_confirm_a_non_sslcommerz_payment(): void
    {
        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $this->customer->customer_code,
            amount: 500.00,
            paymentMethod: 'bkash',
        );
        $payment->update(['transaction_reference' => self::TRAN_ID]);

        Http::fake();

        $this->ipn()->assertNotFound();

        Http::assertNothingSent();
        $this->assertSame('pending', $payment->refresh()->status);
    }

    public function test_duplicate_ipn_is_idempotent_and_does_not_revalidate_or_double_post(): void
    {
        $this->pendingSslPayment();
        $this->fakeValidation($this->validRemote());

        $this->ipn()->assertOk();
        $this->ipn()->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, Transaction::query()->count());
        Http::assertSentCount(1);
    }

    public function test_failed_ipn_is_ignored_so_a_later_successful_retry_still_confirms(): void
    {
        $payment = $this->pendingSslPayment();

        // One stub for the whole test. A bare Http::fake() would register a
        // catch-all stub that shadows any stub registered after it.
        $this->fakeValidation($this->validRemote());

        $this->ipn(['status' => 'FAILED', 'val_id' => ''])
            ->assertOk()
            ->assertJson(['ignored' => true]);

        $this->assertSame('pending', $payment->refresh()->status);
        Http::assertNothingSent();

        $this->ipn()->assertOk()->assertJson(['success' => true]);

        $this->assertSame('received', $payment->refresh()->status);
    }

    public function test_ipn_without_transaction_id_is_a_bad_request(): void
    {
        $this->post(route('customer-payment.sslcommerz.ipn'), ['status' => 'VALID'])
            ->assertStatus(400);
    }

    public function test_success_redirect_never_confirms_payment_it_only_redirects(): void
    {
        $payment = $this->pendingSslPayment();
        Http::fake();

        $this->get(route('customer-payment.sslcommerz.success', ['tran_id' => self::TRAN_ID]))
            ->assertRedirect(route('customer-payment.success', $payment->id));

        $this->post(route('customer-payment.sslcommerz.success'), ['tran_id' => self::TRAN_ID])
            ->assertRedirect(route('customer-payment.success', $payment->id));

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame(0, Transaction::query()->count());
        Http::assertNothingSent();
    }

    public function test_success_redirect_with_unknown_reference_is_404_and_missing_reference_shows_error_page(): void
    {
        $this->get(route('customer-payment.sslcommerz.success', ['tran_id' => 'CUS-0-nope']))
            ->assertNotFound();

        $this->get(route('customer-payment.sslcommerz.success'))
            ->assertOk()
            ->assertSee('Payment reference is missing.');
    }

    public function test_fail_and_cancel_pages_render_for_guests(): void
    {
        $this->get(route('customer-payment.sslcommerz.fail'))->assertOk()->assertSee('failed');
        $this->post(route('customer-payment.sslcommerz.cancel'))->assertOk()->assertSee('cancelled');
    }

    public function test_initiation_stores_our_transaction_id_so_the_ipn_can_find_the_payment(): void
    {
        Http::fake([
            '*gwprocess*' => Http::response([
                'status' => 'SUCCESS',
                'sessionkey' => 'ABC123DEF456',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/testABC123',
            ]),
        ]);

        $response = $this->actingAs($this->companyUser)
            ->postJson(route('customer-payment.initiate', $this->customer->customer_code), [
                'amount' => '100.50',
                'payment_method' => 'sslcommerz',
            ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'redirect_url' => 'https://sandbox.sslcommerz.com/EasyCheckOut/testABC123',
        ]);

        $payment = CustomerPayment::query()->findOrFail($response->json('payment_id'));

        $this->assertStringStartsWith('CUS-' . $this->customer->id . '-', (string) $payment->transaction_reference);
        $this->assertSame('sslcommerz', $payment->metadata['gateway']);
        $this->assertSame('pending', $payment->status);
        $this->assertNull($payment->voucher_id);
    }

    public function test_amount_with_more_than_two_decimals_is_rejected_before_calling_the_gateway(): void
    {
        Http::fake();

        $this->actingAs($this->companyUser)
            ->post(route('customer-payment.initiate', $this->customer->customer_code), [
                'amount' => '10.005',
                'payment_method' => 'sslcommerz',
            ])
            ->assertSessionHasErrors('amount');

        Http::assertNothingSent();
    }

    public function test_untrusted_gateway_redirect_host_falls_back_to_the_known_gateway_url(): void
    {
        Http::fake([
            '*gwprocess*' => Http::response([
                'status' => 'SUCCESS',
                'sessionkey' => 'ABC123DEF456',
                'GatewayPageURL' => 'https://evil.example.com/pay',
            ]),
        ]);

        $this->actingAs($this->companyUser)
            ->postJson(route('customer-payment.initiate', $this->customer->customer_code), [
                'amount' => '100',
                'payment_method' => 'sslcommerz',
            ])
            ->assertOk()
            ->assertJson([
                'redirect_url' => 'https://sandbox.sslcommerz.com/customer/pay/ABC123DEF456',
            ]);
    }
}