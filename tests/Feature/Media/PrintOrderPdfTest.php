<?php

declare(strict_types=1);

use App\Models\PrintOrder;
use App\Models\PrintPlan;
use App\Models\Publication;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

it('downloads a print order as a PDF', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);

    $order = PrintOrder::create([
        'company_id' => $admin->company_id,
        'publication_id' => $publication->id,
        'order_number' => 'PRN-20260901-0001',
        'order_date' => '2026-09-01',
        'ordered_quantity' => 12000,
        'status' => PrintOrder::STATUS_DRAFT,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('media.print-orders.pdf', $order));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('blocks pdf download without the media-print-orders.print permission', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);
    $admin->syncRoles([]);

    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $order = PrintOrder::create([
        'company_id' => $admin->company_id,
        'publication_id' => $publication->id,
        'order_number' => 'PRN-20260901-0002',
        'order_date' => '2026-09-01',
        'ordered_quantity' => 5000,
        'status' => PrintOrder::STATUS_DRAFT,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('media.print-orders.pdf', $order))
        ->assertForbidden();
});
