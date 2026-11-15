<?php

use App\Models\Company;
use App\Models\VoucherType;
use App\Services\VoucherNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'company_name' => 'Voucher Prefix Test Company',
        'business_type' => 'Trading',
    ]);

    $this->makeType = function (
        string $code,
        string $nature,
        ?string $prefix = null
    ): VoucherType {
        return VoucherType::create([
            'company_id' => $this->company->id,
            'name' => $code . ' Voucher',
            'code' => $code,
            'nature' => $nature,
            'prefix' => $prefix,
            'last_number' => 0,
            'description' => $code . ' voucher',
            'is_active' => true,
            'status' => 1,
        ]);
    };

    $this->nextNumber = function (VoucherType $type): string {
        return DB::transaction(
            fn () => app(VoucherNumberService::class)->generate(
                (int) $this->company->id,
                1,
                (int) $type->id,
                2026
            )
        );
    };
});

describe('VoucherNumberService prefix', function () {
    it('keeps Journal, Sales and Purchase vouchers apart even though they share a nature', function () {
        $journal = ($this->makeType)('JV', 'journal');
        $sales = ($this->makeType)('SV', 'journal');
        $purchase = ($this->makeType)('PUV', 'journal');

        expect(($this->nextNumber)($journal))->toBe('JV-2026-000001');
        expect(($this->nextNumber)($sales))->toBe('SV-2026-000001');
        expect(($this->nextNumber)($purchase))->toBe('PUV-2026-000001');
    });

    it('never produces the same number for two different voucher types', function () {
        $journal = ($this->makeType)('JV', 'journal');
        $sales = ($this->makeType)('SV', 'journal');

        $numbers = [
            ($this->nextNumber)($journal),
            ($this->nextNumber)($sales),
            ($this->nextNumber)($journal),
            ($this->nextNumber)($sales),
        ];

        expect(array_unique($numbers))->toHaveCount(4);
    });

    it('uses an explicit prefix when the type has one', function () {
        $type = ($this->makeType)('SV', 'journal', 'sal');

        expect(($this->nextNumber)($type))->toBe('SAL-2026-000001');
    });

    it('continues the counter of each type independently', function () {
        $payment = ($this->makeType)('PV', 'payment');

        expect(($this->nextNumber)($payment))->toBe('PV-2026-000001');
        expect(($this->nextNumber)($payment))->toBe('PV-2026-000002');
    });

    it('falls back to the nature only when the type has no code', function () {
        $type = ($this->makeType)('', 'receipt');

        expect(($this->nextNumber)($type))->toBe('RV-2026-000001');
    });
});