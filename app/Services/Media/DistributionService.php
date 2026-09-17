<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Exceptions\InsufficientNewspaperStockException;
use App\Models\MediaDistribution;
use App\Models\MediaDistributionItem;
use App\Models\MediaParty;
use App\Models\NewspaperStockMovement;
use App\Models\Publication;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * DistributionService
 * ----------------------
 * Owns the Daily Distribution demand workflow so the arithmetic and
 * free-copy priority chain can never be bypassed or duplicated by a
 * controller.
 *
 * A distribution is created as Draft first. Newspaper stock is consumed
 * and accounting is posted only when the draft is explicitly confirmed.
 * This prevents a circular dependency between distribution demand and
 * newspaper stock / print order creation.
 *
 *   1. For every line, resolve the free % via FreePercentageResolver
 *      (Party override -> Publication default -> System default) —
 *      the free % is NEVER accepted from client input.
 *   2. Compute free_quantity / total_quantity / amount per line using
 *      FreePercentageResolver::calculateFreeQuantity() (the one
 *      rounding rule for the whole app).
 *   3. Verify available stock >= total distribution quantity for the
 *      publication BEFORE writing anything. If insufficient, the whole
 *      distribution is rejected — nothing is created.
 *   4. Persist header + item rows and record ONE aggregated
 *      'distribution' stock movement for the whole run, inside a single
 *      DB transaction. Either everything is saved and stock is
 *      decremented, or nothing is.
 *
 * Free copies never create a receivable: MediaDistributionItem.amount
 * is computed from paid_quantity * rate only. total_quantity (paid +
 * free) is what affects physical stock/circulation — it is never used
 * to compute `amount`.
 */
final class DistributionService
{
    public function __construct(
        private readonly FreePercentageResolver $freePercentageResolver,
        private readonly NewspaperStockService $stockService,
        private readonly MediaAccountingService $accountingService,
    ) {
    }

    /**
     * Create a draft daily distribution demand.
     *
     * Draft creation does not consume newspaper stock and does not post
     * accounting entries. Stock is consumed and accounting is posted only
     * when the distribution is confirmed.
     *
     * @param  array<int, array{media_party_id:int, paid_quantity:int, rate:string|float|int}>  $items
     */
    public function create(
        Publication $publication,
        string $distributionDate,
        int $companyId,
        int $createdBy,
        array $items,
        ?string $notes = null,
    ): MediaDistribution {
        if (empty($items)) {
            throw new InvalidArgumentException('A distribution must contain at least one item.');
        }

        if ((int) $publication->company_id !== $companyId) {
            throw new InvalidArgumentException('Publication does not belong to the current company.');
        }

        $partyIds = collect($items)->pluck('media_party_id')->unique();
        $parties = MediaParty::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $partyIds)
            ->get()
            ->keyBy('id');

        if ($parties->count() !== $partyIds->count()) {
            throw new InvalidArgumentException('One or more parties could not be found for this company.');
        }

        $lines = [];
        $totalPaid = 0;
        $totalFree = 0;
        $totalAmount = '0.00';

        foreach ($items as $item) {
            $party = $parties->get((int) $item['media_party_id']);
            $paid = (int) $item['paid_quantity'];
            $rate = (string) $item['rate'];

            if ($paid < 0) {
                throw new InvalidArgumentException('Paid quantity cannot be negative.');
            }

            if ((float) $rate < 0) {
                throw new InvalidArgumentException('Rate cannot be negative.');
            }

            $freePercentage = $this->freePercentageResolver->resolve($party, $publication);
            $free = $this->freePercentageResolver->calculateFreeQuantity($paid, $freePercentage);
            $total = $paid + $free;
            $amount = bcmul((string) $paid, $rate, 2);

            $lines[] = [
                'media_party_id' => $party->id,
                'paid_quantity' => $paid,
                'free_percentage' => $freePercentage,
                'free_quantity' => $free,
                'total_quantity' => $total,
                'rate' => $rate,
                'amount' => $amount,
                'returned_quantity' => 0,
                'net_quantity' => $total,
            ];

            $totalPaid += $paid;
            $totalFree += $free;
            $totalAmount = bcadd($totalAmount, $amount, 2);
        }

        $totalQuantity = $totalPaid + $totalFree;

        if ($totalQuantity <= 0) {
            throw new InvalidArgumentException(
                'Distribution must contain at least one item with a positive paid or free quantity.'
            );
        }

        return DB::transaction(function () use (
            $publication,
            $distributionDate,
            $companyId,
            $createdBy,
            $notes,
            $lines,
            $totalPaid,
            $totalFree,
            $totalQuantity,
            $totalAmount,
        ) {
            $header = MediaDistribution::create([
                'company_id' => $companyId,
                'publication_id' => $publication->id,
                'distribution_date' => $distributionDate,
                'status' => MediaDistribution::STATUS_DRAFT,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            foreach ($lines as $line) {
                MediaDistributionItem::create([
                    'media_distribution_id' => $header->id,
                    ...$line,
                ]);
            }

            $header->update([
                'total_paid_quantity' => $totalPaid,
                'total_free_quantity' => $totalFree,
                'total_quantity' => $totalQuantity,
                'total_amount' => $totalAmount,
            ]);

            return $header->fresh([
                'items.party',
                'publication',
                'transaction',
            ]);
        });
    }

    /**
     * Confirm a draft distribution.
     *
     * Confirmation is the point at which physical newspaper stock is
     * consumed and the distribution accounting entry is posted. Both
     * operations happen in the same database transaction.
     */
    public function confirm(
        MediaDistribution $distribution,
        int $companyId,
        int $confirmedBy,
    ): MediaDistribution {
        if ((int) $distribution->company_id !== $companyId) {
            throw new InvalidArgumentException(
                'Distribution does not belong to the current company.'
            );
        }

        if ($distribution->status !== MediaDistribution::STATUS_DRAFT) {
            throw new InvalidArgumentException(
                'Only a draft distribution can be confirmed.'
            );
        }

        return DB::transaction(function () use (
            $distribution,
            $companyId,
            $confirmedBy,
        ) {
            $distribution = MediaDistribution::query()
                ->where('company_id', $companyId)
                ->whereKey($distribution->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($distribution->status !== MediaDistribution::STATUS_DRAFT) {
                throw new InvalidArgumentException(
                    'Only a draft distribution can be confirmed.'
                );
            }

            $publication = Publication::query()
                ->where('company_id', $companyId)
                ->whereKey($distribution->publication_id)
                ->firstOrFail();

            $requiredQuantity = (int) $distribution->total_quantity;
            $available = $this->stockService->balance($publication);

            if ($available < $requiredQuantity) {
                throw new InsufficientNewspaperStockException(
                    "Insufficient stock for '{$publication->name}'. Available: {$available}, Required: {$requiredQuantity}.",
                    available: $available,
                    required: $requiredQuantity,
                );
            }

            $this->stockService->removeStock(
                $publication,
                NewspaperStockMovement::TYPE_DISTRIBUTION,
                $requiredQuantity,
                $distribution->distribution_date->toDateString(),
                reference: $distribution,
                notes: "Daily Distribution #{$distribution->id}",
                createdBy: $confirmedBy,
            );

            $this->accountingService->postDistribution(
                $distribution->fresh([
                    'items.party',
                    'publication',
                ])
            );

            $distribution->update([
                'status' => MediaDistribution::STATUS_CONFIRMED,
            ]);

            return $distribution->fresh([
                'items.party',
                'publication',
                'transaction',
            ]);
        });
    }
}
