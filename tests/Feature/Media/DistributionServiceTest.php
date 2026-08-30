<?php

declare(strict_types=1);

use App\Exceptions\InsufficientNewspaperStockException;
use App\Models\MediaDistribution;
use App\Models\MediaParty;
use App\Models\NewspaperStockMovement;
use App\Models\Publication;
use App\Services\Media\DistributionService;
use App\Services\Media\FreePercentageResolver;
use App\Services\Media\NewspaperStockService;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

beforeEach(function () {
    $this->company = $this->makeMediaCompany();
    session(['company_id' => $this->company->id]);
    $this->user = \App\Models\User::factory()->create(['company_id' => $this->company->id]);

    $this->stock = new NewspaperStockService();
    $this->service = app(DistributionService::class);
});

function makeDistributionParty(array $attrs = []): MediaParty
{
    static $n = 0;
    $n++;

    return MediaParty::create([
        'name' => "Party {$n}",
        'type' => MediaParty::TYPE_AGENT,
        'code' => "P-{$n}",
        ...$attrs,
    ]);
}

it('creates a distribution for exactly one party and decrements stock accordingly', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 1000, '2026-09-01');
    $party = makeDistributionParty(['free_percentage' => 10]);

    $distribution = $this->service->create(
        $publication, '2026-09-01', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5]],
    );

    expect($distribution->status)->toBe(MediaDistribution::STATUS_CONFIRMED)
        ->and($distribution->items)->toHaveCount(1)
        ->and($distribution->items->first()->paid_quantity)->toBe(100)
        ->and($distribution->items->first()->free_quantity)->toBe(10) // 100 * 10% = 10
        ->and($distribution->items->first()->total_quantity)->toBe(110)
        ->and((float) $distribution->items->first()->amount)->toBe(500.0) // paid only, free excluded
        ->and($this->stock->balance($publication))->toBe(1000 - 110);
});

it('handles ten parties in one distribution run', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 100000, '2026-09-01');

    $items = [];
    for ($i = 0; $i < 10; $i++) {
        $party = makeDistributionParty(['free_percentage' => 5]);
        $items[] = ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5];
    }

    $distribution = $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, $items);

    expect($distribution->items)->toHaveCount(10)
        ->and($distribution->total_paid_quantity)->toBe(1000)
        ->and($distribution->total_free_quantity)->toBe(50)
        ->and($distribution->total_quantity)->toBe(1050);
});

it('handles a 100+ party distribution run without creating separate documents per party', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 100000, '2026-09-01');

    $items = [];
    for ($i = 0; $i < 150; $i++) {
        $party = makeDistributionParty();
        $items[] = ['media_party_id' => $party->id, 'paid_quantity' => 20, 'rate' => 5];
    }

    $distribution = $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, $items);

    expect(MediaDistribution::count())->toBe(1) // ONE header document
        ->and($distribution->items)->toHaveCount(150)
        ->and($distribution->total_paid_quantity)->toBe(150 * 20);
});

it('resolves free percentage independently for agent and hawker parties', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 10000, '2026-09-01');

    $agent  = makeDistributionParty(['type' => MediaParty::TYPE_AGENT, 'free_percentage' => 20]);
    $hawker = makeDistributionParty(['type' => MediaParty::TYPE_HAWKER, 'free_percentage' => 5]);

    $distribution = $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $agent->id, 'paid_quantity' => 100, 'rate' => 5],
        ['media_party_id' => $hawker->id, 'paid_quantity' => 100, 'rate' => 5],
    ]);

    $agentLine  = $distribution->items->firstWhere('media_party_id', $agent->id);
    $hawkerLine = $distribution->items->firstWhere('media_party_id', $hawker->id);

    expect($agentLine->free_quantity)->toBe(20)
        ->and($hawkerLine->free_quantity)->toBe(5);
});

it('follows the Party -> Publication -> System free percentage priority chain', function () {
    config(['media.default_free_percentage' => 2]);

    $publication = Publication::create([
        'name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10,
        'default_free_percentage' => 8,
    ]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 10000, '2026-09-01');

    $partyWithOverride = makeDistributionParty(['free_percentage' => 15]);
    $partyUsingPubDefault = makeDistributionParty(['free_percentage' => null]);

    $distribution = $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $partyWithOverride->id, 'paid_quantity' => 100, 'rate' => 5],
        ['media_party_id' => $partyUsingPubDefault->id, 'paid_quantity' => 100, 'rate' => 5],
    ]);

    expect($distribution->items->firstWhere('media_party_id', $partyWithOverride->id)->free_percentage)->toEqual(15)
        ->and($distribution->items->firstWhere('media_party_id', $partyUsingPubDefault->id)->free_percentage)->toEqual(8);
});

it('ignores a client-supplied free_percentage — it is always resolved server-side', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 10000, '2026-09-01');
    $party = makeDistributionParty(['free_percentage' => 10]);

    // Even if a caller sneaks a 'free_percentage' key into the item
    // array, DistributionService never reads it.
    $distribution = $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5, 'free_percentage' => 99],
    ]);

    expect($distribution->items->first()->free_quantity)->toBe(10);
});

it('rounds free quantity consistently: 75 paid at 10% rounds up to 8', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 10000, '2026-09-01');
    $party = makeDistributionParty(['free_percentage' => 10]);

    $distribution = $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 75, 'rate' => 5],
    ]);

    expect($distribution->items->first()->free_quantity)->toBe(8) // 7.5 -> 8
        ->and($distribution->items->first()->total_quantity)->toBe(83);
});

it('rejects the whole distribution when stock is insufficient, and creates nothing', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 50, '2026-09-01');
    $party = makeDistributionParty(['free_percentage' => 0]);

    expect(fn () => $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 51, 'rate' => 5],
    ]))->toThrow(InsufficientNewspaperStockException::class);

    expect(MediaDistribution::count())->toBe(0)
        ->and($this->stock->balance($publication))->toBe(50);
});

it('allows a distribution that consumes exactly the available stock', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 110, '2026-09-01');
    $party = makeDistributionParty(['free_percentage' => 10]);

    $distribution = $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5], // total 110
    ]);

    expect($distribution->total_quantity)->toBe(110)
        ->and($this->stock->balance($publication))->toBe(0);
});

it('rejects a distribution where every line is a zero quantity', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 1000, '2026-09-01');
    $party = makeDistributionParty(['free_percentage' => 0]);

    expect(fn () => $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 0, 'rate' => 5],
    ]))->toThrow(InvalidArgumentException::class);

    expect(MediaDistribution::count())->toBe(0);
});

it('rejects a negative paid quantity', function () {
    $publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publication, NewspaperStockMovement::TYPE_PRINTED, 1000, '2026-09-01');
    $party = makeDistributionParty();

    expect(fn () => $this->service->create($publication, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => -5, 'rate' => 5],
    ]))->toThrow(InvalidArgumentException::class);
});

it('keeps stock and distributions isolated between companies', function () {
    $publicationA = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publicationA, NewspaperStockMovement::TYPE_PRINTED, 1000, '2026-09-01');
    $partyA = makeDistributionParty();

    $this->service->create($publicationA, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $partyA->id, 'paid_quantity' => 100, 'rate' => 5],
    ]);

    $companyB = $this->makeMediaCompany(['company_name' => 'Company B']);
    session(['company_id' => $companyB->id]);
    $userB = \App\Models\User::factory()->create(['company_id' => $companyB->id]);
    $publicationB = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->stock->addStock($publicationB, NewspaperStockMovement::TYPE_PRINTED, 500, '2026-09-01');
    $partyB = makeDistributionParty();

    $this->service->create($publicationB, '2026-09-01', $companyB->id, $userB->id, [
        ['media_party_id' => $partyB->id, 'paid_quantity' => 50, 'rate' => 5],
    ]);

    expect(MediaDistribution::withoutGlobalScopes()->where('company_id', $this->company->id)->count())->toBe(1)
        ->and(MediaDistribution::withoutGlobalScopes()->where('company_id', $companyB->id)->count())->toBe(1)
        ->and($this->stock->balance($publicationA))->toBe(900)
        ->and($this->stock->balance($publicationB))->toBe(450);
});


