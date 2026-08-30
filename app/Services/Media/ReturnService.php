<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\MediaDistribution;
use App\Models\MediaDistributionItem;
use App\Models\MediaReturn;
use App\Models\MediaReturnItem;
use App\Models\MediaParty;
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
 *   - Free-only returns may be standalone. Paid returns must reference
 *     the original distribution because their accounting amount depends
 *     on the original distribution rate.
 */
final class ReturnService
{
    public function __construct(
        private readonly NewspaperStockService $stockService,
        private readonly MediaAccountingService $accountingService,
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

        if ((int) $publication->company_id !== $companyId) {
            throw new InvalidArgumentException('Publication does not belong to the current company.');
        }

        return DB::transaction(function () use (
            $publication,
            $returnDate,
            $companyId,
            $createdBy,
            $items,
            $distributionId,
            $notes,
        ): MediaReturn {
            $distribution = null;
            $distItemsByParty = collect();

            if ($distributionId !== null) {
                $distribution = MediaDistribution::query()
                    ->whereKey($distributionId)
                    ->where('company_id', $companyId)
                    ->lockForUpdate()
                    ->first();

                if (! $distribution) {
                    throw new InvalidArgumentException(
                        "Distribution #{$distributionId} was not found for this company."
                    );
                }

                if ($distribution->publication_id !== $publication->id) {
                    throw new InvalidArgumentException(
                        "Distribution #{$distributionId} belongs to a different publication."
                    );
                }

                if (! $distribution->isConfirmed()) {
                    throw new InvalidArgumentException(
                        "Only confirmed distribution #{$distributionId} can receive returns."
                    );
                }

                $distItemsByParty = MediaDistributionItem::query()
                    ->where('media_distribution_id', $distribution->id)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('media_party_id');
            }

            $lines = [];
            $totalPaidReturn = 0;
            $totalFreeReturn = 0;

            foreach ($items as $item) {
                $partyId = (int) $item['media_party_id'];
                $paidRtn = (int) $item['paid_return_quantity'];
                $freeRtn = (int) $item['free_return_quantity'];
                $totalRtn = $paidRtn + $freeRtn;

                if ($paidRtn < 0 || $freeRtn < 0) {
                    throw new InvalidArgumentException('Return quantities cannot be negative.');
                }

                if ($totalRtn === 0) {
                    continue;
                }

                $party = MediaParty::query()->find($partyId);
                if (! $party || (int) $party->company_id !== $companyId) {
                    throw new InvalidArgumentException(
                        "Party #{$partyId} was not found for this company."
                    );
                }

                if ($distribution !== null) {
                    /** @var MediaDistributionItem|null $distItem */
                    $distItem = $distItemsByParty->get($partyId);

                    if ($distItem === null) {
                        throw new InvalidArgumentException(
                            "Party #{$partyId} was not part of distribution #{$distributionId}."
                        );
                    }

                 // Only paid return is limited by distribution net_quantity.
                // Free return has no accounting constraint from the original distribution.
                    $netAvailable = (int) $distItem->net_quantity;
                    if ($paidRtn > $netAvailable) {
                        throw new InvalidArgumentException(
                            "Paid return quantity ({$paidRtn}) for party #{$partyId} exceeds the remaining quantity ({$netAvailable}) on distribution #{$distributionId}."
                        );
                    }
                    
                } elseif ($paidRtn > 0) {
                    throw new InvalidArgumentException(
                        'Paid returns must be linked to the original distribution so the sales rate can be determined.'
                    );
                }

                $lines[] = [
                    'media_party_id' => $partyId,
                    'paid_return_quantity' => $paidRtn,
                    'free_return_quantity' => $freeRtn,
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

            $header = MediaReturn::create([
                'company_id' => $companyId,
                'publication_id' => $publication->id,
                'media_distribution_id' => $distributionId,
                'return_date' => $returnDate,
                'status' => MediaReturn::STATUS_DRAFT,
                'total_paid_return_quantity' => $totalPaidReturn,
                'total_free_return_quantity' => $totalFreeReturn,
                'total_return_quantity' => $totalReturnQuantity,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            foreach ($lines as $line) {
                MediaReturnItem::create([
                    'media_return_id' => $header->id,
                    ...$line,
                ]);

                if ($distribution !== null) {
                    /** @var MediaDistributionItem $distItem */
                    $distItem = $distItemsByParty->get($line['media_party_id']);
                    $distItem->increment('returned_quantity', $line['total_return_quantity']);
                    $distItem->decrement('net_quantity', $line['total_return_quantity']);
                }
            }

            $this->stockService->addStock(
                $publication,
                NewspaperStockMovement::TYPE_RETURN,
                $totalReturnQuantity,
                $returnDate,
                reference: $header,
                notes: "Newspaper Return #{$header->id}" . ($distributionId ? " against Distribution #{$distributionId}" : ''),
                createdBy: $createdBy,
            );

            $this->accountingService->postReturn(
                $header->fresh(['items.party', 'publication', 'distribution.items'])
            );

            $header->update(['status' => MediaReturn::STATUS_CONFIRMED]);

            return $header->fresh(['items.party', 'publication', 'distribution', 'transaction']);
        });
    }

}
