<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\User;
use App\Models\FinancialYear;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\Transaction;
use App\Models\Vendor;
use App\Models\VoucherType;
use App\Services\PurchaseAccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PurchaseBillAccountingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private FinancialYear $financialYear;

    private Account $expenseAccount;

    private Account $payableAccount;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        session([
            'company_id' => $this->company->id,
        ]);

        $this->financialYear = FinancialYear::create([
            'company_id' => $this->company->id,
            'year_name' => now()->year . '-' . (now()->year + 1),
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
            'is_closed' => false,
        ]);

        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_code' => '5002',
            'account_name' => 'Purchase Expense',
            'account_type' => 'Expense',
            'is_active' => true,
        ]);

        $this->payableAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_code' => '2001',
            'account_name' => 'Accounts Payable',
            'account_type' => 'Liability',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'company_id' => $this->company->id,
            'name' => 'Test User',
            'email' => 'purchase-accounting-test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($this->user);

        $this->vendor = Vendor::create([
            'company_id' => $this->company->id,
            'name' => 'Test Vendor',
            'account_id' => $this->payableAccount->id,
            'is_active' => true,
        ]);

        VoucherType::create([
            'company_id' => $this->company->id,
            'name' => 'Purchase Voucher',
            'code' => 'PUV',
            'nature' => 'journal',
            'prefix' => 'PUV',
            'last_number' => 0,
            'is_active' => true,
            'description' => 'Purchase transactions generated from purchase bills.',
        ]);
    }

    public function test_purchase_bill_creates_and_posts_automatic_purchase_voucher(): void
    {
        $bill = $this->createBill();

        $transaction = app(PurchaseAccountingService::class)
            ->createAutoVoucher($bill);

        $this->assertInstanceOf(
            Transaction::class,
            $transaction
        );

        $this->assertSame(
            Transaction::STATUS_POSTED,
            $transaction->status
        );

        $this->assertSame(
            'PurchaseBill',
            $transaction->reference_type
        );

        $this->assertSame(
            $bill->id,
            (int) $transaction->reference_id
        );

        $this->assertSame(
            'PB-' . $bill->bill_number,
            $transaction->reference_number
        );

        $this->assertSame(
            $transaction->id,
            (int) $bill->fresh()->transaction_id
        );

        $transaction->load('details');

        $this->assertCount(
            2,
            $transaction->details
        );

        $this->assertEqualsWithDelta(
            1000.0000,
            (float) $transaction->details->sum('debit_amount'),
            0.0001
        );

        $this->assertEqualsWithDelta(
            1000.0000,
            (float) $transaction->details->sum('credit_amount'),
            0.0001
        );
    }

    public function test_purchase_bill_voucher_is_idempotent(): void
    {
        $bill = $this->createBill();

        $service = app(PurchaseAccountingService::class);

        $first = $service->createAutoVoucher($bill);

        $second = $service->createAutoVoucher(
            $bill->fresh()
        );

        $this->assertSame(
            $first->id,
            $second->id
        );

        $this->assertSame(
            1,
            Transaction::query()
                ->where('company_id', $this->company->id)
                ->where('reference_type', 'PurchaseBill')
                ->where('reference_id', $bill->id)
                ->count()
        );
    }

    public function test_purchase_bill_rejects_non_liability_vendor_account(): void
    {
        $wrongAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_code' => '4002',
            'account_name' => 'Wrong Income Account',
            'account_type' => 'Income',
            'is_active' => true,
        ]);

        $this->vendor->update([
            'account_id' => $wrongAccount->id,
        ]);

        $bill = $this->createBill();

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'must be a Liability account'
        );

        app(PurchaseAccountingService::class)
            ->createAutoVoucher($bill);
    }

    public function test_purchase_bill_rejects_non_expense_or_asset_item_account(): void
    {
        $incomeAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_code' => '4002',
            'account_name' => 'Income Account',
            'account_type' => 'Income',
            'is_active' => true,
        ]);

        $bill = $this->createBill([
            'account_id' => $incomeAccount->id,
        ]);

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'must be an Expense or Asset account'
        );

        app(PurchaseAccountingService::class)
            ->createAutoVoucher($bill);
    }

    public function test_purchase_bill_rejects_non_zero_tax(): void
    {
        $bill = $this->createBill([
            'tax' => 100,
            'total' => 1100,
            'due_amount' => 1100,
        ]);

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'tax cannot be posted automatically'
        );

        app(PurchaseAccountingService::class)
            ->createAutoVoucher($bill);
    }

    public function test_purchase_bill_rejects_non_zero_discount(): void
    {
        $bill = $this->createBill([
            'discount' => 100,
            'total' => 900,
            'due_amount' => 900,
        ]);

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage(
            'discount cannot be posted automatically'
        );

        app(PurchaseAccountingService::class)
            ->createAutoVoucher($bill);
    }

    public function test_purchase_bill_rolls_back_when_accounting_fails(): void
    {
        $bill = $this->createBill([
            'tax' => 100,
            'total' => 1100,
            'due_amount' => 1100,
        ]);

        try {
            app(PurchaseAccountingService::class)
                ->createAutoVoucher($bill);
        } catch (RuntimeException) {
            // Expected.
        }

        $this->assertNull(
            $bill->fresh()->transaction_id
        );

        $this->assertSame(
            0,
            Transaction::query()
                ->where('reference_type', 'PurchaseBill')
                ->where('reference_id', $bill->id)
                ->count()
        );
    }

    public function test_purchase_bill_is_company_scoped(): void
    {
        $otherCompany = Company::factory()->create();

        $otherPayableAccount = Account::factory()->create([
            'company_id' => $otherCompany->id,
            'account_code' => '2001',
            'account_name' => 'Other Accounts Payable',
            'account_type' => 'Liability',
            'is_active' => true,
        ]);

        $otherVendor = Vendor::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Company Vendor',
            'account_id' => $otherPayableAccount->id,
            'is_active' => true,
        ]);

        $bill = $this->createBill([
            'vendor_id' => $otherVendor->id,
        ]);

        $this->expectException(RuntimeException::class);

        app(PurchaseAccountingService::class)
            ->createAutoVoucher($bill);
    }

    private function createBill(array $overrides = []): PurchaseBill
    {
        $billData = array_merge([
            'company_id' => $this->company->id,
            'vendor_id' => $this->vendor->id,
            'bill_number' => 'BILL-20261003-0001',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'Unpaid',
            'subtotal' => 1000,
            'tax' => 0,
            'discount' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
        ], $overrides);

        $bill = PurchaseBill::create($billData);

        PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id,
            'account_id' => $overrides['account_id']
                ?? $this->expenseAccount->id,
            'item_name' => 'Test Purchase Item',
            'description' => 'Purchase accounting test item',
            'quantity' => 10,
            'unit' => 'pcs',
            'unit_price' => 100,
            'total' => 1000,
        ]);

        return $bill;
    }
}
