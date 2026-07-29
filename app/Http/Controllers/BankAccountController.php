<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;
use App\Models\Company;



class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   
    public function index()
{
    $bankAccounts = BankAccount::where(
        'company_id',
        session('company_id')
    )
    ->latest()
    ->get();

    return view('banking.index', compact('bankAccounts'));
}

    /**
     * Show the form for creating a new resource.
     */
   public function create()
{
    $companies = Company::orderBy('company_name')->get();

    return view('banking.create', compact('companies'));
}
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $request->validate([
        'company_id'     => 'required|exists:companies,id',
        'bank_name'      => 'required|string|max:255',
        'account_name'   => 'required|string|max:255',
        'account_number' => 'required|string|max:255|unique:bank_accounts,account_number',
        'balance'        => 'required|numeric|min:0',
    ]);

    BankAccount::create([
        'company_id'     => $request->company_id,
        'bank_name'      => $request->bank_name,
        'account_name'   => $request->account_name,
        'account_number' => $request->account_number,
        'balance'        => $request->balance,
        'is_active'      => true,
    ]);

    return redirect()
        ->route('bank-accounts.index')
        ->with('success', 'Bank account created successfully.');
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}