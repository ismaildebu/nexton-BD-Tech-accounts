<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);

    // Make Plus a real paid plan for these tests.
    Plan::query()->where('key', 'plus')->update(['price' => 500]);

    $this->company = Company::factory()->create(['status' => true]);

    // A regular company admin: has the generic `admin` role (like every self-signup).
    $this->companyAdmin = User::factory()->create(['company_id' => $this->company->id]);
    $this->companyAdmin->assignRole('admin');

    $this->superAdmin = User::factory()->create(['company_id' => null]);
    $this->superAdmin->assignRole('super-admin');

    // Pending paid checkout owned by the company admin.
    $service = app(SubscriptionService::class);
    $this->free = $service->subscribeToFreePlan($this->companyAdmin);
    $plus = Plan::query()->where('key', 'plus')->firstOrFail();
    $this->pendingSubscription = $service->createPendingPlan($this->companyAdmin, $plus);
    $this->payment = $this->pendingSubscription->payments()->firstOrFail();
});

it('redirects guests to login', function () {
    $this->get('/admin/payments')->assertRedirect(route('login'));
    $this->post("/admin/payments/{$this->payment->id}/verify")->assertRedirect(route('login'));
});

it('forbids a company admin from every platform payment screen and action', function () {
    $this->actingAs($this->companyAdmin);

    $this->get('/admin/payments')->assertForbidden();
    $this->get("/admin/payments/{$this->payment->id}")->assertForbidden();
    $this->get("/admin/users/{$this->superAdmin->id}/payments")->assertForbidden();
    $this->post("/admin/payments/{$this->payment->id}/verify")->assertForbidden();
    $this->post("/admin/payments/{$this->payment->id}/reject")->assertForbidden();

    // Nothing changed: no free upgrade.
    expect($this->payment->refresh()->status)->toBe(SubscriptionPayment::STATUS_PENDING)
        ->and($this->pendingSubscription->refresh()->status)->toBe(Subscription::STATUS_PENDING)
        ->and($this->free->refresh()->status)->toBe(Subscription::STATUS_ACTIVE);
});

it('forbids a plain company user from verifying payments', function () {
    $user = User::factory()->create(['company_id' => $this->company->id]);

    $this->actingAs($user)
        ->post("/admin/payments/{$this->payment->id}/verify")
        ->assertForbidden();

    expect($this->payment->refresh()->status)->toBe(SubscriptionPayment::STATUS_PENDING);
});

it('lets the super admin open the payment dashboard', function () {
    $this->actingAs($this->superAdmin)->get('/admin/payments')->assertOk();
});

it('lets the super admin verify: payment paid, plan switched, exactly one active subscription', function () {
    $this->actingAs($this->superAdmin)
        ->post("/admin/payments/{$this->payment->id}/verify")
        ->assertRedirect(route('admin.payments.show', $this->payment));

    $this->payment->refresh();

    expect($this->payment->status)->toBe(SubscriptionPayment::STATUS_PAID)
        ->and($this->payment->paid_at)->not->toBeNull()
        ->and($this->payment->metadata['verified_by'])->toBe($this->superAdmin->id)
        ->and($this->pendingSubscription->refresh()->status)->toBe(Subscription::STATUS_ACTIVE)
        ->and($this->free->refresh()->status)->toBe(Subscription::STATUS_CANCELLED)
        ->and(
            Subscription::query()
                ->where('user_id', $this->companyAdmin->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->count()
        )->toBe(1);
});

it('does not verify the same payment twice', function () {
    $this->actingAs($this->superAdmin);

    $this->post("/admin/payments/{$this->payment->id}/verify");
    $paidAt = $this->payment->refresh()->paid_at;

    $this->post("/admin/payments/{$this->payment->id}/verify")->assertSessionHas('error');

    expect($this->payment->refresh()->paid_at->equalTo($paidAt))->toBeTrue()
        ->and(
            Subscription::query()
                ->where('user_id', $this->companyAdmin->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->count()
        )->toBe(1);
});

it('cannot verify a rejected payment', function () {
    $this->actingAs($this->superAdmin);

    $this->post("/admin/payments/{$this->payment->id}/reject");
    $this->post("/admin/payments/{$this->payment->id}/verify")->assertSessionHas('error');

    expect($this->payment->refresh()->status)->toBe(SubscriptionPayment::STATUS_FAILED)
        ->and($this->pendingSubscription->refresh()->status)->toBe(Subscription::STATUS_CANCELLED)
        ->and($this->free->refresh()->status)->toBe(Subscription::STATUS_ACTIVE);
});

it('rejecting a payment keeps the user on the current plan', function () {
    $this->actingAs($this->superAdmin)
        ->post("/admin/payments/{$this->payment->id}/reject")
        ->assertRedirect(route('admin.payments.show', $this->payment));

    expect($this->payment->refresh()->status)->toBe(SubscriptionPayment::STATUS_FAILED)
        ->and($this->free->refresh()->status)->toBe(Subscription::STATUS_ACTIVE);
});

it('cannot reject a payment that is already paid', function () {
    $this->actingAs($this->superAdmin);

    $this->post("/admin/payments/{$this->payment->id}/verify");
    $this->post("/admin/payments/{$this->payment->id}/reject")->assertSessionHas('error');

    expect($this->payment->refresh()->status)->toBe(SubscriptionPayment::STATUS_PAID)
        ->and($this->pendingSubscription->refresh()->status)->toBe(Subscription::STATUS_ACTIVE);
});
