<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Account;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Seeder;

class SalesOrderTestSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // 1. Company
        // ---------------------------------------------------------------
        $superAdmin = \App\Models\User::where('email', 'mallick.jess@gmail.com')->first();

        if (!$superAdmin) {
            $this->command->error('Super Admin not found. Run SuperAdminSeeder first.');
            return;
        }

        $company = Company::create([
            'owner_id'        => $superAdmin->id,
            'company_name'    => 'Demo Trading Ltd.',
            'business_type'   => 'Trading',
            'owner_name'      => 'Test Owner',
            'email'           => 'demo@company.com',
            'phone'           => '01234567890',
            'address'         => '123 Business Street',
            'city'            => 'Dhaka',
            'country'         => 'Bangladesh',  // varchar ✅
            'currency'        => 'BDT',         // varchar ✅
            'currency_symbol' => '৳',           // varchar ✅
            'financial_year'  => 'FY-2026',     // varchar ✅
            'status'          => 1,             // tinyint ✅
        ]);

        $this->command->info("✓ Company created: {$company->company_name}");

        // ---------------------------------------------------------------
        // 2. Financial Year
        // ---------------------------------------------------------------
        $financialYear = FinancialYear::create([
            'company_id' => $company->id,
            'year_name'  => 'FY-2026',      // varchar ✅
            'start_date' => '2026-01-01',   // date (no time) ✅
            'end_date'   => '2026-12-31',   // date (no time) ✅
            'is_active'  => 1,              // tinyint ✅
            'is_closed'  => 0,              // tinyint ✅
        ]);

        $this->command->info("✓ Financial Year created: {$financialYear->year_name}");

        // ---------------------------------------------------------------
        // 3. Chart of Accounts
        // account_type: enum('Asset','Liability','Equity','Income','Expense')
        // balance_type: enum('Debit','Credit')
        // ---------------------------------------------------------------
        $accounts = [
            ['account_code' => 'AR-001',  'account_name' => 'Accounts Receivable', 'account_type' => 'Asset',     'balance_type' => 'Debit',  'opening_balance' => 0],
            ['account_code' => 'BK-001',  'account_name' => 'Bank Account',         'account_type' => 'Asset',     'balance_type' => 'Debit',  'opening_balance' => 50000],
            ['account_code' => 'CSH-001', 'account_name' => 'Cash on Hand',         'account_type' => 'Asset',     'balance_type' => 'Debit',  'opening_balance' => 10000],
            ['account_code' => 'SLS-001', 'account_name' => 'Sales Revenue',        'account_type' => 'Income',    'balance_type' => 'Credit', 'opening_balance' => 0],
            ['account_code' => 'SRV-001', 'account_name' => 'Services Revenue',     'account_type' => 'Income',    'balance_type' => 'Credit', 'opening_balance' => 0],
            ['account_code' => 'AP-001',  'account_name' => 'Accounts Payable',     'account_type' => 'Liability', 'balance_type' => 'Credit', 'opening_balance' => 0],
            ['account_code' => 'CAP-001', 'account_name' => "Owner's Capital",      'account_type' => 'Equity',    'balance_type' => 'Credit', 'opening_balance' => 100000],
        ];

        foreach ($accounts as $acc) {
            Account::create([
                'company_id'      => $company->id,
                'account_code'    => $acc['account_code'], // varchar(20) ✅
                'account_name'    => $acc['account_name'],
                'account_type'    => $acc['account_type'], // enum validated ✅
                'balance_type'    => $acc['balance_type'], // enum('Debit','Credit') ✅
                'opening_balance' => $acc['opening_balance'],
                'is_system'       => 0,  // tinyint ✅
                'is_active'       => 1,  // tinyint ✅
                'level'           => 1,  // tinyint ✅
            ]);
        }

        $this->command->info("✓ Chart of Accounts created (7 accounts)");

        // ---------------------------------------------------------------
        // 4. Customers
        // customer_type: enum('Individual','Business')
        // balance_type:  enum('Receivable','Advance')
        // ---------------------------------------------------------------
        $customersData = [
            ['name' => 'Acme Corporation',   'email' => 'acme@example.com',   'phone' => '01712345678', 'address' => '123 Business Street, Dhaka'],
            ['name' => 'Global Traders Inc', 'email' => 'global@example.com', 'phone' => '01812345678', 'address' => '456 Commerce Avenue, Chittagong'],
            ['name' => 'Tech Solutions Ltd', 'email' => 'tech@example.com',   'phone' => '01912345678', 'address' => '789 Innovation Park, Dhaka'],
        ];

        $createdCustomers = [];
        foreach ($customersData as $data) {
            $createdCustomers[] = Customer::create([
                'company_id'      => $company->id,
                'name'            => $data['name'],
                'email'           => $data['email'],
                'phone'           => $data['phone'],
                'address'         => $data['address'],
                'customer_type'   => 'Business',    // enum('Individual','Business') ✅
                'credit_limit'    => 0,             // decimal ✅
                'opening_balance' => 0,             // decimal ✅
                'balance_type'    => 'Receivable',  // enum('Receivable','Advance') ✅
                'is_active'       => 1,             // tinyint ✅
            ]);
        }

        $this->command->info("✓ Customers created: " . count($createdCustomers));

        // ---------------------------------------------------------------
        // 5. Sales Orders
        // status: enum('Draft','Confirmed','Delivered','Cancelled')
        // order_date / delivery_date: date (no timestamp)
        // ---------------------------------------------------------------
        $soProducts = [
            ['name' => 'Laptop Computer', 'price' => 50000],
            ['name' => 'Smartphone',      'price' => 35000],
            ['name' => 'Office Chair',    'price' => 8000],
            ['name' => 'Desk',            'price' => 15000],
            ['name' => 'Monitor',         'price' => 12000],
        ];

        $salesOrders = [
            ['customer' => $createdCustomers[0], 'status' => 'Draft',     'items' => [['product' => $soProducts[0], 'qty' => 1], ['product' => $soProducts[1], 'qty' => 2]]],
            ['customer' => $createdCustomers[1], 'status' => 'Confirmed', 'items' => [['product' => $soProducts[2], 'qty' => 3], ['product' => $soProducts[3], 'qty' => 1]]],
            ['customer' => $createdCustomers[2], 'status' => 'Delivered', 'items' => [['product' => $soProducts[4], 'qty' => 2]]],
            ['customer' => $createdCustomers[0], 'status' => 'Cancelled', 'items' => [['product' => $soProducts[0], 'qty' => 1]]],
        ];

        $soCount = 0;
        foreach ($salesOrders as $soData) {
            $total = 0;
            foreach ($soData['items'] as $item) {
                $total += $item['product']['price'] * $item['qty'];
            }

            $so = SalesOrder::create([
                'company_id'    => $company->id,
                'customer_id'   => $soData['customer']->id,
                'so_number'     => 'SO-' . str_pad(++$soCount, 4, '0', STR_PAD_LEFT),
                'order_date'    => now()->subDays(rand(0, 30))->toDateString(), // date only ✅
                'delivery_date' => now()->addDays(rand(1, 30))->toDateString(), // date only ✅
                'status'        => $soData['status'], // enum validated ✅
                'subtotal'      => $total,
                'tax'           => 0,
                'discount'      => 0,
                'total'         => $total,
                'is_accounted'  => 0, // tinyint ✅
            ]);

            foreach ($soData['items'] as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'item_name'      => $item['product']['name'], // ✅
                    'quantity'       => $item['qty'],             // decimal ✅
                    'unit'           => 'pcs',                    // varchar ✅
                    'unit_price'     => $item['product']['price'],// decimal ✅
                    'total'          => $item['product']['price'] * $item['qty'], // decimal ✅
                ]);
            }
        }

        $this->command->info("✓ Sales Orders created: $soCount");

        // ---------------------------------------------------------------
        // Summary
        // ---------------------------------------------------------------
        $this->command->line('========================================');
        $this->command->info('✅ Test Data Setup Complete');
        $this->command->line('========================================');
        $this->command->line("Company ID    : {$company->id}");
        $this->command->line("Financial Year: {$financialYear->year_name}");
        $this->command->line("Customers     : " . count($createdCustomers));
        $this->command->line("Sales Orders  : $soCount");
        $this->command->line('========================================');
    }
}