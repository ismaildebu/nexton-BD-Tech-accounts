<?php

declare(strict_types=1);

namespace Tests\Feature\CustomerPayment;

use App\Models\Account;
use App\Models\BkashPayment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VoucherType;
use App\Services\CustomerPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Merchant bKash (checkout URL) flow: initiate -> callback -> execute.
 */
class BkashMerchantPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const MERCHANT_INVOICE = 'INV-1-abc123';

    private Company $company;

    private Customer $customer;

    private User $companyUser;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Cache::forget('bkash_token');

        config([
            'bkash.base_url' => 'https://bkash.test/v1',
            'bkash.app_key' => 'key',
            'bkash.app_secret' => 'secret',
            'bkash.username' => 'user',
            'bkash.password' => 'pass',
        ]);

        $this->company = Company::factory()->create(['status' => true]);
        $this->companyUser = User::factory()->create(['company_id' => $this->company->id]);

        $this->customer = Customer::query()->create([
            'company_id' => $this->company->id,
            'customer_code' => 'SA-BK-MER-1',
            'name' => 'Merchant Customer',
            'phone' => '01700000000',
            'email' => 'merchant@example.com',
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

    private function makeInvoice(string $total = '1000.00', string $paid = '0.00'): Invoice
    {
        return Invoice::query()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-T-' . uniqid(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'unpaid',
            'total_amount' => $total,
            'paid_amount' => $paid,
        ]);
    }

    private function makeBkashPayment(Invoice $invoice, string $amount, string $paymentId = 'PID-1'): BkashPayment
    {
        return BkashPayment::query()->create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'currency' => 'BDT',
            'merchant_invoice_number' => self::MERCHANT_INVOICE,
            'status' => 'initiated',
        ]);
    }

    private function fakeExecute(array $override = [], string $paymentId = 'PID-1'): void
    {
        Http::fake([
            '*token/grant' => Http::response(['id_token' => 'tok']),
            '*checkout/execute' => Http::response(array_merge([
                'statusCode' => '0000',
                'statusMessage' => 'Successful',
                'paymentID' => $paymentId,
                'trxID' => 'TRXABC12345',
                'transactionStatus' => 'Completed',
                'amount' => '400.00',
                'currency' => 'BDT',
                'merchantInvoiceNumber' => self::MERCHANT_INVOICE,
            ], $override)),
        ]);
    }

    private function hitCallback(string $paymentId = 'PID-1', string $status = 'success')
    {
        return $this->get(route('bkash.callback', ['paymentID' => $paymentId, 'status' => $status]));
    }

    private function executeCalls(): int
    {
        return Http::recorded(
            fn ($request) => str_contains($request->url(), '/execute')
        )->count();
    }

    public function test_initiate_works_with_the_active_company_session(): void
    {
        $invoice = $this->makeInvoice();

        Http::fake([
            '*token/grant' => Http::response(['id_token' => 'tok']),
            '*checkout/create' => Http::response([
                'statusCode' => '0000',
                'paymentID' => 'PID-NEW',
                'bkashURL' => 'https://pay.bka.sh/checkout/x',
            ]),
        ]);

        $this->actingAs($this->companyUser)
            ->postJson(route('bkash.initiate', $invoice->id))
            ->assertOk()
            ->assertJson(['success' => true, 'payment_url' => 'https://pay.bka.sh/checkout/x']);

        $this->assertDatabaseHas('bkash_payments', [
            'payment_id' => 'PID-NEW',
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'status' => 'initiated',
        ]);
    }

    public function test_initiate_is_refused_for_an_invoice_of_another_company(): void
    {
        $otherCompany = Company::factory()->create(['status' => true]);
        $otherCustomer = Customer::query()->create([
            'company_id' => $otherCompany->id,
            'customer_code' => 'OTHER-1',
            'name' => 'Other',
            'customer_type' => 'Individual',
            'is_active' => true,
        ]);
        $foreign = Invoice::query()->create([
            'company_id' => $otherCompany->id,
            'customer_id' => $otherCustomer->id,
            'invoice_number' => 'INV-OTHER-1',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'unpaid',
            'total_amount' => '500.00',
            'paid_amount' => '0.00',
        ]);

        Http::fake();

        $response = $this->actingAs($this->companyUser)
            ->postJson(route('bkash.initiate', $foreign->id));

        $this->assertContains($response->status(), [403, 404]);
        Http::assertNothingSent();
        $this->assertSame(0, BkashPayment::withoutGlobalScopes()->count());
    }

    public function test_callback_records_the_payment_once_and_updates_the_invoice(): void
    {
        $invoice = $this->makeInvoice('1000.00');
        $bkash = $this->makeBkashPayment($invoice, '400.00');
        $this->fakeExecute();

        $this->hitCallback()
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success');

        $this->assertSame('completed', $bkash->refresh()->status);
        $this->assertSame('TRXABC12345', $bkash->trx_id);

        $invoice->refresh();
        $this->assertSame('400.00', (string) $invoice->paid_amount);
        $this->assertSame('partial', $invoice->status);

        $customerPayment = CustomerPayment::query()->where('transaction_reference', 'TRXABC12345')->firstOrFail();
        $this->assertSame('received', $customerPayment->status);
        $this->assertNotNull($customerPayment->voucher_id);
        $this->assertSame(1, Transaction::query()->count());
    }

    public function test_admin_verification_afterwards_does_not_count_the_payment_twice(): void
    {
        $invoice = $this->makeInvoice('1000.00', '300.00');
        $this->makeBkashPayment($invoice, '400.00');
        $this->fakeExecute();

        $this->hitCallback();

        $customerPayment = CustomerPayment::query()->where('transaction_reference', 'TRXABC12345')->firstOrFail();

        app(CustomerPaymentService::class)->verifyPayment($customerPayment, $this->companyUser->id);

        $invoice->refresh();
        $this->assertSame('700.00', (string) $invoice->paid_amount); // 300 + 400, not 1100
        $this->assertSame('partial', $invoice->status);
        $this->assertSame('verified', $customerPayment->refresh()->status);
        $this->assertSame(1, Transaction::query()->count());
    }

    public function test_a_payment_that_settles_the_invoice_can_still_be_verified(): void
    {
        // Before the fix verification threw "exceeds the invoice outstanding amount".
        $invoice = $this->makeInvoice('400.00');
        $this->makeBkashPayment($invoice, '400.00');
        $this->fakeExecute();

        $this->hitCallback();

        $customerPayment = CustomerPayment::query()->where('transaction_reference', 'TRXABC12345')->firstOrFail();

        $verified = app(CustomerPaymentService::class)->verifyPayment($customerPayment, $this->companyUser->id);

        $this->assertSame('verified', $verified->status);
        $this->assertSame('400.00', (string) $invoice->refresh()->paid_amount);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_callback_replay_does_not_execute_or_post_twice(): void
    {
        $invoice = $this->makeInvoice('1000.00');
        $this->makeBkashPayment($invoice, '400.00');
        $this->fakeExecute();

        $this->hitCallback();
        $this->hitCallback()->assertSessionHas('success');

        $this->assertSame(1, $this->executeCalls());
        $this->assertSame(1, Transaction::query()->count());
        $this->assertSame('400.00', (string) $invoice->refresh()->paid_amount);
    }

    public function test_failed_execute_records_nothing(): void
    {
        $invoice = $this->makeInvoice('1000.00');
        $bkash = $this->makeBkashPayment($invoice, '400.00');
        $this->fakeExecute(['statusCode' => '2023', 'trxID' => null]);

        $this->hitCallback()->assertSessionHas('error');

        $this->assertSame('failed', $bkash->refresh()->status);
        $this->assertSame('0.00', (string) $invoice->refresh()->paid_amount);
        $this->assertSame(0, CustomerPayment::query()->count());
        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_amount_mismatch_from_bkash_is_not_posted_and_goes_to_review(): void
    {
        $invoice = $this->makeInvoice('1000.00');
        $bkash = $this->makeBkashPayment($invoice, '400.00');
        $this->fakeExecute(['amount' => '999.00']);

        $this->hitCallback()->assertSessionHas('warning');

        $this->assertSame('pending', $bkash->refresh()->status);
        $this->assertSame('0.00', (string) $invoice->refresh()->paid_amount);
        $this->assertSame(0, Transaction::query()->count());
    }

    public function test_local_posting_failure_goes_to_review_and_a_retry_does_not_execute_again(): void
    {
        // The payment (500) is above what the invoice still owes (300): posting must fail.
        $invoice = $this->makeInvoice('300.00');
        $bkash = $this->makeBkashPayment($invoice, '500.00');
        $this->fakeExecute(['amount' => '500.00']);

        $this->hitCallback()->assertSessionHas('warning');

        $this->assertSame('pending', $bkash->refresh()->status);
        $this->assertSame('0.00', (string) $invoice->refresh()->paid_amount);
        $this->assertSame(0, CustomerPayment::query()->count());
        $this->assertSame(0, Transaction::query()->count());
        $this->assertSame(1, $this->executeCalls());

        // The invoice is corrected, then the callback is replayed.
        $invoice->update(['total_amount' => '600.00']);

        $this->hitCallback()->assertSessionHas('success');

        $this->assertSame('completed', $bkash->refresh()->status);
        $this->assertSame('500.00', (string) $invoice->refresh()->paid_amount);
        $this->assertSame(1, Transaction::query()->count());
        $this->assertSame(1, $this->executeCalls()); // bKash was never executed a second time
    }

    public function test_cancelled_callback_changes_nothing(): void
    {
        $invoice = $this->makeInvoice('1000.00');
        $bkash = $this->makeBkashPayment($invoice, '400.00');
        Http::fake();

        $this->hitCallback('PID-1', 'cancel')->assertSessionHas('warning');

        $this->assertSame('cancelled', $bkash->refresh()->status);
        $this->assertSame('0.00', (string) $invoice->refresh()->paid_amount);
        Http::assertNothingSent();
    }
}
