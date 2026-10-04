<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\VoucherType;
use App\Services\CompanyAccountingDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'company_name' => 'Defaults Test Company',
        'business_type' => 'Trading',
    ]);

    $this->defaults = new CompanyAccountingDefaults();

    $this->accountNames = function (int $companyId): array {
        return Account::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->pluck('account_name')
            ->sort()
            ->values()
            ->all();
    };

    $this->voucherCodes = function (int $companyId): array {
        return VoucherType::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->pluck('code')
            ->sort()
            ->values()
            ->all();
    };
});

describe('CompanyAccountingDefaults', function () {
    it('creates the required accounts and voucher types for a new company', function () {
        $this->defaults->ensure($this->company);

        expect(($this->accountNames)($this->company->id))->toBe([
            'Sales Discount',
            'Sales Revenue',
            'VAT Payable',
        ]);

        expect(($this->voucherCodes)($this->company->id))->toBe([
            'CV', 'JV', 'OV', 'PUV', 'PV', 'RV', 'SV',
        ]);
    });

    it('is idempotent', function () {
        $this->defaults->ensure($this->company);
        $this->defaults->ensure($this->company);

        expect(($this->accountNames)($this->company->id))->toHaveCount(3);
        expect(($this->voucherCodes)($this->company->id))->toHaveCount(7);
    });

    it('reuses an existing account even when it has a different code', function () {
        Account::create([
            'company_id' => $this->company->id,
            'account_code' => '4001',
            'account_name' => 'Sales Revenue',
            'account_type' => 'Income',
            'nature' => 'Income',
            'balance_type' => 'Credit',
            'opening_balance' => '0.0000',
            'is_active' => true,
        ]);

        $this->defaults->ensure($this->company);

        $revenueAccounts = Account::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('account_name', 'Sales Revenue')
            ->get();

        expect($revenueAccounts)->toHaveCount(1);
        expect($revenueAccounts->first()->account_code)->toBe('4001');
    });

    it('never reuses an account code that is already taken', function () {
        Account::create([
            'company_id' => $this->company->id,
            'account_code' => '5001',
            'account_name' => 'Salary Expense',
            'account_type' => 'Expense',
            'nature' => 'Expense',
            'balance_type' => 'Debit',
            'opening_balance' => '0.0000',
            'is_active' => true,
        ]);

        $this->defaults->ensure($this->company);

        $salary = Account::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('account_name', 'Salary Expense')
            ->first();

        $revenue = Account::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('account_name', 'Sales Revenue')
            ->first();

        expect($salary->account_code)->toBe('5001');
        expect($revenue)->not->toBeNull();
        expect($revenue->account_code)->not->toBe('5001');
    });

    it('creates a new active account when the only existing one was deleted', function () {
        $deleted = Account::create([
            'company_id' => $this->company->id,
            'account_code' => '5001',
            'account_name' => 'Sales Revenue',
            'account_type' => 'Income',
            'nature' => 'Income',
            'balance_type' => 'Credit',
            'opening_balance' => '0.0000',
            'is_active' => true,
        ]);

        $deleted->delete();

        $this->defaults->ensure($this->company);

        $active = Account::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('account_name', 'Sales Revenue')
            ->whereNull('deleted_at')
            ->get();

        expect($active)->toHaveCount(1);
        expect($active->first()->id)->not->toBe($deleted->id);
    });

    it('does not touch another company', function () {
        $other = Company::create([
            'company_name' => 'Other Defaults Company',
            'business_type' => 'Media',
        ]);

        $this->defaults->ensure($this->company);

        expect(($this->accountNames)($other->id))->toBe([]);
        expect(($this->voucherCodes)($other->id))->toBe([]);
    });
});