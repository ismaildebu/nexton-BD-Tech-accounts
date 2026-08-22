<?php

declare(strict_types=1);

use App\Models\MediaParty;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Media\Concerns\CreatesMediaCompany;

uses(CreatesMediaCompany::class);

it('creates an agent and a hawker independently through the same endpoint', function () {
    $admin = $this->makeMediaAdmin();

    $this->actingAs($admin)->post(route('media.parties.store'), [
        'name' => 'Agent One', 'type' => 'agent', 'code' => 'AG-001',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('media.parties.store'), [
        'name' => 'Hawker One', 'type' => 'hawker', 'code' => 'HK-001',
    ])->assertRedirect();

    expect(MediaParty::agents()->count())->toBe(1)
        ->and(MediaParty::hawkers()->count())->toBe(1);
});

it('has no schema relationship linking an agent to a hawker', function () {
    // Structural guarantee: the media_parties table has no
    // agent_id/hawker_id/parent_id column at all.
    $columns = Schema::getColumnListing('media_parties');

    expect($columns)->not->toContain('agent_id')
        ->and($columns)->not->toContain('hawker_id')
        ->and($columns)->not->toContain('parent_id');
});

it('rejects an invalid party type', function () {
    $admin = $this->makeMediaAdmin();

    $this->actingAs($admin)->post(route('media.parties.store'), [
        'name' => 'Bad Party', 'type' => 'distributor', 'code' => 'X-1',
    ])->assertSessionHasErrors('type');
});

it('respects a 0% free percentage override as distinct from no override', function () {
    $admin = $this->makeMediaAdmin();
    session(['company_id' => $admin->company_id]);

    $party = MediaParty::create([
        'name' => 'Agent Zero Free', 'type' => 'agent', 'code' => 'AG-ZERO',
        'free_percentage' => 0,
    ]);

    expect($party->free_percentage)->toEqual(0)
        ->and($party->free_percentage)->not->toBeNull();

    $partyNoOverride = MediaParty::create([
        'name' => 'Agent No Override', 'type' => 'agent', 'code' => 'AG-NONE',
    ]);

    expect($partyNoOverride->free_percentage)->toBeNull();
});
