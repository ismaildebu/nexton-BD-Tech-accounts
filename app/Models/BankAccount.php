<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'account_id',
        'account_name',
        'bank_name',
        'account_number',
        'branch_name',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'balance'   => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Company owning this bank account.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Accounting account linked to this physical bank account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Current accounting balance.
     *
     * Ledger is the source of truth.
     * BankAccount.balance is retained as the opening/display value
     * for compatibility with the existing schema.
     */
    public function getCurrentBalanceAttribute(): float
    {
        if (! $this->account_id || ! $this->account) {
            return (float) ($this->balance ?? 0);
        }

        $openingBalance = (float) ($this->account->opening_balance ?? 0);

        $debit = (float) $this->account
        ->allLedgerEntries()
        ->sum('debit_amount');

        $credit = (float) $this->account
        ->allLedgerEntries()
        ->sum('credit_amount');

        return $openingBalance + $debit - $credit;
    }
}