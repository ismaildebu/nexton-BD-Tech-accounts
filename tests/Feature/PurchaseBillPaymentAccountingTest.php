<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseBillPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VoucherType;
use App\Services\PurchaseAccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class PurchaseBillPaymentAccountingTest extends TestCase
{
    use RefreshDatabase;

    private int $companyId;

    private Account $payableAccount;

    private Account $cashAccount;

    private Account $bankAccount;

    private Account $purchaseAccount;

    private Vendor $vendor;

    private FinancialYear $financialYear;

    private VoucherType $paymentVoucherType;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();

        $this->companyId = (int) $company->id;

        session([
            'company_id' => $this->companyId,
        ]);

        /*
         * VoucherService requires an authenticated user because
         * transactions.created_by is NOT NULL.
         */
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->financialYear = FinancialYear::create([
            'company_id' => $this->companyId,
            'year_name' => 'FY 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
            'is_closed' => false,
        ]);

        $this->payableAccount = Account::create([
            'company_id' => $this->companyId,
            'account_code' => '3001',
            'account_name' => 'Accounts Payable',
            'account_type' => 'Liability',
            'nature' => 'Supplier',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $this->cashAccount = Account::create([
            'company_id' => $this->companyId,
            'account_code' => '1001',
            'account_name' => 'Cash in Hand',
            'account_type' => 'Asset',
            'nature' => Account::NATURE_CASH,
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $this->bankAccount = Account::create([
            'company_id' => $this->companyId,
            'account_code' => '1002',
            'account_name' => 'Bank Account',
            'account_type' => 'Asset',
            'nature' => Account::NATURE_BANK,
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $this->purchaseAccount = Account::create([
            'company_id' => $this->companyId,
            'account_code' => '5001',
            'account_name' => 'Purchase Expense',
            'account_type' => 'Expense',
            'nature' => 'General',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'company_id' => $this->companyId,
            'name' => 'Test Vendor',
            'account_id' => $this->payableAccount->id,
            'is_active' => true,
        ]);

        VoucherType::create([
            'company_id' => $this->companyId,
            'name' => 'Purchase Voucher',
            'code' => 'PUV',
            'nature' => 'journal',
            'prefix' => 'PUV',
            'description' => 'Purchase bill accounting transactions.',
            'is_active' => true,
            'status' => 1,
        ]);

        $this->paymentVoucherType = VoucherType::create([
            'company_id' => $this->companyId,
            'name' => 'Payment Voucher',
            'code' => 'PV',
            'nature' => 'payment',
            'prefix' => 'PV',
            'description' => 'Cash or bank payment transactions.',
            'is_active' => true,
            'status' => 1,
        ]);
    }

    private function createPurchaseBill(
        float $total = 1000.00
    ): PurchaseBill {
        $bill = PurchaseBill::create([
            'company_id' => $this->companyId,
            'vendor_id' => $this->vendor->id,
            'bill_number' => 'BILL-' . now()->format('Ymd') . '-0001',
            'bill_date' => '2026-10-03',
            'due_date' => '2026-10-31',
            'status' => 'Unpaid',
            'subtotal' => $total,
            'tax' => 0,
            'discount' => 0,
            'total' => $total,
            'paid_amount' => 0,
            'due_amount' => $total,
            'notes' => 'Payment accounting test bill.',
        ]);

        PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id,
            'account_id' => $this->purchaseAccount->id,
            'item_name' => 'Test Purchase Item',
            'description' => 'Purchase accounting test item.',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => $total,
            'total' => $total,
        ]);

        app(PurchaseAccountingService::class)
            ->createAutoVoucher($bill);

        return $bill->fresh();
    }

    private function createPayment(
        PurchaseBill $bill,
        float $amount,
        string $method = 'Cash'
    ): PurchaseBillPayment {
        return PurchaseBillPayment::create([
            'purchase_bill_id' => $bill->id,
            'payment_date' => '2026-10-03',
            'amount' => $amount,
            'payment_method' => $method,
            'reference' => 'TEST-PAYMENT',
            'notes' => 'Payment accounting test.',
        ]);
    }

    public function test_purchase_bill_payment_creates_and_posts_payment_voucher(): void
    {
        $bill = $this->createPurchaseBill(1000);

        $payment = $this->createPayment(
            $bill,
            1000,
            'Cash'
        );

        $transaction = app(PurchaseAccountingService::class)
            ->createPaymentVoucher($payment);

        $this->assertNotNull($transaction->id);

        $this->assertSame(
            $this->companyId,
            (int) $transaction->company_id
        );

        $this->assertSame(
            'PurchaseBillPayment',
            $transaction->reference_type
        );

        $this->assertSame(
            $payment->id,
            (int) $transaction->reference_id
        );

        $this->assertSame(
            Transaction::STATUS_POSTED,
            $transaction->status
        );

        $this->assertEquals(
            1000.0000,
            (float) $transaction->total_debit
        );

        $this->assertEquals(
            1000.0000,
            (float) $transaction->total_credit
        );

        $details = $transaction->details;

        $payableDetail = $details->first(
            fn ($detail) =>
                (int) $detail->account_id === $this->payableAccount->id
        );

        $cashDetail = $details->first(
            fn ($detail) =>
                (int) $detail->account_id === $this->cashAccount->id
        );

        $this->assertNotNull($payableDetail);
        $this->assertNotNull($cashDetail);

        $this->assertEquals(
            1000.0000,
            (float) $payableDetail->debit_amount
        );

        $this->assertEquals(
            0.0000,
            (float) $payableDetail->credit_amount
        );

        $this->assertEquals(
            0.0000,
            (float) $cashDetail->debit_amount
        );

        $this->assertEquals(
            1000.0000,
            (float) $cashDetail->credit_amount
        );
    }

    public function test_purchase_bill_payment_is_idempotent(): void
    {
        $bill = $this->createPurchaseBill(1000);

        $payment = $this->createPayment(
            $bill,
            1000,
            'Cash'
        );

        $service = app(PurchaseAccountingService::class);

        $first = $service->createPaymentVoucher($payment);

        $second = $service->createPaymentVoucher($payment);

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            1,
            Transaction::query()
                ->where('company_id', $this->companyId)
                ->where('reference_type', 'PurchaseBillPayment')
                ->where('reference_id', $payment->id)
                ->count()
        );
    }

    public function test_bank_payment_credits_bank_account(): void
    {
        /*
         * Fund the bank account so the "bank balance must never
         * be negative" guard in VoucherService passes.
         */
        $this->bankAccount->update([
            'opening_balance' => 10000,
        ]);

        $bill = $this->createPurchaseBill(2500);

        $payment = $this->createPayment(
            $bill,
            2500,
            'Bank'
        );

        $transaction = app(PurchaseAccountingService::class)
            ->createPaymentVoucher($payment);

        $bankDetail = $transaction->details->first(
            fn ($detail) =>
                (int) $detail->account_id === $this->bankAccount->id
        );

        $payableDetail = $transaction->details->first(
            fn ($detail) =>
                (int) $detail->account_id === $this->payableAccount->id
        );

        $this->assertNotNull($bankDetail);
        $this->assertNotNull($payableDetail);

        $this->assertEquals(
            0.0000,
            (float) $bankDetail->debit_amount
        );

        $this->assertEquals(
            2500.0000,
            (float) $bankDetail->credit_amount
        );

        $this->assertEquals(
            2500.0000,
            (float) $payableDetail->debit_amount
        );

        $this->assertEquals(
            0.0000,
            (float) $payableDetail->credit_amount
        );
    }

    public function test_bank_payment_rejects_insufficient_balance(): void
    {
        /*
         * Bank opening balance is 0, so a 2500 withdrawal
         * must be rejected by VoucherService.
         */
        $bill = $this->createPurchaseBill(2500);

        $payment = $this->createPayment(
            $bill,
            2500,
            'Bank'
        );

        $this->expectException(
            \App\Exceptions\VoucherValidationException::class
        );

        $this->expectExceptionMessage(
            'Insufficient bank balance in "Bank Account"'
        );

        app(PurchaseAccountingService::class)
            ->createPaymentVoucher($payment);
    }

    public function test_payment_rejects_overpayment(): void
    {
        $bill = $this->createPurchaseBill(1000);

        $payment = $this->createPayment(
            $bill,
            1001,
            'Cash'
        );

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Payment amount exceeds the outstanding Purchase Bill due amount.'
        );

        app(PurchaseAccountingService::class)
            ->createPaymentVoucher($payment);

        $this->assertDatabaseMissing(
            'transactions',
            [
                'reference_type' => 'PurchaseBillPayment',
                'reference_id' => $payment->id,
            ]
        );
    }

    public function test_payment_rejects_non_liability_vendor_account(): void
    {
        // 1. Create the bill while the vendor still has a valid Liability account.
        $bill = $this->createPurchaseBill(1000);

        // 2. Now corrupt the vendor's account mapping.
        $wrongAccount = Account::create([
            'company_id' => $this->companyId,
            'account_code' => '4001',
            'account_name' => 'Sales Revenue',
            'account_type' => 'Income',
            'nature' => 'General',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $this->vendor->update([
            'account_id' => $wrongAccount->id,
        ]);

        // 3. The payment must be rejected by createPaymentVoucher().
        $payment = $this->createPayment(
            $bill,
            1000,
            'Cash'
        );

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            "Vendor account #{$wrongAccount->id} must be a Liability account."
        );

        app(PurchaseAccountingService::class)
            ->createPaymentVoucher($payment);
    }

    public function test_payment_rejects_when_cash_or_bank_account_is_missing(): void
    {
        $this->cashAccount->update([
            'is_active' => false,
        ]);

        $bill = $this->createPurchaseBill(1000);

        $payment = $this->createPayment(
            $bill,
            1000,
            'Cash'
        );

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Cash or bank account is not configured for payment method: Cash'
        );

        app(PurchaseAccountingService::class)
            ->createPaymentVoucher($payment);
    }

    public function test_payment_rejects_unsupported_payment_method(): void
    {
        $bill = $this->createPurchaseBill(1000);

        $payment = $this->createPayment(
            $bill,
            1000,
            'Cheque'
        );

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Unsupported purchase payment method: Cheque'
        );

        app(PurchaseAccountingService::class)
            ->createPaymentVoucher($payment);
    }

    public function test_payment_is_company_scoped(): void
    {
        $otherCompany = Company::factory()->create();

        $otherPayableAccount = Account::create([
            'company_id' => $otherCompany->id,
            'account_code' => '3001',
            'account_name' => 'Other Accounts Payable',
            'account_type' => 'Liability',
            'nature' => 'Supplier',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $otherVendor = Vendor::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Company Vendor',
            'account_id' => $otherPayableAccount->id,
            'is_active' => true,
        ]);

        $bill = PurchaseBill::create([
            'company_id' => $otherCompany->id,
            'vendor_id' => $otherVendor->id,
            'bill_number' => 'OTHER-BILL-001',
            'bill_date' => '2026-10-03',
            'due_date' => '2026-10-31',
            'status' => 'Unpaid',
            'subtotal' => 1000,
            'tax' => 0,
            'discount' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
            'notes' => null,
        ]);

        PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id,
            'account_id' => $this->purchaseAccount->id,
            'item_name' => 'Other Company Item',
            'description' => null,
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 1000,
            'total' => 1000,
        ]);

        /*
         * The current company must not be able to resolve
         * another company's payment accounting data.
         */
        $payment = PurchaseBillPayment::create([
            'purchase_bill_id' => $bill->id,
            'payment_date' => '2026-10-03',
            'amount' => 1000,
            'payment_method' => 'Cash',
            'reference' => 'CROSS-COMPANY-TEST',
            'notes' => null,
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        app(PurchaseAccountingService::class)
            ->createPaymentVoucher($payment);
    }

    public function test_payment_does_not_allow_second_payment_beyond_bill_total(): void
    {
        $bill = $this->createPurchaseBill(1000);

        $firstPayment = $this->createPayment(
            $bill,
            600,
            'Cash'
        );

        app(PurchaseAccountingService::class)
            ->createPaymentVoucher($firstPayment);

        $secondPayment = $this->createPayment(
            $bill,
            401,
            'Cash'
        );

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'Payment amount exceeds the outstanding Purchase Bill due amount.'
        );

        app(PurchaseAccountingService::class)
            ->createPaymentVoucher($secondPayment);

        $this->assertDatabaseMissing(
            'transactions',
            [
                'reference_type' => 'PurchaseBillPayment',
                'reference_id' => $secondPayment->id,
            ]
        );
    }
}