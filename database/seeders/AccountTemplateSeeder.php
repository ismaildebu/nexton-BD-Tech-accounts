<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountTemplate;

class AccountTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [

            /*
            |--------------------------------------------------------------------------
            | ASSETS (1000)
            |--------------------------------------------------------------------------
            */

            [
                'account_code' => 1001,
                'account_name' => 'Cash in Hand',
                'account_type' => 'Asset',
                'nature' => 'Cash',
                'balance_type' => 'Debit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            [
                'account_code' => 1002,
                'account_name' => 'Bank Account',
                'account_type' => 'Asset',
                'nature' => 'Bank',
                'balance_type' => 'Debit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            [
                'account_code' => 1003,
                'account_name' => 'Accounts Receivable',
                'account_type' => 'Asset',
                'nature' => 'Customer',
                'balance_type' => 'Debit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            [
                'account_code' => 1004,
                'account_name' => 'Inventory / Stock',
                'account_type' => 'Asset',
                'nature' => 'Inventory',
                'balance_type' => 'Debit',
                'industry' => 'Trading',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            [
                'account_code' => 1005,
                'account_name' => 'Furniture & Equipment',
                'account_type' => 'Asset',
                'nature' => 'Fixed Asset',
                'balance_type' => 'Debit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | EXPENSES (2000)
            |--------------------------------------------------------------------------
            */

            [
                'account_code' => 2001,
                'account_name' => 'Salary Expense',
                'account_type' => 'Expense',
                'nature' => 'Expense',
                'balance_type' => 'Debit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            [
                'account_code' => 2002,
                'account_name' => 'Rent Expense',
                'account_type' => 'Expense',
                'nature' => 'Expense',
                'balance_type' => 'Debit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            [
                'account_code' => 2003,
                'account_name' => 'Electricity Expense',
                'account_type' => 'Expense',
                'nature' => 'Expense',
                'balance_type' => 'Debit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            [
                'account_code' => 2004,
                'account_name' => 'Marketing Expense',
                'account_type' => 'Expense',
                'nature' => 'Expense',
                'balance_type' => 'Debit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | LIABILITIES (3000)
            |--------------------------------------------------------------------------
            */

            [
                'account_code' => 3001,
                'account_name' => 'Accounts Payable',
                'account_type' => 'Liability',
                'nature' => 'Supplier',
                'balance_type' => 'Credit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            [
                'account_code' => 3002,
                'account_name' => 'Loan Payable',
                'account_type' => 'Liability',
                'nature' => 'General',
                'balance_type' => 'Credit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | EQUITY (4000)
            |--------------------------------------------------------------------------
            */

            [
                'account_code' => 4001,
                'account_name' => "Owner's Capital",
                'account_type' => 'Equity',
                'nature' => 'General',
                'balance_type' => 'Credit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | INCOME (5000)
            |--------------------------------------------------------------------------
            */

            [
                'account_code' => 5001,
                'account_name' => 'Sales Revenue',
                'account_type' => 'Income',
                'nature' => 'Income',
                'balance_type' => 'Credit',
                'industry' => 'Trading',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

            [
                'account_code' => 5002,
                'account_name' => 'Service Income',
                'account_type' => 'Income',
                'nature' => 'Income',
                'balance_type' => 'Credit',
                'industry' => 'Service',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],

        ];

        foreach ($accounts as $account) {

            AccountTemplate::updateOrCreate(
                ['account_code' => $account['account_code']],
                $account
            );

        }
    }
}