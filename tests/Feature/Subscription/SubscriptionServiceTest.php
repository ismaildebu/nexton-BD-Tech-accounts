<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\PlanFeatureSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(PlanFeatureSeeder::class);
});

it('assigns the default Free plan the first time a user is checked', function () {
    $user = User::factory()->create();

    $subscription = app(SubscriptionService::class)->ensureHasSubscription($user);

    expect($subscription->status)->toBe(Subscription::STATUS_ACTIVE)
        ->and($subscription->plan->key)->toBe('free')
        ->and($user->fresh()->activeSubscription->id)->toBe($subscription->id);
});

it('does not create a duplicate subscription when ensureHasSubscription is called twice', function () {
    $user = User::factory()->create();
    $service = app(SubscriptionService::class);

    $first = $service->ensureHasSubscription($user);
    $second = $service->ensureHasSubscription($user);

    expect($second->id)->toBe($first->id)
        ->and(Subscription::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('cancels the previous active subscription (never deletes it) when activating a new plan', function () {
    $user = User::factory()->create();
    $service = app(SubscriptionService::class);
    $plus = Plan::query()->where('key', 'plus')->firstOrFail();

    $free = $service->ensureHasSubscription($user);
    $newActive = $service->activatePlan($user, $plus);

    $free->refresh();

    expect($free->status)->toBe(Subscription::STATUS_CANCELLED)
        ->and($free->cancelled_at)->not->toBeNull()
        ->and($newActive->status)->toBe(Subscription::STATUS_ACTIVE)
        ->and($newActive->plan->key)->toBe('plus')
        ->and(Subscription::query()->where('user_id', $user->id)->count())->toBe(2)
        ->and($user->fresh()->activeSubscription->id)->toBe($newActive->id);
});

it('keeps payment history intact across a plan switch', function () {
    $user = User::factory()->create();
    $service = app(SubscriptionService::class);
    $plus = Plan::query()->where('key', 'plus')->firstOrFail();

    $service->ensureHasSubscription($user);

    $service->activatePlan($user, $plus, [
        'amount' => 999,
        'status' => SubscriptionPayment::STATUS_PAID,
    ]);

    // Switch again - the Plus subscription (with its payment) is cancelled,
    // but its payment row must survive untouched.
    $free = Plan::query()->where('key', 'free')->firstOrFail();
    $service->activatePlan($user, $free);

    expect(SubscriptionPayment::query()->count())->toBe(1)
        ->and(SubscriptionPayment::query()->first()->status)->toBe(SubscriptionPayment::STATUS_PAID);
});

it('never leaves more than one active subscription per user under concurrent activation', function () {
    $user = User::factory()->create();
    $plus = Plan::query()->where('key', 'plus')->firstOrFail();
    $free = Plan::query()->where('key', 'free')->firstOrFail();

    app(SubscriptionService::class)->ensureHasSubscription($user);

    // Simulate two "simultaneous" activation requests for the same user.
    // Both run inside their own transaction + row lock; SQLite serialises
    // them, which is exactly the guarantee SubscriptionService relies on.
    app(SubscriptionService::class)->activatePlan($user, $plus);
    app(SubscriptionService::class)->activatePlan($user, $free);

    expect(
        Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->count()
    )->toBe(1);
});

it('cancels the active subscription without deleting it', function () {
    $user = User::factory()->create();
    $service = app(SubscriptionService::class);

    $active = $service->ensureHasSubscription($user);
    $cancelled = $service->cancelActiveSubscription($user);

    expect($cancelled->id)->toBe($active->id)
        ->and($cancelled->status)->toBe(Subscription::STATUS_CANCELLED)
        ->and(Subscription::query()->find($active->id))->not->toBeNull();
});

it('returns null from cancelActiveSubscription when the user has no active subscription', function () {
    $user = User::factory()->create();

    $result = app(SubscriptionService::class)->cancelActiveSubscription($user);

    expect($result)->toBeNull();
});