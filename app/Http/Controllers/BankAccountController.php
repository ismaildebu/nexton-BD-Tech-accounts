<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    /**
     * Display a listing of bank accounts for the current company.
     *
     * BelongsToCompany global scope স্বয়ংক্রিয়ভাবে company filter করে।
     */
    public function index(): View
    {
        $bankAccounts = BankAccount::latest()->get();

        return view('banking.index', compact('bankAccounts'));
    }

    /**
     * Show the form for creating a new bank account.
     */
    public function create(): View
    {
        return view('banking.create');
    }

    /**
     * Store a newly created bank account.
     *
     * company_id সবসময় session থেকে নেওয়া হয় — request থেকে নয়।
     * এটি company_id tampering (IDOR) সম্পূর্ণ বন্ধ করে।
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name'      => ['required', 'string', 'max:255'],
            'account_name'   => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255', 'unique:bank_accounts,account_number'],
            'branch_name'    => ['nullable', 'string', 'max:255'],
            'balance'        => ['required', 'numeric', 'min:0'],
        ]);

        BankAccount::create([
            'company_id'     => (int) session('company_id'), // ✅ session থেকে, request থেকে নয়
            'bank_name'      => $validated['bank_name'],
            'account_name'   => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'branch_name'    => $validated['branch_name'] ?? null,
            'balance'        => $validated['balance'],
            'is_active'      => true,
        ]);

        return redirect()
            ->route('bank-accounts.index')
            ->with('success', 'Bank account created successfully.');
    }

    /**
     * Display the specified bank account.
     *
     * Route model binding + BelongsToCompany scope মিলে
     * অন্য company-র account হলে স্বয়ংক্রিয়ভাবে 404 দেবে।
     */
    public function show(BankAccount $bankAccount): View
    {
        return view('banking.show', compact('bankAccount'));
    }

    /**
     * Show the form for editing the specified bank account.
     */
    public function edit(BankAccount $bankAccount): View
    {
        return view('banking.edit', compact('bankAccount'));
    }

    /**
     * Update the specified bank account.
     */
    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name'      => ['required', 'string', 'max:255'],
            'account_name'   => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:255', 'unique:bank_accounts,account_number,' . $bankAccount->id],
            'branch_name'    => ['nullable', 'string', 'max:255'],
            'balance'        => ['required', 'numeric', 'min:0'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $bankAccount->update([
            'bank_name'      => $validated['bank_name'],
            'account_name'   => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'branch_name'    => $validated['branch_name'] ?? null,
            'balance'        => $validated['balance'],
            'is_active'      => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('bank-accounts.index')
            ->with('success', 'Bank account updated successfully.');
    }

    /**
     * Delete the specified bank account.
     */
    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        return redirect()
            ->route('bank-accounts.index')
            ->with('success', 'Bank account deleted successfully.');
    }
}