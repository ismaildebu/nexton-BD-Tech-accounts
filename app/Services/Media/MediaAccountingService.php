<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\FinancialYear;
use App\Models\MediaCollection;
use App\Models\MediaDistribution;
use App\Models\MediaReturn;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\VoucherType;
use App\Services\LedgerPostingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * MediaAccountingService
 * ----------------------
 * Media module-এর সব accounting journal entry এখানে।
 *
 * Distribution → Dr. AR (Party) / Cr. Sales (Publication)
 * Return       → Dr. Sales Return / Cr. AR (Party)
 * Collection   → Dr. Cash/Bank / Cr. AR (Party)
 */
final class MediaAccountingService
{
    public function __construct(
        private readonly LedgerPostingService $ledgerPostingService,
    ) {
    }

    // ─── Distribution Journal ─────────────────────────────────────

    /**
     * Distribution confirm হলে journal entry তৈরি ও post করুন।
     *
     * Dr. Accounts Receivable (Party)
     *     Cr. Sales Revenue (Publication)
     */
    public function postDistribution(MediaDistribution $distribution): Transaction
    {
        $distribution->loadMissing(['items.party', 'publication']);

        $companyId = $distribution->company_id;
        $pub       = $distribution->publication;

        if (! $pub->sales_account_id) {
            throw new InvalidArgumentException(
                "Publication '{$pub->name}' has no Sales Account configured."
            );
        }

        $financialYear = $this->activeFinancialYear($companyId);
        $voucherType   = $this->journalVoucherType($companyId);

        return DB::transaction(function () use (
            $distribution, $companyId, $pub, $financialYear, $voucherType
        ) {
            $totalAmount = (float) $distribution->total_amount;

            $transaction = Transaction::create([
                'company_id'        => $companyId,
                'financial_year_id' => $financialYear->id,
                'voucher_type_id'   => $voucherType->id,
                'voucher_number'    => $voucherType->generateNextVoucherNumber(),
                'voucher_date'      => $distribution->distribution_date,
                'narration'         => "Media Distribution #{$distribution->id} — {$pub->name}",
                'total_debit'       => $totalAmount,
                'total_credit'      => $totalAmount,
                'status'            => Transaction::STATUS_APPROVED,
                'created_by'        => $distribution->created_by,
                'approved_by'       => $distribution->created_by,
                'approved_at'       => now(),
            ]);

            // Dr. Accounts Receivable — per party
            foreach ($distribution->items as $item) {
                $party = $item->party;

                if (! $party->account_id) {
                    throw new InvalidArgumentException(
                        "Party '{$party->name}' has no AR Account configured."
                    );
                }

                if ((float) $item->amount <= 0) {
                    continue; // free-only party — কোনো receivable নেই
                }

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $party->account_id,
                    'debit_amount'   => (float) $item->amount,
                    'credit_amount'  => 0,
                    'description'    => "AR — {$party->name}",
                    'sort_order'     => 1,
                ]);
            }

            // Cr. Sales Revenue
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'account_id'     => $pub->sales_account_id,
                'debit_amount'   => 0,
                'credit_amount'  => $totalAmount,
                'description'    => "Sales — {$pub->name}",
                'sort_order'     => 2,
            ]);

            // Ledger-এ post করুন
            $this->ledgerPostingService->post($transaction);

            // Distribution-এ transaction link করুন
            $distribution->update(['transaction_id' => $transaction->id]);

            return $transaction;
        });
    }

    // ─── Return Journal ───────────────────────────────────────────

    /**
     * Return confirm হলে journal entry তৈরি ও post করুন।
     *
     * Dr. Sales Return
     *     Cr. Accounts Receivable (Party)
     */
    public function postReturn(MediaReturn $return): Transaction
    {
        $return->loadMissing(['items.party', 'publication']);

        $companyId = $return->company_id;
        $pub       = $return->publication;

        if (! $pub->sales_return_account_id) {
            throw new InvalidArgumentException(
                "Publication '{$pub->name}' has no Sales Return Account configured."
            );
        }

        $financialYear = $this->activeFinancialYear($companyId);
        $voucherType   = $this->journalVoucherType($companyId);

        return DB::transaction(function () use (
            $return, $companyId, $pub, $financialYear, $voucherType
        ) {
            // Return amount = paid return quantity * rate (from dist item)
            $totalReturnAmount = $return->items->sum(function ($item) {
                $distItem = $item->party->distributionItems()
                    ->where('media_distribution_id', $return->media_distribution_id)
                    ->first();

                $rate = $distItem?->rate ?? 0;

                return $item->paid_return_quantity * $rate;
            });

            if ($totalReturnAmount <= 0) {
                // শুধু free copy return — accounting entry নেই
                return null;
            }

            $transaction = Transaction::create([
                'company_id'        => $companyId,
                'financial_year_id' => $financialYear->id,
                'voucher_type_id'   => $voucherType->id,
                'voucher_number'    => $voucherType->generateNextVoucherNumber(),
                'voucher_date'      => $return->return_date,
                'narration'         => "Media Return #{$return->id} — {$pub->name}",
                'total_debit'       => $totalReturnAmount,
                'total_credit'      => $totalReturnAmount,
                'status'            => Transaction::STATUS_APPROVED,
                'created_by'        => $return->created_by,
                'approved_by'       => $return->created_by,
                'approved_at'       => now(),
            ]);

            // Dr. Sales Return
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'account_id'     => $pub->sales_return_account_id,
                'debit_amount'   => $totalReturnAmount,
                'credit_amount'  => 0,
                'description'    => "Sales Return — {$pub->name}",
                'sort_order'     => 1,
            ]);

            // Cr. AR per party
            foreach ($return->items as $item) {
                $party = $item->party;

                if (! $party->account_id) {
                    throw new InvalidArgumentException(
                        "Party '{$party->name}' has no AR Account configured."
                    );
                }

                $distItem = $party->distributionItems()
                    ->where('media_distribution_id', $return->media_distribution_id)
                    ->first();

                $rate         = $distItem?->rate ?? 0;
                $returnAmount = $item->paid_return_quantity * $rate;

                if ($returnAmount <= 0) {
                    continue;
                }

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'account_id'     => $party->account_id,
                    'debit_amount'   => 0,
                    'credit_amount'  => $returnAmount,
                    'description'    => "AR Reduce — {$party->name}",
                    'sort_order'     => 2,
                ]);
            }

            $this->ledgerPostingService->post($transaction);

            $return->update(['transaction_id' => $transaction->id]);

            return $transaction;
        });
    }

    // ─── Collection Journal ───────────────────────────────────────

    /**
     * Collection record হলে journal entry তৈরি ও post করুন।
     *
     * Dr. Cash/Bank (selected account)
     *     Cr. Accounts Receivable (Party)
     */
    public function postCollection(MediaCollection $collection): Transaction
    {
        $collection->loadMissing(['party', 'account']);

        $companyId = $collection->company_id;
        $party     = $collection->party;
        $account   = $collection->account;

        if (! $party->account_id) {
            throw new InvalidArgumentException(
                "Party '{$party->name}' has no AR Account configured."
            );
        }

        $financialYear = $this->activeFinancialYear($companyId);
        $voucherType   = $this->receiptVoucherType($companyId);

        return DB::transaction(function () use (
            $collection, $companyId, $party, $account, $financialYear, $voucherType
        ) {
            $amount = (float) $collection->amount;

            $transaction = Transaction::create([
                'company_id'        => $companyId,
                'financial_year_id' => $financialYear->id,
                'voucher_type_id'   => $voucherType->id,
                'voucher_number'    => $voucherType->generateNextVoucherNumber(),
                'voucher_date'      => $collection->collection_date,
                'narration'         => "Media Collection #{$collection->id} — {$party->name}",
                'total_debit'       => $amount,
                'total_credit'      => $amount,
                'status'            => Transaction::STATUS_APPROVED,
                'created_by'        => $collection->created_by,
                'approved_by'       => $collection->created_by,
                'approved_at'       => now(),
            ]);

            // Dr. Cash/Bank
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'account_id'     => $account->id,
                'debit_amount'   => $amount,
                'credit_amount'  => 0,
                'description'    => "Receipt — {$account->account_name}",
                'sort_order'     => 1,
            ]);

            // Cr. AR
            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'account_id'     => $party->account_id,
                'debit_amount'   => 0,
                'credit_amount'  => $amount,
                'description'    => "AR — {$party->name}",
                'sort_order'     => 2,
            ]);

            $this->ledgerPostingService->post($transaction);

            $collection->update(['transaction_id' => $transaction->id]);

            return $transaction;
        });
    }

    // ─── Private Helpers ──────────────────────────────────────────

    private function activeFinancialYear(int $companyId): FinancialYear
    {
        $year = FinancialYear::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_closed', false)
            ->first();

        if (! $year) {
            throw new InvalidArgumentException(
                'No active financial year found. Please create one before posting.'
            );
        }

        return $year;
    }

    private function journalVoucherType(int $companyId): VoucherType
    {
        $vt = VoucherType::where('company_id', $companyId)
            ->where('nature', VoucherType::NATURE_JOURNAL)
            ->where('is_active', true)
            ->first();

        if (! $vt) {
            throw new InvalidArgumentException(
                'No active Journal Voucher type found for this company.'
            );
        }

        return $vt;
    }

    private function receiptVoucherType(int $companyId): VoucherType
    {
        $vt = VoucherType::where('company_id', $companyId)
            ->where('nature', VoucherType::NATURE_RECEIPT)
            ->where('is_active', true)
            ->first();

        if (! $vt) {
            throw new InvalidArgumentException(
                'No active Receipt Voucher type found for this company.'
            );
        }

        return $vt;
    }
}