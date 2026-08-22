<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\VoucherValidationException;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class VoucherService
{
    public function __construct(
        private readonly VoucherValidationService $validationService,
        private readonly VoucherNumberService $numberService,
        private readonly LedgerPostingService $ledgerService,
    ) {}

    // ---------------------------------------------------------------
    // Create Draft
    // ---------------------------------------------------------------

    /**
     * Create a new voucher in Draft status.
     *
     * Draft vouchers never affect the Ledger.
     *
     * @param array<string, mixed> $data
     */
    public function createDraft(array $data): Transaction
    {
        return DB::transaction(function () use ($data): Transaction {
            $this->validationService->validate($data);

            $voucherNumber = $this->numberService->generate(
                companyId: (int) $data['company_id'],
                financialYearId: (int) $data['financial_year_id'],
                voucherTypeId: (int) $data['voucher_type_id'],
                year: (int) Carbon::parse($data['voucher_date'])->format('Y'),
            );

            [$totalDebit, $totalCredit] = $this->calculateTotals(
                $data['details']
            );

            $transaction = Transaction::create([
                'company_id'        => $data['company_id'],
                'financial_year_id' => $data['financial_year_id'],
                'voucher_type_id'   => $data['voucher_type_id'],
                'created_by'        => Auth::id(),
                'voucher_number'    => $voucherNumber,
                'voucher_date'      => $data['voucher_date'],
                'reference_number'  => $data['reference_number'] ?? null,
                'narration'         => $data['narration'] ?? null,
                'total_debit'       => $totalDebit,
                'total_credit'      => $totalCredit,
                'status'            => Transaction::STATUS_DRAFT,

                // Approval fields
                'approved_by'       => null,
                'approved_at'       => null,
                'approval_note'     => null,
            ]);

            $this->syncDetails(
                $transaction,
                $data['details']
            );

            return $transaction->fresh([
                'details',
                'voucherType',
                'company',
                'financialYear',
            ]);
        });
    }

    // ---------------------------------------------------------------
    // Update Draft
    // ---------------------------------------------------------------

    /**
     * Update a Draft voucher.
     *
     * @param array<string, mixed> $data
     *
     * @throws VoucherValidationException
     */
    public function updateDraft(
        Transaction $transaction,
        array $data
    ): Transaction {
        if (! $transaction->isDraft()) {
            throw new VoucherValidationException(
                'Only draft vouchers can be updated.'
            );
        }

        return DB::transaction(function () use (
            $transaction,
            $data
        ): Transaction {
            $this->validationService->validate(
                $data,
                $transaction->id
            );

            [$totalDebit, $totalCredit] = $this->calculateTotals(
                $data['details']
            );

            $transaction->update([
                'financial_year_id' => $data['financial_year_id'],
                'voucher_type_id'   => $data['voucher_type_id'],
                'voucher_date'      => $data['voucher_date'],
                'reference_number'  => $data['reference_number'] ?? null,
                'narration'         => $data['narration'] ?? null,
                'total_debit'       => $totalDebit,
                'total_credit'      => $totalCredit,

                // A Draft has no approval.
                'approved_by'       => null,
                'approved_at'       => null,
                'approval_note'     => null,
            ]);

            $this->syncDetails(
                $transaction,
                $data['details']
            );

            return $transaction->fresh([
                'details',
                'voucherType',
                'company',
                'financialYear',
            ]);
        });
    }

    // ---------------------------------------------------------------
    // Submit for Approval
    // ---------------------------------------------------------------

    /**
     * Submit a Draft voucher for approval.
     *
     * Draft -> Submitted
     *
     * No Ledger posting occurs here.
     *
     * @throws VoucherValidationException
     */
    public function submitForApproval(
        Transaction $transaction
    ): Transaction {
        if (! $transaction->isDraft()) {
            throw new VoucherValidationException(
                'Only draft vouchers can be submitted for approval.'
            );
        }

        if (! $transaction->isBalanced) {
            throw new VoucherValidationException(
                'Voucher debit and credit totals must be equal before submission.'
            );
        }

        if ($transaction->details()->count() === 0) {
            throw new VoucherValidationException(
                'Voucher must contain at least one transaction detail.'
            );
        }

        $transaction->update([
            'status' => Transaction::STATUS_SUBMITTED,
        ]);

        return $transaction->fresh();
    }

    // ---------------------------------------------------------------
    // Approve
    // ---------------------------------------------------------------

    /**
     * Approve a submitted voucher.
     *
     * Submitted -> Approved
     *
     * Approval does NOT post anything to the Ledger.
     *
     * @throws VoucherValidationException
     */
    public function approve(
        Transaction $transaction,
        ?string $approvalNote = null
    ): Transaction {
        if (! $transaction->isSubmitted()) {
            throw new VoucherValidationException(
                'Only submitted vouchers can be approved.'
            );
        }

        $userId = Auth::id();

        if ($userId === null) {
            throw new VoucherValidationException(
                'Authenticated user is required to approve a voucher.'
            );
        }

        // Maker-checker control:
        // The person who created the voucher cannot approve it.
        if ((int) $transaction->created_by === (int) $userId) {
            throw new VoucherValidationException(
                'The voucher creator cannot approve the same voucher.'
            );
        }

        if (! $transaction->isBalanced) {
            throw new VoucherValidationException(
                'Only a balanced voucher can be approved.'
            );
        }

        $transaction->update([
            'status'        => Transaction::STATUS_APPROVED,
            'approved_by'   => $userId,
            'approved_at'   => Carbon::now(),
            'approval_note' => $approvalNote,
        ]);

        return $transaction->fresh([
            'creator',
            'approver',
        ]);
    }

    // ---------------------------------------------------------------
    // Save & Post
    // ---------------------------------------------------------------

    /**
     * Legacy compatibility method.
     *
     * IMPORTANT:
     * A newly created voucher must NEVER be posted directly.
     *
     * It is created as Draft and then submitted for approval.
     *
     * @param array<string, mixed> $data
     *
     * @throws VoucherValidationException
     */
    public function saveAndPost(array $data): Transaction
    {
        return DB::transaction(function () use ($data): Transaction {
            $transaction = $this->createDraft($data);

            return $this->submitForApproval($transaction);
        });
    }

    // ---------------------------------------------------------------
    // Post Approved Voucher
    // ---------------------------------------------------------------

    /**
     * Post an Approved voucher to the Ledger.
     *
     * Approved -> Posted
     *
     * This is the ONLY normal path that posts a voucher
     * to the Ledger.
     *
     * @throws VoucherValidationException
     */
    public function postApproved(
        Transaction $transaction
    ): Transaction {
        if (! $transaction->isApproved()) {
            throw new VoucherValidationException(
                'Only approved vouchers can be posted to the Ledger.'
            );
        }

        return DB::transaction(function () use ($transaction): Transaction {
            // Re-fetch the transaction inside the transaction
            // to avoid using stale state.
            $transaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if (! $transaction->isApproved()) {
                throw new VoucherValidationException(
                    'Only approved vouchers can be posted to the Ledger.'
                );
            }

            if (! $transaction->isBalanced) {
                throw new VoucherValidationException(
                    'Only balanced vouchers can be posted to the Ledger.'
                );
            }

            $this->ledgerService->post($transaction);

            return $transaction->fresh([
                'details',
                'entries',
                'creator',
                'approver',
                'poster',
            ]);
        });
    }

    // ---------------------------------------------------------------
    // Deprecated Post Method
    // ---------------------------------------------------------------

    /**
     * Backward-compatible wrapper.
     *
     * Direct Draft -> Posted is intentionally prohibited.
     *
     * @throws VoucherValidationException
     */
    public function postDraft(
        Transaction $transaction
    ): Transaction {
        throw new VoucherValidationException(
            'Direct posting is disabled. The voucher must be submitted and approved before posting.'
        );
    }

    // ---------------------------------------------------------------
    // Cancel
    // ---------------------------------------------------------------

    /**
     * Cancel a voucher.
     *
     * Draft / Submitted / Approved:
     *     Status -> Cancelled
     *
     * Posted:
     *     Ledger reversal is handled by LedgerPostingService.
     *
     * @throws VoucherValidationException
     */
    public function cancel(
        Transaction $transaction,
        string $reason
    ): Transaction {
        if ($transaction->isCancelled()) {
            throw new VoucherValidationException(
                'This voucher is already cancelled.'
            );
        }

        if ($transaction->isPosted()) {
            return DB::transaction(function () use (
                $transaction,
                $reason
            ): Transaction {
                $this->ledgerService->cancel(
                    $transaction,
                    $reason
                );

                return $transaction->fresh();
            });
        }

        return DB::transaction(function () use (
            $transaction,
            $reason
        ): Transaction {
            $transaction->update([
                'status'              => Transaction::STATUS_CANCELLED,
                'cancelled_by'        => Auth::id(),
                'cancelled_at'        => Carbon::now(),
                'cancellation_reason' => $reason,
            ]);

            return $transaction->fresh();
        });
    }

    // ---------------------------------------------------------------
    // Delete Draft
    // ---------------------------------------------------------------

    /**
     * Delete a Draft voucher.
     *
     * Submitted / Approved / Posted vouchers cannot be deleted.
     *
     * @throws VoucherValidationException
     */
    public function deleteDraft(
        Transaction $transaction
    ): void {
        if (! $transaction->isDraft()) {
            throw new VoucherValidationException(
                'Only draft vouchers can be deleted.'
            );
        }

        DB::transaction(function () use ($transaction): void {
            $transaction->details()->delete();
            $transaction->delete();
        });
    }

    // ---------------------------------------------------------------
    // Private Helpers
    // ---------------------------------------------------------------

    /**
     * Calculate total debit and total credit.
     *
     * @param array<int, array<string, mixed>> $details
     *
     * @return array{0: string, 1: string}
     */
    private function calculateTotals(array $details): array
    {
        $totalDebit = '0';
        $totalCredit = '0';

        foreach ($details as $line) {
            if (empty($line['account_id'])) {
                continue;
            }

            $totalDebit = bcadd(
                $totalDebit,
                (string) ($line['debit_amount'] ?? 0),
                4
            );

            $totalCredit = bcadd(
                $totalCredit,
                (string) ($line['credit_amount'] ?? 0),
                4
            );
        }

        return [$totalDebit, $totalCredit];
    }

    /**
     * Synchronize transaction details.
     *
     * @param array<int, array<string, mixed>> $details
     */
    private function syncDetails(
        Transaction $transaction,
        array $details
    ): void {
        $transaction->details()->delete();

        $rows = [];
        $sortOrder = 0;

        foreach ($details as $line) {
            if (empty($line['account_id'])) {
                continue;
            }

            $rows[] = [
                'transaction_id' => $transaction->id,
                'account_id'     => $line['account_id'],
                'sort_order'     => ++$sortOrder,
                'description'    => $line['description'] ?? null,
                'debit_amount'   => $line['debit_amount'] ?? 0,
                'credit_amount'  => $line['credit_amount'] ?? 0,
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ];
        }

        TransactionDetail::insert($rows);
    }
}