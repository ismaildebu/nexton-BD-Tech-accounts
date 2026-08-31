<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use App\Services\PlanLimitService;

/**
 * Shared helper for controllers that must enforce a per-company plan
 * limit (e.g. invoices/month, customers, accounts) without hard-coding
 * any numbers themselves - all limits come from PlanLimitService, which
 * reads them from the active subscription's plan.
 *
 * Legacy companies created before the owner_id column existed (owner_id
 * is null) are intentionally left unrestricted, so pre-existing
 * customers are never retroactively blocked by this rebuild.
 */
trait EnforcesPlanLimits
{
    protected function enforcePlanLimit(
        PlanLimitService $planLimitService,
        ?int $companyId,
        string $featureKey,
        int $currentUsage,
    ): void {
        if ($companyId === null) {
            return;
        }

        $owner = Company::query()->find($companyId)?->owner;

        if ($owner === null) {
            return;
        }

        abort_unless(
            $planLimitService->canUse($owner, $featureKey, $currentUsage),
            403,
            "Plan limit reached for '{$featureKey}'. Please upgrade your plan to continue."
        );
    }
}