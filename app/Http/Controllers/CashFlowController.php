<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CashFlowController
 * ──────────────────
 * ❌ পুরাতন কোডে ৩টি সমস্যা ছিল:
 *
 *  Bug #1 — পুরাতন column ব্যবহার:
 *    $entry->debit / $entry->credit
 *    → Migration drop করার পরে এটা সবসময় null হবে।
 *    ✅ Fix: $entry->debit_amount / $entry->credit_amount
 *
 *  Bug #2 — N+1 Query:
 *    Account::with('ledgerEntries') → ১০০টি account = ১০০টি query!
 *    ledgerEntries-এর ভেতরে আবার $entry->transaction → আরও N query।
 *    ✅ Fix: একটি aggregate DB query দিয়ে সব হিসাব করা।
 *
 *  Bug #3 — transaction_type mismatch:
 *    পুরাতন enum: ['Income','Expense','Journal']
 *    পুরাতন কোড: 'Asset Purchase', 'Loan Received', 'Drawings' ইত্যাদি
 *    → এগুলো DB-তে নেই, কখনো match হয় না, তাই সব category সবসময় 0।
 *    ✅ Fix: transaction_type-এর বদলে VoucherType.nature ব্যবহার করা।
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

        // ── Optional date filter ──────────────────────────────────────
        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        // ---------------------------------------------------------------
        // ✅ Fix #2 — N+1 দূর করা: একটি aggregate query দিয়ে সব হিসাব
        //
        // Cash/Bank account-এর সব ledger entry JOIN করো।
        // VoucherType.nature দিয়ে category নির্ধারণ করো।
        // ---------------------------------------------------------------
        $query = DB::table('ledger_entries AS le')
            ->join('accounts AS a', 'a.id', '=', 'le.account_id')
            ->join('transactions AS t', 't.id', '=', 'le.transaction_id')
            ->join('voucher_types AS vt', 'vt.id', '=', 't.voucher_type_id')
            ->where('a.company_id', $companyId)
            ->whereIn('a.nature', [Account::NATURE_CASH, Account::NATURE_BANK])
            ->where('le.is_reversed', false)
            ->where('t.status', 'Posted')
            ->select([
                'vt.nature AS voucher_nature',
                DB::raw('COALESCE(SUM(le.debit_amount), 0)  AS total_debit'),   // ✅ Fix #1
                DB::raw('COALESCE(SUM(le.credit_amount), 0) AS total_credit'),  // ✅ Fix #1
            ])
            ->groupBy('vt.nature');

        if ($fromDate) {
            $query->whereDate('le.voucher_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('le.voucher_date', '<=', $toDate);
        }

        $rows = $query->get()->keyBy('voucher_nature');

        // ---------------------------------------------------------------
        // ✅ Fix #3 — VoucherType.nature দিয়ে category ভাগ করা
        //
        // VoucherType.nature এর possible values (VoucherType model থেকে):
        //   'journal'  → General journal / adjustments
        //   'receipt'  → Cash/Bank inflow (Operating বা Financing)
        //   'payment'  → Cash/Bank outflow (Operating বা Financing)
        //   'contra'   → Internal bank↔cash transfer (বাদ দিতে হবে)
        //   'opening'  → Opening balance entry (বাদ)
        //
        // Cash Flow statement-এ:
        //   Receipt Voucher → Operating Inflow
        //   Payment Voucher → Operating Outflow
        //   Journal Voucher → Dr হলে Investing In, Cr হলে Investing Out
        //   Contra Voucher  → Internal transfer, ignore
        //
        // NOTE: আপনার system যদি Financing (Loan/Capital) track করতে চায়,
        //       তাহলে Transaction-এ একটি `cash_flow_category` column যোগ
        //       করুন। এখনকার VoucherType.nature দিয়ে যতটুকু সম্ভব করা হলো।
        // ---------------------------------------------------------------

        /** @var float $operatingIn  — Receipt voucher (cash/bank inflow) */
        $operatingIn  = (float) ($rows->get('receipt')?->total_debit ?? 0);

        /** @var float $operatingOut — Payment voucher (cash/bank outflow) */
        $operatingOut = (float) ($rows->get('payment')?->total_credit ?? 0);

        /** @var float $investingIn  — Journal Dr to cash (asset sale etc.) */
        $investingIn  = (float) ($rows->get('journal')?->total_debit ?? 0);

        /** @var float $investingOut — Journal Cr from cash (asset purchase etc.) */
        $investingOut = (float) ($rows->get('journal')?->total_credit ?? 0);

        // Financing: এই version-এ VoucherType দিয়ে আলাদা করা সম্ভব নয়।
        // ভবিষ্যতে Transaction-এ cash_flow_category যোগ করলে এটা নির্ভুল হবে।
        $financingIn  = 0.0;
        $financingOut = 0.0;

        // ── Opening Balance (date filter থাকলে filter-এর আগের balance) ──
        $openingBalance = $this->calculateOpeningBalance(
            companyId: $companyId,
            fromDate:  $fromDate,
        );

        // ── Closing Balance ───────────────────────────────────────────
        $closingBalance = $openingBalance
            + $operatingIn  - $operatingOut
            + $investingIn  - $investingOut
            + $financingIn  - $financingOut;

        // ── Cash/Bank account list (sidebar-এ দেখানোর জন্য) ──────────
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
            'closingBalance',
        ));
    }

    // ---------------------------------------------------------------
    // Private Helper
    // ---------------------------------------------------------------

    /**
     * Date filter-এর আগের Cash/Bank entries দিয়ে opening balance হিসাব।
     * কোনো date filter না থাকলে account-এর opening_balance যোগ করো।
     */
    private function calculateOpeningBalance(int $companyId, ?string $fromDate): float
    {
        // Cash/Bank account-গুলোর opening_balance যোগ করো
        $accountOpening = (float) Account::query()
            ->where('company_id', $companyId)
            ->whereIn('nature', [Account::NATURE_CASH, Account::NATURE_BANK])
            ->sum('opening_balance');

        if (! $fromDate) {
            return $accountOpening;
        }

        // Date filter আছে → filter-এর আগের ledger entries দিয়ে balance
        $prior = DB::table('ledger_entries AS le')
            ->join('accounts AS a', 'a.id', '=', 'le.account_id')
            ->join('transactions AS t', 't.id', '=', 'le.transaction_id')
            ->where('a.company_id', $companyId)
            ->whereIn('a.nature', [Account::NATURE_CASH, Account::NATURE_BANK])
            ->where('le.is_reversed', false)
            ->where('t.status', 'Posted')
            ->whereDate('le.voucher_date', '<', $fromDate)
            ->selectRaw('
                COALESCE(SUM(le.debit_amount), 0)  AS prior_debit,
                COALESCE(SUM(le.credit_amount), 0) AS prior_credit
            ')
            ->first();

        // Cash/Bank = Asset/Debit Normal → Dr বাড়লে balance বাড়ে
        return $accountOpening
            + (float) $prior->prior_debit
            - (float) $prior->prior_credit;
    }
}