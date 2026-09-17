<?php

declare(strict_types=1);

namespace App\Models;
use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerEntry extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToCompany;

    protected $table = 'ledger_entries';

    protected $fillable = [
        'transaction_id',
        'company_id',
        'financial_year_id',
        'voucher_type_id',
        'account_id',
        'voucher_number',
        'voucher_date',
        'entry_date',
        // ✅ শুধু এই দুটো column — পুরাতন 'debit'/'credit' বাদ
        'debit_amount',
        'credit_amount',
        'is_reversed',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        // ✅ পুরাতন 'debit'/'credit' cast বাদ দেওয়া হয়েছে
        'debit_amount'  => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'is_reversed'   => 'boolean',
        'voucher_date'  => 'date',
        'entry_date'    => 'date',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    public function voucherType(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class, 'voucher_type_id');
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeForCompany(\Illuminate\Database\Eloquent\Builder $query, int $companyId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_reversed', false);
    }

    public function scopeForAccount(\Illuminate\Database\Eloquent\Builder $query, int $accountId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Trial Balance Verification Scope
     * 
     * সমস্যা #4 সমাধান: Trial Balance calculation
     * মোট Debit = মোট Credit যাচাই করুন
     */
    public function scopeTrialBalance(\Illuminate\Database\Eloquent\Builder $query, int $companyId, int $fyId): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where('company_id', $companyId)
            ->where('financial_year_id', $fyId)
            ->where('is_reversed', false)
            ->selectRaw('
                SUM(CAST(debit_amount AS DECIMAL(18,4))) as total_debit,
                SUM(CAST(credit_amount AS DECIMAL(18,4))) as total_credit,
                COUNT(*) as entry_count
            ');
    }

        // ---------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------

    /**
     * Accessor: debit
     * debit_amount → debit mapping করুন backward compatibility এর জন্য
     */
    public function getDebitAttribute(): float
    {
        return (float) $this->debit_amount;
    }

    /**
     * Accessor: credit
     * credit_amount → credit mapping করুন
     */
    public function getCreditAttribute(): float
    {
        return (float) $this->credit_amount;
    }

    public function getEffectiveDebitAttribute(): float
    {
        return (float) $this->debit_amount;
    }

    public function getEffectiveCreditAttribute(): float
    {
        return (float) $this->credit_amount;
    }

    public function getEffectiveDateAttribute(): ?string
    {
        return $this->voucher_date
            ? $this->voucher_date->format('Y-m-d')
            : ($this->entry_date ? $this->entry_date->format('Y-m-d') : null);
    }

    public function getEffectiveVoucherNumberAttribute(): string
    {
        return $this->voucher_number
            ?? $this->transaction?->voucher_number
            ?? '—';
    }
}