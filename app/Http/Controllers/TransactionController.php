<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Company;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
   

    /**
 * Transaction List
 */
public function index()
{
    $companyId = session('company_id');


    if (!$companyId) {
    return redirect()
        ->route('dashboard.index')
        ->with('error', 'Please select company first.');
}


    $transactions = Transaction::with([
        'details.account',
        'user'
    ])
    ->where('company_id', $companyId)
    ->latest()
    ->paginate(20);



    return view(
        'transactions.index',
        compact('transactions')
    );
   

}
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyId = session('company_id');

        if (!$companyId) {
            return redirect()
                ->route('dashboard.index')
                ->with('error', 'Please select company first.');
        }

        $company = Company::findOrFail($companyId);

        $accounts = Account::where('company_id', $companyId)
            ->orderBy('account_code')
            ->get();

        return view('transactions.create', compact('company', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $companyId = session('company_id');

                    if (!$companyId) {
                    return redirect()
                        ->route('dashboard.index')
                        ->with('error', 'Please select company first.');
                }

        $request->validate([
            'debit_account_id' => 'required|exists:accounts,id',
            'credit_account_id' => 'required|exists:accounts,id|different:debit_account_id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:Income,Expense,Journal',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $companyId) {
            // Voucher Number Generate
            
            $prefix = match($request->transaction_type) {
    'Income' => 'INC',
    'Expense' => 'EXP',
    default => 'JV',
};


$last = Transaction::where('voucher_no', 'like', $prefix . '-%')
    ->latest('id')
    ->first();


if ($last) {

    $lastNumber = (int) str_replace(
        $prefix . '-',
        '',
        $last->voucher_no
    );

    $next = $lastNumber + 1;

} else {

    $next = 1;

}


$voucherNo = $prefix . '-' . str_pad(
    $next,
    6,
    '0',
    STR_PAD_LEFT
);

            // Transaction Create
            $transaction = Transaction::create([
                'company_id' => $companyId,
                'account_id' => $request->debit_account_id,
                'debit_account_id' => $request->debit_account_id,
                'credit_account_id' => $request->credit_account_id,
                'transaction_date' => $request->transaction_date,
                'voucher_no' => $voucherNo,
                'transaction_type' => $request->transaction_type,
                'amount' => $request->amount,
                'description' => $request->description,
                'created_by' => Auth::id(),
            ]);

            // Debit Detail
TransactionDetail::create([
    'transaction_id' => $transaction->id,
    'account_id'     => $request->debit_account_id,
    'debit'          => $request->amount,
    'credit'         => 0,
]);

// Credit Detail
TransactionDetail::create([
    'transaction_id' => $transaction->id,
    'account_id'     => $request->credit_account_id,
    'debit'          => 0,
    'credit'         => $request->amount,
]);

            // Debit Ledger Entry
            LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'company_id' => $companyId,
                'account_id' => $request->debit_account_id,
                'entry_date' => $request->transaction_date,
                'debit' => $request->amount,
                'credit' => 0,
                'description' => $request->description,
            ]);

            // Credit Ledger Entry
            LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'company_id' => $companyId,
                'account_id' => $request->credit_account_id,
                'entry_date' => $request->transaction_date,
                'debit' => 0,
                'credit' => $request->amount,
                'description' => $request->description,
            ]);
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction posted successfully.');
    }

    /**
     * Show the specified resource.
     */
    public function show(Transaction $transaction)
    {
        $companyId = session('company_id');

        // সিকিউরিটি চেক: ট্রানজেকশনটি বর্তমান কোম্পানির কিনা
        if ($transaction->company_id != $companyId) {
            abort(403, 'Unauthorized action.');
        }

        return view('transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        $companyId = session('company_id');

        if (!$companyId) {
            return redirect()
                ->route('dashboard.index')
                ->with('error', 'Please select company first.');
}

        // সিকিউরিটি চেক: ট্রানজেকশনটি বর্তমান কোম্পানির কিনা
        if ($transaction->company_id != $companyId) {
            abort(403, 'Unauthorized action.');
        }

        $company = Company::findOrFail($companyId);

        $accounts = Account::where('company_id', $companyId)
            ->orderBy('account_code')
            ->get();

        return view('transactions.edit', compact('transaction', 'company', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        $companyId = session('company_id');

        if (!$companyId) {
            return redirect()
                ->route('dashboard.index')
                ->with('error', 'Please select company first.');
        }

        // সিকিউরিটি চেক: ট্রানজেকশনটি বর্তমান কোম্পানির কিনা
        if ($transaction->company_id != $companyId) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'debit_account_id' => 'required|exists:accounts,id',
            'credit_account_id' => 'required|exists:accounts,id|different:debit_account_id',
            'transaction_date' => 'required|date',
            'transaction_type' => 'required|in:Income,Expense,Journal',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $transaction, $companyId) {
            // পুরাতন Ledger Entry Delete
            LedgerEntry::where('transaction_id', $transaction->id)->delete();

            // পুরাতন Transaction Details Delete
TransactionDetail::where('transaction_id', $transaction->id)->delete();

            // Transaction Update
            $transaction->update([
                'company_id' => $companyId,
                'account_id' => $request->debit_account_id,
                'debit_account_id' => $request->debit_account_id,
                'credit_account_id' => $request->credit_account_id,
                'transaction_date' => $request->transaction_date,
                'transaction_type' => $request->transaction_type,
                'amount' => $request->amount,
                'description' => $request->description,
            ]);

            // Debit Detail
TransactionDetail::create([
    'transaction_id' => $transaction->id,
    'account_id'     => $request->debit_account_id,
    'debit'          => $request->amount,
    'credit'         => 0,
]);

// Credit Detail
TransactionDetail::create([
    'transaction_id' => $transaction->id,
    'account_id'     => $request->credit_account_id,
    'debit'          => 0,
    'credit'         => $request->amount,
]);

           

            // Debit Ledger
            LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'company_id' => $companyId,
                'account_id' => $request->debit_account_id,
                'entry_date' => $request->transaction_date,
                'debit' => $request->amount,
                'credit' => 0,
                'description' => $request->description,
            ]);

            // Credit Ledger
            LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'company_id' => $companyId,
                'account_id' => $request->credit_account_id,
                'entry_date' => $request->transaction_date,
                'debit' => 0,
                'credit' => $request->amount,
                'description' => $request->description,
            ]);
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        $companyId = session('company_id');

        // সিকিউরিটি চেক: ট্রানজেকশনটি বর্তমান কোম্পানির কিনা
        if ($transaction->company_id != $companyId) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($transaction) {
            LedgerEntry::where('transaction_id', $transaction->id)->delete();
            TransactionDetail::where('transaction_id', $transaction->id)->delete();
            $transaction->delete();
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', 'Transaction deleted successfully.');

    }
}