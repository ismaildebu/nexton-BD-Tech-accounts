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
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A manual bKash TrxID is only a customer's claim. It must never reach the
 * ledger until an admin verifies it.
 */
class BkashManualPaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private CustomerPaymentService $service;

    private Company $company;

    private Customer $customer;

    private User $companyUser;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->service = app(CustomerPaymentService::class);

        $this->company = Company::factory()->create(['status' => true]);

        $this->companyUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->customer = $this->makeCustomer($this->company, 'SA-BK-001');

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

    private function makeCustomer(Company $company, string $code): Customer
    {
        return Customer::query()->create([
            'company_id' => $company->id,
            'customer_code' => $code,
            'name' => 'Test Customer ' . $code,
            'phone' => '01700000000',
            'email' => $code . '@example.com',
            'customer_type' => 'Individual',
            'credit_limit' => 0,
            'opening_balance' => 0,
            'balance_type' => 'Receivable',
            'is_active' => true,
        ]);
    }

    private function makeBkashPayment(?Customer $customer = null, float $amount = 500.00): CustomerPayment
    {
        $customer ??= $this->customer;

        return $this->service->createPayment(
            companyId: $customer->company_id,
            customerId: $customer->id,
            referenceId: $customer->customer_code,
            amount: $amount,
            paymentMethod: 'bkash',
        );
    }

    private function submit(CustomerPayment $payment, array $data, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->companyUser)
            ->post(route('customer-payment.bkash.submit', $payment->id), $data);
    }

    public function test_submitting_bkash_details_does_not_post_to_the_ledger(): void
    {
        $payment = $this->makeBkashPayment();

        $this->submit($payment, [
            'transaction_id' => ' 8n7a6d5ez1 ',
            'sender_number' => '01712345678',
        ])->assertRedirect(route('customer-payment.success', $payment->id));

        $payment->refresh();

        $this->assertSame('pending', $payment->status);
        $this->assertNull($payment->voucher_id);
        $this->assertSame('8N7A6D5EZ1', $payment->transaction_reference);
        $this->assertSame('01712345678', $payment->metadata['sender_number']);
        $this->assertTrue($payment->metadata['awaiting_verification']);

        $this->assertSame(0, Transaction::query()->count());
        $this->assertSame(0, LedgerEntry::query()->count());
    }

    public function test_admin_verification_creates_exactly_one_balanced_voucher(): void
    {
        $payment = $this->makeBkashPayment(amount: 750.00);

        $this->submit($payment, [
            'transaction_id' => 'AB12CD34EF',
            'sender_number' => '01712345678',
        ]);

        $verified = $this->service->verifyPayment($payment->refresh(), $this->companyUser->id);
        $this->service->verifyPayment($verified, $this->companyUser->id); // replay

        $verified->refresh();

        $this->assertSame('verified', $verified->status);
        $this->assertNotNull($verified->voucher_id);
        $this->assertSame(1, Transaction::query()->count());

        $voucher = Transaction::query()->findOrFail($verified->voucher_id);

        $this->assertSame(
            0,
            bccomp((string) $voucher->total_debit, (string) $voucher->total_credit, 4)
        );
        $this->assertSame(0, bccomp((string) $voucher->total_debit, '750', 4));
    }

    public function test_bkash_payment_cannot_be_verified_without_a_transaction_id(): void
    {
        $payment = $this->makeBkashPayment();

        $this->expectException(\RuntimeException::class);

        try {
            $this->service->verifyPayment($payment, $this->companyUser->id);
        } finally {
            $this->assertSame(0, Transaction::query()->count());
            $this->assertSame('pending', $payment->refresh()->status);
        }
    }

    public function test_duplicate_transaction_id_is_rejected_with_a_validation_error(): void
    {
        $first = $this->makeBkashPayment();
        $second = $this->makeBkashPayment();

        $this->submit($first, [
            'transaction_id' => 'AB12CD34EF',
            'sender_number' => '01712345678',
        ]);

        $this->submit($second, [
            'transaction_id' => 'ab12cd34ef', // same ID, different case
            'sender_number' => '01712345678',
        ])->assertSessionHasErrors('transaction_id');

        $this->assertNull($second->refresh()->transaction_reference);
    }

    public function test_details_cannot_be_changed_after_verification(): void
    {
        $payment = $this->makeBkashPayment();

        $this->submit($payment, [
            'transaction_id' => 'AB12CD34EF',
            'sender_number' => '01712345678',
        ]);
        $this->service->verifyPayment($payment->refresh(), $this->companyUser->id);

        $this->submit($payment, [
            'transaction_id' => 'ZZ99YY88XX',
            'sender_number' => '01799999999',
        ])->assertSessionHasErrors('transaction_id');

        $this->assertSame('AB12CD34EF', $payment->refresh()->transaction_reference);
        $this->assertSame('verified', $payment->status);
    }

    public function test_malformed_transaction_id_and_sender_number_are_rejected(): void
    {
        $payment = $this->makeBkashPayment();

        $this->submit($payment, [
            'transaction_id' => 'abc',
            'sender_number' => '12345',
        ])->assertSessionHasErrors(['transaction_id', 'sender_number']);

        $this->assertNull($payment->refresh()->transaction_reference);
    }

    public function test_user_of_another_company_cannot_submit_for_this_payment(): void
    {
        $payment = $this->makeBkashPayment();

        $otherCompany = Company::factory()->create(['status' => true]);
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);

        $this->submit($payment, [
            'transaction_id' => 'AB12CD34EF',
            'sender_number' => '01712345678',
        ], $otherUser)->assertNotFound();

        $this->assertNull($payment->refresh()->transaction_reference);
    }
}
