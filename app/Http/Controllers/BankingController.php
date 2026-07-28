<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BankingController extends Controller
{
    public function index()
    {
        return view('banking.index');
    }
}