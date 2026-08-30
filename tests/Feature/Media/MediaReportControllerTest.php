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
            $this->admin   = $this->makeMediaAdmin();
            session(['company_id' => $this->admin->company_id]);

            $this->stock       = new NewspaperStockService();
            $this->distService = app(DistributionService::class);
            $this->retService  = app(ReturnService::class);
        });

function rPub(int $printed = 1000): Publication
{
    static $n = 0; $n++;
    $pub = Publication::create(['name' => "Paper {$n}", 'code' => "RP{$n}", 'selling_price' => 10]);
    (new NewspaperStockService())->addStock($pub, NewspaperStockMovement::TYPE_PRINTED, $printed, '2026-09-01');
    return $pub;
}

function rMParty(string $type = 'agent'): MediaParty
{
    static $n = 0; $n++;
    return MediaParty::create(['name' => "Party {$n}", 'type' => $type, 'code' => "MP{$n}", 'free_percentage' => 0]);
}

function makeAccount(int $companyId, string $name, int $code): Account
{
    return Account::create([
        'company_id'      => $companyId,
        'account_name'    => $name,
        'account_code'    => $code,
        'account_type'    => 'Asset',
        'balance_type'    => 'Debit',
        'nature'          => 'Cash',
        'level'           => 1,
        'is_system'       => false,
        'is_active'       => true,
        'opening_balance' => 0,
    ]);
}

// ─── Stock Report ─────────────────────────────────────────────────────────────

it('stock report returns 200 without filters', function () {
    $this->actingAs($this->admin)
        ->get(route('media.reports.stock'))
        ->assertOk();
});

it('stock report shows movements when publication filter applied', function () {
    $pub = rPub(500);

    $this->actingAs($this->admin)
        ->get(route('media.reports.stock', ['publication_id' => $pub->id]))
        ->assertOk()
        ->assertSee('500');
});

it('stock report running balance reflects distribution deduction', function () {
    $pub   = rPub(1000);
    $party = rMParty();

    $this->distService->create($pub, '2026-09-01', $this->admin->company_id, $this->admin->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 200, 'rate' => 5],
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('media.reports.stock', ['publication_id' => $pub->id]));

    $response->assertOk()->assertSee('800');
});

it('stock report PDF returns a PDF for a valid publication', function () {
    $pub = rPub(500);

    $response = $this->actingAs($this->admin)
        ->get(route('media.reports.stock.pdf', ['publication_id' => $pub->id]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('stock report does not show another company publication movements', function () {
    $otherCompany = $this->makeMediaCompany(['company_name' => 'Other Co']);
    session(['company_id' => $otherCompany->id]);
    $foreignPub = rPub(999);

    session(['company_id' => $this->admin->company_id]);

    $this->actingAs($this->admin)
        ->get(route('media.reports.stock', ['publication_id' => $foreignPub->id]))
        ->assertStatus(404);
});

// ─── Distribution Summary ─────────────────────────────────────────────────────

it('distribution summary returns 200', function () {
    $this->actingAs($this->admin)
        ->get(route('media.reports.distribution-summary'))
        ->assertOk();
});

it('distribution summary shows confirmed distributions in date range', function () {
    $pub   = rPub(1000);
    $party = rMParty();

    $this->distService->create($pub, '2026-09-05', $this->admin->company_id, $this->admin->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 300, 'rate' => 5],
    ]);

    $response = $this->actingAs($this->admin)->get(route('media.reports.distribution-summary', [
        'from_date' => '2026-09-01',
        'to_date'   => '2026-09-30',
    ]));

    $response->assertOk()->assertSee('300');
});

it('distribution summary PDF downloads correctly', function () {
    $pub   = rPub(500);
    $party = rMParty();

    $this->distService->create($pub, '2026-09-01', $this->admin->company_id, $this->admin->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5],
    ]);

    $response = $this->actingAs($this->admin)->get(route('media.reports.distribution-summary.pdf', [
        'from_date' => '2026-09-01',
    ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

// ─── Return Summary ───────────────────────────────────────────────────────────

it('return summary returns 200', function () {
    $this->actingAs($this->admin)
        ->get(route('media.reports.return-summary'))
        ->assertOk();
});


it('return summary shows confirmed returns', function () {
    $pub   = rPub(1000);
    $party = rMParty();

    $distribution = $this->distService->create($pub, '2026-09-01', $this->admin->company_id, $this->admin->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 200, 'rate' => 5],
    ]);

    $this->retService->create($pub, '2026-09-02', $this->admin->company_id, $this->admin->id, [
        ['media_party_id' => $party->id, 'paid_return_quantity' => 40, 'free_return_quantity' => 0],
    ], $distribution->id);

    $response = $this->actingAs($this->admin)->get(route('media.reports.return-summary', [
        'from_date' => '2026-09-01',
    ]));

    $response->assertOk()->assertSee('40');
});

// ─── Collection Summary ───────────────────────────────────────────────────────

it('collection summary returns 200', function () {
    $this->actingAs($this->admin)
        ->get(route('media.reports.collection-summary'))
        ->assertOk();
});

it('collection summary shows collections in date range', function () {
    $party   = rMParty();
    $account = makeAccount($this->admin->company_id, 'Cash', 1001);

    MediaCollection::create([
        'company_id'      => $this->admin->company_id,
        'media_party_id'  => $party->id,
        'account_id'      => $account->id,
        'collection_date' => '2026-09-05',
        'amount'          => 7500.00,
        'payment_method'  => MediaCollection::METHOD_CASH,
        'created_by'      => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->get(route('media.reports.collection-summary', [
        'from_date' => '2026-09-01',
    ]));

    $response->assertOk()->assertSee('7,500');
});

// ─── Party Ledger ─────────────────────────────────────────────────────────────

it('party ledger returns 200 without party filter', function () {
    $this->actingAs($this->admin)
        ->get(route('media.reports.party-ledger'))
        ->assertOk();
});

it('party ledger shows distribution and collection lines for the selected party', function () {
    $pub   = rPub(1000);
    $party = rMParty();

    $this->distService->create($pub, '2026-09-01', $this->admin->company_id, $this->admin->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 10],
    ]);

    $account = makeAccount($this->admin->company_id, 'Cash', 1002);

    MediaCollection::create([
        'company_id'      => $this->admin->company_id,
        'media_party_id'  => $party->id,
        'account_id'      => $account->id,
        'collection_date' => '2026-09-03',
        'amount'          => 500.00,
        'payment_method'  => MediaCollection::METHOD_CASH,
        'created_by'      => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->get(route('media.reports.party-ledger', [
        'media_party_id' => $party->id,
    ]));

    $response->assertOk()
        ->assertSee('Distribution')
        ->assertSee('Collection')
        ->assertSee('1,000')
        ->assertSee('500');
});

it('party ledger PDF downloads correctly', function () {
    $pub   = rPub(1000);
    $party = rMParty();

    $this->distService->create($pub, '2026-09-01', $this->admin->company_id, $this->admin->id, [
        ['media_party_id' => $party->id, 'paid_quantity' => 50, 'rate' => 5],
    ]);

    $response = $this->actingAs($this->admin)->get(route('media.reports.party-ledger.pdf', [
        'media_party_id' => $party->id,
    ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('party ledger blocks access without media-reports.view permission', function () {
    $user = \App\Models\User::factory()->create(['company_id' => $this->admin->company_id]);
    $user->syncRoles([]);

    $this->actingAs($user)
        ->get(route('media.reports.stock'))
        ->assertForbidden();
});

it('party ledger does not leak another company party data', function () {
    $otherCompany = $this->makeMediaCompany(['company_name' => 'Other Co 2']);
    session(['company_id' => $otherCompany->id]);
    $otherParty = rMParty();

    session(['company_id' => $this->admin->company_id]);

    $this->actingAs($this->admin)
        ->get(route('media.reports.party-ledger', ['media_party_id' => $otherParty->id]))
        ->assertStatus(404);
});