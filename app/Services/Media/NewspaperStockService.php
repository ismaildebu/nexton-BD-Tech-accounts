<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Exceptions\InsufficientNewspaperStockException;
use App\Models\NewspaperStockMovement;
use App\Models\Publication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * NewspaperStockService
 * -----------------------
 * Single point of entry for every change to a publication's newspaper
 * stock. No controller or other service is allowed to insert into
 * newspaper_stock_movements directly — always go through here so the
 * negative-stock rule and the "every change is in a DB transaction"
 * rule can never be bypassed.
 *
 * Movement types (must match NewspaperStockMovement::TYPES):
 *   opening, printed, received  -> increase stock (addStock)
 *   distribution, damage        -> decrease stock (removeStock)
 *   return                      -> increase stock (addStock) — paper
 *                                   physically comes back into hand
 *   adjustment                  -> either direction (adjust), still
 *                                   subject to the same negative-stock
 *                                   check when it decreases stock
 *
 * Balance is never stored — it is always the signed SUM of every
 * movement row for that publication, so it can never drift out of
 * sync with the log (mirrors the doc-comment already on
 * NewspaperStockMovement and the newspaper_stock_movements migration).
 */
final class NewspaperStockService
{
    /**
     * Current stock balance for a publication: signed SUM of every
     * movement recorded against it, scoped to its own company.
     *
     * withoutGlobalScopes() is mandatory here. NewspaperStockMovement
     * uses BelongsToCompany, whose global scope filters by
     * session('company_id'). When this service is called with a
     * publication belonging to a company other than the one currently
     * in the session (e.g. cross-company isolation tests, or background
     * jobs), the global scope would silently return 0 instead of the
     * publication's real balance. We bypass it and apply the correct
     * company_id directly from the publication record itself.
     */
    public function balance(Publication $publication): int
    {
        return (int) NewspaperStockMovement::withoutGlobalScopes()
            ->where('company_id', $publication->company_id)
            ->where('publication_id', $publication->id)
            ->sum('quantity');
    }

    /**
     * Read-only availability check. Does NOT lock — callers that will
     * immediately follow this with a removeStock() should be aware the
     * balance could change between the check and the write under
     * concurrent load. removeStock() re-checks under a row lock itself,
     * so this method is safe to use for early/UI-facing validation
     * (e.g. rejecting a distribution before doing any other work) while
     * removeStock() remains the sole source of truth for correctness.
     */
    public function hasSufficientStock(Publication $publication, int $requiredQuantity): bool
    {
        return $this->balance($publication) >= $requiredQuantity;
    }

    /**
     * Record a movement that ADDS to stock: opening, printed, received,
     * return, or a positive adjustment. $quantity is given positive and
     * stored positive.
     */
    public function addStock(
        Publication $publication,
        string $type,
        int $quantity,
        string $movementDate,
        ?Model $reference = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): NewspaperStockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity to add must be greater than zero.');
        }

        return DB::transaction(
            fn () => $this->record($publication, $type, $quantity, $movementDate, $reference, $notes, $createdBy)
        );
    }

    /**
     * Record a movement that REMOVES stock: distribution, damage, or a
     * negative adjustment. $quantity is given positive (the amount being
     * removed) and stored as a negative value.
     *
     * Runs inside its own DB transaction and locks the publication row
     * first, so two concurrent removals against the same publication can
     * never both pass the availability check and push stock negative —
     * the second one blocks until the first commits, then re-reads the
     * balance and is correctly rejected if there is no longer enough
     * stock. This is the same lockForUpdate()-before-check pattern
     * already used by InventoryController::storeStockOut() for
     * ProductStock and by PrintOrderService::nextOrderNumber() for
     * document numbering.
     *
     * @throws InsufficientNewspaperStockException
     */
    public function removeStock(
        Publication $publication,
        string $type,
        int $quantity,
        string $movementDate,
        ?Model $reference = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): NewspaperStockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity to remove must be greater than zero.');
        }

        return DB::transaction(function () use ($publication, $type, $quantity, $movementDate, $reference, $notes, $createdBy) {
            $locked = Publication::query()
                ->whereKey($publication->id)
                ->lockForUpdate()
                ->firstOrFail();

            $available = $this->balance($locked);

            if ($available < $quantity) {
                throw new InsufficientNewspaperStockException(
                    "Insufficient stock for '{$locked->name}'. Available: {$available}, Required: {$quantity}.",
                    available: $available,
                    required: $quantity,
                );
            }

            return $this->record($locked, $type, -$quantity, $movementDate, $reference, $notes, $createdBy);
        });
    }

    /**
     * A signed manual adjustment: positive increases stock, negative
     * decreases it. A decreasing adjustment still goes through the same
     * lockForUpdate()+availability check as removeStock() — adjustments
     * are not a backdoor around the negative-stock rule.
     */
    public function adjust(
        Publication $publication,
        int $signedQuantity,
        string $movementDate,
        ?string $notes = null,
        ?int $createdBy = null,
    ): NewspaperStockMovement {
        if ($signedQuantity === 0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        return $signedQuantity > 0
            ? $this->addStock($publication, NewspaperStockMovement::TYPE_ADJUSTMENT, $signedQuantity, $movementDate, null, $notes, $createdBy)
            : $this->removeStock($publication, NewspaperStockMovement::TYPE_ADJUSTMENT, abs($signedQuantity), $movementDate, null, $notes, $createdBy);
    }

    private function record(
        Publication $publication,
        string $type,
        int $signedQuantity,
        string $movementDate,
        ?Model $reference,
        ?string $notes,
        ?int $createdBy,
    ): NewspaperStockMovement {
        if (! in_array($type, NewspaperStockMovement::TYPES, true)) {
            throw new InvalidArgumentException("Unknown stock movement type '{$type}'.");
        }

        return NewspaperStockMovement::create([
            'company_id'     => $publication->company_id,
            'publication_id' => $publication->id,
            'movement_date'  => $movementDate,
            'type'           => $type,
            'quantity'       => $signedQuantity,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id'   => $reference?->getKey(),
            'notes'          => $notes,
            'created_by'     => $createdBy,
        ]);
    }
}
