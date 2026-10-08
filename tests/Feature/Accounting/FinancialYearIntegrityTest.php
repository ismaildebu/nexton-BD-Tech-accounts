<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VoucherType;

beforeEach(function () {
    $this->company = Company::factory()->create(['status' => true]);

    // A company admin: has financial-years.manage and financial-years.reopen.
    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');

    $this->year = FinancialYear::create([
        'company_id' => $this->company->id,
        'year_name' => '2026-2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
        'is_closed' => false,
    ]);

    $voucherType = VoucherType::create([
        'company_id' => $this->company->id,
        'code' => 'JV',
        'name' => 'Journal Voucher',
        'nature' => VoucherType::NATURE_RECEIPT,
        'prefix' => 'JV',
        'last_number' => 0,
        'is_active' => true,
        'status' => 1,
    ]);

    $cash = Account::create([
        'company_id' => $this->company->id,
        'account_code' => '1001',
        'account_name' => 'Cash',
        'account_type' => Account::TYPE_ASSET,
        'balance_type' => Account::BALANCE_DEBIT,
        'nature' => Account::NATURE_CASH,
        'level' => 1,
        'is_system' => false,
        'is_active' => true,
        'opening_balance' => 0,
    ]);

    $this->makeTransaction = function (string $status, ?FinancialYear $year = null) use ($voucherType, $cash) {
        $year ??= $this->year;
        $number = 'JV-' . uniqid();

        return Transaction::create([
            'company_id' => $this->company->id,
            'financial_year_id' => $year->id,
            'voucher_type_id' => $voucherType->id,
            'voucher_number' => $number,
            'voucher_date' => $year->start_date->toDateString(),
            'total_debit' => 1000,
            'total_credit' => 1000,
            'voucher_no' => $number,
            'transaction_date' => $year->start_date->toDateString(),
            'transaction_type' => 'Journal',
            'amount' => 1000,
            'description' => 'Test voucher',
            'account_id' => $cash->id,
            'narration' => 'Test',
            'status' => $status,
            'created_by' => $this->admin->id,
        ]);
    };
});

// ---------------------------------------------------------------- create

it('rejects a new year whose dates overlap an existing year', function () {
    $this->actingAs($this->admin)
        ->post(route('financial-years.store'), [
            'year_name' => 'Overlapping',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ])
        ->assertSessionHasErrors('financial_year');

    expect(FinancialYear::withoutGlobalScopes()->count())->toBe(1);
});

it('creates the following year and never creates it closed', function () {
    $this->actingAs($this->admin)
        ->post(route('financial-years.store'), [
            'year_name' => '2027-2028',
            'start_date' => '2027-07-01',
            'end_date' => '2028-06-30',
            'is_closed' => 1, // must be ignored
        ])
        ->assertRedirect(route('financial-years.index'));

    $next = FinancialYear::withoutGlobalScopes()->where('year_name', '2027-2028')->firstOrFail();

    expect($next->is_closed)->toBeFalse();
});

// ---------------------------------------------------------------- update

it('does not let the request change the closed flag through update', function () {
    $this->actingAs($this->admin)
        ->put(route('financial-years.update', $this->year), [
            'year_name' => '2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => 1,
            'is_closed' => 1,
        ])
        ->assertRedirect(route('financial-years.index'));

    expect($this->year->refresh()->is_closed)->toBeFalse();
});

it('refuses to change the dates of a year that already has vouchers', function () {
    ($this->makeTransaction)(Transaction::STATUS_DRAFT);

    $this->actingAs($this->admin)
        ->put(route('financial-years.update', $this->year), [
            'year_name' => '2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-06-30',
            'is_active' => 1,
        ])
        ->assertSessionHasErrors('financial_year');

    expect($this->year->refresh()->start_date->toDateString())->toBe('2026-07-01');
});

it('still allows renaming a year that has vouchers when the dates stay the same', function () {
    ($this->makeTransaction)(Transaction::STATUS_POSTED);

    $this->actingAs($this->admin)
        ->put(route('financial-years.update', $this->year), [
            'year_name' => 'FY 2026-27',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => 1,
        ])
        ->assertRedirect(route('financial-years.index'));

    expect($this->year->refresh()->year_name)->toBe('FY 2026-27');
});

it('refuses to move a year onto the dates of another year', function () {
    $next = FinancialYear::create([
        'company_id' => $this->company->id,
        'year_name' => '2027-2028',
        'start_date' => '2027-07-01',
        'end_date' => '2028-06-30',
        'is_active' => false,
        'is_closed' => false,
    ]);

    $this->actingAs($this->admin)
        ->put(route('financial-years.update', $next), [
            'year_name' => '2027-2028',
            'start_date' => '2027-01-01',
            'end_date' => '2028-06-30',
        ])
        ->assertSessionHasErrors('financial_year');

    expect($next->refresh()->start_date->toDateString())->toBe('2027-07-01');
});

it('refuses to modify a closed year', function () {
    $this->year->update(['is_closed' => true]);

    $this->actingAs($this->admin)
        ->put(route('financial-years.update', $this->year), [
            'year_name' => 'Changed',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ])
        ->assertSessionHasErrors('financial_year');

    expect($this->year->refresh()->year_name)->toBe('2026-2027');
});

// ---------------------------------------------------------------- delete

it('refuses to delete a year that has vouchers and keeps the voucher attached', function () {
    $transaction = ($this->makeTransaction)(Transaction::STATUS_POSTED);

    $this->actingAs($this->admin)
        ->delete(route('financial-years.destroy', $this->year))
        ->assertSessionHasErrors('financial_year');

    expect(FinancialYear::withoutGlobalScopes()->whereKey($this->year->id)->exists())->toBeTrue()
        ->and($transaction->refresh()->financial_year_id)->toBe($this->year->id);
});

it('deletes a year that has no vouchers', function () {
    $this->actingAs($this->admin)
        ->delete(route('financial-years.destroy', $this->year))
        ->assertRedirect(route('financial-years.index'));

    expect(FinancialYear::withoutGlobalScopes()->whereKey($this->year->id)->exists())->toBeFalse();
});

// ----------------------------------------------------------------- close

it('cannot be closed while a voucher is still Draft, Submitted or Approved', function () {
    ($this->makeTransaction)(Transaction::STATUS_DRAFT);
    ($this->makeTransaction)(Transaction::STATUS_APPROVED);

    $this->actingAs($this->admin)
        ->post(route('financial-years.close', $this->year))
        ->assertSessionHasErrors('financial_year');

    expect($this->year->refresh()->is_closed)->toBeFalse();
});

it('closes when every voucher is Posted or Cancelled and records who and when', function () {
    ($this->makeTransaction)(Transaction::STATUS_POSTED);
    ($this->makeTransaction)(Transaction::STATUS_CANCELLED);

    $this->actingAs($this->admin)
        ->post(route('financial-years.close', $this->year))
        ->assertRedirect(route('financial-years.index'));

    $this->year->refresh();

    expect($this->year->is_closed)->toBeTrue()
        ->and($this->year->closed_at)->not->toBeNull()
        ->and($this->year->closed_by)->toBe($this->admin->id);
});

it('cannot close the year of another company', function () {
    $other = Company::factory()->create(['status' => true]);
    $foreign = FinancialYear::create([
        'company_id' => $other->id,
        'year_name' => '2026-2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
        'is_closed' => false,
    ]);

    $this->actingAs($this->admin)
        ->post(route('financial-years.close', $foreign))
        ->assertNotFound();

    expect($foreign->refresh()->is_closed)->toBeFalse();
});

// --------------------------------------------------------------- reopen

it('forbids reopening without the financial-years.reopen permission', function () {
    $this->year->update(['is_closed' => true]);

    $manager = User::factory()->create(['company_id' => $this->company->id]);
    $manager->givePermissionTo('financial-years.manage');

    $this->actingAs($manager)
        ->post(route('financial-years.reopen', $this->year), ['reason' => 'Correcting an audit finding'])
        ->assertForbidden();

    expect($this->year->refresh()->is_closed)->toBeTrue();
});

it('requires a written reason to reopen', function () {
    $this->year->update(['is_closed' => true]);

    $this->actingAs($this->admin)
        ->post(route('financial-years.reopen', $this->year), ['reason' => 'short'])
        ->assertSessionHasErrors('reason');

    expect($this->year->refresh()->is_closed)->toBeTrue();
});

it('reopens with a reason and keeps the audit trail', function () {
    $this->year->update(['is_closed' => true, 'closed_at' => now(), 'closed_by' => $this->admin->id]);

    $this->actingAs($this->admin)
        ->post(route('financial-years.reopen', $this->year), ['reason' => 'Correcting an audit finding'])
        ->assertRedirect(route('financial-years.index'));

    $this->year->refresh();

    expect($this->year->is_closed)->toBeFalse()
        ->and($this->year->reopen_reason)->toBe('Correcting an audit finding')
        ->and($this->year->reopened_by)->toBe($this->admin->id)
        ->and($this->year->reopened_at)->not->toBeNull()
        ->and($this->year->closed_at)->toBeNull();
});

it('cannot reopen a year that is not closed', function () {
    $this->actingAs($this->admin)
        ->post(route('financial-years.reopen', $this->year), ['reason' => 'Correcting an audit finding'])
        ->assertSessionHasErrors('financial_year');
});
