<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Owns the lifecycle of a user's subscription: activating a plan,
 * cancelling the previous one, and preserving payment history.
 *
 * Concurrency: every write path locks the target User row
 * (`lockForUpdate`) inside a DB transaction before reading/writing
 * subscriptions, so two simultaneous "activate plan" requests for the
 * same user cannot both succeed in creating an active subscription.
 * This is the primary safeguard; the generated-column unique index on
 * the subscriptions table (MySQL only - see the migration) is a
 * secondary safety net.
 */
class SubscriptionService
{
    /**
     * Ensure the user has an active subscription, defaulting to the
     * Free plan if they have none yet. Safe to call unconditionally
     * from anywhere that needs to read the user's current plan.
     */
    public function ensureHasSubscription(User $user): Subscription
    {
        $existing = $user->activeSubscription;

        if ($existing !== null) {
            return $existing;
        }

        return $this->subscribeToFreePlan($user);
    }

    /**
     * Subscribe the user to the default (Free) plan. Idempotent: if the
     * user already has an active subscription, that subscription is
     * returned unchanged rather than creating a duplicate.
     */
    public function subscribeToFreePlan(User $user): Subscription
    {
        $freePlan = Plan::defaultPlan();

        if ($freePlan === null) {
            throw new RuntimeException(
                'No default plan is configured. Run PlanSeeder before assigning subscriptions.'
            );
        }

        return DB::transaction(function () use ($user, $freePlan) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $active = Subscription::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->first();

            if ($active !== null) {
                return $active;
            }

            return Subscription::query()->create([
                'user_id' => $lockedUser->id,
                'plan_id' => $freePlan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
            ]);
        });
    }

    /**
     * Activate the given plan for the user. Any existing active
     * subscription is cancelled (never deleted) so its payment history
     * remains intact. Runs inside a DB transaction with a row lock on
     * the user to prevent concurrent activations from both succeeding.
     *
     * @param  array{amount: float|string, currency?: string, status?: string, payment_method?: string, transaction_reference?: string, paid_at?: \DateTimeInterface|string|null, metadata?: array}|null  $paymentData
     *         Optional payment to record against the new subscription.
     */
    public function activatePlan(User $user, Plan $plan, ?array $paymentData = null): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $paymentData) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $previousActive = Subscription::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($previousActive !== null) {
                $previousActive->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);
            }

            $subscription = Subscription::query()->create([
                'user_id' => $lockedUser->id,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
            ]);

            if ($paymentData !== null) {
                SubscriptionPayment::query()->create([
                    'subscription_id' => $subscription->id,
                    'amount' => $paymentData['amount'],
                    'currency' => $paymentData['currency'] ?? 'BDT',
                    'status' => $paymentData['status'] ?? SubscriptionPayment::STATUS_PENDING,
                    'payment_method' => $paymentData['payment_method'] ?? null,
                    'transaction_reference' => $paymentData['transaction_reference'] ?? null,
                    'paid_at' => $paymentData['paid_at'] ?? null,
                    'metadata' => $paymentData['metadata'] ?? null,
                ]);
            }

            return $subscription;
        });
    }

    /**
     * Cancel the user's active subscription, if any. The row is kept
     * (status = 'cancelled'), never deleted, so payment history is
     * preserved. Returns null if the user had no active subscription.
     */
    public function cancelActiveSubscription(User $user): ?Subscription
{
    return DB::transaction(function () use ($user) {
        $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

        $active = Subscription::query()
            ->where('user_id', $lockedUser->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->lockForUpdate()
            ->first();

        if ($active === null) {
            return null; // ✅ এই line টা missing ছিল
        }

        $active->update([
            'status'       => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $active->refresh();
    });
}
    
}