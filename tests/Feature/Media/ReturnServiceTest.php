<?php

declare(strict_types=1);

use App\Models\MediaDistribution;
use App\Models\MediaDistributionItem;
use App\Models\MediaParty;
use App\Models\MediaReturn;
use App\Models\NewspaperStockMovement;
use App\Models\Publication;
use App\Services\Media\DistributionService;
use App\Services\Media\FreePercentageResolver;
use App\Services\Media\NewspaperStockService;
use App\Services\Media\ReturnService;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

beforeEach(function () {
    $this->company     = $this->makeMediaCompany();
    session(['company_id' => $this->company->id]);
    $this->user        = \App\Models\User::factory()->create(['company_id' => $this->company->id]);
    $this->stock       = new NewspaperStockService();
    $this->distService = new DistributionService(new FreePercentageResolver(), $this->stock);
    $this->service     = new ReturnService($this->stock);
});

// Helper: create a publication with printed stock
function pubWithStock(int $qty = 1000): Publication
{
    static $n = 0; $n++;
    $pub = Publication::create(['name' => "Paper {$n}", 'code' => "P{$n}", 'selling_price' => 10]);
    (new NewspaperStockService())->addStock($pub, NewspaperStockMovement::TYPE_PRINTED, $qty, '2026-09-01');
    return $pub;
}

// Helper: make a MediaParty
function rParty(string $type = 'agent', ?float $freePct = 0): MediaParty
{
    static $n = 0; $n++;
    return MediaParty::create(['name' => "Party {$n}", 'type' => $type, 'code' => "RP{$n}", 'free_percentage' => $freePct]);
}

// ─── Standalone return (no distribution link) ────────────────────────────────

it('records a standalone return and adds stock back', function () {
    $pub   = pubWithStock(500);
    $party = rParty();

    // First distribute some copies
    $this->distService->create($pub, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5],
    ]);
    $balanceAfterDist = $this->stock->balance($pub); // 400

    // Return 30 without linking to distribution
    $return = $this->service->create(
        $pub, '2026-09-02', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 20, 'free_return_quantity' => 10]],
    );

    expect($return->status)->toBe(MediaReturn::STATUS_CONFIRMED)
        ->and($return->total_return_quantity)->toBe(30)
        ->and($this->stock->balance($pub))->toBe($balanceAfterDist + 30);
});

it('tracks paid return and free return separately per line', function () {
    $pub   = pubWithStock(500);
    $party = rParty();

    $return = $this->service->create(
        $pub, '2026-09-01', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 15, 'free_return_quantity' => 5]],
    );

    $item = $return->items->first();

    expect($item->paid_return_quantity)->toBe(15)
        ->and($item->free_return_quantity)->toBe(5)
        ->and($item->total_return_quantity)->toBe(20);
});

// ─── Distribution-linked return ───────────────────────────────────────────────

it('links return to distribution and updates net_quantity on the distribution item', function () {
    $pub   = pubWithStock(1000);
    $party = rParty(freePct: 0);

    $dist = $this->distService->create($pub, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5],
    ]);

    $this->service->create(
        $pub, '2026-09-02', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 30, 'free_return_quantity' => 0]],
        distributionId: $dist->id,
    );

    $distItem = MediaDistributionItem::where('media_distribution_id', $dist->id)
        ->where('media_party_id', $party->id)
        ->first();

    expect($distItem->returned_quantity)->toBe(30)
        ->and($distItem->net_quantity)->toBe(70); // 100 total - 30 returned
});

it('allows returning exactly the full distributed quantity (exact return)', function () {
    $pub   = pubWithStock(1000);
    $party = rParty(freePct: 0);

    $dist = $this->distService->create($pub, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 50, 'rate' => 5],
    ]);

    $return = $this->service->create(
        $pub, '2026-09-02', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 50, 'free_return_quantity' => 0]],
        distributionId: $dist->id,
    );

    expect($return->total_return_quantity)->toBe(50);

    $distItem = MediaDistributionItem::where('media_distribution_id', $dist->id)->first();
    expect($distItem->net_quantity)->toBe(0);
});

it('rejects a return that exceeds the distribution net_quantity', function () {
    $pub   = pubWithStock(1000);
    $party = rParty(freePct: 0);

    $dist = $this->distService->create($pub, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 50, 'rate' => 5],
    ]);

    expect(fn () => $this->service->create(
        $pub, '2026-09-02', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 51, 'free_return_quantity' => 0]],
        distributionId: $dist->id,
    ))->toThrow(InvalidArgumentException::class);

    // Nothing persisted
    expect(MediaReturn::count())->toBe(0);
});

it('allows partial returns on multiple occasions, net_quantity decrements correctly each time', function () {
    $pub   = pubWithStock(1000);
    $party = rParty(freePct: 0);

    $dist = $this->distService->create($pub, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5],
    ]);

    $this->service->create(
        $pub, '2026-09-02', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 30, 'free_return_quantity' => 0]],
        distributionId: $dist->id,
    );

    $this->service->create(
        $pub, '2026-09-03', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 20, 'free_return_quantity' => 0]],
        distributionId: $dist->id,
    );

    $distItem = MediaDistributionItem::where('media_distribution_id', $dist->id)->first();

    expect($distItem->returned_quantity)->toBe(50)
        ->and($distItem->net_quantity)->toBe(50);
});

it('rejects a return for a party that was not in the linked distribution', function () {
    $pub     = pubWithStock(1000);
    $party   = rParty(freePct: 0);
    $outsider = rParty();

    $dist = $this->distService->create($pub, '2026-09-01', $this->company->id, $this->user->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5],
    ]);

    expect(fn () => $this->service->create(
        $pub, '2026-09-02', $this->company->id, $this->user->id,
        [['media_party_id' => $outsider->id, 'paid_return_quantity' => 10, 'free_return_quantity' => 0]],
        distributionId: $dist->id,
    ))->toThrow(InvalidArgumentException::class);
});

// ─── Stock movement verification ──────────────────────────────────────────────

it('writes a return stock movement and balance increases accordingly', function () {
    $pub   = pubWithStock(500);
    $party = rParty();

    $balanceBefore = $this->stock->balance($pub);

    $this->service->create(
        $pub, '2026-09-01', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 25, 'free_return_quantity' => 5]],
    );

    expect($this->stock->balance($pub))->toBe($balanceBefore + 30);

    $movement = NewspaperStockMovement::where('type', NewspaperStockMovement::TYPE_RETURN)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->quantity)->toBe(30);
});

// ─── Edge cases ───────────────────────────────────────────────────────────────

it('rejects an all-zero return', function () {
    $pub   = pubWithStock(500);
    $party = rParty();

    expect(fn () => $this->service->create(
        $pub, '2026-09-01', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 0, 'free_return_quantity' => 0]],
    ))->toThrow(InvalidArgumentException::class);

    expect(MediaReturn::count())->toBe(0);
});

it('rejects negative return quantities', function () {
    $pub   = pubWithStock(500);
    $party = rParty();

    expect(fn () => $this->service->create(
        $pub, '2026-09-01', $this->company->id, $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => -5, 'free_return_quantity' => 0]],
    ))->toThrow(InvalidArgumentException::class);
});

it('handles a multi-party return in one document', function () {
    $pub    = pubWithStock(1000);
    $party1 = rParty();
    $party2 = rParty('hawker');

    $return = $this->service->create(
        $pub, '2026-09-01', $this->company->id, $this->user->id,
        [
            ['media_party_id' => $party1->id, 'paid_return_quantity' => 10, 'free_return_quantity' => 2],
            ['media_party_id' => $party2->id, 'paid_return_quantity' => 5,  'free_return_quantity' => 1],
        ],
    );

    expect($return->items)->toHaveCount(2)
        ->and($return->total_return_quantity)->toBe(18)
        ->and($this->stock->balance($pub))->toBe(1000 + 18);
});

// ─── Company isolation ────────────────────────────────────────────────────────

it('keeps returns isolated between companies', function () {
    $pubA  = pubWithStock(500);
    $partyA = rParty();

    $this->service->create(
        $pubA, '2026-09-01', $this->company->id, $this->user->id,
        [['media_party_id' => $partyA->id, 'paid_return_quantity' => 10, 'free_return_quantity' => 0]],
    );

    $companyB = $this->makeMediaCompany(['company_name' => 'Company B']);
    session(['company_id' => $companyB->id]);
    $pubB  = pubWithStock(500);
    $partyB = rParty();
    $userB  = \App\Models\User::factory()->create(['company_id' => $companyB->id]);

    $this->service->create(
        $pubB, '2026-09-01', $companyB->id, $userB->id,
        [['media_party_id' => $partyB->id, 'paid_return_quantity' => 20, 'free_return_quantity' => 0]],
    );

    expect(MediaReturn::withoutGlobalScopes()->where('company_id', $this->company->id)->count())->toBe(1)
        ->and(MediaReturn::withoutGlobalScopes()->where('company_id', $companyB->id)->count())->toBe(1)
        ->and($this->stock->balance($pubA))->toBe(510)
        ->and($this->stock->balance($pubB))->toBe(520);
});
