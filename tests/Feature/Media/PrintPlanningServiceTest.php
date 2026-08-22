<?php

declare(strict_types=1);

use App\Models\MediaDistribution;
use App\Models\Publication;
use App\Models\User;
use App\Services\Media\PrintPlanningService;
use Carbon\Carbon;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

beforeEach(function () {
    $this->company = $this->makeMediaCompany();
    session(['company_id' => $this->company->id]);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->publication = Publication::create([
        'name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10,
    ]);
    $this->service = new PrintPlanningService();
});

it('returns all zeros when there is no distribution history yet', function () {
    $figures = $this->service->calculate($this->publication, Carbon::parse('2026-09-01'), bufferPercentage: 5);

    expect($figures)->toBe([
        'previous_distribution_quantity' => 0,
        'average_distribution_quantity'  => 0,
        'expected_paid_quantity'         => 0,
        'expected_free_quantity'         => 0,
        'expected_total_quantity'        => 0,
        'buffer_quantity'                => 0,
        'recommended_quantity'           => 0,
    ]);
});

it('averages recent confirmed distributions and applies the buffer percentage', function () {
    // Two confirmed distributions before the plan date.
    MediaDistribution::create([
        'company_id' => $this->company->id,
        'publication_id' => $this->publication->id,
        'distribution_date' => '2026-08-30',
        'status' => MediaDistribution::STATUS_CONFIRMED,
        'total_paid_quantity' => 10000,
        'total_free_quantity' => 1000,
        'total_quantity' => 11000,
    ]);

    MediaDistribution::create([
        'company_id' => $this->company->id,
        'publication_id' => $this->publication->id,
        'distribution_date' => '2026-08-31',
        'status' => MediaDistribution::STATUS_CONFIRMED,
        'total_paid_quantity' => 10904,
        'total_free_quantity' => 1000,
        'total_quantity' => 11904,
    ]);

    // A Draft distribution must be ignored — only Confirmed counts.
    MediaDistribution::create([
        'company_id' => $this->company->id,
        'publication_id' => $this->publication->id,
        'distribution_date' => '2026-08-29',
        'status' => MediaDistribution::STATUS_DRAFT,
        'total_paid_quantity' => 99999,
        'total_free_quantity' => 99999,
        'total_quantity' => 99999,
    ]);

    $figures = $this->service->calculate($this->publication, Carbon::parse('2026-09-01'), bufferPercentage: 5);

    // previous = most recent confirmed (2026-08-31)
    expect($figures['previous_distribution_quantity'])->toBe(11904);
    // average of the two confirmed total_quantity values: (11000+11904)/2 = 11452
    expect($figures['average_distribution_quantity'])->toBe(11452);
    // average paid: (10000+10904)/2 = 10452
    expect($figures['expected_paid_quantity'])->toBe(10452);
    // average free: (1000+1000)/2 = 1000
    expect($figures['expected_free_quantity'])->toBe(1000);
    // expected total = 10452 + 1000 = 11452
    expect($figures['expected_total_quantity'])->toBe(11452);
    // buffer = round(11452 * 5%) = 573
    expect($figures['buffer_quantity'])->toBe(573);
    // recommended = 11452 + 573 = 12025
    expect($figures['recommended_quantity'])->toBe(12025);
});

it('requires an adjustment reason only when the approver overrides the recommended quantity', function () {
    $plan = $this->service->createPlan($this->publication, Carbon::parse('2026-09-01'), $this->company->id, $this->user->id, bufferPercentage: 5);

    // Approving with no override at all — no reason needed.
    $approved = $this->service->approve($plan, $this->user);
    expect($approved->status)->toBe('Approved')
        ->and($approved->adjusted_quantity)->toBeNull();
});

it('throws when overriding the recommended quantity without a reason', function () {
    $plan = $this->service->createPlan($this->publication, Carbon::parse('2026-09-01'), $this->company->id, $this->user->id, bufferPercentage: 5);

    expect(fn () => $this->service->approve($plan, $this->user, adjustedQuantity: $plan->recommended_quantity + 500))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts an override when a reason is given, and that becomes final_quantity', function () {
    $plan = $this->service->createPlan($this->publication, Carbon::parse('2026-09-01'), $this->company->id, $this->user->id, bufferPercentage: 5);

    $approved = $this->service->approve(
        $plan,
        $this->user,
        adjustedQuantity: $plan->recommended_quantity + 500,
        adjustmentReason: 'Festival edition — extra copies expected'
    );

    expect($approved->adjusted_quantity)->toBe($plan->recommended_quantity + 500)
        ->and($approved->final_quantity)->toBe($plan->recommended_quantity + 500);
});

it('will not approve a plan that is already approved', function () {
    $plan = $this->service->createPlan($this->publication, Carbon::parse('2026-09-01'), $this->company->id, $this->user->id, bufferPercentage: 5);
    $this->service->approve($plan, $this->user);

    expect(fn () => $this->service->approve($plan->fresh(), $this->user))
        ->toThrow(RuntimeException::class);
});

it('rejects a plan with a reason', function () {
    $plan = $this->service->createPlan($this->publication, Carbon::parse('2026-09-01'), $this->company->id, $this->user->id, bufferPercentage: 5);

    $rejected = $this->service->reject($plan, $this->user, 'Publication going on hold this week');

    expect($rejected->status)->toBe('Rejected')
        ->and($rejected->adjustment_reason)->toBe('Publication going on hold this week');
});
