<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\MediaCollection;
use App\Models\MediaDistribution;
use App\Models\MediaReturn;
use App\Models\NewspaperStockMovement;
use App\Models\Publication;
use App\Models\Transaction;
use App\Services\Media\DistributionService;
use App\Services\Media\NewspaperStockService;
use App\Services\Media\ReturnService;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

beforeEach(function () {
    $this->company = $this->makeMediaCompany();
    session(['company_id' => $this->company->id]);
    $this->user = $this->makeMediaAdmin($this->company);
    $this->stock = new NewspaperStockService();
});

function accountingPublication(int $stock = 1000): Publication
{
    static $n = 0;
    $n++;

    $publication = Publication::create([
        'name' => "Accounting Paper {$n}",
        'code' => "AP{$n}",
        'selling_price' => 10,
    ]);

    (new NewspaperStockService())->addStock(
        $publication,
        NewspaperStockMovement::TYPE_PRINTED,
        $stock,
        '2026-09-01',
    );

    return $publication;
}

function accountingParty(): \App\Models\MediaParty
{
    static $n = 0;
    $n++;

    return \App\Models\MediaParty::create([
        'name' => "Accounting Party {$n}",
        'type' => 'agent',
        'code' => "ACP{$n}",
        'free_percentage' => 0,
    ]);
}

it('posts a balanced distribution journal and links the transaction atomically', function () {
    $publication = accountingPublication();
    $party = accountingParty();

    $distribution = app(DistributionService::class)->create(
        $publication,
        '2026-09-01',
        $this->company->id,
        $this->user->id,
        [['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5]],
    );

    $distribution->refresh()->load('transaction');
    $transaction = $distribution->transaction;

    expect($transaction)->not->toBeNull()
        ->and($transaction->status)->toBe(Transaction::STATUS_POSTED)
        ->and($distribution->status)->toBe(MediaDistribution::STATUS_CONFIRMED)
        ->and((float) $transaction->total_debit)->toBe(500.0)
        ->and((float) $transaction->total_credit)->toBe(500.0)
        ->and($transaction->details)->toHaveCount(2)
        ->and($transaction->entries)->toHaveCount(2)
        ->and($distribution->transaction_id)->toBe($transaction->id)
        ->and($this->stock->balance($publication))->toBe(900);

    expect($transaction->details->sum(fn ($detail) => (float) $detail->debit_amount))
        ->toBe(500.0)
        ->and($transaction->details->sum(fn ($detail) => (float) $detail->credit_amount))
        ->toBe(500.0);
});

it('rolls back distribution and stock when accounting configuration is missing', function () {
    $publication = accountingPublication();
    $party = accountingParty();
    $publication->update(['sales_account_id' => null]);

    expect(fn () => app(DistributionService::class)->create(
        $publication,
        '2026-09-01',
        $this->company->id,
        $this->user->id,
        [['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5]],
    ))->toThrow(\InvalidArgumentException::class);

    expect(MediaDistribution::count())->toBe(0)
        ->and(Transaction::count())->toBe(0)
        ->and(LedgerEntry::count())->toBe(0)
        ->and($this->stock->balance($publication))->toBe(1000);
});

it('posts a balanced return journal and reduces party receivable', function () {
    $publication = accountingPublication();
    $party = accountingParty();

    $distribution = app(DistributionService::class)->create(
        $publication,
        '2026-09-01',
        $this->company->id,
        $this->user->id,
        [['media_party_id' => $party->id, 'paid_quantity' => 100, 'rate' => 5]],
    );

    $return = app(ReturnService::class)->create(
        $publication,
        '2026-09-02',
        $this->company->id,
        $this->user->id,
        [['media_party_id' => $party->id, 'paid_return_quantity' => 20, 'free_return_quantity' => 0]],
        $distribution->id,
    );

    $return->refresh()->load('transaction');
    $transaction = $return->transaction;

    expect($transaction)->not->toBeNull()
        ->and($transaction->status)->toBe(Transaction::STATUS_POSTED)
        ->and((float) $transaction->total_debit)->toBe(100.0)
        ->and((float) $transaction->total_credit)->toBe(100.0)
        ->and($transaction->details)->toHaveCount(2)
        ->and($return->transaction_id)->toBe($transaction->id)
        ->and($distribution->refresh()->items->first()->net_quantity)->toBe(80)
        ->and($this->stock->balance($publication))->toBe(920);
});

it('posts a balanced collection receipt journal', function () {
    $party = accountingParty();
    $cash = Account::create([
        'company_id' => $this->company->id,
        'account_code' => Account::generateNextCode(Account::TYPE_ASSET, $this->company->id),
        'account_name' => 'Media Cash',
        'account_type' => Account::TYPE_ASSET,
        'nature' => Account::NATURE_CASH,
        'level' => 1,
        'is_system' => false,
        'is_active' => true,
        'opening_balance' => 0,
        'balance_type' => Account::BALANCE_DEBIT,
    ]);

    $collection = MediaCollection::create([
        'company_id' => $this->company->id,
        'media_party_id' => $party->id,
        'account_id' => $cash->id,
        'amount' => '250.00',
        'payment_method' => MediaCollection::METHOD_CASH,
        'collection_date' => '2026-09-02',
        'created_by' => $this->user->id,
    ]);

    app(\App\Services\Media\MediaAccountingService::class)->postCollection($collection);

    $collection->refresh()->load('transaction');
    $transaction = $collection->transaction;

    expect($transaction)->not->toBeNull()
        ->and($transaction->status)->toBe(Transaction::STATUS_POSTED)
        ->and((float) $transaction->total_debit)->toBe(250.0)
        ->and((float) $transaction->total_credit)->toBe(250.0)
        ->and($transaction->details)->toHaveCount(2)
        ->and($collection->transaction_id)->toBe($transaction->id);
});
