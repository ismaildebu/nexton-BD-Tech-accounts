<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\AccountCodeRangeExceededException;
use App\Exceptions\CannotDeleteAccountException;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Fix #5 — BelongsToCompany Trait
    // Route model binding automatically checks company ownership.
    use BelongsToCompany;

    // ──────────────────────────────────────────────────────────────
    // Constants
    // ──────────────────────────────────────────────────────────────

    public const BALANCE_DEBIT  = 'Debit';
    public const BALANCE_CREDIT = 'Credit';

    public const TYPE_ASSET     = 'Asset';
    public const TYPE_LIABILITY = 'Liability';
    public const TYPE_EQUITY    = 'Equity';
    public const TYPE_INCOME    = 'Income';
    public const TYPE_EXPENSE   = 'Expense';

    public const NATURE_CASH        = 'Cash';
    public const NATURE_BANK        = 'Bank';
    public const NATURE_CUSTOMER    = 'Customer';
    public const NATURE_SUPPLIER    = 'Supplier';
    public const NATURE_INVENTORY   = 'Inventory';
    public const NATURE_FIXED_ASSET = 'Fixed Asset';
    public const NATURE_EXPENSE     = 'Expense';
    public const NATURE_INCOME      = 'Income';
    public const NATURE_VAT         = 'VAT';
    public const NATURE_TAX         = 'Tax';
    public const NATURE_GENERAL     = 'General';

    /**
     * Account code ranges.
     *
     * Asset     → 1000–1999
     * Liability → 2000–2999
     * Equity    → 3000–3999
     * Income    → 4000–4999
     * Expense   → 5000–5999
     */
    public const CODE_RANGES = [
        self::TYPE_ASSET     => ['min' => 1000, 'max' => 1999],
        self::TYPE_LIABILITY => ['min' => 2000, 'max' => 2999],
        self::TYPE_EQUITY    => ['min' => 3000, 'max' => 3999],
        self::TYPE_INCOME    => ['min' => 4000, 'max' => 4999],
        self::TYPE_EXPENSE   => ['min' => 5000, 'max' => 5999],
    ];

    // ──────────────────────────────────────────────────────────────
    // Fillable & Casts
    // ──────────────────────────────────────────────────────────────

    protected $fillable = [
        'company_id',
        'account_code',
        'account_name',
        'account_type',
        'parent_id',
        'nature',
        'level',
        'color',
        'is_system',
        'is_active',
        'opening_balance',
        'balance_type',
    ];

    protected $casts = [
        'account_code'    => 'integer',
        'opening_balance' => 'decimal:2',
        'is_system'       => 'boolean',
        'is_active'       => 'boolean',
        'level'           => 'integer',
    ];

    // ──────────────────────────────────────────────────────────────
    // Booted
    // ──────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (Account $account): void {
            if (! $account->canDelete()) {
                throw new CannotDeleteAccountException(
                    "অ্যাকাউন্ট '{$account->account_name}' ডিলিট করা সম্ভব নয়।"
                );
            }
        });
    }

    // ──────────────────────────────────────────────────────────────
    // Static Helpers
    // ──────────────────────────────────────────────────────────────

    public static function accountTypes(): array
    {
        return [
            self::TYPE_ASSET,
            self::TYPE_LIABILITY,
            self::TYPE_EQUITY,
            self::TYPE_INCOME,
            self::TYPE_EXPENSE,
        ];
    }

    public static function accountNatures(): array
    {
        return [
            self::NATURE_CASH,
            self::NATURE_BANK,
            self::NATURE_CUSTOMER,
            self::NATURE_SUPPLIER,
            self::NATURE_INVENTORY,
            self::NATURE_FIXED_ASSET,
            self::NATURE_EXPENSE,
            self::NATURE_INCOME,
            self::NATURE_VAT,
            self::NATURE_TAX,
            self::NATURE_GENERAL,
        ];
    }

    public static function defaultBalanceType(string $type): string
    {
        return in_array(
            $type,
            [self::TYPE_ASSET, self::TYPE_EXPENSE],
            strict: true
        )
            ? self::BALANCE_DEBIT
            : self::BALANCE_CREDIT;
    }

    /**
     * Generate the next account code.
     *
     * @throws AccountCodeRangeExceededException
     */
    public static function generateNextCode(
        string $type,
        int $companyId
    ): int {
        $range = self::CODE_RANGES[$type]
            ?? ['min' => 1000, 'max' => 1999];

        $lastAccount = self::withTrashed()
            ->withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereBetween(
                'account_code',
                [$range['min'], $range['max']]
            )
            ->orderBy('account_code', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastAccount) {
            $nextCode = (int) $lastAccount->account_code + 1;

            if ($nextCode > $range['max']) {
                throw new AccountCodeRangeExceededException(
                    "{$type} টাইপের জন্য নির্ধারিত সর্বোচ্চ অ্যাকাউন্ট কোড সীমা "
                    . "({$range['max']}) পূর্ণ হয়ে গেছে!"
                );
            }

            return $nextCode;
        }

        return $range['min'];
    }

    // ──────────────────────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────────────────────

    public function scopeForCompany($query, ?int $companyId)
    {
        if (! $companyId) {
            return $query;
        }

        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Active ledger entries only.
     *
     * Used by normal ledger/reporting operations.
     * Reversed original entries are intentionally excluded.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class)
            ->where('is_reversed', false);
    }

    /**
     * All ledger entries, including reversed entries.
     *
     * Used for calculating the actual account balance because
     * a cancelled voucher contains both the original entry and
     * its reversal entry.
     */
    public function allLedgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    // ──────────────────────────────────────────────────────────────
    // Business Helpers
    // ──────────────────────────────────────────────────────────────

    public function canDelete(): bool
    {
        if ($this->is_system) {
            return false;
        }

        if ($this->children()->exists()) {
            return false;
        }

        if ($this->hasTransactions()) {
            return false;
        }

        return true;
    }

    public function hasTransactions(): bool
    {
        return $this->ledgerEntries()->exists();
    }

    /**
     * Determine whether this account has a debit-normal balance.
     *
     * Asset   → Debit
     * Expense → Debit
     * Liability → Credit
     * Equity → Credit
     * Income → Credit
     */
    public function isDebitNormal(): bool
    {
        return in_array(
            $this->account_type,
            [
                self::TYPE_ASSET,
                self::TYPE_EXPENSE,
            ],
            strict: true
        );
    }

    // ──────────────────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────────────────

    /**
     * Calculate the actual current balance.
     *
     * Includes both original and reversal ledger entries.
     *
     * Example:
     *
     * Original Credit 12,000
     * Reversal Debit  12,000
     * -----------------------
     * Net Balance          0
     */
    public function getCurrentBalanceAttribute(): float
    {
        $debitTotal = $this->allLedgerEntries()
            ->sum('debit_amount');

        $creditTotal = $this->allLedgerEntries()
            ->sum('credit_amount');

        if ($this->isDebitNormal()) {
            return (float) (
                $this->opening_balance
                + ($debitTotal - $creditTotal)
            );
        }

        return (float) (
            $this->opening_balance
            + ($creditTotal - $debitTotal)
        );
    }

    public function getFormattedBalanceAttribute(): string
    {
        $symbol = optional(
            $this->relationLoaded('company')
                ? $this->company
                : null
        )->currency_symbol ?? '৳';

        return $symbol
            . ' '
            . number_format($this->current_balance, 2);
    }
}