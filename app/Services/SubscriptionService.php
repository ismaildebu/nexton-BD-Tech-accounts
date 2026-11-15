<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionService
{
    public function ensureHasSubscription(User $user): Subscription
    {
        return $user->activeSubscription ?? $this->subscribeToFreePlan($user);
    }

    public function subscribeToFreePlan(User $user): Subscription
    {
        $freePlan = Plan::defaultPlan();
        if ($freePlan === null) {
            throw new RuntimeException('No default plan is configured. Run PlanSeeder first.');
        }

        return DB::transaction(function () use ($user, $freePlan): Subscription {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $active = $this->activeForUser($lockedUser->id, true);
            if ($active !== null) {
                return $active;
            }

            $subscription = Subscription::query()->create([
                'user_id' => $lockedUser->id,
                'plan_id' => $freePlan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
            ]);
       
            return $subscription;
        });
    }

    /** Create a paid checkout without changing the current active plan. */
    public function createPendingPlan(User $user, Plan $plan): Subscription
    {
        if ((float) $plan->price <= 0.0) {
            return $this->activatePlan($user, $plan);
        }

        return DB::transaction(function () use ($user, $plan): Subscription {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $existing = Subscription::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', Subscription::STATUS_PENDING)
                ->where('plan_id', $plan->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            $subscription = Subscription::query()->create([
                'user_id' => $lockedUser->id,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'starts_at' => null,
                'metadata' => ['checkout_started_at' => now()->toIso8601String()],
            ]);
            $subscription->payments()->create([
                'amount' => number_format((float) $plan->price, 2, '.', ''),
                'currency' => 'BDT',
                'status' => SubscriptionPayment::STATUS_PENDING,
                'metadata' => ['plan_type' => 'paid'],
            ]);
            return $subscription;
        });
    }

    /** Activate only after a gateway-verified payment. */
    public function activatePendingSubscription(Subscription $pending): Subscription
    {
        return DB::transaction(function () use ($pending): Subscription {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($pending->id);
            if ($locked->status === Subscription::STATUS_ACTIVE) {
                return $locked;
            }
            if ($locked->status !== Subscription::STATUS_PENDING) {
                throw new RuntimeException('Only a pending subscription can be activated.');
            }

            $active = $this->activeForUser($locked->user_id, true);
            $active?->update([
                'status' => Subscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
            $locked->update([
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'cancelled_at' => null,
            ]);
            return $locked->refresh();
        });
    }

    /** Legacy API: free plans activate immediately; paid plans must use pending checkout. */
    public function activatePlan(User $user, Plan $plan, ?array $paymentData = null): Subscription
    {
        if ((float) $plan->price > 0.0 && $paymentData === null) {
            throw new RuntimeException('Paid plans must be activated only after verified payment.');
        }

        return DB::transaction(function () use ($user, $plan, $paymentData): Subscription {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $active = $this->activeForUser($lockedUser->id, true);
            $active?->update(['status' => Subscription::STATUS_CANCELLED, 'cancelled_at' => now()]);
           
            $subscription = Subscription::query()->create([
                'user_id' => $lockedUser->id,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
            ]);
          
          if ($paymentData !== null) {
             $subscription->payments()->create($paymentData);
        }

            return $subscription;
            });
            
    }

    public function cancelActiveSubscription(User $user): ?Subscription
    {
        return DB::transaction(function () use ($user): ?Subscription {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $active = $this->activeForUser($lockedUser->id, true);
            if ($active === null) {
                return null;
            }
            $active->update(['status' => Subscription::STATUS_CANCELLED, 'cancelled_at' => now()]);
            return $active->refresh();
        });
    }

    private function activeForUser(int $userId, bool $lock = false): ?Subscription
    {
        $query = Subscription::query()
            ->where('user_id', $userId)
            ->where('status', Subscription::STATUS_ACTIVE);
        if ($lock) {
            $query->lockForUpdate();
        }
        return $query->first();
    }
}
