<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\MediaCollection;
use App\Models\MediaDistribution;
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
    $this->company = $this->makeMediaCompany();
    session(['company_id' => $this->company->id]);
    $this->admin   = $this->makeMediaAdmin();
    session(['company_id' => $this->admin->company_id]);

    $this->stock = new NewspaperStockService();
});

function makePub(int $stock = 1000): Publication
{
    static $n = 0; $n++;
    $pub = Publication::create(['name' => "Paper {$n}", 'code' => "C{$n}", 'selling_price' => 10]);
    (new NewspaperStockService())->addStock($pub, NewspaperStockMovement::TYPE_PRINTED, $stock, '2026-09-01');
    return $pub;
}

function makeParty(string $type = 'agent'): MediaParty
{
    static $n = 0; $n++;
    return MediaParty::create(['name' => "Party {$n}", 'type' => $type, 'code' => "CP{$n}", 'free_percentage' => 0]);
}

// ─── MediaReturnController ────────────────────────────────────────────────────

it('stores a return via HTTP and stock increases', function () {
    $pub   = makePub(500);
    $party = makeParty();

    $balBefore = $this->stock->balance($pub);

    $this->actingAs($this->admin)->post(route('media.returns.store'), [
        'publication_id' => $pub->id,
        'return_date'    => '2026-09-02',
        'items'          => [
            // Standalone return: only free returns (no distribution needed)
            ['media_party_id' => $party->id, 'paid_return_quantity' => 0, 'free_return_quantity' => 25],
        ],
    ])->assertRedirect();

    expect(MediaReturn::count())->toBe(1)
        ->and($this->stock->balance($pub))->toBe($balBefore + 25);
});

it('rejects an all-zero return via HTTP with validation error', function () {
    $pub   = makePub();
    $party = makeParty();

    $this->actingAs($this->admin)->post(route('media.returns.store'), [
        'publication_id' => $pub->id,
        'return_date'    => '2026-09-02',
        'items'          => [
            ['media_party_id' => $party->id, 'paid_return_quantity' => 0, 'free_return_quantity' => 0],
        ],
    ])->assertSessionHasErrors('items');

    expect(MediaReturn::count())->toBe(0);
});

it('rejects a negative return quantity at the HTTP validation layer', function () {
    $pub   = makePub();
    $party = makeParty();

    $this->actingAs($this->admin)->post(route('media.returns.store'), [
        'publication_id' => $pub->id,
        'return_date'    => '2026-09-02',
        'items'          => [
            ['media_party_id' => $party->id, 'paid_return_quantity' => -5, 'free_return_quantity' => 0],
        ],
    ])->assertSessionHasErrors('items.0.paid_return_quantity');
});

it('rejects return that exceeds distribution net_quantity via HTTP', function () {
    $pub   = makePub(1000);
    $party = makeParty();

    $dist = app(DistributionService::class)
        ->create($pub, '2026-09-01', $this->admin->company_id, $this->admin->id, [
            ['media_party_id' => $party->id, 'paid_quantity' => 50, 'rate' => 5],
        ]);

    $this->actingAs($this->admin)->post(route('media.returns.store'), [
        'publication_id'        => $pub->id,
        'media_distribution_id' => $dist->id,
        'return_date'           => '2026-09-02',
        'items'                 => [
            ['media_party_id' => $party->id, 'paid_return_quantity' => 51, 'free_return_quantity' => 0],
        ],
    ])->assertRedirect()->assertSessionHasErrors('items');

    expect(MediaReturn::count())->toBe(0);
});

it('cannot return against another company distribution (company isolation)', function () {
    $otherCompany = $this->makeMediaCompany(['company_name' => 'Other Co']);
    session(['company_id' => $otherCompany->id]);
    $pubOther   = makePub(500);
    $partyOther = makeParty();
    $otherUser  = \App\Models\User::factory()->create(['company_id' => $otherCompany->id]);

    $distOther = app(DistributionService::class)
        ->create($pubOther, '2026-09-01', $otherCompany->id, $otherUser->id, [
            ['media_party_id' => $partyOther->id, 'paid_quantity' => 50, 'rate' => 5],
        ]);

    session(['company_id' => $this->admin->company_id]);
    $myPub = makePub();

    $this->actingAs($this->admin)->post(route('media.returns.store'), [
        'publication_id'        => $myPub->id,
        'media_distribution_id' => $distOther->id, // foreign company's dist
        'return_date'           => '2026-09-02',
        'items'                 => [
            ['media_party_id' => $partyOther->id, 'paid_return_quantity' => 10, 'free_return_quantity' => 0],
        ],
    ])->assertSessionHasErrors();

    expect(MediaReturn::withoutGlobalScopes()->where('company_id', $this->admin->company_id)->count())->toBe(0);
});

// ─── MediaCollectionController ────────────────────────────────────────────────

it('stores a collection with payment_method via HTTP', function () {
    $party   = makeParty();
    $account = Account::create([
        'company_id' => $this->admin->company_id,
        'account_name' => 'Cash in Hand',
        'code'       => 'CASH01',
        'account_type' => 'Asset',
    ]);

    $this->actingAs($this->admin)->post(route('media.collections.store'), [
        'media_party_id'  => $party->id,
        'account_id'      => $account->id,
        'amount'          => 5000.00,
        'payment_method'  => MediaCollection::METHOD_CASH,
        'collection_date' => '2026-09-01',
    ])->assertRedirect();

    expect(MediaCollection::count())->toBe(1);
    $collection = MediaCollection::first();
    expect((float) $collection->amount)->toBe(5000.0)
        ->and($collection->payment_method)->toBe(MediaCollection::METHOD_CASH);
});

it('rejects a collection without a payment method', function () {
    $party   = makeParty();
    $account = Account::create([
        'company_id' => $this->admin->company_id,
        'account_name' => 'Cash in Hand',
        'code'       => 'CASH02',
        'account_type' => 'Asset',
    ]);

    $this->actingAs($this->admin)->post(route('media.collections.store'), [
        'media_party_id'  => $party->id,
        'account_id'      => $account->id,
        'amount'          => 1000.00,
        'collection_date' => '2026-09-01',
        // payment_method intentionally missing
    ])->assertSessionHasErrors('payment_method');

    expect(MediaCollection::count())->toBe(0);
});

it('rejects a zero or negative collection amount', function () {
    $party   = makeParty();
    $account = Account::create([
        'company_id' => $this->admin->company_id,
        'account_name' => 'Cash in Hand',
        'code'       => 'CASH03',
        'account_type' => 'Asset',
    ]);

    $this->actingAs($this->admin)->post(route('media.collections.store'), [
        'media_party_id'  => $party->id,
        'account_id'      => $account->id,
        'amount'          => 0,
        'payment_method'  => MediaCollection::METHOD_CASH,
        'collection_date' => '2026-09-01',
    ])->assertSessionHasErrors('amount');
});

it('cannot collect against another company party (company isolation)', function () {
    $otherCompany = $this->makeMediaCompany(['company_name' => 'Other Co 2']);
    session(['company_id' => $otherCompany->id]);
    $foreignParty = makeParty();

    session(['company_id' => $this->admin->company_id]);
    $account = Account::create([
        'company_id' => $this->admin->company_id,
        'account_name' => 'Cash in Hand',
        'code'       => 'CASH04',
        'account_type' => 'Asset',
    ]);

    $this->actingAs($this->admin)->post(route('media.collections.store'), [
        'media_party_id'  => $foreignParty->id,
        'account_id'      => $account->id,
        'amount'          => 1000,
        'payment_method'  => MediaCollection::METHOD_CASH,
        'collection_date' => '2026-09-01',
    ])->assertSessionHasErrors('media_party_id');

    expect(MediaCollection::count())->toBe(0);
});

it('accepts all valid payment methods', function () {
    $party   = makeParty();
    $account = Account::create([
        'company_id' => $this->admin->company_id,
        'account_name' => 'Bank Account',
        'code'       => 'BANK01',
        'account_type' => 'Asset',
    ]);

    $methods = [
        MediaCollection::METHOD_CASH,
        MediaCollection::METHOD_BANK,
        MediaCollection::METHOD_MOBILE_BANKING,
        MediaCollection::METHOD_CHEQUE,
        MediaCollection::METHOD_OTHER,
    ];

    foreach ($methods as $method) {
        $this->actingAs($this->admin)->post(route('media.collections.store'), [
            'media_party_id'  => $party->id,
            'account_id'      => $account->id,
            'amount'          => 500,
            'payment_method'  => $method,
            'collection_date' => '2026-09-01',
        ])->assertRedirect();
    }

    expect(MediaCollection::count())->toBe(count($methods));
});



