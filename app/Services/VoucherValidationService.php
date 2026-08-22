<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\VoucherValidationException;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\VoucherType;

final class VoucherValidationService
{
    public function __construct(
        private readonly VoucherNumberService $voucherNumberService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @throws VoucherValidationException
     */
    public function validate(array $data, ?int $excludeTransactionId = null): void
    {
        $this->validateFinancialYear(
            (int) $data['company_id'],
            (int) $data['financial_year_id'],
            (string) $data['voucher_date']
        );

        $this->validateVoucherType(
            (int) $data['company_id'],
            (int) $data['voucher_type_id']
        );

        $this->validateLines(
            $data['details'] ?? [],
            (int) $data['company_id']
        );

        $this->validateBalance($data['details'] ?? []);

        $this->validateVoucherNumberUniqueness(
            (int) $data['company_id'],
            (int) $data['financial_year_id'],
            $data['voucher_number'] ?? null,
            $excludeTransactionId
        );
    }

    // ---------------------------------------------------------------
    // Financial Year
    // ---------------------------------------------------------------

    private function validateFinancialYear(int $companyId, int $financialYearId, string $voucherDate): void
    {
        $financialYear = FinancialYear::query()
            ->where('id', $financialYearId)
            ->where('company_id', $companyId)
            ->first();

        if ($financialYear === null) {
            throw new VoucherValidationException(
                'Financial year does not belong to this company.'
            );
        }

        if (! $financialYear->is_active) {
            throw new VoucherValidationException(
                'The selected financial year is not active.'
            );
        }

        if (isset($financialYear->is_closed) && $financialYear->is_closed) {
            throw new VoucherValidationException(
                'The selected financial year is closed. No vouchers can be posted.'
            );
        }

        $startDate = $financialYear->start_date ?? ($financialYear->from_date ?? null);
            $endDate   = $financialYear->end_date   ?? ($financialYear->to_date   ?? null);

            if ($startDate && $endDate) {
                $voucherDateFormatted = date('Y-m-d', strtotime($voucherDate));
                $startDateFormatted   = date('Y-m-d', strtotime((string) $startDate));
                $endDateFormatted     = date('Y-m-d', strtotime((string) $endDate));

                if ($voucherDateFormatted < $startDateFormatted || $voucherDateFormatted > $endDateFormatted) {
                    throw new VoucherValidationException(
                        "Voucher date must be within the financial year period ({$startDateFormatted} to {$endDateFormatted})."
                    );
                }
            }
    }

    // ---------------------------------------------------------------
    // Voucher Type
    // ---------------------------------------------------------------

    private function validateVoucherType(int $companyId, int $voucherTypeId): void
    {
        $voucherType = VoucherType::query()
            ->where('id', $voucherTypeId)
            ->where('company_id', $companyId)
            ->first();

        if ($voucherType === null) {
            throw new VoucherValidationException(
                'Voucher type does not belong to this company.'
            );
        }

        if (! $voucherType->is_active) {
            throw new VoucherValidationException(
                'The selected voucher type is inactive.'
            );
        }
    }

    // ---------------------------------------------------------------
    // Lines
    // ---------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $details
     */
    private function validateLines(array $details, int $companyId): void
    {
        $validLines = array_filter(
            $details,
            static fn (array $line): bool => ! empty($line['account_id'])
        );

        if (count($validLines) < 2) {
            throw new VoucherValidationException(
                'A voucher must contain at least two ledger lines.'
            );
        }

        foreach ($validLines as $index => $line) {
            $rowNumber = $index + 1;

            $debit  = (float) ($line['debit_amount']  ?? 0);
            $credit = (float) ($line['credit_amount'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw new VoucherValidationException(
                    "Row {$rowNumber}: Debit and Credit amounts cannot be negative."
                );
            }

            if ($debit === 0.0 && $credit === 0.0) {
                throw new VoucherValidationException(
                    "Row {$rowNumber}: Amount cannot be zero."
                );
            }

            if ($debit > 0.0 && $credit > 0.0) {
                throw new VoucherValidationException(
                    "Row {$rowNumber}: A single ledger line cannot contain both Debit and Credit amounts."
                );
            }

            $this->validateAccount((int) $line['account_id'], $companyId, $rowNumber);
        }
    }

    private function validateAccount(int $accountId, int $companyId, int $rowNumber): void
    {
        $account = Account::query()
            ->where('id', $accountId)
            ->where('company_id', $companyId)
            ->first();

        if ($account === null) {
            throw new VoucherValidationException(
                "Row {$rowNumber}: Account does not belong to this company."
            );
        }

        $isActive = $account->is_active ?? true;

        if ($isActive === false || $isActive === 0) {
            throw new VoucherValidationException(
                "Row {$rowNumber}: Account '{$account->account_name}' is inactive."
            );
        }
    }

    // ---------------------------------------------------------------
    // Balance
    // ---------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $details
     */
    private function validateBalance(array $details): void
    {
        $totalDebit  = '0';
        $totalCredit = '0';

        foreach ($details as $line) {
            if (empty($line['account_id'])) {
                continue;
            }
            $totalDebit  = bcadd($totalDebit,  (string) ($line['debit_amount']  ?? 0), 4);
            $totalCredit = bcadd($totalCredit, (string) ($line['credit_amount'] ?? 0), 4);
        }

        if (bccomp($totalDebit, '0', 4) <= 0) {
            throw new VoucherValidationException(
                'Total debit amount must be greater than zero.'
            );
        }

        if (bccomp($totalDebit, $totalCredit, 4) !== 0) {
            $diff = bcsub($totalDebit, $totalCredit, 4);
            throw new VoucherValidationException(
                "Voucher is not balanced. Difference: {$diff}."
            );
        }
    }

    // ---------------------------------------------------------------
    // Voucher Number Uniqueness
    // ---------------------------------------------------------------

    private function validateVoucherNumberUniqueness(
        int     $companyId,
        int     $financialYearId,
        ?string $voucherNumber,
        ?int    $excludeTransactionId
    ): void {
        if ($voucherNumber === null || $voucherNumber === '') {
            return;
        }

        if (! $this->voucherNumberService->isUnique(
            $companyId,
            $financialYearId,
            $voucherNumber,
            $excludeTransactionId
        )) {
            throw new VoucherValidationException(
                "Voucher number '{$voucherNumber}' already exists for this company and financial year."
            );
        }
    }
}