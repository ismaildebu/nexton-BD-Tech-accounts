<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows registration when no users exist', function () {
    $response = $this->get('/register');

    $response->assertOk();
});

it('allows the first user to register as super admin', function () {
    $response = $this->post('/register', [
        'name' => 'System Administrator',
        'email' => 'admin@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect(route('dashboard.index'));

    $user = User::query()
        ->where('email', 'admin@example.com')
        ->first();

    expect($user)->not->toBeNull();
    expect($user->role)->toBe('Admin');
    expect($user->status)->toBeTrue();
    expect($user->hasRole('super-admin'))->toBeTrue();
});

it('blocks the registration page after the first user exists', function () {
    User::factory()->create();

    $response = $this->get('/register');

    $response->assertForbidden();
});

it('blocks direct registration requests after the first user exists', function () {
    User::factory()->create();

    $response = $this->post('/register', [
        'name' => 'Unauthorized User',
        'email' => 'unauthorized@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertForbidden();

    expect(
        User::query()
            ->where('email', 'unauthorized@example.com')
            ->exists()
    )->toBeFalse();
});