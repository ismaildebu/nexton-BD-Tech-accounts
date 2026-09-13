<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\FinancialYear;
use App\Models\Salary;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\VoucherType;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SalaryAccountingService
{
    public function __construct(
        private readonly LedgerPostingService $ledgerPostingService,
    ) {
    }

    public function postPayment(
        Salary $salary,
        int $paymentAccountId
    ): Transaction {
        return DB::transaction(function () use (
            $salary,
            $paymentAccountId
        ): Transaction {
            $salary = Salary::query()
                ->whereKey($salary->id)
                ->where('company_id', $salary->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($salary->transaction_id !== null) {
                return Transaction::query()
                    ->whereKey($salary->transaction_id)
                    ->where(
                        'company_id',
                        $salary->company_id
                    )
                    ->firstOrFail();
            }

            if ($salary->status === 'paid') {
                throw new InvalidArgumentException(
                    "Salary #{$salary->id} is already marked as paid."
                );
            }

            $amount = (string) $salary->net_salary;

            if (bccomp($amount, '0.00', 2) <= 0) {
                throw new InvalidArgumentException(
                    "Salary #{$salary->id} must have a positive net salary."
                );
            }

            $salary->loadMissing('employee');

            if (! $salary->employee) {
                throw new InvalidArgumentException(
                    "Employee for Salary #{$salary->id} could not be found."
                );
            }

            if (
                (int) $salary->employee->company_id
                !== (int) $salary->company_id
            ) {
                throw new InvalidArgumentException(
                    'Employee does not belong to the current company.'
                );
            }

            /*
             * Resolve the selected payment account.
             *
             * The submitted ID must belong to either:
             *
             * 1. The company's active Cash in Hand account, or
             * 2. An active BankAccount linked to a company's
             *    Chart of Accounts account.
             */
            $paymentAccount = $this->resolvePaymentAccount(
                $salary->company_id,
                $paymentAccountId
            );

            /*
             * Lock the payment account's ledger rows before checking
             * the available balance.
             *
             * This prevents concurrent payments from both passing
             * the same balance check.
             */
            $this->assertSufficientPaymentBalance(
                $paymentAccount,
                $amount
            );

            $salaryExpenseAccount =
                Account::query()
                    ->where(
                        'company_id',
                        $salary->company_id
                    )
                    ->where(
                        'account_name',
                        'Salary Expense'
                    )
                    ->where(
                        'account_type',
                        'Expense'
                    )
                    ->where(
                        'nature',
                        'Expense'
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->get();

            if ($salaryExpenseAccount->count() !== 1) {
                throw new InvalidArgumentException(
                    "Exactly one active 'Salary Expense' account is required for this company."
                );
            }

            $salaryExpenseAccountId =
                (int) $salaryExpenseAccount->first()->id;

            $this->assertActiveAccount(
                $salaryExpenseAccountId,
                $salary->company_id,
                'Salary Expense'
            );

            $financialYear =
                $this->activeFinancialYear(
                    $salary->company_id
                );

            $voucherType =
                $this->paymentVoucherType(
                    $salary->company_id
                );

            $userId = auth()->id();

            if (! $userId) {
                throw new InvalidArgumentException(
                    'An authenticated user is required to post salary payment.'
                );
            }

            $transaction = Transaction::create([
                'company_id' =>
                    $salary->company_id,

                'financial_year_id' =>
                    $financialYear->id,

                'voucher_type_id' =>
                    $voucherType->id,

                'voucher_number' =>
                    $voucherType->generateNextVoucherNumber(),

                'voucher_date' =>
                    now()->toDateString(),

                'narration' =>
                    "Salary Payment — "
                    . "{$salary->employee->name} — "
                    . "{$salary->month}/{$salary->year}",

                'total_debit' =>
                    $amount,

                'total_credit' =>
                    $amount,

                'status' =>
                    Transaction::STATUS_APPROVED,

                'created_by' =>
                    $userId,

                'approved_by' =>
                    $userId,

                'approved_at' =>
                    now(),
            ]);

            /*
             * Debit Salary Expense.
             */
            TransactionDetail::create([
                'transaction_id' =>
                    $transaction->id,

                'account_id' =>
                    $salaryExpenseAccountId,

                'debit_amount' =>
                    $amount,

                'credit_amount' =>
                    '0.00',

                'description' =>
                    "Salary Expense — "
                    . "{$salary->employee->name} — "
                    . "{$salary->month}/{$salary->year}",

                'sort_order' =>
                    1,
            ]);

            /*
             * Credit the exact Cash/Bank account selected
             * by the user.
             */
            TransactionDetail::create([
                'transaction_id' =>
                    $transaction->id,

                'account_id' =>
                    $paymentAccount->id,

                'debit_amount' =>
                    '0.00',

                'credit_amount' =>
                    $amount,

                'description' =>
                    "Salary Paid — "
                    . "{$salary->employee->name} — "
                    . "{$salary->month}/{$salary->year} — "
                    . "{$paymentAccount->account_name}",

                'sort_order' =>
                    2,
            ]);

            $this->ledgerPostingService->post(
                $transaction
            );

            $salary->update([
                'status' =>
                    'paid',

                'paid_date' =>
                    now()->toDateString(),

                'transaction_id' =>
                    $transaction->id,

                'payment_account_id' =>
                    $paymentAccount->id,
            ]);

            return $transaction->fresh([
                'details',
                'entries',
            ]);
        });
    }

    /**
     * Ensure the selected Cash/Bank account has sufficient
     * available balance before creating a payment voucher.
     */
    private function assertSufficientPaymentBalance(
        Account $paymentAccount,
        string $amount
    ): void {
        /*
         * Lock all existing ledger rows for this account.
         *
         * New ledger entries for this account cannot be safely
         * created concurrently while these rows are being locked,
         * so the account row itself is also locked.
         */
        $paymentAccount = Account::query()
            ->whereKey($paymentAccount->id)
            ->where('company_id', $paymentAccount->company_id)
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();

        $debitTotal = $paymentAccount
            ->allLedgerEntries()
            ->lockForUpdate()
            ->sum('debit_amount');

        $creditTotal = $paymentAccount
            ->allLedgerEntries()
            ->lockForUpdate()
            ->sum('credit_amount');

        /*
         * Cash and Bank accounts are Asset accounts,
         * therefore their available balance is:
         *
         * Opening Balance + Debit - Credit
         */
        $availableBalance =
            (float) $paymentAccount->opening_balance
            + (float) $debitTotal
            - (float) $creditTotal;

        if (
            bccomp(
                number_format($availableBalance, 2, '.', ''),
                $amount,
                2
            ) < 0
        ) {
            throw new InvalidArgumentException(
                'Insufficient balance in '
                . $paymentAccount->account_name
                . '. Available: '
                . number_format($availableBalance, 2)
                . ', Required: '
                . number_format((float) $amount, 2)
                . '.'
            );
        }
    }

    /**
     * Resolve a valid Cash or Bank payment account.
     */
    private function resolvePaymentAccount(
        int $companyId,
        int $paymentAccountId
    ): Account {
        /*
         * Cash in Hand.
         */
        $cashAccount = Account::query()
            ->where('id', $paymentAccountId)
            ->where('company_id', $companyId)
            ->where('account_name', 'Cash in Hand')
            ->where('is_active', true)
            ->first();

        if ($cashAccount) {
            return $cashAccount;
        }

        /*
         * Specific physical bank account.
         *
         * paymentAccountId is the linked accounts.id,
         * NOT bank_accounts.id.
         */
        $bankAccount = BankAccount::query()
            ->with('account')
            ->where('company_id', $companyId)
            ->where('account_id', $paymentAccountId)
            ->where('is_active', true)
            ->whereHas('account', function ($query) use ($companyId) {
                $query
                    ->where('company_id', $companyId)
                    ->where('is_active', true);
            })
            ->first();

        if (! $bankAccount || ! $bankAccount->account) {
            throw new InvalidArgumentException(
                'Selected payment account is not a valid active Cash or Bank account for this company.'
            );
        }

        return $bankAccount->account;
    }

    private function activeFinancialYear(
        int $companyId
    ): FinancialYear {
        $year = FinancialYear::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_closed', false)
            ->first();

        if (! $year) {
            throw new InvalidArgumentException(
                'No active financial year found. Please create one before posting.'
            );
        }

        return $year;
    }

    private function paymentVoucherType(
        int $companyId
    ): VoucherType {
        $voucherType = VoucherType::query()
            ->where('company_id', $companyId)
            ->where(
                'nature',
                VoucherType::NATURE_PAYMENT
            )
            ->where('is_active', true)
            ->first();

        if (! $voucherType) {
            throw new InvalidArgumentException(
                'No active Payment Voucher type found for this company.'
            );
        }

        return $voucherType;
    }

    private function assertActiveAccount(
        int $accountId,
        int $companyId,
        string $purpose
    ): void {
        $account = Account::query()
            ->where('id', $accountId)
            ->where('company_id', $companyId)
            ->first();

        if (! $account) {
            throw new InvalidArgumentException(
                "Configured {$purpose} account does not belong to this company."
            );
        }

        if (! $account->is_active) {
            throw new InvalidArgumentException(
                "Configured {$purpose} account '{$account->account_name}' is inactive."
            );
        }
    }
}