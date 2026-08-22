<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\LedgerPostingException;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class LedgerPostingService
{
    /**
     * Post a transaction: generate ledger entries and mark as posted.
     *
     * @throws LedgerPostingException
     */
    public function post(Transaction $transaction): void
    {
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

        $this->preventDuplicateLedgerEntries($transaction);

        $transaction->loadMissing('details');

        $this->generateLedgerEntries($transaction);

        $transaction->update([
            'status'    => Transaction::STATUS_POSTED,
            'posted_by' => Auth::id(),
            'posted_at' => Carbon::now(),
        ]);
    }

    /**
     * Cancel a posted transaction: reverse ledger entries and mark as cancelled.
     *
     * @throws LedgerPostingException
     */
    public function cancel(Transaction $transaction, string $reason): void
    {
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
    }

    // ---------------------------------------------------------------
    // Private: Ledger Generation
    // ---------------------------------------------------------------

    private function generateLedgerEntries(Transaction $transaction): void
    {
        $entries   = [];
        $entryDate = $transaction->voucher_date
            ? Carbon::parse($transaction->voucher_date)->toDateString()
            : Carbon::now()->toDateString();

        foreach ($transaction->details as $detail) {
            $entries[] = [
                'transaction_id'    => $transaction->id,
                'company_id'        => $transaction->company_id,
                'financial_year_id' => $transaction->financial_year_id,
                'voucher_type_id'   => $transaction->voucher_type_id,
                'account_id'        => $detail->account_id,
                // ✅ Fix #2 এখানেও — voucher_no → voucher_number
                'voucher_number'    => $transaction->voucher_number,
                'voucher_date'      => $entryDate,
                'entry_date'        => $entryDate,
                'description'       => $detail->description ?? $transaction->narration ?? null,
                // ✅ Fix #1 — শুধু debit_amount/credit_amount, duplicate 'debit'/'credit' বাদ
                'debit_amount'      => $detail->debit_amount,
                'credit_amount'     => $detail->credit_amount,
                'is_reversed'       => false,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ];
        }

        if (empty($entries)) {
            throw new LedgerPostingException(
                "Transaction #{$transaction->id} has no detail lines to post."
            );
        }

        LedgerEntry::insert($entries);
    }

    private function reverseLedgerEntries(Transaction $transaction): void
    {
        $existingEntries = LedgerEntry::query()
            ->where('transaction_id', $transaction->id)
            ->where('is_reversed', false)
            ->get();

        if ($existingEntries->isEmpty()) {
            throw new LedgerPostingException(
                "No active ledger entries found for transaction #{$transaction->id}."
            );
        }

        $reversals = [];
        $now       = Carbon::now()->toDateString();

        foreach ($existingEntries as $entry) {
            $reversals[] = [
                'transaction_id'    => $transaction->id,
                'company_id'        => $entry->company_id,
                'financial_year_id' => $entry->financial_year_id,
                'voucher_type_id'   => $entry->voucher_type_id,
                'account_id'        => $entry->account_id,
                'voucher_number'    => $transaction->voucher_number,
                'voucher_date'      => $now,
                'entry_date'        => $now,
                'description'       => 'Reversal: ' . ($entry->description ?? $transaction->narration ?? ''),
                // Swap debit ↔ credit to zero-out the original entry's effect.
                'debit_amount'      => $entry->credit_amount,
                'credit_amount'     => $entry->debit_amount,
                // ✅ Mark reversal rows as is_reversed = true immediately.
                //
                // ❌ আগের সমস্যা: is_reversed = false দিয়ে insert করা হচ্ছিল।
                //    Ledger query-তে where('is_reversed', false) থাকায়
                //    এই reversal rows দেখা যেত এবং ভুল balance দেখাত।
                //    যেমন: Cash account-এ 1,000 Cr দেখাত cancelled voucher-এর জন্য।
                //
                // ✅ সঠিক: Reversal rows audit trail হিসেবে থাকবে,
                //    কিন্তু Ledger ও Balance Sheet-এ কখনো দেখাবে না।
                'is_reversed'       => true,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ];
        }

        LedgerEntry::insert($reversals);

        // Mark the original entries as reversed.
        LedgerEntry::query()
            ->whereIn('id', $existingEntries->pluck('id'))
            ->update(['is_reversed' => true]);
    }

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