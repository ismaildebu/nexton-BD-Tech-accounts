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
 * Owns the entire "create a Daily Distribution" workflow so the
 * arithmetic, the free-copy priority chain, and the stock check can
 * never be bypassed or duplicated by a controller:
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
    ) {
    }

    /**
     * @param  array<int, array{media_party_id:int, paid_quantity:int, rate:float}>  $items
     *
     * @throws InsufficientNewspaperStockException
     * @throws InvalidArgumentException
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

        // Load every party up front (company-scoped via BelongsToCompany's
        // global scope) so a spoofed/foreign party_id fails loudly instead
        // of silently resolving free% off the wrong company's defaults.
        $partyIds = collect($items)->pluck('media_party_id')->unique();
        $parties  = MediaParty::query()->whereIn('id', $partyIds)->get()->keyBy('id');

        if ($parties->count() !== $partyIds->count()) {
            throw new InvalidArgumentException('One or more parties could not be found for this company.');
        }

        // --- Step 1 & 2: resolve free % and compute per-line figures ---
        $lines = [];
        $totalPaid   = 0;
        $totalFree   = 0;
        $totalAmount = 0;

        foreach ($items as $item) {
            $party = $parties->get((int) $item['media_party_id']);
            $paid  = (int) $item['paid_quantity'];
            $rate  = (float) $item['rate'];

            if ($paid < 0) {
                throw new InvalidArgumentException('Paid quantity cannot be negative.');
            }

            if ($rate < 0) {
                throw new InvalidArgumentException('Rate cannot be negative.');
            }

            $freePercentage = $this->freePercentageResolver->resolve($party, $publication);
            $free           = $this->freePercentageResolver->calculateFreeQuantity($paid, $freePercentage);
            $total          = $paid + $free;

            // Paid quantity creates the financial sales amount. Free
            // copies never create a receivable — they are deliberately
            // excluded from `amount` here.
            $amount = round($paid * $rate, 2);

            $lines[] = [
                'media_party_id'    => $party->id,
                'paid_quantity'     => $paid,
                'free_percentage'   => $freePercentage,
                'free_quantity'     => $free,
                'total_quantity'    => $total,
                'rate'              => $rate,
                'amount'            => $amount,
                'returned_quantity' => 0,
                'net_quantity'      => $total,
            ];

            $totalPaid   += $paid;
            $totalFree   += $free;
            $totalAmount += $amount;
        }

        $totalQuantity = $totalPaid + $totalFree;

        if ($totalQuantity <= 0) {
            throw new InvalidArgumentException(
                'Distribution must contain at least one item with a positive paid or free quantity.'
            );
        }

        // --- Step 3: fail fast before writing anything at all ---
        if (! $this->stockService->hasSufficientStock($publication, $totalQuantity)) {
            $available = $this->stockService->balance($publication);

            throw new InsufficientNewspaperStockException(
                "Insufficient stock for '{$publication->name}'. Available: {$available}, Required: {$totalQuantity}.",
                available: $available,
                required: $totalQuantity,
            );
        }

        // --- Step 4: persist everything atomically ---
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
                'company_id'        => $companyId,
                'publication_id'    => $publication->id,
                'distribution_date' => $distributionDate,
                'status'            => MediaDistribution::STATUS_DRAFT,
                'notes'             => $notes,
                'created_by'        => $createdBy,
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
                'total_quantity'      => $totalQuantity,
                'total_amount'        => $totalAmount,
            ]);

            // Re-checks availability under a row lock (see
            // NewspaperStockService::removeStock) — this is the real
            // enforcement point; the hasSufficientStock() call above is
            // only a fast, early rejection so we don't build item rows
            // for a request that will fail anyway.
            $this->stockService->removeStock(
                $publication,
                NewspaperStockMovement::TYPE_DISTRIBUTION,
                $totalQuantity,
                $distributionDate,
                reference: $header,
                notes: "Daily Distribution #{$header->id}",
                createdBy: $createdBy,
            );

            $header->update(['status' => MediaDistribution::STATUS_CONFIRMED]);

            return $header->fresh(['items.party', 'publication']);
        });
    }
}
