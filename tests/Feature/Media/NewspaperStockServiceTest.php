<?php

declare(strict_types=1);

use App\Exceptions\InsufficientNewspaperStockException;
use App\Models\NewspaperStockMovement;
use App\Models\Publication;
use App\Services\Media\NewspaperStockService;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

beforeEach(function () {
    $this->company = $this->makeMediaCompany();
    session(['company_id' => $this->company->id]);
    $this->publication = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->service = new NewspaperStockService();
});

it('starts a publication at zero balance', function () {
    expect($this->service->balance($this->publication))->toBe(0);
});

it('adds opening, printed and received stock', function () {
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_OPENING, 500, '2026-09-01');
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, 10000, '2026-09-01');
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_RECEIVED, 200, '2026-09-01');

    expect($this->service->balance($this->publication))->toBe(10700);
});

it('removes stock for a distribution when enough is available', function () {
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, 1000, '2026-09-01');

    $movement = $this->service->removeStock($this->publication, NewspaperStockMovement::TYPE_DISTRIBUTION, 400, '2026-09-01');

    expect($movement->quantity)->toBe(-400)
        ->and($this->service->balance($this->publication))->toBe(600);
});

it('allows removing exactly the full available balance (exact stock)', function () {
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, 500, '2026-09-01');

    $this->service->removeStock($this->publication, NewspaperStockMovement::TYPE_DISTRIBUTION, 500, '2026-09-01');

    expect($this->service->balance($this->publication))->toBe(0);
});

it('rejects removing more stock than is available and prevents negative stock', function () {
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, 100, '2026-09-01');

    expect(fn () => $this->service->removeStock($this->publication, NewspaperStockMovement::TYPE_DISTRIBUTION, 101, '2026-09-01'))
        ->toThrow(InsufficientNewspaperStockException::class);

    // Balance must be untouched — the rejected attempt wrote nothing.
    expect($this->service->balance($this->publication))->toBe(100);
});

it('never persists a movement when removeStock fails', function () {
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, 50, '2026-09-01');

    try {
        $this->service->removeStock($this->publication, NewspaperStockMovement::TYPE_DISTRIBUTION, 999, '2026-09-01');
    } catch (InsufficientNewspaperStockException) {
        // expected
    }

    expect(NewspaperStockMovement::where('type', NewspaperStockMovement::TYPE_DISTRIBUTION)->count())->toBe(0);
});

it('treats a return as adding stock back', function () {
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, 100, '2026-09-01');
    $this->service->removeStock($this->publication, NewspaperStockMovement::TYPE_DISTRIBUTION, 100, '2026-09-01');
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_RETURN, 20, '2026-09-02');

    expect($this->service->balance($this->publication))->toBe(20);
});

it('supports positive and negative adjustments, both subject to the negative-stock rule', function () {
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, 100, '2026-09-01');

    $this->service->adjust($this->publication, 10, '2026-09-01', 'found extra bundle');
    expect($this->service->balance($this->publication))->toBe(110);

    $this->service->adjust($this->publication, -50, '2026-09-01', 'damaged in transit');
    expect($this->service->balance($this->publication))->toBe(60);

    expect(fn () => $this->service->adjust($this->publication, -1000, '2026-09-01'))
        ->toThrow(InsufficientNewspaperStockException::class);
});

it('rejects zero/invalid quantities', function () {
    expect(fn () => $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, 0, '2026-09-01'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, -5, '2026-09-01'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $this->service->removeStock($this->publication, NewspaperStockMovement::TYPE_DISTRIBUTION, 0, '2026-09-01'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $this->service->adjust($this->publication, 0, '2026-09-01'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects an unknown movement type', function () {
    expect(fn () => $this->service->addStock($this->publication, 'not-a-real-type', 10, '2026-09-01'))
        ->toThrow(InvalidArgumentException::class);
});

it('keeps stock balances isolated per company for the same-named publication', function () {
    $companyB = $this->makeMediaCompany(['company_name' => 'Other Media House']);

    session(['company_id' => $companyB->id]);
    $publicationB = Publication::create(['name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10]);
    $this->service->addStock($publicationB, NewspaperStockMovement::TYPE_PRINTED, 5000, '2026-09-01');

    session(['company_id' => $this->company->id]);
    $this->service->addStock($this->publication, NewspaperStockMovement::TYPE_PRINTED, 100, '2026-09-01');

    expect($this->service->balance($this->publication))->toBe(100)
        ->and($this->service->balance($publicationB))->toBe(5000);
});
