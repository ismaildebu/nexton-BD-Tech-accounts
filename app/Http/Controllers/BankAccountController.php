<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\FinancialYear;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\VoucherType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(): View
    {
        $bankAccounts = BankAccount::query()
            ->with('account')
            ->latest()
            ->get();

        return view('banking.index', compact('bankAccounts'));
    }

    public function create(): View
    {
        return view('banking.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) session('company_id');

        abort_unless($companyId > 0, 403, 'No active company selected.');

        $validated = $request->validate([
            'bank_name' => [
                'required',
                'string',
                'max:255',
            ],

            'account_name' => [
                'required',
                'string',
                'max:255',
            ],

            'account_number' => [
                'required',
                'string',
                'max:255',

                Rule::unique('bank_accounts', 'account_number')
                    ->where(
                        fn ($query) => $query->where(
                            'company_id',
                            $companyId
                        )
                    ),
            ],

            'branch_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'balance' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $bankAccount = DB::transaction(function () use (
            $validated,
            $companyId
        ) {
            $openingBalance = (string) $validated['balance'];

            /*
            * Create the Accounting Account.
            *
            * Opening balance must remain zero here because the actual
            * opening balance will be recorded through an Opening Voucher.
            */
            $account = new Account([
                'company_id'      => $companyId,
                'account_name'    => $this->makeAccountingAccountName(
                    $validated['bank_name'],
                    $validated['account_number']
                ),
                'account_type'    => Account::TYPE_ASSET,
                'parent_id'       => null,
                'nature'          => Account::NATURE_BANK,
                'level'           => 1,
                'color'           => '#2563eb',
                'is_system'       => false,
                'is_active'       => true,
                'opening_balance' => '0.00',
                'balance_type'    => Account::defaultBalanceType(
                    Account::TYPE_ASSET
                ),
            ]);

            $account->account_code = Account::generateNextCode(
                Account::TYPE_ASSET,
                $companyId
            );

            $account->save();

            /*
            * Create Opening Voucher:
            *
            * Dr. Bank Account
            * Cr. Owner's Capital
            */
            if (bccomp($openingBalance, '0.00', 2) > 0) {
                $capitalAccount = Account::query()
                    ->where('company_id', $companyId)
                    ->where('account_type', Account::TYPE_EQUITY)
                    ->where('account_name', "Owner's Capital")
                    ->where('is_active', true)
                    ->first();

                if (! $capitalAccount) {
                    throw new \RuntimeException(
                        "Owner's Capital account was not found for this company."
                    );
                }

                $financialYear = FinancialYear::query()
                    ->where('company_id', $companyId)
                    ->where('is_active', true)
                    ->orderByDesc('start_date')
                    ->first();

                if (! $financialYear) {
                    throw new \RuntimeException(
                        'No active financial year found for this company.'
                    );
                }

                $voucherType = VoucherType::query()
                    ->where('company_id', $companyId)
                    ->where(
                        'nature',
                        VoucherType::NATURE_OPENING
                    )
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $voucherType) {
                    $voucherType = VoucherType::create([
                        'company_id'  => $companyId,
                        'name'        => 'Opening Voucher',
                        'code'        => 'OPENING',
                        'nature'      => VoucherType::NATURE_OPENING,
                        'prefix'      => 'OB',
                        'last_number' => 0,
                        'is_active'   => true,
                        'description' => 'Opening balance voucher',
                    ]);
                }

                $voucherNumber = $voucherType->generateNextVoucherNumber();

                $now = now();

                $transaction = Transaction::create([
                    'company_id'        => $companyId,
                    'financial_year_id' => $financialYear->id,
                    'voucher_type_id'   => $voucherType->id,
                    'voucher_number'    => $voucherNumber,
                    'voucher_date'      => $now->toDateString(),
                    'narration'         => "Opening Balance - {$account->account_name}",
                    'total_debit'       => $openingBalance,
                    'total_credit'      => $openingBalance,
                    'status'            => Transaction::STATUS_APPROVED,
                    'created_by'        => auth()->id(),
                    'approved_by'       => auth()->id(),
                    'approved_at'       => $now,
                ]);

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $account->id,
                    'debit_amount'   => $openingBalance,
                    'credit_amount'  => '0.00',
                    'description'    => "Opening Balance - {$account->account_name}",
                    'sort_order'     => 1,
                ]);

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $capitalAccount->id,
                    'debit_amount'   => '0.00',
                    'credit_amount'  => $openingBalance,
                    'description'    => "Opening Capital - {$account->account_name}",
                    'sort_order'     => 2,
                ]);

                app(\App\Services\LedgerPostingService::class)
                    ->post($transaction);
            }

            /*
            * Create the physical BankAccount and link it
            * to the Accounting Account.
            */
            return BankAccount::create([
                'company_id'     => $companyId,
                'account_id'     => $account->id,
                'bank_name'      => $validated['bank_name'],
                'account_name'   => $validated['account_name'],
                'account_number' => $validated['account_number'],
                'branch_name'    => $validated['branch_name'] ?? null,

                /*
                * Keep the submitted value for the physical bank
                * account record. Accounting balance comes from ledger.
                */
                'balance'        => $validated['balance'],
                'is_active'      => true,
            ]);
        });

        return redirect()
            ->route('bank-accounts.index')
            ->with(
                'success',
                "Bank account created successfully. "
                . "Accounting Account {$bankAccount->account->account_code} "
                . "has been linked."
            );
    }

    public function show(BankAccount $bankAccount): View
    {
        $bankAccount->load('account');

        return view('banking.show', compact('bankAccount'));
    }

    public function edit(BankAccount $bankAccount): View
    {
        $bankAccount->load('account');

        return view('banking.edit', compact('bankAccount'));
    }

    public function update(
        Request $request,
        BankAccount $bankAccount
    ): RedirectResponse {
        $companyId = (int) session('company_id');

        $validated = $request->validate([
            'bank_name' => [
                'required',
                'string',
                'max:255',
            ],

            'account_name' => [
                'required',
                'string',
                'max:255',
            ],

            'account_number' => [
                'required',
                'string',
                'max:255',

                Rule::unique('bank_accounts', 'account_number')
                    ->where(
                        fn ($query) => $query->where(
                            'company_id',
                            $companyId
                        )
                    )
                    ->ignore($bankAccount->id),
            ],

            'branch_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'balance' => [
                'required',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $request,
            $bankAccount,
            $companyId
        ) {
            /*
             * If this is an old BankAccount created before
             * account_id existed, create its Accounting Account now.
             */
            if (! $bankAccount->account_id) {
                $account = new Account([
                    'company_id'      => $companyId,
                    'account_name'    => $this->makeAccountingAccountName(
                        $validated['bank_name'],
                        $validated['account_number']
                    ),
                    'account_type'    => Account::TYPE_ASSET,
                    'parent_id'       => null,
                    'nature'          => Account::NATURE_BANK,
                    'level'           => 1,
                    'color'           => '#2563eb',
                    'is_system'       => false,
                    'is_active'       => true,
                    'opening_balance' => $validated['balance'],
                    'balance_type'    => Account::defaultBalanceType(
                        Account::TYPE_ASSET
                    ),
                ]);

                $account->account_code = Account::generateNextCode(
                    Account::TYPE_ASSET,
                    $companyId
                );

                $account->save();

                $bankAccount->account_id = $account->id;
            } else {
                /*
                 * Existing linked account.
                 *
                 * Do not overwrite its opening balance if it already
                 * has ledger transactions.
                 */
                $account = Account::forCompany($companyId)
                    ->findOrFail($bankAccount->account_id);

                if (! $account->hasTransactions()) {
                    $account->opening_balance = $validated['balance'];
                }

                $account->account_name = $this->makeAccountingAccountName(
                    $validated['bank_name'],
                    $validated['account_number']
                );

                $account->is_active = $request->boolean('is_active');

                $account->save();
            }

            $bankAccount->update([
                'bank_name'      => $validated['bank_name'],
                'account_name'   => $validated['account_name'],
                'account_number' => $validated['account_number'],
                'branch_name'    => $validated['branch_name'] ?? null,

                /*
                 * Keep legacy opening/display field synchronized only
                 * when there are no accounting transactions.
                 */
                'balance'        => $validated['balance'],
                'is_active'      => $request->boolean('is_active'),
                'account_id'     => $bankAccount->account_id,
            ]);
        });

        return redirect()
            ->route('bank-accounts.index')
            ->with('success', 'Bank account updated successfully.');
    }

    public function destroy(
        BankAccount $bankAccount
    ): RedirectResponse {
        DB::transaction(function () use ($bankAccount) {
            /*
             * Do not delete an Accounting Account automatically.
             * BankAccount may have historical ledger transactions.
             *
             * Soft removal of the physical bank account is safer:
             * mark it inactive.
             */
            $bankAccount->update([
                'is_active' => false,
            ]);

            if ($bankAccount->account_id) {
                Account::forCompany($bankAccount->company_id)
                    ->whereKey($bankAccount->account_id)
                    ->update([
                        'is_active' => false,
                    ]);
            }
        });

        return redirect()
            ->route('bank-accounts.index')
            ->with(
                'success',
                'Bank account has been deactivated successfully.'
            );
    }

    /**
     * Generate a clear Accounting Account name.
     */
    private function makeAccountingAccountName(
        string $bankName,
        string $accountNumber
    ): string {
        $lastFourDigits = substr(
            preg_replace('/\D/', '', $accountNumber) ?? '',
            -4
        );

        return $lastFourDigits !== ''
            ? "{$bankName} - {$lastFourDigits}"
            : $bankName;
    }
}