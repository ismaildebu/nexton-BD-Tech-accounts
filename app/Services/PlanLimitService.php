<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanFeature;
use App\Models\User;

/**
 * Single source of truth for reading plan limits/toggles.
 *
 * Controllers must never hard-code limits - they call this service
 * instead, which reads everything from the plan_features table via the
 * user's active subscription (auto-provisioning the Free plan if the
 * user somehow has none yet).
 *
 * Limit semantics:
 *   limit_value = null  -> feature has no numeric limit
 *   limit_value = -1    -> unlimited
 *   limit_value = N     -> maximum allowed quantity
 */
class PlanLimitService
{
    /**
     * Per-request memoization: user_id => [feature_key => PlanFeature].
     * Deliberately request-lifecycle only (not cached across requests),
     * so a plan change is reflected immediately on the next request.
     *
     * @var array<int, array<string, PlanFeature|null>>
     */
    private array $resolved = [];

    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    /**
     * The maximum allowed quantity for the given feature under the
     * user's current plan, or null if the feature has no numeric limit.
     * Callers must separately check for -1 (unlimited) before treating
     * the return value as a hard cap - see canUse() for that logic.
     */
    public function limit(User $user, string $featureKey): ?int
    {
        return $this->resolveFeature($user, $featureKey)?->limit_value;
    }

    /**
     * Whether the given feature is turned on for the user's current
     * plan (used for boolean toggles like backup, media, pdf_export).
     */
    public function isEnabled(User $user, string $featureKey): bool
    {
        return $this->resolveFeature($user, $featureKey)?->is_enabled ?? false;
    }

    /**
     * Whether the user can use one more unit of the given feature,
     * given their current usage count.
     *
     * - limit is null (no numeric cap)  -> true
     * - limit is -1 (unlimited)         -> true
     * - otherwise                       -> currentUsage < limit
     *
     * Note: this only checks the numeric limit. For pure on/off
     * features, check isEnabled() as well (or instead).
     */
    public function canUse(User $user, string $featureKey, int $currentUsage = 0): bool
    {
        $limit = $this->limit($user, $featureKey);

        if ($limit === null) {
            return true;
        }

        if ($limit === -1) {
            return true;
        }

        return $currentUsage < $limit;
    }

    /**
     * Resolve the PlanFeature row for a given user + feature key,
     * auto-provisioning the Free plan if the user has no subscription
     * yet. Returns null if the plan has no row for that feature key
     * (treated as "no limit / not enabled" by the public methods above).
     */
    private function resolveFeature(User $user, string $featureKey): ?PlanFeature
    {
        if (array_key_exists($featureKey, $this->resolved[$user->id] ?? [])) {
            return $this->resolved[$user->id][$featureKey];
        }

        $subscription = $this->subscriptionService->ensureHasSubscription($user);

        $feature = PlanFeature::query()
            ->where('plan_id', $subscription->plan_id)
            ->where('feature_key', $featureKey)
            ->first();

        $this->resolved[$user->id][$featureKey] = $feature;

        return $feature;
    }
}