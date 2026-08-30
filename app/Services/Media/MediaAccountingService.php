<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\Account;
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
 * Posts Media business events to the accounting ledger.
 *
 * Financial rule:
 * - Distribution: Dr Party AR / Cr Publication Sales.
 * - Return: Dr Sales Return / Cr Party AR.
 * - Collection: Dr Cash/Bank / Cr Party AR.
 *
 * The caller owns the business transaction. This service also uses a database
 * transaction so it remains atomic when called directly; Laravel will use a
 * nested transaction/savepoint when it is called from a larger workflow.
 */
final class MediaAccountingService
{
    public function __construct(
        private readonly LedgerPostingService $ledgerPostingService,
    ) {
    }

    public function postDistribution(MediaDistribution $distribution): ?Transaction
    {
        return DB::transaction(function () use ($distribution): ?Transaction {
            $distribution = MediaDistribution::query()
                ->whereKey($distribution->id)
                ->where('company_id', $distribution->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($distribution->transaction_id !== null) {
                return Transaction::query()->findOrFail($distribution->transaction_id);
            }

            $distribution->loadMissing(['items.party', 'publication']);
            $this->assertCompany($distribution->publication->company_id, $distribution->company_id, 'Publication');

            $totalAmount = (string) $distribution->total_amount;

            if (bccomp($totalAmount, '0.00', 2) === 0) {
                return null;
            }

            $salesAccountId = $distribution->publication->sales_account_id;
            if (! $salesAccountId) {
                throw new InvalidArgumentException(
                    "Publication '{$distribution->publication->name}' has no Sales Account configured."
                );
            }

            $this->assertActiveAccount($salesAccountId, $distribution->company_id, 'Sales');

            $financialYear = $this->activeFinancialYear($distribution->company_id);
            $voucherType = $this->journalVoucherType($distribution->company_id);

            $transaction = Transaction::create([
                'company_id' => $distribution->company_id,
                'financial_year_id' => $financialYear->id,
                'voucher_type_id' => $voucherType->id,
                'voucher_number' => $voucherType->generateNextVoucherNumber(),
                'voucher_date' => $distribution->distribution_date,
                'narration' => "Media Distribution #{$distribution->id} — {$distribution->publication->name}",
                'total_debit' => $totalAmount,
                'total_credit' => $totalAmount,
                'status' => Transaction::STATUS_APPROVED,
                'created_by' => $distribution->created_by,
                'approved_by' => $distribution->created_by,
                'approved_at' => now(),
            ]);

            $debitTotal = '0.00';
            foreach ($distribution->items as $item) {
                $party = $item->party;
                $amount = (string) $item->amount;

                if (bccomp($amount, '0.00', 2) <= 0) {
                    continue;
                }

                if (! $party || ! $party->account_id) {
                    throw new InvalidArgumentException(
                        "Party #{$item->media_party_id} has no AR Account configured."
                    );
                }

                $this->assertActiveAccount($party->account_id, $distribution->company_id, 'AR');

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $party->account_id,
                    'debit_amount' => $amount,
                    'credit_amount' => '0.00',
                    'description' => "AR — {$party->name}",
                    'sort_order' => 1,
                ]);

                $debitTotal = bcadd($debitTotal, $amount, 2);
            }

            if (bccomp($debitTotal, $totalAmount, 2) !== 0) {
                throw new InvalidArgumentException(
                    "Distribution #{$distribution->id} accounting total does not match its sales amount."
                );
            }

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'account_id' => $salesAccountId,
                'debit_amount' => '0.00',
                'credit_amount' => $totalAmount,
                'description' => "Sales — {$distribution->publication->name}",
                'sort_order' => 2,
            ]);

            $this->ledgerPostingService->post($transaction);

            $distribution->update(['transaction_id' => $transaction->id]);

            return $transaction->fresh(['details', 'entries']);
        });
    }

    public function postReturn(MediaReturn $return): ?Transaction
    {
        return DB::transaction(function () use ($return): ?Transaction {
            $return = MediaReturn::query()
                ->whereKey($return->id)
                ->where('company_id', $return->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($return->transaction_id !== null) {
                return Transaction::query()->findOrFail($return->transaction_id);
            }

            $return->loadMissing(['items.party', 'publication', 'distribution.items']);
            $this->assertCompany($return->publication->company_id, $return->company_id, 'Publication');

            if ($return->media_distribution_id !== null) {
                if (! $return->distribution || $return->distribution->company_id !== $return->company_id) {
                    throw new InvalidArgumentException('The linked distribution does not belong to this company.');
                }

                if ($return->distribution->publication_id !== $return->publication_id) {
                    throw new InvalidArgumentException('The linked distribution belongs to a different publication.');
                }
            }

            $rateByParty = $return->distribution?->items
                ->keyBy('media_party_id') ?? collect();

            
                $amountByParty = [];
                    $totalReturnAmount = '0.00';

                    foreach ($return->items as $item) {
                        $distItem = $rateByParty->get($item->media_party_id);

                        if ($return->media_distribution_id !== null && $distItem === null) {
                            throw new InvalidArgumentException(
                                "Party #{$item->media_party_id} was not found on distribution #{$return->media_distribution_id}."
                            );
                        }

                        $rate = (string) ($distItem?->rate ?? '0.00');
                        $amount = bcmul((string) $item->paid_return_quantity, $rate, 2);

                        $amountByParty[$item->media_party_id] = bcadd(
                            $amountByParty[$item->media_party_id] ?? '0.00',
                            $amount,
                            2
                        );

                        $totalReturnAmount = bcadd($totalReturnAmount, $amount, 2);
                    }

            if (bccomp($totalReturnAmount, '0.00', 2) === 0) {
                return null;
            }

            $salesReturnAccountId = $return->publication->sales_return_account_id;
            if (! $salesReturnAccountId) {
                throw new InvalidArgumentException(
                    "Publication '{$return->publication->name}' has no Sales Return Account configured."
                );
            }

            $this->assertActiveAccount($salesReturnAccountId, $return->company_id, 'Sales Return');

            $financialYear = $this->activeFinancialYear($return->company_id);
            $voucherType = $this->journalVoucherType($return->company_id);

            $transaction = Transaction::create([
                'company_id' => $return->company_id,
                'financial_year_id' => $financialYear->id,
                'voucher_type_id' => $voucherType->id,
                'voucher_number' => $voucherType->generateNextVoucherNumber(),
                'voucher_date' => $return->return_date,
                'narration' => "Media Return #{$return->id} — {$return->publication->name}",
                'total_debit' => $totalReturnAmount,
                'total_credit' => $totalReturnAmount,
                'status' => Transaction::STATUS_APPROVED,
                'created_by' => $return->created_by,
                'approved_by' => $return->created_by,
                'approved_at' => now(),
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'account_id' => $salesReturnAccountId,
                'debit_amount' => $totalReturnAmount,
                'credit_amount' => '0.00',
                'description' => "Sales Return — {$return->publication->name}",
                'sort_order' => 1,
            ]);

            $creditTotal = '0.00';
            foreach ($return->items as $item) {
                $amount = $amountByParty[$item->media_party_id] ?? '0.00';
                if (bccomp($amount, '0.00', 2) <= 0) {
                    continue;
                }

                $party = $item->party;
                if (! $party || ! $party->account_id) {
                    throw new InvalidArgumentException(
                        "Party #{$item->media_party_id} has no AR Account configured."
                    );
                }

                $this->assertActiveAccount($party->account_id, $return->company_id, 'AR');

                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $party->account_id,
                    'debit_amount' => '0.00',
                    'credit_amount' => $amount,
                    'description' => "AR Reduce — {$party->name}",
                    'sort_order' => 2,
                ]);

                $creditTotal = bcadd($creditTotal, $amount, 2);
            }

            if (bccomp($creditTotal, $totalReturnAmount, 2) !== 0) {
                throw new InvalidArgumentException(
                    "Return #{$return->id} accounting total does not match its sales return amount."
                );
            }

            $this->ledgerPostingService->post($transaction);
            $return->update(['transaction_id' => $transaction->id]);

            return $transaction->fresh(['details', 'entries']);
        });
    }

    public function postCollection(MediaCollection $collection): Transaction
    {
        return DB::transaction(function () use ($collection): Transaction {
            $collection = MediaCollection::query()
                ->whereKey($collection->id)
                ->where('company_id', $collection->company_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($collection->transaction_id !== null) {
                return Transaction::query()->findOrFail($collection->transaction_id);
            }

            $collection->loadMissing(['party', 'account']);

            if (! $collection->party) {
                throw new InvalidArgumentException(
                    "Party #{$collection->media_party_id} could not be found."
                );
            }

            $this->assertCompany($collection->party->company_id, $collection->company_id, 'Party');
            $this->assertActiveAccount($collection->account_id, $collection->company_id, 'Receiving');

            if (! $collection->party->account_id) {
                throw new InvalidArgumentException(
                    "Party '{$collection->party->name}' has no AR Account configured."
                );
            }

            $this->assertActiveAccount($collection->party->account_id, $collection->company_id, 'AR');

            $amount = (string) $collection->amount;
            if (bccomp($amount, '0.00', 2) <= 0) {
                throw new InvalidArgumentException('Collection amount must be greater than zero.');
            }

            $financialYear = $this->activeFinancialYear($collection->company_id);
            $voucherType = $this->receiptVoucherType($collection->company_id);

            $transaction = Transaction::create([
                'company_id' => $collection->company_id,
                'financial_year_id' => $financialYear->id,
                'voucher_type_id' => $voucherType->id,
                'voucher_number' => $voucherType->generateNextVoucherNumber(),
                'voucher_date' => $collection->collection_date,
                'narration' => "Media Collection #{$collection->id} — {$collection->party->name}",
                'total_debit' => $amount,
                'total_credit' => $amount,
                'status' => Transaction::STATUS_APPROVED,
                'created_by' => $collection->created_by,
                'approved_by' => $collection->created_by,
                'approved_at' => now(),
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'account_id' => $collection->account_id,
                'debit_amount' => $amount,
                'credit_amount' => '0.00',
                'description' => "Receipt — {$collection->account->account_name}",
                'sort_order' => 1,
            ]);

            TransactionDetail::create([
                'transaction_id' => $transaction->id,
                'account_id' => $collection->party->account_id,
                'debit_amount' => '0.00',
                'credit_amount' => $amount,
                'description' => "AR — {$collection->party->name}",
                'sort_order' => 2,
            ]);

            $this->ledgerPostingService->post($transaction);
            $collection->update(['transaction_id' => $transaction->id]);

            return $transaction->fresh(['details', 'entries']);
        });
    }

    private function activeFinancialYear(int $companyId): FinancialYear
    {
        $year = FinancialYear::query()
            ->where('company_id', $companyId)
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
        $voucherType = VoucherType::query()
            ->where('company_id', $companyId)
            ->where('nature', VoucherType::NATURE_JOURNAL)
            ->where('is_active', true)
            ->first();

        if (! $voucherType) {
            throw new InvalidArgumentException(
                'No active Journal Voucher type found for this company.'
            );
        }

        return $voucherType;
    }

    private function receiptVoucherType(int $companyId): VoucherType
    {
        $voucherType = VoucherType::query()
            ->where('company_id', $companyId)
            ->where('nature', VoucherType::NATURE_RECEIPT)
            ->where('is_active', true)
            ->first();

        if (! $voucherType) {
            throw new InvalidArgumentException(
                'No active Receipt Voucher type found for this company.'
            );
        }

        return $voucherType;
    }

    private function assertActiveAccount(int $accountId, int $companyId, string $purpose): void
    {
        $account = Account::query()->find($accountId);

        if (! $account || (int) $account->company_id !== $companyId) {
            throw new InvalidArgumentException(
                "Configured {$purpose} account #{$accountId} does not belong to this company."
            );
        }

        if (! $account->is_active) {
            throw new InvalidArgumentException(
                "Configured {$purpose} account '{$account->account_name}' is inactive."
            );
        }
    }

    private function assertCompany(int|string|null $actualCompanyId, int $expectedCompanyId, string $entity): void
    {
        if ($actualCompanyId === null || (int) $actualCompanyId !== $expectedCompanyId) {
            throw new InvalidArgumentException(
                "{$entity} does not belong to the current company."
            );
        }
    }
}
