<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\VoucherType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Cash Flow Statement controller.
 *
 * Classification is derived from the non-cash accounts participating in
 * each transaction. Voucher nature alone is not sufficient because a
 * Receipt can represent operating, investing or financing cash flow.
 */
class CashFlowController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $companyId = (int) session('company_id');

        if (! $companyId) {
            return redirect()
                ->route('dashboard.index')
                ->with('error', 'Please select a company first.');
        }

        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date'   => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $fromDate = $validated['from_date'] ?? null;
        $toDate   = $validated['to_date'] ?? null;

        /*
         * A cash movement is classified from its counter-account(s):
         *
         *   Fixed Asset account          -> Investing
         *   Liability (except Supplier,
         *   VAT and Tax) / Equity        -> Financing
         *   Everything else              -> Operating
         *
         * This deliberately does not use Receipt/Payment as the category,
         * because those voucher types describe the document, not its
         * economic purpose.
         */
        $classification = <<<'SQL'
            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM transaction_details AS cfd
                    INNER JOIN accounts AS cfa
                        ON cfa.id = cfd.account_id
                    WHERE cfd.transaction_id = le.transaction_id
                      AND cfa.company_id = le.company_id
                      AND cfa.nature = ?
                      AND cfa.nature NOT IN (?, ?)
                ) THEN 'investing'

                WHEN EXISTS (
                    SELECT 1
                    FROM transaction_details AS cfd
                    INNER JOIN accounts AS cfa
                        ON cfa.id = cfd.account_id
                    WHERE cfd.transaction_id = le.transaction_id
                      AND cfa.company_id = le.company_id
                      AND (
                          cfa.account_type = ?
                          OR cfa.account_type = ?
                      )
                      AND cfa.nature NOT IN (?, ?, ?)
                ) THEN 'financing'

                ELSE 'operating'
            END
        SQL;

        $query = DB::table('ledger_entries AS le')
            ->join('accounts AS a', 'a.id', '=', 'le.account_id')
            ->join('transactions AS t', 't.id', '=', 'le.transaction_id')
            ->where('a.company_id', $companyId)
            ->where('t.company_id', $companyId)
            ->whereIn('a.nature', [Account::NATURE_CASH, Account::NATURE_BANK])
            ->where('le.is_reversed', false)
            ->where('t.status', 'Posted')
            // Opening balances are represented by accounts.opening_balance.
            ->where(function ($q): void {
                $q->whereNull('t.voucher_type_id')
                    ->orWhereNotIn(
                        't.voucher_type_id',
                        fn ($sub) => $sub
                            ->select('id')
                            ->from('voucher_types')
                            ->where('nature', VoucherType::NATURE_OPENING)
                    );
            })
            // Contra is an internal cash/bank transfer, not external cash flow.
            ->where(function ($q): void {
                $q->whereNull('t.voucher_type_id')
                    ->orWhereNotIn(
                        't.voucher_type_id',
                        fn ($sub) => $sub
                            ->select('id')
                            ->from('voucher_types')
                            ->where('nature', VoucherType::NATURE_CONTRA)
                    );
            })
            ->selectRaw(
                $classification . ' AS cash_flow_category',
                [
                    Account::NATURE_FIXED_ASSET,
                    Account::NATURE_CASH,
                    Account::NATURE_BANK,
                    Account::TYPE_LIABILITY,
                    Account::TYPE_EQUITY,
                    Account::NATURE_SUPPLIER,
                    Account::NATURE_VAT,
                    Account::NATURE_TAX,
                ]
            )
            ->selectRaw('COALESCE(SUM(le.debit_amount), 0) AS total_debit')
            ->selectRaw('COALESCE(SUM(le.credit_amount), 0) AS total_credit')
            ->groupBy('cash_flow_category');

        if ($fromDate !== null) {
            $query->whereDate('le.voucher_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('le.voucher_date', '<=', $toDate);
        }

        $rows = $query->get()->keyBy('cash_flow_category');

        $operatingIn = (float) ($rows->get('operating')?->total_debit ?? 0);
        $operatingOut = (float) ($rows->get('operating')?->total_credit ?? 0);

        $investingIn = (float) ($rows->get('investing')?->total_debit ?? 0);
        $investingOut = (float) ($rows->get('investing')?->total_credit ?? 0);

        $financingIn = (float) ($rows->get('financing')?->total_debit ?? 0);
        $financingOut = (float) ($rows->get('financing')?->total_credit ?? 0);

        $openingBalance = $this->calculateOpeningBalance(
            companyId: $companyId,
            fromDate: $fromDate,
        );

        $netChange =
            $operatingIn - $operatingOut
            + $investingIn - $investingOut
            + $financingIn - $financingOut;

        $closingBalance = $openingBalance + $netChange;

        $cashAccounts = Account::query()
            ->where('company_id', $companyId)
            ->whereIn('nature', [Account::NATURE_CASH, Account::NATURE_BANK])
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('cash-flow.index', compact(
            'cashAccounts',
            'openingBalance',
            'operatingIn',
            'operatingOut',
            'investingIn',
            'investingOut',
            'financingIn',
            'financingOut',
            'netChange',
            'closingBalance',
            'fromDate',
            'toDate',
        ));
    }

    /**
     * Calculate the cash/bank balance immediately before from_date.
     *
     * Account opening_balance is the base opening position, so Opening
     * Vouchers are excluded to prevent double counting.
     */
    private function calculateOpeningBalance(int $companyId, ?string $fromDate): float
    {
        $accountOpening = (float) Account::query()
            ->where('company_id', $companyId)
            ->whereIn('nature', [Account::NATURE_CASH, Account::NATURE_BANK])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN balance_type = ? THEN opening_balance ELSE -opening_balance END), 0) AS opening_balance',
                [Account::BALANCE_DEBIT]
            )
            ->value('opening_balance');

        if ($fromDate === null) {
            return $accountOpening;
        }

        $prior = DB::table('ledger_entries AS le')
            ->join('accounts AS a', 'a.id', '=', 'le.account_id')
            ->join('transactions AS t', 't.id', '=', 'le.transaction_id')
            ->where('a.company_id', $companyId)
            ->where('t.company_id', $companyId)
            ->whereIn('a.nature', [Account::NATURE_CASH, Account::NATURE_BANK])
            ->where('le.is_reversed', false)
            ->where('t.status', 'Posted')
            ->whereDate('le.voucher_date', '<', $fromDate)
            ->where(function ($q): void {
                $q->whereNull('t.voucher_type_id')
                    ->orWhereNotIn(
                        't.voucher_type_id',
                        fn ($sub) => $sub
                            ->select('id')
                            ->from('voucher_types')
                            ->where('nature', VoucherType::NATURE_OPENING)
                    );
            })
            ->where(function ($q): void {
                $q->whereNull('t.voucher_type_id')
                    ->orWhereNotIn(
                        't.voucher_type_id',
                        fn ($sub) => $sub
                            ->select('id')
                            ->from('voucher_types')
                            ->where('nature', VoucherType::NATURE_CONTRA)
                    );
            })
            ->selectRaw('COALESCE(SUM(le.debit_amount), 0) AS prior_debit')
            ->selectRaw('COALESCE(SUM(le.credit_amount), 0) AS prior_credit')
            ->first();

        return $accountOpening
            + (float) ($prior->prior_debit ?? 0)
            - (float) ($prior->prior_credit ?? 0);
    }
}