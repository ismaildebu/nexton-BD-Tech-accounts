<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Business rules for financial years.
 *
 * Every rule violation is a DomainException whose message is safe to show.
 */
class FinancialYearService
{
    /**
     * @param  array{year_name: string, start_date: string, end_date: string, is_active?: bool}  $data
     */
    public function create(int $companyId, array $data): FinancialYear
    {
        $this->assertNoOverlap($companyId, $data['start_date'], $data['end_date']);

        return FinancialYear::query()->create([
            'company_id' => $companyId,
            'year_name' => $data['year_name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            // A new year is always open; closing is an explicit action.
            'is_closed' => false,
        ]);
    }

    /**
     * @param  array{year_name: string, start_date: string, end_date: string, is_active?: bool}  $data
     */
    public function update(FinancialYear $financialYear, array $data): FinancialYear
    {
        return DB::transaction(function () use ($financialYear, $data): FinancialYear {
            $year = $this->lock($financialYear);

            if ($year->is_closed) {
                throw new DomainException(
                    'A closed financial year cannot be modified. Reopen it first.'
                );
            }

            $datesChanged = $year->start_date->toDateString() !== Carbon::parse($data['start_date'])->toDateString()
                || $year->end_date->toDateString() !== Carbon::parse($data['end_date'])->toDateString();

            if ($datesChanged) {
                if ($this->hasPostings($year)) {
                    throw new DomainException(
                        'The dates cannot be changed because vouchers already exist in this financial year.'
                    );
                }

                $this->assertNoOverlap(
                    (int) $year->company_id,
                    $data['start_date'],
                    $data['end_date'],
                    (int) $year->id
                );
            }

            $year->update([
                'year_name' => $data['year_name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            return $year;
        });
    }

    public function delete(FinancialYear $financialYear): void
    {
        DB::transaction(function () use ($financialYear): void {
            $year = $this->lock($financialYear);

            if ($this->hasPostings($year)) {
                throw new DomainException(
                    'This financial year cannot be deleted because vouchers exist in it.'
                );
            }

            $year->delete();
        });
    }

    /**
     * Close a year. Only possible when every voucher in it is Posted or
     * Cancelled, so nothing is left half-finished behind a closed period.
     */
    public function close(FinancialYear $financialYear, int $userId): FinancialYear
    {
        return DB::transaction(function () use ($financialYear, $userId): FinancialYear {
            $year = $this->lock($financialYear);

            if ($year->is_closed) {
                throw new DomainException('This financial year is already closed.');
            }

            $unfinished = Transaction::query()
                ->withoutGlobalScopes()
                ->where('company_id', $year->company_id)
                ->where('financial_year_id', $year->id)
                ->whereIn('status', [
                    Transaction::STATUS_DRAFT,
                    Transaction::STATUS_SUBMITTED,
                    Transaction::STATUS_APPROVED,
                ])
                ->count();

            if ($unfinished > 0) {
                throw new DomainException(
                    "The financial year cannot be closed: {$unfinished} voucher(s) are still "
                    . 'Draft, Submitted or Approved. Post or cancel them first.'
                );
            }

            $year->update([
                'is_closed' => true,
                'closed_at' => now(),
                'closed_by' => $userId,
            ]);

            Log::info('Financial year closed.', [
                'financial_year_id' => $year->id,
                'company_id' => $year->company_id,
                'closed_by' => $userId,
            ]);

            return $year;
        });
    }

    /**
     * Reopen a closed year. Always requires a written reason, which is
     * stored on the year and written to the log.
     */
    public function reopen(FinancialYear $financialYear, int $userId, string $reason): FinancialYear
    {
        return DB::transaction(function () use ($financialYear, $userId, $reason): FinancialYear {
            $year = $this->lock($financialYear);

            if (! $year->is_closed) {
                throw new DomainException('This financial year is not closed.');
            }

            $year->update([
                'is_closed' => false,
                'closed_at' => null,
                'closed_by' => null,
                'reopened_at' => now(),
                'reopened_by' => $userId,
                'reopen_reason' => $reason,
            ]);

            Log::warning('Financial year reopened.', [
                'financial_year_id' => $year->id,
                'company_id' => $year->company_id,
                'reopened_by' => $userId,
                'reason' => $reason,
            ]);

            return $year;
        });
    }

    /**
     * A year "has postings" as soon as any voucher or ledger entry points to
     * it, whatever its status.
     */
    public function hasPostings(FinancialYear $financialYear): bool
    {
        return Transaction::query()
            ->withoutGlobalScopes()
            ->where('company_id', $financialYear->company_id)
            ->where('financial_year_id', $financialYear->id)
            ->exists()
            || LedgerEntry::query()
                ->withoutGlobalScopes()
                ->where('company_id', $financialYear->company_id)
                ->where('financial_year_id', $financialYear->id)
                ->exists();
    }

    public function assertNoOverlap(int $companyId, string $startDate, string $endDate, ?int $ignoreId = null): void
    {
        $overlap = FinancialYear::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->first();

        if ($overlap !== null) {
            throw new DomainException(
                "The dates overlap with the financial year \"{$overlap->year_name}\"."
            );
        }
    }

    private function lock(FinancialYear $financialYear): FinancialYear
    {
        return FinancialYear::query()
            ->withoutGlobalScopes()
            ->where('company_id', $financialYear->company_id)
            ->lockForUpdate()
            ->findOrFail($financialYear->id);
    }
}
