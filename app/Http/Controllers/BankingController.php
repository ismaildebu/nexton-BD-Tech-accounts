<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;

class BankingController extends Controller
{
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
}