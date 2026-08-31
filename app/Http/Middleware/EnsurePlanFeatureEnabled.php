<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeatureEnabled
{
    /**
     * Block access to a route when the active company owner's plan does
     * not have the given boolean feature enabled (e.g. advanced_reports,
     * excel_export, backup, media, multi_user_permissions, priority_support).
     *
     * A legacy company with no owner_id is left unrestricted, consistent
     * with EnforcesPlanLimits used in controllers.
     *
     * Usage: ->middleware('plan-feature:advanced_reports')
     */
    public function __construct(
        private readonly PlanLimitService $planLimitService,
    ) {
    }

    
    public function handle(Request $request, Closure $next, string $featureKey): Response
        {
            /*
            * Super Admin is a global administrator and is not restricted
            * by an individual company's subscription plan.
            *
            * Other users continue to be checked against the active
            * company's owner subscription.
            */
            if ($request->user()?->isSuperAdmin()) {
                return $next($request);
            }

            $company = Company::find(session('company_id'));
            $owner = $company?->owner;

            if ($owner !== null && ! $this->planLimitService->isEnabled($owner, $featureKey)) {
                abort(
                    403,
                    "This feature ('{$featureKey}') is not available on your current plan. Please upgrade your plan."
                );
            }

            return $next($request);
        }
}