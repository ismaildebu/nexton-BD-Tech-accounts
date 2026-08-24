<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\MediaDistribution;
use App\Models\MediaDistributionItem;
use App\Models\MediaReturn;
use App\Models\MediaReturnItem;
use App\Models\NewspaperStockMovement;
use App\Models\Publication;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * ReturnService
 * ---------------
 * Owns the entire "record a newspaper return" workflow:
 *
 *   1. If linked to a distribution, validate that each party's
 *      return quantity does not exceed what was originally distributed
 *      minus what has already been returned (net_quantity).
 *   2. Persist MediaReturn header + MediaReturnItem lines atomically.
 *   3. Update MediaDistributionItem.returned_quantity and net_quantity
 *      for each returned line (if distribution-linked).
 *   4. Write one 'return' NewspaperStockMovement (addStock) for the
 *      total quantity coming back into physical stock.
 *
 * Business rules:
 *   - Paid return and free return are tracked separately per line.
 *   - A return cannot exceed the party's net_quantity on the
 *     linked distribution item (net = total_quantity - already returned).
 *   - Returned copies add back to newspaper stock (they are physically
 *     back in hand and can be redistributed or counted as damage).
 *   - A return can be created without a distribution reference
 *     (standalone return), in which case quantity validation against
 *     a specific distribution is skipped — only the positive stock
 *     movement is written.
 */
final class ReturnService
{
    public function __construct(
        private readonly NewspaperStockService $stockService,
    ) {
    }

    /**
     * @param  array<int, array{
     *     media_party_id: int,
     *     paid_return_quantity: int,
     *     free_return_quantity: int
     * }>  $items
     *
     * @throws InvalidArgumentException
     */
    public function create(
        Publication $publication,
        string $returnDate,
        int $companyId,
        int $createdBy,
        array $items,
        ?int $distributionId = null,
        ?string $notes = null,
    ): MediaReturn {
        if (empty($items)) {
            throw new InvalidArgumentException('A return must contain at least one item.');
        }

        // Load the linked distribution (if any) for quantity validation
        $distribution = null;
        $distItemsByParty = collect();

        if ($distributionId !== null) {
            $distribution = MediaDistribution::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->with('items')
                ->findOrFail($distributionId);

            $distItemsByParty = $distribution->items->keyBy('media_party_id');
        }

        // --- Validate and build lines ---
        $lines = [];
        $totalPaidReturn = 0;
        $totalFreeReturn = 0;

        foreach ($items as $item) {
            $partyId  = (int) $item['media_party_id'];
            $paidRtn  = (int) $item['paid_return_quantity'];
            $freeRtn  = (int) $item['free_return_quantity'];
            $totalRtn = $paidRtn + $freeRtn;

            if ($paidRtn < 0 || $freeRtn < 0) {
                throw new InvalidArgumentException('Return quantities cannot be negative.');
            }

            if ($totalRtn === 0) {
                // Skip zero-quantity lines silently (user may have added a
                // row but left it at 0 for a party that returned nothing).
                continue;
            }

            // Distribution-linked validation: cannot return more than the
            // party's net_quantity (what was distributed minus already returned).
            if ($distribution !== null) {
                /** @var MediaDistributionItem|null $distItem */
                $distItem = $distItemsByParty->get($partyId);

                if ($distItem === null) {
                    throw new InvalidArgumentException(
                        "Party #{$partyId} was not part of distribution #{$distributionId}."
                    );
                }

                $netAvailable = $distItem->net_quantity;

                if ($totalRtn > $netAvailable) {
                    throw new InvalidArgumentException(
                        "Return quantity ({$totalRtn}) for party #{$partyId} exceeds the " .
                        "remaining distributable quantity ({$netAvailable}) on distribution #{$distributionId}."
                    );
                }
            }

            $lines[] = [
                'media_party_id'        => $partyId,
                'paid_return_quantity'  => $paidRtn,
                'free_return_quantity'  => $freeRtn,
                'total_return_quantity' => $totalRtn,
            ];

            $totalPaidReturn += $paidRtn;
            $totalFreeReturn += $freeRtn;
        }

        $totalReturnQuantity = $totalPaidReturn + $totalFreeReturn;

        if ($totalReturnQuantity === 0) {
            throw new InvalidArgumentException(
                'At least one item must have a return quantity greater than zero.'
            );
        }

        // --- Persist atomically ---
        return DB::transaction(function () use (
            $publication,
            $returnDate,
            $companyId,
            $createdBy,
            $notes,
            $distributionId,
            $distribution,
            $distItemsByParty,
            $lines,
            $totalPaidReturn,
            $totalFreeReturn,
            $totalReturnQuantity,
        ) {
            $header = MediaReturn::create([
                'company_id'                 => $companyId,
                'publication_id'             => $publication->id,
                'media_distribution_id'      => $distributionId,
                'return_date'                => $returnDate,
                'status'                     => MediaReturn::STATUS_DRAFT,
                'total_paid_return_quantity' => $totalPaidReturn,
                'total_free_return_quantity' => $totalFreeReturn,
                'total_return_quantity'      => $totalReturnQuantity,
                'notes'                      => $notes,
                'created_by'                 => $createdBy,
            ]);

            foreach ($lines as $line) {
                MediaReturnItem::create([
                    'media_return_id' => $header->id,
                    ...$line,
                ]);

                // Update the linked distribution item's returned/net figures
                // so the distribution always reflects what is still outstanding.
                if ($distribution !== null) {
                    /** @var MediaDistributionItem $distItem */
                    $distItem = $distItemsByParty->get($line['media_party_id']);

                    $distItem->increment('returned_quantity', $line['total_return_quantity']);
                    $distItem->decrement('net_quantity', $line['total_return_quantity']);
                }
            }

            // Returned copies go back into physical stock.
            $this->stockService->addStock(
                $publication,
                NewspaperStockMovement::TYPE_RETURN,
                $totalReturnQuantity,
                $returnDate,
                reference: $header,
                notes: "Newspaper Return #{$header->id}" . ($distributionId ? " against Distribution #{$distributionId}" : ''),
                createdBy: $createdBy,
            );

            $header->update(['status' => MediaReturn::STATUS_CONFIRMED]);

            return $header->fresh(['items.party', 'publication']);
        });
    }
}
