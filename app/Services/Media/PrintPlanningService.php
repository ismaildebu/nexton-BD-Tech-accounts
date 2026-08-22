<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\MediaDistribution;
use App\Models\PrintPlan;
use App\Models\Publication;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * PrintPlanningService
 * -----------------------
 * All Print Planning arithmetic lives here — never in a controller or
 * a Blade view. Given a publication + a plan date, it looks at the
 * most recent confirmed MediaDistribution history for that publication
 * and produces a recommended print quantity.
 *
 * Calculation, step by step:
 *
 *   previous_distribution_quantity
 *     = total_quantity of the single most recent Confirmed distribution
 *       for this publication, strictly before plan_date. 0 if none exists.
 *
 *   average_distribution_quantity
 *     = average total_quantity across the last N Confirmed distributions
 *       before plan_date (N = config('media.print_plan_history_count')).
 *       0 if no history exists yet.
 *
 *   expected_paid_quantity
 *     = average total_paid_quantity across that same window.
 *       Bootstrap case (no history): 0 — there is nothing paid-side to
 *       extrapolate from without at least one real distribution.
 *
 *   expected_free_quantity
 *     = average total_free_quantity across that same window, when
 *       history exists.
 *       Bootstrap case (no history): expected_paid_quantity is 0 too,
 *       so this is 0 — there's nothing to apply a free % to yet.
 *
 *   expected_total_quantity = expected_paid_quantity + expected_free_quantity
 *
 *   buffer_quantity = round(expected_total_quantity * bufferPercentage / 100)
 *
 *   recommended_quantity = expected_total_quantity + buffer_quantity
 *
 * Example (matches the Phase 2 brief):
 *   expected_total_quantity = 10,952 (paid + free from history)
 *   bufferPercentage        = 5%
 *   buffer_quantity         = 548
 *   recommended_quantity    = 11,500
 *
 * A human can then approve with an adjusted_quantity of 12,000 — see
 * approve(). That final figure (adjusted_quantity ?? recommended_quantity,
 * exposed as PrintPlan::final_quantity) is what PrintOrderService reads
 * when creating the Print Order from an approved plan.
 */
final class PrintPlanningService
{
    /**
     * Pure calculation — does not touch the database for writes,
     * only reads distribution history. Returns the fields ready to
     * be merged into a new PrintPlan.
     *
     * @return array<string, int>
     */
    public function calculate(
        Publication $publication,
        CarbonInterface $planDate,
        ?float $bufferPercentage = null,
    ): array {
        $bufferPercentage ??= (float) config('media.default_buffer_percentage', 5);
        $historyCount = (int) config('media.print_plan_history_count', 7);

        $history = MediaDistribution::query()
            ->where('company_id', $publication->company_id)
            ->where('publication_id', $publication->id)
            ->where('status', MediaDistribution::STATUS_CONFIRMED)
            ->where('distribution_date', '<', $planDate->toDateString())
            ->orderByDesc('distribution_date')
            ->limit($historyCount)
            ->get(['total_quantity', 'total_paid_quantity', 'total_free_quantity', 'distribution_date']);

        $previousDistributionQuantity = (int) ($history->first()->total_quantity ?? 0);

        $averageDistributionQuantity = $history->isNotEmpty()
            ? (int) round($history->avg('total_quantity'))
            : 0;

        $expectedPaidQuantity = $history->isNotEmpty()
            ? (int) round($history->avg('total_paid_quantity'))
            : 0;

        $expectedFreeQuantity = $history->isNotEmpty()
            ? (int) round($history->avg('total_free_quantity'))
            : 0;

        $expectedTotalQuantity = $expectedPaidQuantity + $expectedFreeQuantity;

        $bufferQuantity = (int) round($expectedTotalQuantity * $bufferPercentage / 100);

        $recommendedQuantity = $expectedTotalQuantity + $bufferQuantity;

        return [
            'previous_distribution_quantity' => $previousDistributionQuantity,
            'average_distribution_quantity'  => $averageDistributionQuantity,
            'expected_paid_quantity'         => $expectedPaidQuantity,
            'expected_free_quantity'         => $expectedFreeQuantity,
            'expected_total_quantity'        => $expectedTotalQuantity,
            'buffer_quantity'                => $bufferQuantity,
            'recommended_quantity'           => $recommendedQuantity,
        ];
    }

    /**
     * Calculate + persist a new Draft PrintPlan.
     */
    public function createPlan(
        Publication $publication,
        CarbonInterface $planDate,
        int $companyId,
        int $createdBy,
        ?float $bufferPercentage = null,
    ): PrintPlan {
        $figures = $this->calculate($publication, $planDate, $bufferPercentage);

        return PrintPlan::create([
            ...$figures,
            'company_id'     => $companyId,
            'publication_id' => $publication->id,
            'plan_date'      => $planDate->toDateString(),
            'status'         => PrintPlan::STATUS_DRAFT,
            'created_by'     => $createdBy,
        ]);
    }

    /**
     * Approve a plan, optionally overriding the recommended quantity.
     * A reason is required whenever the approver's figure differs from
     * the system recommendation, so every override is auditable.
     */
    public function approve(
        PrintPlan $plan,
        User $approver,
        ?int $adjustedQuantity = null,
        ?string $adjustmentReason = null,
    ): PrintPlan {
        if (! in_array($plan->status, [PrintPlan::STATUS_DRAFT, PrintPlan::STATUS_SUBMITTED], true)) {
            throw new RuntimeException('Only a Draft or Submitted print plan can be approved.');
        }

        if ($adjustedQuantity !== null
            && $adjustedQuantity !== $plan->recommended_quantity
            && blank($adjustmentReason)
        ) {
            throw new InvalidArgumentException('An adjustment reason is required when the approved quantity differs from the recommended quantity.');
        }

        return DB::transaction(function () use ($plan, $approver, $adjustedQuantity, $adjustmentReason) {
            $plan->update([
                'adjusted_quantity'  => $adjustedQuantity,
                'adjustment_reason'  => $adjustedQuantity !== null ? $adjustmentReason : null,
                'status'             => PrintPlan::STATUS_APPROVED,
                'approved_by'        => $approver->id,
                'approved_at'        => now(),
            ]);

            return $plan->refresh();
        });
    }

    public function reject(PrintPlan $plan, User $approver, string $reason): PrintPlan
    {
        if (! in_array($plan->status, [PrintPlan::STATUS_DRAFT, PrintPlan::STATUS_SUBMITTED], true)) {
            throw new RuntimeException('Only a Draft or Submitted print plan can be rejected.');
        }

        $plan->update([
            'status'            => PrintPlan::STATUS_REJECTED,
            'adjustment_reason' => $reason,
            'approved_by'       => $approver->id,
            'approved_at'       => now(),
        ]);

        return $plan->refresh();
    }
}
