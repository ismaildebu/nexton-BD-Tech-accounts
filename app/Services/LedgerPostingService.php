<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\LedgerPostingException;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class LedgerPostingService
{
    /**
     * Post an approved transaction to the ledger.
     *
     * ✅ FIX #3: Added pessimistic locking with lockForUpdate()
     * Prevents race condition where two concurrent requests both
     * read is_posted() = false and both proceed to create ledger entries.
     *
     * @throws LedgerPostingException
     */
    public function post(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            // ========================================================
            // Acquire lock to prevent concurrent posting
            // ========================================================
            $transaction = Transaction::query()
                ->where('id', $transaction->id)
                ->lockForUpdate()
                ->first();

            if ($transaction === null) {
                throw new LedgerPostingException(
                    "Transaction could not be locked for posting. It may have been deleted."
                );
            }

            if ($transaction->isPosted()) {
                throw new LedgerPostingException(
                    "Transaction #{$transaction->id} is already posted."
                );
            }

            if ($transaction->isCancelled()) {
                throw new LedgerPostingException(
                    "Cancelled transaction #{$transaction->id} cannot be posted."
                );
            }

            if (! $transaction->isApproved()) {
                throw new LedgerPostingException(
                    "Transaction #{$transaction->id} must be approved before it can be posted."
                );
            }

            // ========================================================
            // ✅ BONUS FIX #4: Verify financial year is not closed
            // ========================================================
            if ($transaction->financialYear && $transaction->financialYear->is_closed) {
                throw new LedgerPostingException(
                    "Transaction #{$transaction->id} cannot be posted. The financial year is closed."
                );
            }

            $transaction->loadMissing('details');

            if ($transaction->details->isEmpty()) {
                throw new LedgerPostingException(
                    "Transaction #{$transaction->id} has no detail lines to post."
                );
            }

            if (! $transaction->is_balanced) {
                throw new LedgerPostingException(
                    "Transaction #{$transaction->id} is not balanced."
                );
            }

            $this->preventDuplicateLedgerEntries($transaction);

            $this->generateLedgerEntries($transaction);

            $transaction->update([
                'status'    => Transaction::STATUS_POSTED,
                'posted_by' => Auth::id(),
                'posted_at' => Carbon::now(),
            ]);
        });
    }

    /**
     * Cancel a transaction and reverse its active ledger entries.
     *
     * The original ledger entries are marked as reversed.
     * New reversal entries remain active.
     *
     * ✅ FIX #3: Added pessimistic locking with lockForUpdate()
     * Prevents concurrent cancellation of the same transaction.
     *
     * @throws LedgerPostingException
     */
    public function cancel(Transaction $transaction, string $reason): void
    {
        DB::transaction(function () use ($transaction, $reason): void {
            // ========================================================
            // Acquire lock to prevent concurrent cancellation
            // ========================================================
            $transaction = Transaction::query()
                ->where('id', $transaction->id)
                ->lockForUpdate()
                ->first();

            if ($transaction === null) {
                throw new LedgerPostingException(
                    "Transaction could not be locked for cancellation. It may have been deleted."
                );
            }

            if ($transaction->isCancelled()) {
                throw new LedgerPostingException(
                    "Transaction #{$transaction->id} is already cancelled."
                );
            }

            if ($transaction->isDraft()) {
                throw new LedgerPostingException(
                    "Draft transaction #{$transaction->id} cannot be cancelled via reversal. Use delete instead."
                );
            }

            $this->reverseLedgerEntries($transaction);

            $transaction->update([
                'status'              => Transaction::STATUS_CANCELLED,
                'cancelled_by'        => Auth::id(),
                'cancelled_at'        => Carbon::now(),
                'cancellation_reason' => $reason,
            ]);
        });
    }

    // ---------------------------------------------------------------
    // Private: Ledger Generation
    // ---------------------------------------------------------------

    /**
     * Generate active ledger entries from transaction detail lines.
     *
     * @throws LedgerPostingException
     */
    private function generateLedgerEntries(Transaction $transaction): void
    {
        $entryDate = $transaction->voucher_date
            ? Carbon::parse($transaction->voucher_date)->toDateString()
            : Carbon::now()->toDateString();

        $now = Carbon::now();

        $entries = [];

        foreach ($transaction->details as $detail) {
            if ((int) $detail->transaction_id !== (int) $transaction->id) {
                throw new LedgerPostingException(
                    "Transaction detail #{$detail->id} does not belong to transaction #{$transaction->id}."
                );
            }

            $entries[] = [
                'transaction_id'    => $transaction->id,
                'company_id'        => $transaction->company_id,
                'financial_year_id' => $transaction->financial_year_id,
                'voucher_type_id'   => $transaction->voucher_type_id,
                'account_id'        => $detail->account_id,
                'voucher_number'    => $transaction->voucher_number,
                'voucher_date'      => $entryDate,
                'entry_date'        => $entryDate,
                'description'       => $detail->description
                    ?? $transaction->narration
                    ?? null,
                'debit_amount'      => $detail->debit_amount,
                'credit_amount'     => $detail->credit_amount,

                // Newly posted ledger entries are active.
                'is_reversed'       => false,

                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        LedgerEntry::insert($entries);
    }

    /**
     * Reverse only the active ledger entries belonging to this transaction
     * and company.
     *
     * Original entries become reversed.
     * Reversal entries remain active so that the cancellation is reflected
     * in the effective ledger balance.
     */
    private function reverseLedgerEntries(Transaction $transaction): void
    {
        $existingEntries = LedgerEntry::query()
            ->where('transaction_id', $transaction->id)
            ->where('company_id', $transaction->company_id)
            ->where('is_reversed', false)
            ->lockForUpdate()
            ->get();

        if ($existingEntries->isEmpty()) {
            throw new LedgerPostingException(
                "No active ledger entries found for transaction #{$transaction->id}."
            );
        }

        $now = Carbon::now();

        $reversals = [];

        foreach ($existingEntries as $entry) {
            $reversals[] = [
                'transaction_id'    => $transaction->id,
                'company_id'        => $entry->company_id,
                'financial_year_id' => $entry->financial_year_id,
                'voucher_type_id'   => $entry->voucher_type_id,
                'account_id'        => $entry->account_id,
                'voucher_number'    => $transaction->voucher_number,
                'voucher_date'      => $now->toDateString(),
                'entry_date'        => $now->toDateString(),
                'description'       => 'Reversal: '
                    . ($entry->description ?? $transaction->narration ?? ''),

                // Reverse debit/credit amounts.
                'debit_amount'      => $entry->credit_amount,
                'credit_amount'     => $entry->debit_amount,

                /*
                 * IMPORTANT:
                 *
                 * The reversal entry itself remains active.
                 * The original entry is marked as reversed below.
                 */
                'is_reversed'       => false,

                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        /*
         * Create reversal entries first.
         */
        LedgerEntry::insert($reversals);

        /*
         * Mark only the original active entries as reversed.
         */
        LedgerEntry::query()
            ->whereIn('id', $existingEntries->pluck('id'))
            ->where('transaction_id', $transaction->id)
            ->where('company_id', $transaction->company_id)
            ->where('is_reversed', false)
            ->update([
                'is_reversed' => true,
                'updated_at'  => $now,
            ]);
    }

    /**
     * Prevent duplicate active ledger entries for THIS transaction only.
     *
     * IMPORTANT:
     * This must never inspect unrelated transactions.
     *
     * @throws LedgerPostingException
     */
    private function preventDuplicateLedgerEntries(Transaction $transaction): void
    {
        $exists = LedgerEntry::query()
            ->where('transaction_id', $transaction->id)
            ->where('is_reversed', false)
            ->exists();

        if ($exists) {
            throw new LedgerPostingException(
                "Transaction #{$transaction->id} already has active ledger entries. Duplicate posting prevented."
            );
        }
    }
}