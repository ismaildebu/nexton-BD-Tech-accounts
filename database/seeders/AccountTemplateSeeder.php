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
            | EXPENSES (5000)
            |--------------------------------------------------------------------------
            */

            [
                'account_code' => 5001,
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
                'account_code' => 5002,
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
                'account_code' => 5003,
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
                'account_code' => 5004,
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
            | LIABILITIES (2000)
            |--------------------------------------------------------------------------
            */

            [
                'account_code' => 2001,
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
                'account_code' => 2002,
                'account_name' => 'Loan Payable',
                'account_type' => 'Liability',
                'nature' => 'General',
                'balance_type' => 'Credit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],


            [
    'account_code' => 2003,
    'account_name' => 'Accrued Salary Payable',
    'account_type' => 'Liability',
    'nature' => 'General',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2004,
    'account_name' => 'Tax Payable',
    'account_type' => 'Liability',
    'nature' => 'General',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2005,
    'account_name' => 'VAT Payable',
    'account_type' => 'Liability',
    'nature' => 'General',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 2006,
    'account_name' => 'Customer Advance / Subscription Advance',
    'account_type' => 'Liability',
    'nature' => 'General',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],



            /*
            |--------------------------------------------------------------------------
            | EQUITY (3000)
            |--------------------------------------------------------------------------
            */

            [
                'account_code' => 3001,
                'account_name' => "Owner's Capital",
                'account_type' => 'Equity',
                'nature' => 'General',
                'balance_type' => 'Credit',
                'industry' => 'All',
                'business_type' => null,
                'is_system' => true,
                'is_active' => true,
            ],


            [
    'account_code' => 3002,
    'account_name' => 'Retained Earnings',
    'account_type' => 'Equity',
    'nature' => 'General',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 3003,
    'account_name' => "Owner's Drawings",
    'account_type' => 'Equity',
    'nature' => 'General',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],




            /*
            |--------------------------------------------------------------------------
            | INCOME (4000)
            |--------------------------------------------------------------------------
            */

            [
                'account_code' => 4001,
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
                'account_code' => 4002,
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

[
    'account_code' => 1104,
    'account_name' => 'Newspaper Finished Goods / Stock',
    'account_type' => 'Asset',
    'nature' => 'Inventory',
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
    'account_code' => 5101,
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
    'account_code' => 5102,
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
    'account_code' => 5103,
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
    'account_code' => 5104,
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
    'account_code' => 5105,
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
    'account_code' => 5106,
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
    'account_code' => 5107,
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
    'account_code' => 5108,
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
    'account_code' => 5109,
    'account_name' => 'Press Maintenance Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5110,
    'account_name' => 'Freelancer / Contributor Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5111,
    'account_name' => 'News Gathering Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5112,
    'account_name' => 'Transportation Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5113,
    'account_name' => 'Commission Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5114,
    'account_name' => 'Advertisement Promotion Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5115,
    'account_name' => 'Digital Content Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5116,
    'account_name' => 'Software & Subscription Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5117,
    'account_name' => 'Telephone & Communication Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5118,
    'account_name' => 'Office Supplies Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5119,
    'account_name' => 'Legal & Compliance Expense',
    'account_type' => 'Expense',
    'nature' => 'Expense',
    'balance_type' => 'Debit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 5120,
    'account_name' => 'Depreciation Expense',
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
    'account_code' => 4101,
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
    'account_code' => 4102,
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
    'account_code' => 4103,
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
    'account_code' => 4104,
    'account_name' => 'Sponsored Content Revenue',
    'account_type' => 'Income',
    'nature' => 'Income',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],


[
    'account_code' => 4105,
    'account_name' => 'Newspaper Sales Revenue',
    'account_type' => 'Income',
    'nature' => 'Income',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 4106,
    'account_name' => 'Content Licensing Revenue',
    'account_type' => 'Income',
    'nature' => 'Income',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 4107,
    'account_name' => 'Event Revenue',
    'account_type' => 'Income',
    'nature' => 'Income',
    'balance_type' => 'Credit',
    'industry' => 'Media',
    'business_type' => null,
    'is_system' => true,
    'is_active' => true,
],

[
    'account_code' => 4108,
    'account_name' => 'Other Media Revenue',
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


