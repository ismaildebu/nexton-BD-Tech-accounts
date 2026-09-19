<?php

declare(strict_types=1);

namespace Tests\Feature\CustomerPayment;

use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Transaction;
use App\Models\VoucherType;
use App\Models\User;
use App\Services\CustomerPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerPaymentTest extends TestCase
{
    use RefreshDatabase;

    private CustomerPaymentService $service;

    private Company $company;

    private Customer $customer;

    private FinancialYear $financialYear;

    private VoucherType $receiptVoucherType;

    private Account $bankAccount;

    private Account $receivableAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        User::factory()->create([
        'id' => 1,
        ]);

        $this->service = app(CustomerPaymentService::class);

        $this->company = Company::factory()->create();

        $this->customer = Customer::query()->create([
            'company_id' => $this->company->id,
            'customer_code' => 'SA-TEST-001',
            'name' => 'Test Customer',
            'phone' => '01700000000',
            'email' => 'customer@example.com',
            'customer_type' => 'Individual',
            'credit_limit' => 0,
            'opening_balance' => 0,
            'balance_type' => 'Receivable',
            'is_active' => true,
        ]);

        $this->financialYear = FinancialYear::query()->create([
            'company_id' => $this->company->id,
            'year_name' => '2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'is_closed' => false,
        ]);

        $this->receiptVoucherType = VoucherType::query()->create([
            'company_id' => $this->company->id,
            'code' => 'RV',
            'name' => 'Receipt Voucher',
            'nature' => VoucherType::NATURE_RECEIPT,
            'prefix' => 'RV',
            'last_number' => 0,
            'is_active' => true,
            'status' => 1,
        ]);

        $this->bankAccount = Account::query()->create([
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

        $this->receivableAccount = Account::query()->create([
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

    public function test_it_creates_pending_payment_using_customer_code(): void
    {
        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $this->customer->customer_code,
            amount: 40.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-001',
        );

        $payment->refresh();

        $this->assertSame(
            $this->customer->customer_code,
            $payment->reference_id
        );

        $this->assertSame(
            $this->company->id,
            $payment->company_id
        );

        $this->assertSame(
            $this->customer->id,
            $payment->customer_id
        );

        $this->assertSame(
            'pending',
            $payment->status
        );

        $this->assertNull($payment->voucher_id);

        $this->assertDatabaseHas('customer_payments', [
            'id' => $payment->id,
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'reference_id' => $this->customer->customer_code,
            'amount' => '40.0000',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'TXN-001',
            'status' => 'pending',
        ]);
    }

    public function test_it_marks_payment_received_and_creates_receipt_voucher(): void
    {
        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $this->customer->customer_code,
            amount: 40.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-002',
        );

        $receivedPayment = $this->service->markAsReceived($payment);

        $receivedPayment->refresh();

        $this->assertSame(
            'received',
            $receivedPayment->status
        );

        $this->assertNotNull(
            $receivedPayment->voucher_id
        );

        $voucher = Transaction::query()
            ->with('details')
            ->findOrFail($receivedPayment->voucher_id);

        $this->assertSame(
            $this->company->id,
            $voucher->company_id
        );

        $this->assertSame(
            $this->financialYear->id,
            $voucher->financial_year_id
        );

        $this->assertSame(
            $this->receiptVoucherType->id,
            $voucher->voucher_type_id
        );

        $this->assertSame(
            'CustomerPayment',
            $voucher->reference_type
        );

        $this->assertSame(
            $payment->id,
            $voucher->reference_id
        );

        $this->assertTrue(
            $voucher->is_balanced
        );

        $this->assertSame(
            Transaction::STATUS_APPROVED,
            $voucher->status
        );

        $this->assertCount(
            2,
            $voucher->details
        );

        $this->assertSame(
            '40.0000',
            number_format(
                (float) $voucher->details->sum('debit_amount'),
                4,
                '.',
                ''
            )
        );

        $this->assertSame(
            '40.0000',
            number_format(
                (float) $voucher->details->sum('credit_amount'),
                4,
                '.',
                ''
            )
        );

        $this->assertDatabaseHas('customer_payments', [
            'id' => $payment->id,
            'voucher_id' => $voucher->id,
            'status' => 'received',
        ]);
    }

    public function test_it_does_not_create_duplicate_receipt_voucher(): void
    {
        $payment = $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: $this->customer->customer_code,
            amount: 25.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-003',
        );

        $firstReceivedPayment = $this->service->markAsReceived($payment);

        $firstReceivedPayment->refresh();

        $firstVoucherId = $firstReceivedPayment->voucher_id;

        $this->assertNotNull($firstVoucherId);

        $voucherCountAfterFirstReceive = Transaction::query()
            ->where('company_id', $this->company->id)
            ->where('reference_type', 'CustomerPayment')
            ->where('reference_id', $payment->id)
            ->count();

        $secondReceivedPayment = $this->service->markAsReceived(
            $firstReceivedPayment
        );

        $secondReceivedPayment->refresh();

        $voucherCountAfterSecondReceive = Transaction::query()
            ->where('company_id', $this->company->id)
            ->where('reference_type', 'CustomerPayment')
            ->where('reference_id', $payment->id)
            ->count();

        $this->assertSame(
            $firstVoucherId,
            $secondReceivedPayment->voucher_id
        );

        $this->assertSame(
            1,
            $voucherCountAfterFirstReceive
        );

        $this->assertSame(
            $voucherCountAfterFirstReceive,
            $voucherCountAfterSecondReceive
        );
    }

    public function test_it_rejects_invalid_customer_code_for_selected_company(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(
            'Invalid customer code for the selected company.'
        );

        $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $this->customer->id,
            referenceId: 'INVALID-CUSTOMER-CODE',
            amount: 20.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-004',
        );
    }

    public function test_it_does_not_accept_customer_from_another_company(): void
    {
        $companyB = Company::factory()->create();

        $customerB = Customer::query()->create([
            'company_id' => $companyB->id,
            'customer_code' => 'CB-TEST-001',
            'name' => 'Company B Customer',
            'phone' => '01900000000',
            'email' => 'companyb@example.com',
            'customer_type' => 'Individual',
            'credit_limit' => 0,
            'opening_balance' => 0,
            'balance_type' => 'Receivable',
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);

        $this->expectExceptionMessage(
            'Invalid customer code for the selected company.'
        );

        $this->service->createPayment(
            companyId: $this->company->id,
            customerId: $customerB->id,
            referenceId: $customerB->customer_code,
            amount: 20.00,
            paymentMethod: 'bank_transfer',
            transactionReference: 'TXN-005',
        );
    }
}