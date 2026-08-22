<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\VoucherType;
use Illuminate\Support\Facades\DB;

final class VoucherNumberService
{
    /**
     * Generate and reserve the next voucher number for a given
     * company + financial year + voucher type combination.
     *
     * Must be called inside an existing DB transaction.
     */
    public function generate(
        int $companyId,
        int $financialYearId,
        int $voucherTypeId,
        int $year
    ): string {
        /** @var VoucherType $voucherType */
        $voucherType = VoucherType::query()
            ->where('id', $voucherTypeId)
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->firstOrFail();

        $voucherType->increment('last_number');
        $voucherType->refresh();

        $prefix = $this->resolvePrefix($voucherType);
        $number = str_pad((string) $voucherType->last_number, 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$number}";
    }

    /**
     * Check uniqueness within company + financial year scope.
     */
    public function isUnique(
        int    $companyId,
        int    $financialYearId,
        string $voucherNumber,
        ?int   $excludeTransactionId = null
    ): bool {
        $query = DB::table('transactions')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('voucher_number', $voucherNumber);

        if ($excludeTransactionId !== null) {
            $query->where('id', '!=', $excludeTransactionId);
        }

        return ! $query->exists();
    }

    private function resolvePrefix(VoucherType $voucherType): string
    {
        if (filled($voucherType->prefix)) {
            return strtoupper($voucherType->prefix);
        }

        return match ($voucherType->nature) {
            'journal'  => 'JV',
            'payment'  => 'PV',
            'receipt'  => 'RV',
            'contra'   => 'CV',
            'opening'  => 'OV',
            default    => strtoupper(substr($voucherType->code, 0, 2)),
        };
    }
}