<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VoucherType;
use App\Services\PurchaseAccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PurchaseBillItemAccountNatureTest extends TestCase
{
    use RefreshDatabase;

    private int $companyId;

    private Account $payableAccount;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();

        $this->companyId = (int) $company->id;

        session([
            'company_id' => $this->companyId,
        ]);

        $this->actingAs(User::factory()->create());

        FinancialYear::create([
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

        $this->vendor = Vendor::create([
            'company_id' => $this->companyId,
            'name' => 'Nature Test Vendor',
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
    }

    private function makeAssetAccount(
        string $code,
        string $name,
        string $nature
    ): Account {
        return Account::create([
            'company_id' => $this->companyId,
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => 'Asset',
            'nature' => $nature,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
    }

    private function createBillFor(Account $itemAccount): PurchaseBill
    {
        $bill = PurchaseBill::create([
            'company_id' => $this->companyId,
            'vendor_id' => $this->vendor->id,
            'bill_number' => 'BILL-' . uniqid(),
            'bill_date' => '2026-10-03',
            'due_date' => '2026-10-31',
            'status' => 'Unpaid',
            'subtotal' => 1000,
            'tax' => 0,
            'discount' => 0,
            'total' => 1000,
            'paid_amount' => 0,
            'due_amount' => 1000,
            'notes' => 'Account nature test bill.',
        ]);

        PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id,
            'account_id' => $itemAccount->id,
            'item_name' => 'Nature Test Item',
            'description' => 'Account nature test.',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 1000,
            'total' => 1000,
        ]);

        return $bill;
    }

    public function test_purchase_item_cannot_use_customer_receivable_account(): void
    {
        $receivable = $this->makeAssetAccount(
            '1003',
            'Accounts Receivable',
            'Customer'
        );

        $bill = $this->createBillFor($receivable);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot receive purchases');

        app(PurchaseAccountingService::class)->createAutoVoucher($bill);
    }

    public function test_purchase_item_cannot_use_cash_account(): void
    {
        $cash = $this->makeAssetAccount(
            '1001',
            'Cash in Hand',
            Account::NATURE_CASH
        );

        $bill = $this->createBillFor($cash);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot receive purchases');

        app(PurchaseAccountingService::class)->createAutoVoucher($bill);
    }

    public function test_purchase_item_cannot_use_bank_account(): void
    {
        $bank = $this->makeAssetAccount(
            '1002',
            'Bank Account',
            Account::NATURE_BANK
        );

        $bill = $this->createBillFor($bank);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot receive purchases');

        app(PurchaseAccountingService::class)->createAutoVoucher($bill);
    }

    public function test_rejected_purchase_posts_nothing(): void
    {
        $receivable = $this->makeAssetAccount(
            '1003',
            'Accounts Receivable',
            'Customer'
        );

        $bill = $this->createBillFor($receivable);

        try {
            app(PurchaseAccountingService::class)->createAutoVoucher($bill);
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(
            0,
            Transaction::query()
                ->where('company_id', $this->companyId)
                ->where('reference_type', 'PurchaseBill')
                ->count()
        );

        $this->assertNull($bill->fresh()->transaction_id);
    }

    public function test_purchase_item_can_use_inventory_asset_account(): void
    {
        $inventory = $this->makeAssetAccount(
            '1004',
            'Inventory / Stock',
            'Inventory'
        );

        $bill = $this->createBillFor($inventory);

        $transaction = app(PurchaseAccountingService::class)
            ->createAutoVoucher($bill);

        $this->assertSame(
            Transaction::STATUS_POSTED,
            $transaction->status
        );

        $inventoryDetail = $transaction->details->first(
            fn ($detail) =>
                (int) $detail->account_id === $inventory->id
        );

        $this->assertNotNull($inventoryDetail);

        $this->assertEquals(
            1000.0000,
            (float) $inventoryDetail->debit_amount
        );
    }

    public function test_purchase_item_can_use_fixed_asset_account(): void
    {
        $equipment = $this->makeAssetAccount(
            '1005',
            'Furniture & Equipment',
            'Fixed Asset'
        );

        $bill = $this->createBillFor($equipment);

        $transaction = app(PurchaseAccountingService::class)
            ->createAutoVoucher($bill);

        $this->assertSame(
            Transaction::STATUS_POSTED,
            $transaction->status
        );
    }
}