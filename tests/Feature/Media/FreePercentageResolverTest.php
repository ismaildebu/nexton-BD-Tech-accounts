<?php

declare(strict_types=1);

use App\Models\MediaParty;
use App\Models\Publication;
use App\Services\Media\FreePercentageResolver;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

beforeEach(function () {
    $this->company = $this->makeMediaCompany();
    session(['company_id' => $this->company->id]);
    $this->resolver = new FreePercentageResolver();
});

it('uses the party override when one is set, even if it is exactly 0%', function () {
    $publication = Publication::create([
        'name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10,
        'default_free_percentage' => 8,
    ]);

    $party = MediaParty::create([
        'name' => 'Agent A', 'type' => 'agent', 'code' => 'AG-1',
        'free_percentage' => 0,
    ]);

    expect($this->resolver->resolve($party, $publication))->toBe(0.0)
        ->and($this->resolver->source($party, $publication))->toBe('party');
});

it('falls back to the publication default when the party has no override', function () {
    $publication = Publication::create([
        'name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10,
        'default_free_percentage' => 8.5,
    ]);

    $party = MediaParty::create([
        'name' => 'Agent A', 'type' => 'agent', 'code' => 'AG-1',
        'free_percentage' => null,
    ]);

    expect($this->resolver->resolve($party, $publication))->toBe(8.5)
        ->and($this->resolver->source($party, $publication))->toBe('publication');
});

it('falls back to the system default when neither party nor publication has one', function () {
    config(['media.default_free_percentage' => 3.25]);

    $publication = Publication::create([
        'name' => 'Daily Star', 'code' => 'DS', 'selling_price' => 10,
        'default_free_percentage' => null,
    ]);

    $party = MediaParty::create([
        'name' => 'Hawker A', 'type' => 'hawker', 'code' => 'HK-1',
        'free_percentage' => null,
    ]);

    expect($this->resolver->resolve($party, $publication))->toBe(3.25)
        ->and($this->resolver->source($party, $publication))->toBe('system');
});
