<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Services\SubscriptionService;
use Database\Seeders\PlanFeatureSeeder;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
    $this->seed(PlanFeatureSeeder::class);
});

it('reads a numeric limit from the Free plan for a fresh user', function () {
    $user = User::factory()->create();

    $limit = app(PlanLimitService::class)->limit($user, 'invoices_monthly');

    expect($limit)->toBe(30);
});

it('treats -1 as unlimited via canUse regardless of current usage', function () {
    $user = User::factory()->create();
    $plus = Plan::query()->where('key', 'plus')->firstOrFail();
    app(SubscriptionService::class)->activatePlan($user, $plus);

    $canUse = app(PlanLimitService::class)->canUse($user, 'invoices_monthly', currentUsage: 100000);

    expect($canUse)->toBeTrue();
});

it('blocks usage once the numeric limit is reached on the Free plan', function () {
    $user = User::factory()->create();
    $service = app(PlanLimitService::class);

    // Free plan: invoices_monthly = 30.
    expect($service->canUse($user, 'invoices_monthly', currentUsage: 29))->toBeTrue()
        ->and($service->canUse($user, 'invoices_monthly', currentUsage: 30))->toBeFalse();
});

it('treats a null limit_value as no numeric limit', function () {
    $user = User::factory()->create();

    // basic_reports has limit_value = null for both plans (pure toggle).
    $canUse = app(PlanLimitService::class)->canUse($user, 'basic_reports', currentUsage: 999999);

    expect($canUse)->toBeTrue();
});

it('reports boolean feature toggles correctly for Free vs Plus', function () {
    $freeUser = User::factory()->create();

    $plusUser = User::factory()->create();
    $plus = Plan::query()->where('key', 'plus')->firstOrFail();
    app(SubscriptionService::class)->activatePlan($plusUser, $plus);

    $service = app(PlanLimitService::class);

    expect($service->isEnabled($freeUser, 'advanced_reports'))->toBeFalse()
        ->and($service->isEnabled($plusUser, 'advanced_reports'))->toBeTrue()
        ->and($service->isEnabled($freeUser, 'backup'))->toBeFalse()
        ->and($service->isEnabled($plusUser, 'backup'))->toBeTrue();
});

it('reflects a plan change on the very next call (no stale caching across requests)', function () {
    $user = User::factory()->create();
    $service = app(PlanLimitService::class);

    expect($service->limit($user, 'companies'))->toBe(1);

    $plus = Plan::query()->where('key', 'plus')->firstOrFail();
    app(SubscriptionService::class)->activatePlan($user, $plus);

    // Resolve via a fresh service instance, simulating the next request.
    $freshService = app(PlanLimitService::class);
    expect($freshService->limit($user->fresh(), 'companies'))->toBe(-1);
});

it('returns false for isEnabled and true for canUse when a feature key does not exist for the plan', function () {
    $user = User::factory()->create();
    $service = app(PlanLimitService::class);

    expect($service->isEnabled($user, 'nonexistent_feature'))->toBeFalse()
        ->and($service->canUse($user, 'nonexistent_feature', currentUsage: 5))->toBeTrue();
});