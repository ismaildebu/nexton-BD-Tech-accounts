<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VoucherType;
use App\Models\Company;

class VoucherTypeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            return;
        }

        $voucherTypes = [
            [
                'voucher_name' => 'Journal Voucher',
                'voucher_code' => 'JV',
            ],
            [
                'voucher_name' => 'Payment Voucher',
                'voucher_code' => 'PV',
            ],
            [
                'voucher_name' => 'Receipt Voucher',
                'voucher_code' => 'RV',
            ],
            [
                'voucher_name' => 'Contra Voucher',
                'voucher_code' => 'CV',
            ],
            [
                'voucher_name' => 'Sales Voucher',
                'voucher_code' => 'SV',
            ],
            [
                'voucher_name' => 'Purchase Voucher',
                'voucher_code' => 'PUR',
            ],
        ];

        foreach ($voucherTypes as $voucher) {

            VoucherType::create([
                'company_id' => $company->id,
                'voucher_name' => $voucher['voucher_name'],
                'voucher_code' => $voucher['voucher_code'],
                'is_active' => true,
            ]);

        }
    }
}