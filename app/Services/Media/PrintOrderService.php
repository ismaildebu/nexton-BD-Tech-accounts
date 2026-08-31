<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\PrintOrder;
use App\Models\PrintPlan;
use App\Models\Publication;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * PrintOrderService
 * -------------------
 * Order-number generation, plan-linked creation, and the controlled
 * status workflow for Print Orders. Kept out of the controller so the
 * transition rules and numbering logic can be unit-tested directly
 * and never bypassed by a shortcut in a view/controller.
 *
 * Status workflow (only these transitions are allowed):
 *
 *   Draft -----> Ordered ----> Printing ----> Printed ----> Received
 *     \             \              \
 *      \-> Cancelled \-> Cancelled  \-> Cancelled
 *
 *   Received and Cancelled are terminal.
 */
final class PrintOrderService
{
    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TRANSITIONS = [
        PrintOrder::STATUS_DRAFT     => [PrintOrder::STATUS_ORDERED, PrintOrder::STATUS_CANCELLED],
        PrintOrder::STATUS_ORDERED   => [PrintOrder::STATUS_PRINTING, PrintOrder::STATUS_CANCELLED],
        PrintOrder::STATUS_PRINTING  => [PrintOrder::STATUS_PRINTED, PrintOrder::STATUS_CANCELLED],
        PrintOrder::STATUS_PRINTED   => [PrintOrder::STATUS_RECEIVED],
        PrintOrder::STATUS_RECEIVED  => [],
        PrintOrder::STATUS_CANCELLED => [],
    ];

    /**
     * Create a Print Order from an Approved Print Plan. The ordered
     * quantity is always taken from the plan's final_quantity
     * (adjusted_quantity if the approver overrode it, otherwise
     * recommended_quantity) — never re-entered by hand, so the
     * approved figure and the order figure can never drift apart.
     */
    public function createFromPlan(
        PrintPlan $plan,
        int $companyId,
        int $createdBy,
        array $data,
    ): PrintOrder {
        if (! $plan->isApproved()) {
            throw new RuntimeException('Only an Approved print plan can produce a print order.');
        }

        return $this->create($plan->publication, $companyId, $createdBy, [
            ...$data,
            'print_plan_id'    => $plan->id,
            'ordered_quantity' => $plan->final_quantity,
        ]);
    }

    /**
     * Create an ad-hoc Print Order (no plan). Used when the module is
     * printing outside the planning workflow, e.g. a reprint.
     */
    public function create(
        Publication $publication,
        int $companyId,
        int $createdBy,
        array $data,
    ): PrintOrder {
        return DB::transaction(function () use ($publication, $companyId, $createdBy, $data) {
            $orderNumber = $this->nextOrderNumber($companyId);

            return PrintOrder::create([
                ...$data,
                'company_id'     => $companyId,
                'publication_id' => $publication->id,
                'order_number'   => $orderNumber,
                'status'         => PrintOrder::STATUS_DRAFT,
                'created_by'     => $createdBy,
            ]);
        });
    }

    /**
     * Draft -> Ordered. Confirms the order is placed with the press.
     */
    public function approve(PrintOrder $order): PrintOrder
    {
        return $this->transition($order, PrintOrder::STATUS_ORDERED);
    }

    public function markPrinting(PrintOrder $order): PrintOrder
    {
        return $this->transition($order, PrintOrder::STATUS_PRINTING);
    }

    public function markPrinted(PrintOrder $order, int $printedQuantity): PrintOrder
    {
        if ($printedQuantity < 0) {
            throw new RuntimeException('Printed quantity cannot be negative.');
        }

        $order = $this->transition($order, PrintOrder::STATUS_PRINTED);
        $order->update(['printed_quantity' => $printedQuantity]);

        return $order->refresh();
    }

    public function markReceived(PrintOrder $order, int $receivedQuantity): PrintOrder
    {
        if ($receivedQuantity < 0) {
            throw new RuntimeException('Received quantity cannot be negative.');
        }

        if ($receivedQuantity > $order->printed_quantity) {
            throw new RuntimeException('Received quantity cannot exceed printed quantity.');
        }

        $order = $this->transition($order, PrintOrder::STATUS_RECEIVED);
        $order->update(['received_quantity' => $receivedQuantity]);

        return $order->refresh();
    }

    public function cancel(PrintOrder $order): PrintOrder
    {
        return $this->transition($order, PrintOrder::STATUS_CANCELLED);
    }

    private function transition(PrintOrder $order, string $to): PrintOrder
    {
        $allowed = self::ALLOWED_TRANSITIONS[$order->status] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new RuntimeException("Cannot move a print order from '{$order->status}' to '{$to}'.");
        }

        $order->update(['status' => $to]);

        return $order->refresh();
    }

    /**
     * lockForUpdate()+count() numbering, matching the pattern already
     * established by PurchaseOrderController/PurchaseBillController
     * for race-safe document numbers. Must be called inside a
     * transaction (create() above already wraps it).
     */
    private function nextOrderNumber(int $companyId): string
    {
        $count = PrintOrder::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->count();

        return 'PRN-' . date('Ymd') . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
