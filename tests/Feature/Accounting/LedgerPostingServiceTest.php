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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LedgerPostingServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private FinancialYear $financialYear;

    private VoucherType $voucherType;

    private Account $cashAccount;

    private Account $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        $this->company = Company::create([
            'company_name' => 'Test Company',
            'owner_name' => 'Test Owner',
            'email' => 'test@example.com',
            'phone' => '01700000000',
            'address' => 'Test Address',
            'city' => 'Jessore',
            'country' => 'Bangladesh',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'financial_year' => '2026-2027',
            'status' => true,
            'business_type' => 'General',
        ]);

        /*
        |--------------------------------------------------------------------------
        | User
        |--------------------------------------------------------------------------
        */

        $this->user = User::factory()->create([
            'status' => true,
        ]);

        Auth::login($this->user);

        /*
        |--------------------------------------------------------------------------
        | Financial Year
        |--------------------------------------------------------------------------
        */

        $this->financialYear = FinancialYear::create([
            'company_id' => $this->company->id,
            'year_name' => '2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'is_closed' => false,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Voucher Type
        |--------------------------------------------------------------------------
        */

        $this->voucherType = VoucherType::create([
            'company_id' => $this->company->id,
            'name' => 'Journal Voucher',
            'code' => 'JV',
            'nature' => 'journal',
            'last_number' => 0,
            'is_active' => true,
            'description' => 'Test journal voucher',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cash Account
        |--------------------------------------------------------------------------
        */

        $this->cashAccount = Account::create([
            'company_id' => $this->company->id,
            'account_code' => '1001',
            'account_name' => 'Cash in Hand',
            'account_type' => 'Asset',
            'nature' => 'Cash',
            'is_active' => true,
            'is_system' => false,
            'opening_balance' => 0,
            'balance_type' => 'Debit',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Income Account
        |--------------------------------------------------------------------------
        */

        $this->incomeAccount = Account::create([
            'company_id' => $this->company->id,
            'account_code' => '4001',
            'account_name' => 'Sales Income',
            'account_type' => 'Income',
            'nature' => 'Income',
            'is_active' => true,
            'is_system' => false,
            'opening_balance' => 0,
            'balance_type' => 'Credit',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Approved Transaction
    |--------------------------------------------------------------------------
    */

    public function test_approved_transaction_can_be_posted(): void
    {
        $transaction = $this->makeTransaction();

        app(LedgerPostingService::class)->post($transaction);

        $transaction->refresh();

        $this->assertSame(
            Transaction::STATUS_POSTED,
            $transaction->status
        );

        $this->assertNotNull($transaction->posted_at);

        $this->assertSame(
            $this->user->id,
            $transaction->posted_by
        );

        $this->assertDatabaseCount('ledger_entries', 2);

        $this->assertDatabaseHas('ledger_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $this->cashAccount->id,
            'debit_amount' => '1000.0000',
            'credit_amount' => '0.0000',
            'is_reversed' => false,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'transaction_id' => $transaction->id,
            'account_id' => $this->incomeAccount->id,
            'debit_amount' => '0.0000',
            'credit_amount' => '1000.0000',
            'is_reversed' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Unapproved Transaction
    |--------------------------------------------------------------------------
    */

    public function test_unapproved_transaction_cannot_be_posted(): void
    {
        $transaction = $this->makeTransaction(
            status: Transaction::STATUS_SUBMITTED
        );

        $this->expectException(LedgerPostingException::class);

        $this->expectExceptionMessage(
            "Transaction #{$transaction->id} must be approved before it can be posted."
        );

        app(LedgerPostingService::class)->post($transaction);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelled Transaction
    |--------------------------------------------------------------------------
    */

    public function test_cancelled_transaction_cannot_be_posted(): void
    {
        $transaction = $this->makeTransaction(
            status: Transaction::STATUS_CANCELLED
        );

        $this->expectException(LedgerPostingException::class);

        app(LedgerPostingService::class)->post($transaction);
    }

    /*
    |--------------------------------------------------------------------------
    | Already Posted Transaction
    |--------------------------------------------------------------------------
    */

    public function test_already_posted_transaction_cannot_be_posted_again(): void
    {
        $transaction = $this->makeTransaction();

        app(LedgerPostingService::class)->post($transaction);

        $transaction->refresh();

        $this->expectException(LedgerPostingException::class);

        app(LedgerPostingService::class)->post($transaction);
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Without Details
    |--------------------------------------------------------------------------
    */

    public function test_transaction_without_details_cannot_be_posted(): void
    {
        $transaction = Transaction::create([
            /*
            |--------------------------------------------------------------------------
            | ERP Fields
            |--------------------------------------------------------------------------
            */

            'company_id' => $this->company->id,

            'financial_year_id' => $this->financialYear->id,

            'voucher_type_id' => $this->voucherType->id,

            'voucher_number' => 'JV-00002',

            'voucher_date' => '2026-08-24',

            'reference_number' => null,

            'total_debit' => 0,

            'total_credit' => 0,

            /*
            |--------------------------------------------------------------------------
            | Legacy Transaction Fields
            |
            | These are required by the original transactions migration.
            | They are supplied explicitly because SQLite test migrations
            | do not apply the MySQL-specific nullable ALTER statements
            | from make_legacy_transaction_columns_nullable migration.
            |--------------------------------------------------------------------------
            */

            'transaction_date' => '2026-08-24',

            'voucher_no' => 'LEGACY-JV-00002',

            'transaction_type' => 'Journal',

            'amount' => 0,

            'description' => 'Empty transaction',

            /*
            |--------------------------------------------------------------------------
            | Existing Transaction Fields
            |--------------------------------------------------------------------------
            */

            'account_id' => $this->cashAccount->id,

            'narration' => 'Empty transaction',

            'status' => Transaction::STATUS_APPROVED,

            'created_by' => $this->user->id,
        ]);

        $this->expectException(LedgerPostingException::class);

        $this->expectExceptionMessage(
            "Transaction #{$transaction->id} has no detail lines to post."
        );

        app(LedgerPostingService::class)->post($transaction);
    }

    /*
    |--------------------------------------------------------------------------
    | Multiple Transactions
    |--------------------------------------------------------------------------
    */

    public function test_another_transaction_with_active_ledger_entries_does_not_block_posting(): void
    {
        $firstTransaction = $this->makeTransaction(
            voucherNumber: 'JV-00001'
        );

        app(LedgerPostingService::class)->post($firstTransaction);

        $secondTransaction = $this->makeTransaction(
            voucherNumber: 'JV-00002'
        );

        app(LedgerPostingService::class)->post($secondTransaction);

        $this->assertDatabaseCount(
            'ledger_entries',
            4
        );

        $this->assertDatabaseHas('ledger_entries', [
            'transaction_id' => $firstTransaction->id,
            'is_reversed' => false,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'transaction_id' => $secondTransaction->id,
            'is_reversed' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancellation / Reversal
    |--------------------------------------------------------------------------
    */

    public function test_cancel_reverses_existing_ledger_entries(): void
    {
        $transaction = $this->makeTransaction();

        app(LedgerPostingService::class)->post($transaction);

        $transaction->refresh();

        app(LedgerPostingService::class)->cancel(
            $transaction,
            'Test cancellation'
        );

        $transaction->refresh();

        $this->assertSame(
            Transaction::STATUS_CANCELLED,
            $transaction->status
        );

        $this->assertNotNull(
            $transaction->cancelled_at
        );

        $this->assertSame(
            $this->user->id,
            $transaction->cancelled_by
        );

        $this->assertSame(
            'Test cancellation',
            $transaction->cancellation_reason
        );

        /*
        |--------------------------------------------------------------------------
        | Original + Reversal Entries
        |--------------------------------------------------------------------------
        */

        $this->assertDatabaseCount(
            'ledger_entries',
            4
        );

        $this->assertSame(
            2,
            LedgerEntry::query()
                ->where('transaction_id', $transaction->id)
                ->where('is_reversed', false)
                ->count()
        );

        $this->assertSame(
            2,
            LedgerEntry::query()
                ->where('transaction_id', $transaction->id)
                ->where('is_reversed', true)
                ->count()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Factory Helper
    |--------------------------------------------------------------------------
    */
    private function makeTransaction(
        string $status = Transaction::STATUS_APPROVED,
        ?string $voucherNumber = null
            ): Transaction {
        // Generate unique voucher number if not provided
        if ($voucherNumber === null) {
            $count = Transaction::query()
                ->where('company_id', $this->company->id)
                ->where('financial_year_id', $this->financialYear->id)
                ->count();
            
            $voucherNumber = 'JV-' . str_pad(
                (string) ($count + 1),
                5,
                '0',
                STR_PAD_LEFT
            );
        }

        $transaction = Transaction::create([
            /*
            |--------------------------------------------------------------------------
            | ERP Fields
            |--------------------------------------------------------------------------
            */

            'company_id' => $this->company->id,

            'financial_year_id' => $this->financialYear->id,

            'voucher_type_id' => $this->voucherType->id,

            'voucher_number' => $voucherNumber,  // ← Now unique!

            'voucher_date' => '2026-08-24',

            'reference_number' => null,

            'total_debit' => 1000,

            'total_credit' => 1000,

            /*
            |--------------------------------------------------------------------------
            | Legacy Transaction Fields
            |--------------------------------------------------------------------------
            */

            'transaction_date' => '2026-08-24',

            'voucher_no' => 'LEGACY-' . $voucherNumber,

            'transaction_type' => 'Journal',

            'amount' => 1000,

            'description' => 'Test transaction',

            /*
            |--------------------------------------------------------------------------
            | Existing Transaction Fields
            |--------------------------------------------------------------------------
            */

            'account_id' => $this->cashAccount->id,

            'narration' => 'Test transaction',

            'status' => $status,

            'created_by' => $this->user->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Transaction Detail - Debit
        |--------------------------------------------------------------------------
        */

        TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'account_id' => $this->cashAccount->id,
            'sort_order' => 1,
            'description' => 'Cash received',
            'debit_amount' => 1000,
            'credit_amount' => 0,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Transaction Detail - Credit
        |--------------------------------------------------------------------------
        */

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