<?php

declare(strict_types=1);

namespace Tests\Feature\Media\Concerns;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\MediaParty;
use App\Models\Publication;
use App\Models\User;
use App\Models\VoucherType;
use Database\Seeders\RoleAndPermissionSeeder;

/**
 * Shared setup for Media module tests.
 *
 * Media accounting is mandatory in production, so the test fixture creates
 * an active financial year and voucher types and automatically assigns test
 * AR/sales accounts to publications and media parties.
 */
trait CreatesMediaCompany
{
    protected function makeMediaCompany(array $attributes = []): Company
    {
        $this->registerMediaAccountingFixtures();

        $company = Company::create([
            'company_name' => 'Test Media House',
            'business_type' => 'Media',
            'status' => true,
            ...$attributes,
        ]);

        FinancialYear::create([
            'company_id' => $company->id,
            'year_name' => 'FY ' . now()->year . '-' . now()->addYear()->year,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
            'is_closed' => false,
        ]);

        VoucherType::create([
            'company_id' => $company->id,
            'name' => 'Journal Voucher',
            'code' => 'JV',
            'nature' => VoucherType::NATURE_JOURNAL,
            'prefix' => 'JV',
            'last_number' => 0,
            'is_active' => true,
            'status' => true,
            'description' => 'Media journal vouchers',
        ]);

        VoucherType::create([
            'company_id' => $company->id,
            'name' => 'Receipt Voucher',
            'code' => 'RV',
            'nature' => VoucherType::NATURE_RECEIPT,
            'prefix' => 'RV',
            'last_number' => 0,
            'is_active' => true,
            'status' => true,
            'description' => 'Media receipt vouchers',
        ]);

        return $company;
    }

    
    private function registerMediaAccountingFixtures(): void
        {
            Publication::created(static function (Publication $publication): void {
                $companyId = (int) $publication->company_id;
                $dirty = [];

                if (! $publication->sales_account_id) {
                    $dirty['sales_account_id'] = self::createFixtureAccount(
                        $companyId,
                        'Media Sales Revenue',
                        Account::TYPE_INCOME,
                        Account::NATURE_INCOME,
                        Account::BALANCE_CREDIT,
                    )->id;
                }

                if (! $publication->sales_return_account_id) {
                    $dirty['sales_return_account_id'] = self::createFixtureAccount(
                        $companyId,
                        'Media Sales Return',
                        Account::TYPE_INCOME,
                        Account::NATURE_INCOME,
                        Account::BALANCE_DEBIT,
                    )->id;
                }

                if ($dirty !== []) {
                    $publication->forceFill($dirty)->saveQuietly();
                }
            });

            MediaParty::created(static function (MediaParty $party): void {
                if (! $party->account_id) {
                    $companyId = (int) $party->company_id;
                    $accountId = self::createFixtureAccount(
                        $companyId,
                        'Media Party AR',
                        Account::TYPE_ASSET,
                        Account::NATURE_CUSTOMER,
                        Account::BALANCE_DEBIT,
                    )->id;

                    $party->forceFill(['account_id' => $accountId])->saveQuietly();
                }
            });
        }

    private static function createFixtureAccount(
        int $companyId,
        string $name,
        string $type,
        string $nature,
        string $balanceType,
    ): Account {
        return Account::create([
            'company_id' => $companyId,
            'account_code' => Account::generateNextCode($type, $companyId),
            'account_name' => $name,
            'account_type' => $type,
            'nature' => $nature,
            'level' => 1,
            'is_system' => false,
            'is_active' => true,
            'opening_balance' => 0,
            'balance_type' => $balanceType,
        ]);
    }

    protected function makeMediaAdmin(?Company $company = null): User
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $company ??= $this->makeMediaCompany();

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('admin');

        return $user;
    }
}
