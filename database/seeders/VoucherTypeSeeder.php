<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\VoucherType;
use Illuminate\Database\Seeder;

class VoucherTypeSeeder extends Seeder
{
    private array $defaultTypes = [
        [
            'name'        => 'Journal Voucher',
            'code'        => 'JV',
            'nature'      => 'journal',
            'prefix'      => 'JV',
            'description' => 'General journal entries for adjustments and transfers.',
        ],
        [
            'name'        => 'Payment Voucher',
            'code'        => 'PV',
            'nature'      => 'payment',
            'prefix'      => 'PV',
            'description' => 'Cash or bank payment transactions.',
        ],
        [
            'name'        => 'Receipt Voucher',
            'code'        => 'RV',
            'nature'      => 'receipt',
            'prefix'      => 'RV',
            'description' => 'Cash or bank receipt transactions.',
        ],
        [
            'name'        => 'Contra Voucher',
            'code'        => 'CV',
            'nature'      => 'contra',
            'prefix'      => 'CV',
            'description' => 'Fund transfers between cash and bank accounts.',
        ],
        [
            'name'        => 'Opening Voucher',
            'code'        => 'OV',
            'nature'      => 'opening',
            'prefix'      => 'OV',
            'description' => 'Opening balance entries for a new financial year.',
        ],
    ];

    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            foreach ($this->defaultTypes as $type) {
                VoucherType::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'code'       => $type['code'],
                    ],
                    [
                        'name'        => $type['name'],
                        'nature'      => $type['nature'],
                        'prefix'      => $type['prefix'],
                        'last_number' => 0,
                        'is_active'   => true,
                        'description' => $type['description'],
                    ]
                );
            }
        }
    }
}