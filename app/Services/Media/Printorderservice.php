<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\MediaDistribution;
use App\Models\NewspaperStockMovement;
use App\Models\PrintOrder;
use App\Models\PrintPlan;
use App\Models\Publication;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * PrintOrderService
 * -------------------
 * Handles:
 *
 * 1. Demand-driven Print Order creation
 *    Actual Distribution
 *          ↓
 *    Actual Demand
 *          ↓
 *    Buffer %
 *          ↓
 *    Buffer Quantity
 *          ↓
 *    Final Print Quantity
 *
 * 2. Legacy / optional Print Plan linked creation
 *
 * 3. Controlled Print Order status workflow
 *
 * Status workflow:
 *
 *   Draft -----> Ordered ----> Printing ----> Printed ----> Received
 *     \             \              \
 *      \-> Cancelled \-> Cancelled  \-> Cancelled
 *
 *   Received and Cancelled are terminal.
 */
final class PrintOrderService
{
    public function __construct(
        private readonly NewspaperStockService $stockService,
        private readonly MediaAccountingService $mediaAccountingService,
    ) {}

    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TRANSITIONS = [
        PrintOrder::STATUS_DRAFT => [
            PrintOrder::STATUS_ORDERED,
            PrintOrder::STATUS_CANCELLED,
        ],

        PrintOrder::STATUS_ORDERED => [
            PrintOrder::STATUS_PRINTING,
            PrintOrder::STATUS_CANCELLED,
        ],

        PrintOrder::STATUS_PRINTING => [
            PrintOrder::STATUS_PRINTED,
            PrintOrder::STATUS_CANCELLED,
        ],

        PrintOrder::STATUS_PRINTED => [
            PrintOrder::STATUS_RECEIVED,
        ],

        PrintOrder::STATUS_RECEIVED => [],

        PrintOrder::STATUS_CANCELLED => [],
    ];

    /**
     * Create a Print Order from an Approved Print Plan.
     *
     * This is kept as an optional / legacy flow.
     *
     * The normal operational flow should use create():
     *
     * Confirmed Distribution
     *       ↓
     * Demand
     *       ↓
     * Buffer
     *       ↓
     * Final Print Quantity
     */
    public function createFromPlan(
        PrintPlan $plan,
        int $companyId,
        int $createdBy,
        array $data,
    ): PrintOrder {
        if (! $plan->isApproved()) {
            throw new RuntimeException(
                'Only an Approved print plan can produce a print order.'
            );
        }

        /*
         * Company isolation.
         *
         * A Print Plan from another company must never be usable
         * for the current company.
         */
        if ((int) $plan->company_id !== $companyId) {
            throw new RuntimeException(
                'The selected print plan does not belong to the current company.'
            );
        }

        /*
         * Publication isolation.
         *
         * The publication linked to the plan must also belong
         * to the same company.
         */
        $publication = $plan->publication;

        if (! $publication) {
            throw new RuntimeException(
                'The print plan does not have a valid publication.'
            );
        }

        if ((int) $publication->company_id !== $companyId) {
            throw new RuntimeException(
                'The selected publication does not belong to the current company.'
            );
        }

        return DB::transaction(function () use (
            $plan,
            $publication,
            $companyId,
            $createdBy,
            $data
        ) {
            $orderNumber = $this->nextOrderNumber($companyId);

            $orderedQuantity = (int) $plan->final_quantity;

            if ($orderedQuantity <= 0) {
                throw new RuntimeException(
                    'Approved print plan must have a final quantity greater than zero.'
                );
            }

            return PrintOrder::create([
                ...$data,

                'company_id' => $companyId,
                'publication_id' => $publication->id,
                'print_plan_id' => $plan->id,

                'order_number' => $orderNumber,

                'ordered_quantity' => $orderedQuantity,

                /*
                 * Plan-linked orders do not use the new
                 * distribution-demand buffer calculation.
                 */
                'demand_quantity' => 0,
                'buffer_percentage' => 0,
                'buffer_quantity' => 0,
                'final_quantity' => $orderedQuantity,

                'status' => PrintOrder::STATUS_DRAFT,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Create a demand-driven Print Order.
     *
     * Operational flow:
     *
     * Latest Confirmed Distribution
     *              ↓
     *       Demand Quantity
     *              ↓
     *        Buffer Percentage
     *              ↓
     *        Buffer Quantity
     *              ↓
     *        Final Quantity
     *              ↓
     *        Print Order
     *
     * IMPORTANT:
     * Demand quantity is always calculated on the server.
     *
     * Client supplied demand_quantity / ordered_quantity /
     * final_quantity values are NOT trusted.
     */
    public function create(
        Publication $publication,
        int $companyId,
        int $createdBy,
        array $data,
    ): PrintOrder {
        /*
         * Publication company isolation.
         */
        if ((int) $publication->company_id !== $companyId) {
            throw new RuntimeException(
                'The selected publication does not belong to the current company.'
            );
        }

        /*
         * Find the latest distribution demand for this publication
         * and company. Draft and Confirmed distributions are valid
         * demand sources. Cancelled distributions are ignored.
         */
        $distribution = MediaDistribution::query()
            ->where('company_id', $companyId)
            ->where('publication_id', $publication->id)
            ->whereIn('status', [
                MediaDistribution::STATUS_DRAFT,
                MediaDistribution::STATUS_CONFIRMED,
            ])
            ->latest('distribution_date')
            ->latest('id')
            ->first();

        if (! $distribution) {
            throw new RuntimeException(
                'No distribution demand exists for this publication.'
            );
        }

        /*
         * Actual physical demand comes from the confirmed
         * distribution header.
         *
         * total_quantity =
         * paid quantity + free quantity
         */
        $demandQuantity = (int) $distribution->total_quantity;

        if ($demandQuantity <= 0) {
            throw new RuntimeException(
                'The latest distribution demand has no demand quantity.'
            );
        }

        /*
         * Buffer percentage comes from user input.
         *
         * The value is validated by StorePrintOrderRequest,
         * but we still normalize it here.
         */
        $bufferPercentage = (float) ($data['buffer_percentage'] ?? 0);

        if ($bufferPercentage < 0 || $bufferPercentage > 100) {
            throw new RuntimeException(
                'Buffer percentage must be between 0 and 100.'
            );
        }

        /*
         * Calculate buffer quantity on the server.
         *
         * Example:
         *
         * Demand = 1000
         * Buffer = 10%
         *
         * 1000 × 10 / 100 = 100
         */
        $bufferQuantity = (int) ceil(
            $demandQuantity * ($bufferPercentage / 100)
        );

        /*
         * Final Print Quantity.
         *
         * Example:
         *
         * Demand = 1000
         * Buffer = 100
         * Final  = 1100
         */
        $finalQuantity = $demandQuantity + $bufferQuantity;

        if ($finalQuantity <= 0) {
            throw new RuntimeException(
                'Final print quantity must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $publication,
            $companyId,
            $createdBy,
            $data,
            $demandQuantity,
            $bufferPercentage,
            $bufferQuantity,
            $finalQuantity
        ) {
            $orderNumber = $this->nextOrderNumber($companyId);

            return PrintOrder::create([
                /*
                 * Only safe/order-related input fields are explicitly
                 * persisted here.
                 *
                 * Client supplied quantity values are intentionally
                 * ignored and replaced with server-calculated values.
                 */
                'company_id' => $companyId,
                'publication_id' => $publication->id,

                /*
                 * Print Plan is optional in the new operational flow.
                 */
                'print_plan_id' => null,

                'vendor_id' => $data['vendor_id'] ?? null,

                'order_number' => $orderNumber,

                'order_date' => $data['order_date'],

                'print_date' => $data['print_date'] ?? null,

                /*
                 * Demand-driven quantities.
                 */
                'demand_quantity' => $demandQuantity,

                'buffer_percentage' => $bufferPercentage,

                'buffer_quantity' => $bufferQuantity,

                'final_quantity' => $finalQuantity,

                /*
                 * Existing ordered_quantity remains synchronized
                 * with the final print quantity.
                 */
                'ordered_quantity' => $finalQuantity,

                'printed_quantity' => 0,

                'received_quantity' => 0,

                'status' => PrintOrder::STATUS_DRAFT,

                'notes' => $data['notes'] ?? null,

                'created_by' => $createdBy,

                /*
                 * Printing cost information.
                 */
                'unit_printing_cost' => $data['unit_printing_cost'] ?? null,

                'total_printing_cost' => 0,
            ]);
        });
    }

    /**
     * Draft -> Ordered.
     */
    public function approve(PrintOrder $order): PrintOrder
    {
        return $this->transition(
            $order,
            PrintOrder::STATUS_ORDERED
        );
    }

    /**
     * Ordered -> Printing.
     */
    public function markPrinting(PrintOrder $order): PrintOrder
    {
        return $this->transition(
            $order,
            PrintOrder::STATUS_PRINTING
        );
    }

    /**
     * Printing -> Printed.
     */
    public function markPrinted(
        PrintOrder $order,
        int $printedQuantity
    ): PrintOrder {
        if ($printedQuantity < 0) {
            throw new RuntimeException(
                'Printed quantity cannot be negative.'
            );
        }

        /*
         * Printed quantity should not exceed ordered quantity.
         */
        if ($printedQuantity > $order->ordered_quantity) {
            throw new RuntimeException(
                'Printed quantity cannot exceed ordered quantity.'
            );
        }

        $order = $this->transition(
            $order,
            PrintOrder::STATUS_PRINTED
        );

        $order->update([
            'printed_quantity' => $printedQuantity,
        ]);

        return $order->refresh();
    }

    /**
     * Printed -> Received.
     *
     * On receipt:
     *
     * 1. Print Order becomes Received.
     * 2. Newspaper stock is increased.
     * 3. Printing Expense accounting is posted.
     *
     * Stock and accounting are part of the same database transaction.
     */
    public function markReceived(
        PrintOrder $order,
        int $receivedQuantity
    ): PrintOrder {
        if ($receivedQuantity < 0) {
            throw new RuntimeException(
                'Received quantity cannot be negative.'
            );
        }

        if ($receivedQuantity > $order->printed_quantity) {
            throw new RuntimeException(
                'Received quantity cannot exceed printed quantity.'
            );
        }

        return DB::transaction(function () use (
            $order,
            $receivedQuantity
        ) {
            $order = $this->transition(
                $order,
                PrintOrder::STATUS_RECEIVED
            );

            /*
             * Printing cost is based on actual received quantity.
             */
            $unitPrintingCost = (float) (
                $order->unit_printing_cost ?? 0
            );

            $totalPrintingCost = round(
                $receivedQuantity * $unitPrintingCost,
                2
            );

            $order->update([
                'received_quantity' => $receivedQuantity,
                'total_printing_cost' => $totalPrintingCost,
            ]);

            /*
             * Physical stock receipt.
             */
            if ($receivedQuantity > 0) {
                $this->stockService->addStock(
                    $order->publication,
                    NewspaperStockMovement::TYPE_RECEIVED,
                    $receivedQuantity,
                    $order->print_date?->toDateString()
                        ?? $order->order_date->toDateString(),
                    $order,
                    "Received from Print Order {$order->order_number}",
                    $order->created_by,
                );
            }

            /*
             * Accounting:
             *
             * Dr Printing Expense
             * Cr Vendor Payable
             *
             * total_printing_cost =
             * received_quantity × unit_printing_cost
             */
            $this->mediaAccountingService->postPrintOrderReceived(
                $order
            );

            return $order->refresh();
        });
    }

    /**
     * Cancel a Print Order.
     */
    public function cancel(PrintOrder $order): PrintOrder
    {
        return $this->transition(
            $order,
            PrintOrder::STATUS_CANCELLED
        );
    }

    /**
     * Apply a controlled status transition.
     */
    private function transition(
        PrintOrder $order,
        string $to
    ): PrintOrder {
        $allowed = self::ALLOWED_TRANSITIONS[$order->status] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new RuntimeException(
                "Cannot move a print order from '{$order->status}' to '{$to}'."
            );
        }

        $order->update([
            'status' => $to,
        ]);

        return $order->refresh();
    }

    /**
     * Generate the next company-scoped Print Order number.
     *
     * Must be called inside a transaction.
     */
    private function nextOrderNumber(int $companyId): string
    {
        $count = PrintOrder::withoutCompanyScope()
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->count();

        return 'PRN-'
            . date('Ymd')
            . '-'
            . str_pad(
                (string) ($count + 1),
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}