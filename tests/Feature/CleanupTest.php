<?php

declare(strict_types=1);

use App\Exceptions\AccountCodeRangeExceededException;
use App\Mail\AdminPaymentNotification;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\User;
use App\Services\CustomerPaymentService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

// --------------------------------------------------------- account code range

it('raises a readable error when the account code range of a type is full', function () {
    $company = Company::factory()->create(['status' => true]);

    Account::create([
        'company_id' => $company->id,
        'account_code' => '1999',
        'account_name' => 'Last Asset Code',
        'account_type' => Account::TYPE_ASSET,
        'balance_type' => Account::BALANCE_DEBIT,
        'nature' => Account::NATURE_CASH,
        'level' => 1,
        'is_system' => false,
        'is_active' => true,
        'opening_balance' => 0,
    ]);

    expect(fn () => Account::generateNextCode(Account::TYPE_ASSET, $company->id))
        ->toThrow(AccountCodeRangeExceededException::class);

    // A type that still has room is unaffected.
    expect(Account::generateNextCode(Account::TYPE_LIABILITY, $company->id))->toBe(2000);
});

// ------------------------------------------------------------ password policy

it('requires at least 8 characters outside production', function () {
    $fails = fn (string $password) => Validator::make(
        ['password' => $password],
        ['password' => ['required', Password::defaults()]]
    )->fails();

    expect($fails('short'))->toBeTrue()
        ->and($fails('longenough'))->toBeFalse();
});

it('requires a stronger password in production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $fails = fn (string $password) => Validator::make(
        ['password' => $password],
        ['password' => ['required', Password::defaults()]]
    )->fails();

    expect($fails('alllowercase1'))->toBeTrue()      // no capital letter
        ->and($fails('NoNumbersHere'))->toBeTrue()   // no digit
        ->and($fails('Short1a'))->toBeTrue()         // under 10 characters
        ->and($fails('Strong1Passw0rd'))->toBeFalse();
});

// ---------------------------------------------------- payment admin notification

it('mails the admins of the paying company and nobody else', function () {
    Mail::fake();
    config(['payments.admin_notification_emails' => []]);

    $company = Company::factory()->create(['status' => true]);
    $otherCompany = Company::factory()->create(['status' => true]);

    $admin = User::factory()->create(['company_id' => $company->id, 'email' => 'owner@example.test']);
    $admin->assignRole('admin');

    $plainUser = User::factory()->create(['company_id' => $company->id, 'email' => 'staff@example.test']);

    $otherAdmin = User::factory()->create(['company_id' => $otherCompany->id, 'email' => 'other@example.test']);
    $otherAdmin->assignRole('admin');

    $customer = Customer::query()->create([
        'company_id' => $company->id,
        'customer_code' => 'SA-MAIL-1',
        'name' => 'Mail Customer',
        'customer_type' => 'Individual',
        'credit_limit' => 0,
        'opening_balance' => 0,
        'balance_type' => 'Receivable',
        'is_active' => true,
    ]);

    $payment = CustomerPayment::query()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'reference_id' => $customer->customer_code,
        'amount' => '500.00',
        'payment_date' => now()->toDateString(),
        'payment_method' => 'bkash',
        'status' => 'pending',
    ]);

    app(CustomerPaymentService::class)->notifyAdminOfPayment($payment);

    Mail::assertQueued(AdminPaymentNotification::class, fn ($mail) => $mail->hasTo('owner@example.test'));
    Mail::assertNotQueued(AdminPaymentNotification::class, fn ($mail) => $mail->hasTo('staff@example.test'));
    Mail::assertNotQueued(AdminPaymentNotification::class, fn ($mail) => $mail->hasTo('other@example.test'));
    Mail::assertNotQueued(AdminPaymentNotification::class, fn ($mail) => $mail->hasTo('admin@example.com'));
});

it('does not fail when the company has no admin to notify', function () {
    Mail::fake();
    config(['payments.admin_notification_emails' => []]);

    $company = Company::factory()->create(['status' => true]);

    $customer = Customer::query()->create([
        'company_id' => $company->id,
        'customer_code' => 'SA-MAIL-2',
        'name' => 'Lonely Customer',
        'customer_type' => 'Individual',
        'credit_limit' => 0,
        'opening_balance' => 0,
        'balance_type' => 'Receivable',
        'is_active' => true,
    ]);

    $payment = CustomerPayment::query()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'reference_id' => $customer->customer_code,
        'amount' => '500.00',
        'payment_date' => now()->toDateString(),
        'payment_method' => 'bkash',
        'status' => 'pending',
    ]);

    app(CustomerPaymentService::class)->notifyAdminOfPayment($payment);

    Mail::assertNothingQueued();
});
