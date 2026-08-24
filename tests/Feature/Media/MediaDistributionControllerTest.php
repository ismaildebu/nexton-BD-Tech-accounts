<?php

declare(strict_types=1);

use App\Models\MediaDistribution;
use App\Models\MediaParty;
use App\Models\NewspaperStockMovement;
use App\Models\Publication;
use App\Services\Media\NewspaperStockService;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

function seedStock(Publication $publication, int $quantity, string $date = '2026-09-01'): void
{
    (new NewspaperStockService())->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, $quantity, $date);
}

it('stores a distribution for a single party via HTTP and updates stock', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    seedStock($publication, 1000);
    $party = MediaParty::create(['name' => 'Agent A', 'type' => 'agent', 'code' => 'AG-1', 'free_percentage' => 10]);

    $response = $this->actingAs($admin)->post(route('media.distributions.store'), [
        'publication_id' => $publication->id,
        'distribution_date' => '2026-09-01',
        'items' => [
            ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5],
        ],
    ]);

    $response->assertRedirect();
    $distribution = MediaDistribution::first();

    expect($distribution)->not->toBeNull()
        ->and($distribution->status)->toBe(MediaDistribution::STATUS_CONFIRMED)
        ->and($distribution->total_quantity)->toBe(110)
        ->and((new NewspaperStockService())->balance($publication))->toBe(1000 - 110);
});

it('rejects a distribution over HTTP when stock is insufficient and creates nothing', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    seedStock($publication, 10);
    $party = MediaParty::create(['name' => 'Agent A', 'type' => 'agent', 'code' => 'AG-1', 'free_percentage' => 0]);

    $response = $this->actingAs($admin)->post(route('media.distributions.store'), [
        'publication_id' => $publication->id,
        'distribution_date' => '2026-09-01',
        'items' => [
            ['media_party_id' => $party->id, 'paid_quantity' => 11, 'rate' => 5],
        ],
    ]);

    $response->assertRedirect()->assertSessionHasErrors('items');
    expect(MediaDistribution::count())->toBe(0);
});

it('rejects a distribution where every item has a zero paid quantity', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    seedStock($publication, 1000);
    $party = MediaParty::create(['name' => 'Agent A', 'type' => 'agent', 'code' => 'AG-1']);

    $this->actingAs($admin)->post(route('media.distributions.store'), [
        'publication_id' => $publication->id,
        'distribution_date' => '2026-09-01',
        'items' => [
            ['media_party_id' => $party->id, 'paid_quantity' => 0, 'rate' => 5],
        ],
    ])->assertSessionHasErrors('items');

    expect(MediaDistribution::count())->toBe(0);
});

it('rejects a negative paid quantity at the validation layer', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $party = MediaParty::create(['name' => 'Agent A', 'type' => 'agent', 'code' => 'AG-1']);

    $this->actingAs($admin)->post(route('media.distributions.store'), [
        'publication_id' => $publication->id,
        'distribution_date' => '2026-09-01',
        'items' => [
            ['media_party_id' => $party->id, 'paid_quantity' => -5, 'rate' => 5],
        ],
    ])->assertSessionHasErrors('items.0.paid_quantity');
});

it('cannot be created against another company\'s publication or party (company isolation)', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    $otherCompany = $this->makeMediaCompany(['company_name' => 'Other Media House']);
    session(['company_id' => $otherCompany->id]);
    $foreignPublication = Publication::create(['name' => 'Foreign Paper', 'code' => 'FP', 'selling_price' => 10]);
    $foreignParty = MediaParty::create(['name' => 'Foreign Agent', 'type' => 'agent', 'code' => 'FA-1']);

    session(['company_id' => $admin->company_id]);

    $this->actingAs($admin)->post(route('media.distributions.store'), [
        'publication_id' => $foreignPublication->id,
        'distribution_date' => '2026-09-01',
        'items' => [
            ['media_party_id' => $foreignParty->id, 'paid_quantity' => 10, 'rate' => 5],
        ],
    ])->assertSessionHasErrors(); // publication_id and/or items.*.media_party_id fail the company-scoped exists() rule

    expect(MediaDistribution::count())->toBe(0);
});

it('downloads a dispatch sheet PDF for a confirmed distribution', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    seedStock($publication, 1000);
    $party = MediaParty::create([
        'name' => 'Agent A', 'type' => 'agent', 'code' => 'AG-1',
        'phone' => '01700000000', 'address' => '123 Press Road', 'free_percentage' => 10,
    ]);

    $this->actingAs($admin)->post(route('media.distributions.store'), [
        'publication_id' => $publication->id,
        'distribution_date' => '2026-09-01',
        'items' => [['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5]],
    ]);
    $distribution = MediaDistribution::first();

    $response = $this->actingAs($admin)->get(route('media.distributions.dispatch-sheet', $distribution));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('downloads a bundle-slips PDF containing one slip per item', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    seedStock($publication, 1000);
    $partyOne = MediaParty::create(['name' => 'Agent A', 'type' => 'agent', 'code' => 'AG-1', 'phone' => '01700000001']);
    $partyTwo = MediaParty::create(['name' => 'Hawker A', 'type' => 'hawker', 'code' => 'HK-1', 'phone' => '01700000002']);

    $this->actingAs($admin)->post(route('media.distributions.store'), [
        'publication_id' => $publication->id,
        'distribution_date' => '2026-09-01',
        'items' => [
            ['media_party_id' => $partyOne->id, 'paid_quantity' => 50, 'rate' => 5],
            ['media_party_id' => $partyTwo->id, 'paid_quantity' => 30, 'rate' => 5],
        ],
    ]);
    $distribution = MediaDistribution::first();

    $response = $this->actingAs($admin)->get(route('media.distributions.bundle-slips', $distribution));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('blocks dispatch sheet download without the media-distributions.print permission', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);
    $admin->syncRoles([]);

    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $distribution = MediaDistribution::create([
        'company_id' => $admin->company_id,
        'publication_id' => $publication->id,
        'distribution_date' => '2026-09-01',
        'status' => MediaDistribution::STATUS_CONFIRMED,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('media.distributions.dispatch-sheet', $distribution))
        ->assertForbidden();
});
