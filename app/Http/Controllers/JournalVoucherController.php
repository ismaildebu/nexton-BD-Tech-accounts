<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VoucherType;
use App\Models\Account;
use App\Models\FinancialYear;

class JournalVoucherController extends Controller
{
    /**
     * Journal Voucher List
     */
    public function index()
    {
        return view('vouchers.journal.index');
    }


    /**
     * Create Journal Voucher Form
     */
    public function create()
    {
        $voucherType = VoucherType::where(
            'voucher_code',
            'JV'
        )->first();


        $accounts = Account::where(
            'is_active',
            true
        )->get();


        $financialYear = FinancialYear::where(
            'is_active',
            true
        )->first();


        return view(
            'vouchers.journal.create',
            compact(
                'voucherType',
                'accounts',
                'financialYear'
            )
        );
    }


    /**
     * Store Journal Voucher
     */
    public function store(Request $request)
{
    $request->validate([
        'transaction_date' => 'required|date',
        'accounts' => 'required|array|min:2',
        'accounts.*' => 'required|exists:accounts,id',
        'debits' => 'required|array',
        'credits' => 'required|array',
        'narration' => 'nullable|string|max:1000',
    ]);

    $totalDebit = array_sum($request->debits);
    $totalCredit = array_sum($request->credits);

    if ($totalDebit <= 0 || $totalCredit <= 0) {
        return back()
            ->withInput()
            ->withErrors([
                'error' => 'Debit and Credit amount required.'
            ]);
    }

    if ($totalDebit != $totalCredit) {
        return back()
            ->withInput()
            ->withErrors([
                'error' => 'Debit and Credit must be equal.'
            ]);
    }

    // এখানে Transaction Save হবে
    // এখানে Ledger Posting হবে

    return redirect()
        ->route('journal-vouchers.index')
        ->with('success', 'Journal Voucher Saved Successfully.');
}
}