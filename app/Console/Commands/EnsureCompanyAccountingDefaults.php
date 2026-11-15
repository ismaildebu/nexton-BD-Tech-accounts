<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Company;
use App\Models\VoucherType;
use App\Services\CompanyAccountingDefaults;
use Illuminate\Console\Command;

/**
 * Backfills the default accounts and voucher types for companies that were
 * created before a default (for example "Purchase Clearing") existed.
 *
 * CompanyAccountingDefaults is idempotent: it only creates what is missing
 * and never changes an existing account or voucher type.
 */
class EnsureCompanyAccountingDefaults extends Command
{
    protected $signature = 'accounting:ensure-defaults
                            {--company= : Only this company id (default: all companies)}';

    protected $description = 'Create missing default accounts and voucher types for existing companies';

    public function handle(CompanyAccountingDefaults $defaults): int
    {
        $query = Company::query()->orderBy('id');

        if ($this->option('company') !== null) {
            $query->whereKey((int) $this->option('company'));
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->error('No company found.');

            return self::FAILURE;
        }

        foreach ($companies as $company) {
            $before = $this->countDefaults((int) $company->id);

            $defaults->ensure($company);

            $after = $this->countDefaults((int) $company->id);

            $this->line(sprintf(
                'Company #%d: +%d account(s), +%d voucher type(s)',
                $company->id,
                $after['accounts'] - $before['accounts'],
                $after['voucher_types'] - $before['voucher_types'],
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return array{accounts: int, voucher_types: int}
     */
    private function countDefaults(int $companyId): array
    {
        return [
            'accounts' => Account::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->count(),
            'voucher_types' => VoucherType::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->count(),
        ];
    }
}
