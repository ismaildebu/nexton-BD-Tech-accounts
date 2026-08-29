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
        // 1. Create Test Company
        // ---------------------------------------------------------------
        // Note: Company requires owner_id (Super Admin user)
        $superAdmin = \App\Models\User::where('email', 'mallick.jess@gmail.com')->first();
        
        if (!$superAdmin) {
            $this->command->error('Super Admin user not found. Run SuperAdminSeeder first.');
            return;
        }

        $company = Company::create([
            'owner_id' => $superAdmin->id,
            'company_name' => 'Demo Trading Ltd.',
            'owner_name' => 'Test Owner',
            'email' => 'demo@company.com',
            'phone' => '01234567890',
            'address' => '123 Business Street, Dhaka',
            'city' => 'Dhaka',
            'country' => 'BD',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'business_type' => 'Trading',
            'status' => 'active',
        ]);

        echo "✓ Company created: {$company->company_name}\n";

        // ---------------------------------------------------------------
        // 2. Create Financial Year
        // ---------------------------------------------------------------
        $financialYear = FinancialYear::create([
            'company_id' => $company->id,
            'name' => now()->year,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_closed' => false,
        ]);

        echo "✓ Financial Year created: {$financialYear->name}\n";

        // ---------------------------------------------------------------
        // 3. Create Chart of Accounts
        // ---------------------------------------------------------------

        // Asset Accounts
        $arAccount = Account::create([
            'company_id' => $company->id,
            'account_name' => 'Accounts Receivable',
            'account_type' => 'Asset',
            'nature' => 'Receivable',
            'code' => 'AR-001',
            'opening_balance' => 0,
        ]);

        $bankAccount = Account::create([
            'company_id' => $company->id,
            'account_name' => 'Bank Account',
            'account_type' => 'Asset',
            'nature' => 'Bank',
            'code' => 'BK-001',
            'opening_balance' => 50000,
        ]);

        $cashAccount = Account::create([
            'company_id' => $company->id,
            'account_name' => 'Cash on Hand',
            'account_type' => 'Asset',
            'nature' => 'Cash',
            'code' => 'CSH-001',
            'opening_balance' => 10000,
        ]);

        // Income Accounts
        $salesAccount = Account::create([
            'company_id' => $company->id,
            'account_name' => 'Sales Revenue',
            'account_type' => 'Income',
            'nature' => 'Sales',
            'code' => 'SLS-001',
            'opening_balance' => 0,
        ]);

        $servicesAccount = Account::create([
            'company_id' => $company->id,
            'account_name' => 'Services Revenue',
            'account_type' => 'Income',
            'nature' => 'Services',
            'code' => 'SRV-001',
            'opening_balance' => 0,
        ]);

        // Liability Accounts
        $apAccount = Account::create([
            'company_id' => $company->id,
            'account_name' => 'Accounts Payable',
            'account_type' => 'Liability',
            'nature' => 'Supplier',
            'code' => 'AP-001',
            'opening_balance' => 0,
        ]);

        // Equity Account
        $capitalAccount = Account::create([
            'company_id' => $company->id,
            'account_name' => 'Owner\'s Capital',
            'account_type' => 'Equity',
            'nature' => 'Capital',
            'code' => 'CAP-001',
            'opening_balance' => 100000,
        ]);

        echo "✓ Chart of Accounts created (7 accounts)\n";

        // ---------------------------------------------------------------
        // 4. Create Customers
        // ---------------------------------------------------------------
        $customers = [
            [
                'name' => 'Acme Corporation',
                'email' => 'acme@example.com',
                'phone' => '01712345678',
                'address' => '123 Business Street, Dhaka',
            ],
            [
                'name' => 'Global Traders Inc',
                'email' => 'global@example.com',
                'phone' => '01812345678',
                'address' => '456 Commerce Avenue, Chittagong',
            ],
            [
                'name' => 'Tech Solutions Ltd',
                'email' => 'tech@example.com',
                'phone' => '01912345678',
                'address' => '789 Innovation Park, Dhaka',
            ],
        ];

        $createdCustomers = [];
        foreach ($customers as $customerData) {
            $createdCustomers[] = Customer::create(array_merge(
                $customerData,
                [
                    'company_id' => $company->id,
                    'customer_type' => 'Retail',
                    'is_active' => true,
                ]
            ));
        }

        echo "✓ Customers created: " . count($createdCustomers) . "\n";

        // ---------------------------------------------------------------
        // 5. Create Sample Sales Orders
        // ---------------------------------------------------------------
        $soProducts = [
            ['name' => 'Laptop Computer', 'price' => 50000],
            ['name' => 'Smartphone', 'price' => 35000],
            ['name' => 'Office Chair', 'price' => 8000],
            ['name' => 'Desk', 'price' => 15000],
            ['name' => 'Monitor', 'price' => 12000],
        ];

        $salesOrders = [
            // SO 1: Draft
            [
                'customer' => $createdCustomers[0],
                'status' => 'Draft',
                'items' => [
                    ['product' => $soProducts[0], 'qty' => 1],
                    ['product' => $soProducts[1], 'qty' => 2],
                ],
            ],
            // SO 2: Confirmed (ready for accounting)
            [
                'customer' => $createdCustomers[1],
                'status' => 'Confirmed',
                'items' => [
                    ['product' => $soProducts[2], 'qty' => 3],
                    ['product' => $soProducts[3], 'qty' => 1],
                ],
            ],
            // SO 3: Delivered
            [
                'customer' => $createdCustomers[2],
                'status' => 'Delivered',
                'items' => [
                    ['product' => $soProducts[4], 'qty' => 2],
                ],
            ],
            // SO 4: Cancelled
            [
                'customer' => $createdCustomers[0],
                'status' => 'Cancelled',
                'items' => [
                    ['product' => $soProducts[0], 'qty' => 1],
                ],
            ],
        ];

        $soCount = 0;
        foreach ($salesOrders as $soData) {
            $total = 0;
            $items = $soData['items'];

            // Calculate total
            foreach ($items as $item) {
                $total += $item['product']['price'] * $item['qty'];
            }

            $so = SalesOrder::create([
                'company_id' => $company->id,
                'customer_id' => $soData['customer']->id,
                'so_number' => 'SO-' . str_pad(++$soCount, 4, '0', STR_PAD_LEFT),
                'order_date' => now()->subDays(rand(0, 30)),
                'delivery_date' => now()->addDays(rand(1, 30)),
                'status' => $soData['status'],
                'subtotal' => $total,
                'tax' => 0,
                'discount' => 0,
                'total' => $total,
                'is_accounted' => false,
            ]);

            // Create items
            foreach ($items as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_name' => $item['product']['name'],
                    'quantity' => $item['qty'],
                    'unit_price' => $item['product']['price'],
                ]);
            }
        }

        echo "✓ Sales Orders created: $soCount (Draft, Confirmed, Delivered, Cancelled)\n";

        // ---------------------------------------------------------------
        // Summary
        // ---------------------------------------------------------------
        echo "\n";
        echo "========================================\n";
        echo "✅ Test Data Setup Complete\n";
        echo "========================================\n";
        echo "Company ID: {$company->id}\n";
        echo "Financial Year: {$financialYear->name}\n";
        echo "Customers: " . count($createdCustomers) . "\n";
        echo "Sales Orders: $soCount\n";
        echo "\nNow test with:\n";
        echo "- Dashboard: http://localhost:8000/dashboard\n";
        echo "- Sales Orders: http://localhost:8000/sales-orders\n";
        echo "- Click 'Confirm' on Draft SO to see accounting entries\n";
        echo "========================================\n";
    }
}