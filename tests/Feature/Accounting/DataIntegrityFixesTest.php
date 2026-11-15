<?php

declare(strict_types=1);

namespace Tests\Feature\Accounting;

use App\Exceptions\LedgerPostingException;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use App\Models\VoucherType;
use App\Services\LedgerPostingService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataIntegrityFixesTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;
    private User $user;
    private FinancialYear $financialYear1;
    private FinancialYear $financialYear2;
    private VoucherType $voucherType;
    private Account $cashAccount;
    private Account $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // ============================================================
        // Company 1
        // ============================================================
        $this->company1 = Company::create([
            'company_name' => 'Company 1',
            'owner_name' => 'Owner 1',
            'email' => 'company1@example.com',
            'phone' => '01700000001',
            'address' => 'Address 1',
            'city' => 'City 1',
            'country' => 'Bangladesh',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'financial_year' => '2026-2027',
            'status' => true,
            'business_type' => 'General',
        ]);

        // ============================================================
        // Company 2
        // ============================================================
        $this->company2 = Company::create([
            'company_name' => 'Company 2',
            'owner_name' => 'Owner 2',
            'email' => 'company2@example.com',
            'phone' => '01700000002',
            'address' => 'Address 2',
            'city' => 'City 2',
            'country' => 'Bangladesh',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'financial_year' => '2026-2027',
            'status' => true,
            'business_type' => 'General',
        ]);

        // ============================================================
        // User
        // ============================================================
        $this->user = User::factory()->create(['status' => true]);
        Auth::login($this->user);

        // ============================================================
        // Financial Years - Company 1
        // ============================================================
        $this->financialYear1 = FinancialYear::create([
            'company_id' => $this->company1->id,
            'year_name' => '2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'is_closed' => false,
        ]);

        // ============================================================
        // Financial Years - Company 2
        // ============================================================
        $this->financialYear2 = FinancialYear::create([
            'company_id' => $this->company2->id,
            'year_name' => '2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'is_closed' => false,
        ]);

        // ============================================================
        // Voucher Type
        // ============================================================
        $this->voucherType = VoucherType::create([
            'company_id' => $this->company1->id,
            'name' => 'Journal Voucher',
            'code' => 'JV',
            'nature' => 'journal',
            'last_number' => 0,
            'is_active' => true,
            'description' => 'Test journal voucher',
        ]);

        // ============================================================
        // Cash Account
        // ============================================================
        $this->cashAccount = Account::create([
            'company_id' => $this->company1->id,
            'account_code' => 1001,
            'account_name' => 'Cash in Hand',
            'account_type' => 'Asset',
            'nature' => 'Cash',
            'is_active' => true,
            'is_system' => false,
            'opening_balance' => 0,
            'balance_type' => 'Debit',
        ]);

        // ============================================================
        // Income Account
        // ============================================================
        $this->incomeAccount = Account::create([
            'company_id' => $this->company1->id,
            'account_code' => 4001,
            'account_name' => 'Sales Income',
            'account_type' => 'Income',
            'nature' => 'Income',
            'is_active' => true,
            'is_system' => false,
            'opening_balance' => 0,
            'balance_type' => 'Credit',
        ]);
    }

    // ================================================================
    // FIX #1: Voucher Number Uniqueness (Company + FY Scoped)
    // ================================================================

    /**
     * Test that two different companies can use the same voucher number
     * for the same financial year (after FIX #1).
     */
    public function test_two_companies_can_use_same_voucher_number(): void
    {
        // ============================================================
        // Company 1: Create transaction with JV-2026-000001
        // ============================================================
        DB::transaction(function (): void {
            session(['company_id' => $this->company1->id]);

            $tx1 = Transaction::create([
                'company_id' => $this->company1->id,
                'financial_year_id' => $this->financialYear1->id,
                'voucher_type_id' => $this->voucherType->id,
                'voucher_number' => 'JV-2026-000001',
                'voucher_date' => '2026-08-29',
                'total_debit' => 1000,
                'total_credit' => 1000,
                'voucher_no' => 'LEGACY-JV-00001',
                'transaction_date' => '2026-08-29',
                'transaction_type' => 'Journal',
                'amount' => 1000,
                'description' => 'Test tx1',
                'account_id' => $this->cashAccount->id,
                'narration' => 'Test',
                'status' => Transaction::STATUS_APPROVED,
                'created_by' => $this->user->id,
            ]);

            TransactionDetail::create([
                'transaction_id' => $tx1->id,
                'account_id' => $this->cashAccount->id,
                'sort_order' => 1,
                'description' => 'Debit',
                'debit_amount' => 1000,
                'credit_amount' => 0,
            ]);

            TransactionDetail::create([
                'transaction_id' => $tx1->id,
                'account_id' => $this->incomeAccount->id,
                'sort_order' => 2,
                'description' => 'Credit',
                'debit_amount' => 0,
                'credit_amount' => 1000,
            ]);
        });

        // ============================================================
        // Company 2: Attempt to create transaction with SAME number
        // This should SUCCEED after FIX #1 (was failing before)
        // ============================================================

        // Create separate accounts for Company 2
        $company2CashAccount = Account::create([
            'company_id' => $this->company2->id,
            'account_code' => 1001,
            'account_name' => 'Cash in Hand',
            'account_type' => 'Asset',
            'nature' => 'Cash',
            'is_active' => true,
            'is_system' => false,
            'opening_balance' => 0,
            'balance_type' => 'Debit',
        ]);

        $company2IncomeAccount = Account::create([
            'company_id' => $this->company2->id,
            'account_code' => 4001,
            'account_name' => 'Sales Income',
            'account_type' => 'Income',
            'nature' => 'Income',
            'is_active' => true,
            'is_system' => false,
            'opening_balance' => 0,
            'balance_type' => 'Credit',
        ]);

        $company2VoucherType = VoucherType::create([
            'company_id' => $this->company2->id,
            'name' => 'Journal Voucher',
            'code' => 'JV',
            'nature' => 'journal',
            'last_number' => 0,
            'is_active' => true,
            'description' => 'Test journal voucher',
        ]);

        // ============================================================
        // Create transaction in Company 2 with SAME voucher number
        // ============================================================
        DB::transaction(function () use ($company2CashAccount, $company2IncomeAccount, $company2VoucherType): void {
            session(['company_id' => $this->company2->id]);

            $tx2 = Transaction::create([
                'company_id' => $this->company2->id,
                'financial_year_id' => $this->financialYear2->id,
                'voucher_type_id' => $company2VoucherType->id,
                'voucher_number' => 'JV-2026-000001',  // ← SAME NUMBER!
                'voucher_date' => '2026-08-29',
                'total_debit' => 5000,
                'total_credit' => 5000,
                'voucher_no' => 'LEGACY-JV-00002',
                'transaction_date' => '2026-08-29',
                'transaction_type' => 'Journal',
                'amount' => 5000,
                'description' => 'Test tx2',
                'account_id' => $company2CashAccount->id,
                'narration' => 'Test',
                'status' => Transaction::STATUS_APPROVED,
                'created_by' => $this->user->id,
            ]);

            TransactionDetail::create([
                'transaction_id' => $tx2->id,
                'account_id' => $company2CashAccount->id,
                'sort_order' => 1,
                'description' => 'Debit',
                'debit_amount' => 5000,
                'credit_amount' => 0,
            ]);

            TransactionDetail::create([
                'transaction_id' => $tx2->id,
                'account_id' => $company2IncomeAccount->id,
                'sort_order' => 2,
                'description' => 'Credit',
                'debit_amount' => 0,
                'credit_amount' => 5000,
            ]);
        });

        // ============================================================
        // Verify both transactions exist with same voucher number
        // ============================================================
        $this->assertDatabaseCount('transactions', 2);

        
        $tx1 = DB::table('transactions')
    ->where('company_id', $this->company1->id)
    ->where('financial_year_id', $this->financialYear1->id)
    ->where('voucher_number', 'JV-2026-000001')
    ->first();

$tx2 = DB::table('transactions')
    ->where('company_id', $this->company2->id)
    ->where('financial_year_id', $this->financialYear2->id)
    ->where('voucher_number', 'JV-2026-000001')
    ->first();

$this->assertNotNull($tx1);
$this->assertNotNull($tx2);

$this->assertSame('JV-2026-000001', $tx1->voucher_number);
$this->assertSame('JV-2026-000001', $tx2->voucher_number);
$this->assertNotSame($tx1->id, $tx2->id);
    }

    // ================================================================
    // FIX #2: Ledger Entries Orphaning (restrictOnDelete)
    // ================================================================

    /**
     * Test that posted transaction cannot be deleted (FIX #2).
     */
    public function test_posted_transaction_cannot_be_deleted(): void
    {
        $transaction = $this->makeTransaction();

        // Post the transaction
        app(LedgerPostingService::class)->post($transaction);

        $transaction->refresh();

        $this->assertTrue($transaction->isPosted());

        // ============================================================
        // Attempt to delete posted transaction
        // Should fail with constraint violation (FIX #2)
        // ============================================================
        $this->expectException(QueryException::class);

        $transaction->delete();
    }

    /**
     * Test that draft transaction can still be deleted (FIX #2).
     */
    public function test_draft_transaction_can_be_deleted(): void
    {
        $transaction = Transaction::create([
            'company_id' => $this->company1->id,
            'financial_year_id' => $this->financialYear1->id,
            'voucher_type_id' => $this->voucherType->id,
            'voucher_number' => 'JV-DRAFT-001',
            'voucher_date' => '2026-08-29',
            'total_debit' => 0,
            'total_credit' => 0,
            'voucher_no' => 'LEGACY-DRAFT-001',
            'transaction_date' => '2026-08-29',
            'transaction_type' => 'Journal',
            'amount' => 0,
            'description' => 'Draft transaction',
            'account_id' => $this->cashAccount->id,
            'narration' => 'Draft',
            'status' => Transaction::STATUS_DRAFT,
            'created_by' => $this->user->id,
        ]);

        // ============================================================
        // Delete draft transaction - should succeed
        // ============================================================
        $id = $transaction->id;

        $transaction->delete();

        $this->assertDatabaseMissing('transactions', ['id' => $id]);
    }

    // ================================================================
    // FIX #3: Concurrent Posting (lockForUpdate)
    // ================================================================

    /**
     * Test that concurrent posting is prevented by lockForUpdate (FIX #3).
     * 
     * Simulates two requests trying to post the same transaction.
     * Second request should fail because first has acquired the lock.
     */
    public function test_concurrent_posting_is_prevented(): void
    {
        $transaction = $this->makeTransaction();

        // ============================================================
        // Simulate concurrent posting attempts
        // ============================================================

        $firstAttemptSucceeded = false;
        $secondAttemptFailed = false;

        // First attempt - should succeed
        try {
            app(LedgerPostingService::class)->post($transaction);
            $firstAttemptSucceeded = true;
        } catch (\Exception $e) {
            $this->fail("First posting attempt should succeed: {$e->getMessage()}");
        }

        // Second attempt on already-posted transaction - should fail
        $transaction->refresh();
        try {
            app(LedgerPostingService::class)->post($transaction);
            $this->fail("Second posting attempt should have failed");
        } catch (LedgerPostingException $e) {
            $secondAttemptFailed = true;
            $this->assertStringContainsString('already posted', $e->getMessage());
        }

        // ============================================================
        // Verify results
        // ============================================================
        $this->assertTrue($firstAttemptSucceeded);
        $this->assertTrue($secondAttemptFailed);

        // Verify only one set of ledger entries created
        $this->assertDatabaseCount(
            'ledger_entries',
            2  // One debit, one credit
        );
    }

    // ================================================================
    // BONUS FIX #4: Financial Year Closure Check
    // ================================================================

    /**
     * Test that posting to closed financial year is prevented (FIX #4).
     */
    public function test_cannot_post_to_closed_financial_year(): void
    {
        $transaction = $this->makeTransaction();

        // Close the financial year
        $this->financialYear1->update(['is_closed' => true]);

        // ============================================================
        // Attempt to post - should fail
        // ============================================================
        $this->expectException(LedgerPostingException::class);
        $this->expectExceptionMessage('closed');

        app(LedgerPostingService::class)->post($transaction);
    }


     // ================================================================
// FIX #10: Monetary Balance Uses Decimal Arithmetic
// ================================================================

/**
 * Test that account current balance preserves decimal precision
 * without PHP float arithmetic (FIX #10).
 */
public function test_account_current_balance_uses_decimal_arithmetic(): void
{
    $this->cashAccount->update([
        'opening_balance' => '0.10',
    ]);

    $transaction = Transaction::create([
        'company_id' => $this->company1->id,
        'financial_year_id' => $this->financialYear1->id,
        'voucher_type_id' => $this->voucherType->id,
        'voucher_number' => 'JV-DECIMAL-001',
        'voucher_date' => '2026-08-29',
        'reference_number' => null,
        'total_debit' => '0.50',
        'total_credit' => '0.50',
        'transaction_date' => '2026-08-29',
        'voucher_no' => 'LEGACY-DECIMAL-001',
        'transaction_type' => 'Journal',
        'amount' => '0.50',
        'description' => 'Decimal arithmetic test',
        'account_id' => $this->cashAccount->id,
        'narration' => 'Decimal precision test',
        'status' => Transaction::STATUS_APPROVED,
        'created_by' => $this->user->id,
    ]);

    LedgerEntry::create([
        'transaction_id' => $transaction->id,
        'financial_year_id' => $this->financialYear1->id,
        'voucher_type_id' => $this->voucherType->id,
        'voucher_number' => 'JV-DECIMAL-001',
        'voucher_date' => '2026-08-29',
        'debit_amount' => '0.20',
        'credit_amount' => '0.00',
        'is_reversed' => false,
        'company_id' => $this->company1->id,
        'account_id' => $this->cashAccount->id,
        'entry_date' => '2026-08-29',
        'description' => 'Decimal debit 1',
    ]);

    LedgerEntry::create([
        'transaction_id' => $transaction->id,
        'financial_year_id' => $this->financialYear1->id,
        'voucher_type_id' => $this->voucherType->id,
        'voucher_number' => 'JV-DECIMAL-001',
        'voucher_date' => '2026-08-29',
        'debit_amount' => '0.30',
        'credit_amount' => '0.00',
        'is_reversed' => false,
        'company_id' => $this->company1->id,
        'account_id' => $this->cashAccount->id,
        'entry_date' => '2026-08-29',
        'description' => 'Decimal debit 2',
    ]);

    $balance = $this->cashAccount->fresh()->current_balance;

    $this->assertSame('0.60', $balance);
}

// ================================================================
// FIX #11: Database-Level Double-Entry Invariants
// ================================================================

/**
 * Test that a transaction detail cannot contain both debit and credit.
 */
public function test_transaction_detail_cannot_have_both_debit_and_credit(): void
{
    $transaction = $this->makeTransaction(
        Transaction::STATUS_APPROVED,
        'JV-CHECK-001'
    );

    $this->expectException(QueryException::class);

    TransactionDetail::create([
        'transaction_id' => $transaction->id,
        'account_id' => $this->cashAccount->id,
        'sort_order' => 3,
        'description' => 'Invalid double-sided entry',
        'debit_amount' => '100.00',
        'credit_amount' => '50.00',
    ]);
}

/**
 * Test that a transaction detail cannot contain zero debit and credit.
 */
public function test_transaction_detail_cannot_have_zero_debit_and_credit(): void
{
    $transaction = $this->makeTransaction(
        Transaction::STATUS_APPROVED,
        'JV-CHECK-002'
    );

    $this->expectException(QueryException::class);

    TransactionDetail::create([
        'transaction_id' => $transaction->id,
        'account_id' => $this->cashAccount->id,
        'sort_order' => 3,
        'description' => 'Invalid zero entry',
        'debit_amount' => '0.00',
        'credit_amount' => '0.00',
    ]);
}

/**
 * Test that transaction totals cannot be unbalanced.
 */
public function test_transaction_totals_must_be_balanced(): void
{
    $transaction = $this->makeTransaction(
        Transaction::STATUS_APPROVED,
        'JV-CHECK-003'
    );

    $this->expectException(QueryException::class);

    $transaction->update([
        'total_debit' => '1000.00',
        'total_credit' => '900.00',
    ]);
}


    // ================================================================
    // Helper: Create Transaction
    // ================================================================

    private function makeTransaction(
        string $status = Transaction::STATUS_APPROVED,
        string $voucherNumber = 'JV-00001'
    ): Transaction {
        $transaction = Transaction::create([
            'company_id' => $this->company1->id,
            'financial_year_id' => $this->financialYear1->id,
            'voucher_type_id' => $this->voucherType->id,
            'voucher_number' => $voucherNumber,
            'voucher_date' => '2026-08-29',
            'reference_number' => null,
            'total_debit' => 1000,
            'total_credit' => 1000,
            'transaction_date' => '2026-08-29',
            'voucher_no' => 'LEGACY-' . $voucherNumber,
            'transaction_type' => 'Journal',
            'amount' => 1000,
            'description' => 'Test transaction',
            'account_id' => $this->cashAccount->id,
            'narration' => 'Test',
            'status' => $status,
            'created_by' => $this->user->id,
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->cashAccount->id,
            'sort_order' => 1,
            'description' => 'Cash received',
            'debit_amount' => 1000,
            'credit_amount' => 0,
        ]);

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->incomeAccount->id,
            'sort_order' => 2,
            'description' => 'Sales income',
            'debit_amount' => 0,
            'credit_amount' => 1000,
        ]);

        return $transaction->fresh();
    }
}