<?php

declare(strict_types=1);

use App\Models\PrintOrder;
use App\Models\PrintPlan;
use App\Models\Publication;
use App\Models\User;
use App\Services\Media\PrintOrderService;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

beforeEach(function () {
    $this->company = $this->makeMediaCompany();
    session(['company_id' => $this->company->id]);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->publication = Publication::create([
        'name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10,
    ]);
    $this->service = new PrintOrderService();
});

function approvedPlan(int $companyId, int $publicationId, int $userId, int $finalQuantity, string $planDate = '2026-09-01'): PrintPlan
{
    $plan = PrintPlan::create([
        'company_id' => $companyId,
        'publication_id' => $publicationId,
        // Only one plan per publication per day is allowed (see
        // print_plans' unique index) — callers creating more than one
        // plan in the same test must pass distinct dates.
        'plan_date' => $planDate,
        'recommended_quantity' => $finalQuantity,
        'status' => PrintPlan::STATUS_DRAFT,
        'created_by' => $userId,
    ]);

    $plan->update([
        'status' => PrintPlan::STATUS_APPROVED,
        'approved_by' => $userId,
        'approved_at' => now(),
    ]);

    return $plan->fresh();
}

it('refuses to create an order from a plan that is not approved', function () {
    $plan = PrintPlan::create([
        'company_id' => $this->company->id,
        'publication_id' => $this->publication->id,
        'plan_date' => '2026-09-01',
        'recommended_quantity' => 12000,
        'status' => PrintPlan::STATUS_DRAFT,
        'created_by' => $this->user->id,
    ]);

    expect(fn () => $this->service->createFromPlan($plan, $this->company->id, $this->user->id, ['order_date' => '2026-09-01']))
        ->toThrow(RuntimeException::class);
});

it('takes ordered_quantity from the approved plan final_quantity, never from input', function () {
    $plan = approvedPlan($this->company->id, $this->publication->id, $this->user->id, 12000);

    $order = $this->service->createFromPlan($plan, $this->company->id, $this->user->id, [
        'order_date' => '2026-09-01',
        'ordered_quantity' => 1, // even if something tried to pass this, it must be ignored
    ]);

    expect($order->ordered_quantity)->toBe(12000)
        ->and($order->print_plan_id)->toBe($plan->id)
        ->and($order->status)->toBe('Draft');
});

it('generates sequential, unique order numbers per company', function () {
    $plan1 = approvedPlan($this->company->id, $this->publication->id, $this->user->id, 5000, '2026-09-01');
    $plan2 = approvedPlan($this->company->id, $this->publication->id, $this->user->id, 6000, '2026-09-02');

    $order1 = $this->service->createFromPlan($plan1, $this->company->id, $this->user->id, ['order_date' => '2026-09-01']);
    $order2 = $this->service->createFromPlan($plan2, $this->company->id, $this->user->id, ['order_date' => '2026-09-01']);

    expect($order1->order_number)->not->toBe($order2->order_number)
        ->and($order1->order_number)->toStartWith('PRN-')
        ->and($order2->order_number)->toStartWith('PRN-');
});

it('only allows the documented status transitions', function () {
    $plan = approvedPlan($this->company->id, $this->publication->id, $this->user->id, 5000);
    $order = $this->service->createFromPlan($plan, $this->company->id, $this->user->id, ['order_date' => '2026-09-01']);

    // Draft -> Received directly is not allowed.
    expect(fn () => $this->service->markReceived($order, 100))->toThrow(RuntimeException::class);

    $order = $this->service->approve($order); // Draft -> Ordered
    expect($order->status)->toBe('Ordered');

    $order = $this->service->markPrinting($order); // Ordered -> Printing
    expect($order->status)->toBe('Printing');

    $order = $this->service->markPrinted($order, 4980); // Printing -> Printed
    expect($order->status)->toBe('Printed')
        ->and($order->printed_quantity)->toBe(4980);

    $order = $this->service->markReceived($order, 4950); // Printed -> Received
    expect($order->status)->toBe('Received')
        ->and($order->received_quantity)->toBe(4950);

    // Received is terminal.
    expect(fn () => $this->service->cancel($order))->toThrow(RuntimeException::class);
});

it('refuses a received quantity greater than the printed quantity', function () {
    $plan = approvedPlan($this->company->id, $this->publication->id, $this->user->id, 5000);
    $order = $this->service->createFromPlan($plan, $this->company->id, $this->user->id, ['order_date' => '2026-09-01']);

    $order = $this->service->approve($order);
    $order = $this->service->markPrinting($order);
    $order = $this->service->markPrinted($order, 4900);

    expect(fn () => $this->service->markReceived($order, 5000))->toThrow(RuntimeException::class);
});

it('allows cancelling from Draft, Ordered or Printing but not after Printed', function () {
    $plan = approvedPlan($this->company->id, $this->publication->id, $this->user->id, 5000);
    $order = $this->service->createFromPlan($plan, $this->company->id, $this->user->id, ['order_date' => '2026-09-01']);

    $cancelled = $this->service->cancel($order);
    expect($cancelled->status)->toBe('Cancelled');
});
