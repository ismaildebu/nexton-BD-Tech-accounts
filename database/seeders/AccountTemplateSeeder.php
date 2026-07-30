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

/*
|--------------------------------------------------------------------------
| MEDIA / NEWSPAPER ACCOUNTS
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| ASSETS
|--------------------------------------------------------------------------
*/

[
    'account_code' => 1101,
    'account_name' => 'Newsprint Inventory',
    'account_type' => 'Asset',
    'nature' => 'Inventory',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 1102,
    'account_name' => 'Printing Equipment',
    'account_type' => 'Asset',
    'nature' => 'Fixed Asset',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 1103,
    'account_name' => 'Camera & Media Equipment',
    'account_type' => 'Asset',
    'nature' => 'Fixed Asset',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

/*
|--------------------------------------------------------------------------
| EXPENSES
|--------------------------------------------------------------------------
*/

[
    'account_code' => 2101,
    'account_name' => 'Printing Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2102,
    'account_name' => 'Newsprint Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2103,
    'account_name' => 'Reporter Salary',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2104,
    'account_name' => 'Editor Salary',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2105,
    'account_name' => 'Distribution Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2106,
    'account_name' => 'Website Hosting Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2107,
    'account_name' => 'Internet Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2108,
    'account_name' => 'Photography Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2109,
    'account_name' => 'Press Maintenance Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

/*
|--------------------------------------------------------------------------
| INCOME
|--------------------------------------------------------------------------
*/

[
    'account_code' => 5101,
    'account_name' => 'Advertisement Revenue',
    'account_type' => 'Income',
    'nature' => 'Income',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5102,
    'account_name' => 'Subscription Revenue',
    'account_type' => 'Income',
    'nature' => 'Income',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5103,
    'account_name' => 'Online Advertisement Revenue',
    'account_type' => 'Income',
    'nature' => 'Income',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5104,
    'account_name' => 'Sponsored Content Revenue',
    'account_type' => 'Income',
    'nature' => 'Income',
    'balance_type' => 'Credit',
    'industry' => 'Media',
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