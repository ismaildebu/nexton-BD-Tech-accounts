<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\PurchaseBill;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VoucherType;

beforeEach(function () {
    $this->company = Company::factory()->create(['status' => true]);

    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');

    FinancialYear::create([
        'company_id' => $this->company->id,
        'year_name' => now()->year . '-' . (now()->year + 1),
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'is_active' => true,
        'is_closed' => false,
    ]);

    $this->payable = Account::create([
        'company_id' => $this->company->id,
        'account_code' => '3001',
        'account_name' => 'Accounts Payable',
        'account_type' => 'Liability',
        'nature' => 'Supplier',
        'opening_balance' => 0,
        'is_active' => true,
    ]);

    $this->cash = Account::create([
        'company_id' => $this->company->id,
        'account_code' => '1001',
        'account_name' => 'Cash in Hand',
        'account_type' => 'Asset',
        'nature' => Account::NATURE_CASH,
        'opening_balance' => 0,
        'is_active' => true,
    ]);

    $this->expense = Account::create([
        'company_id' => $this->company->id,
        'account_code' => '5001',
        'account_name' => 'Purchase Expense',
        'account_type' => 'Expense',
        'nature' => 'General',
        'opening_balance' => 0,
        'is_active' => true,
    ]);

    $this->vendor = Vendor::create([
        'company_id' => $this->company->id,
        'name' => 'Test Vendor',
        'account_id' => $this->payable->id,
        'is_active' => true,
    ]);

    VoucherType::create([
        'company_id' => $this->company->id,
        'name' => 'Purchase Voucher',
        'code' => 'PUV',
        'nature' => 'journal',
        'prefix' => 'PUV',
        'description' => 'Purchase bill accounting transactions.',
        'is_active' => true,
        'status' => 1,
    ]);

    VoucherType::create([
        'company_id' => $this->company->id,
        'name' => 'Payment Voucher',
        'code' => 'PV',
        'nature' => 'payment',
        'prefix' => 'PV',
        'description' => 'Cash or bank payment transactions.',
        'is_active' => true,
        'status' => 1,
    ]);

    $this->billPayload = fn (array $items) => [
        'vendor_id' => $this->vendor->id,
        'bill_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'items' => $items,
        'tax' => '0',
        'discount' => '0',
    ];

    $this->item = fn (string $name, string $qty, string $price) => [
        'account_id' => $this->expense->id,
        'item_name' => $name,
        'quantity' => $qty,
        'unit_price' => $price,
    ];
});

it('creates a bill whose item totals add up exactly to the subtotal', function () {
    // 1.5 x 3.33 = 4.995 per line. Rounded per line that is 5.00 + 5.00 = 10.00;
    // the old float code summed the unrounded lines (9.99) and the voucher then
    // refused the bill as "not balanced".
    $this->actingAs($this->admin)
        ->post(route('purchase-bills.store'), ($this->billPayload)([
            ($this->item)('Paper', '1.5', '3.33'),
            ($this->item)('Ink', '1.5', '3.33'),
        ]))
        ->assertSessionHasNoErrors();

    $bill = PurchaseBill::withoutGlobalScopes()->firstOrFail();

    expect((string) $bill->subtotal)->toBe('10.00')
        ->and((string) $bill->total)->toBe('10.00')
        ->and($bill->items->pluck('total')->map(fn ($t) => (string) $t)->all())->toBe(['5.00', '5.00']);

    $voucher = Transaction::query()->findOrFail($bill->transaction_id);

    expect($voucher->status)->toBe(Transaction::STATUS_POSTED)
        ->and(bccomp((string) $voucher->total_debit, '10', 4))->toBe(0)
        ->and(bccomp((string) $voucher->total_debit, (string) $voucher->total_credit, 4))->toBe(0);
});

it('refuses a third decimal place instead of rounding it silently', function () {
    $this->actingAs($this->admin)
        ->post(route('purchase-bills.store'), ($this->billPayload)([
            ($this->item)('Paper', '1', '3.335'),
        ]))
        ->assertSessionHasErrors('items.0.unit_price');

    expect(PurchaseBill::withoutGlobalScopes()->count())->toBe(0);
});

it('tracks partial and full payment without drift and refuses an overpayment', function () {
    $this->actingAs($this->admin)
        ->post(route('purchase-bills.store'), ($this->billPayload)([
            ($this->item)('Paper', '1.5', '3.33'),
            ($this->item)('Ink', '1.5', '3.33'),
        ]));

    $bill = PurchaseBill::withoutGlobalScopes()->firstOrFail();

    $pay = fn (string $amount) => $this->actingAs($this->admin)
        ->post(route('purchase-bills.payment', $bill), [
            'payment_date' => now()->toDateString(),
            'payment_items' => [['account_id' => $this->cash->id, 'amount' => $amount]],
        ]);

    $pay('3.33')->assertSessionHas('success');

    $bill->refresh();
    expect((string) $bill->paid_amount)->toBe('3.33')
        ->and((string) $bill->due_amount)->toBe('6.67')
        ->and($bill->status)->toBe('Partial');

    $pay('6.68')->assertSessionHas('error'); // 1 paisa too much

    expect((string) $bill->refresh()->paid_amount)->toBe('3.33');

    $pay('6.67')->assertSessionHas('success');

    $bill->refresh();
    expect((string) $bill->paid_amount)->toBe('10.00')
        ->and((string) $bill->due_amount)->toBe('0.00')
        ->and($bill->status)->toBe('Paid');

    $pay('0.01')->assertSessionHas('error'); // nothing left to pay
});
