<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoucherType extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToCompany;

    protected $table = 'voucher_types';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'nature',
        'prefix',
        'last_number',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'last_number' => 'integer',
    ];

    // ---------------------------------------------------------------
    // Constants
    // ---------------------------------------------------------------

    public const NATURE_JOURNAL = 'journal';
    public const NATURE_PAYMENT = 'payment';
    public const NATURE_RECEIPT = 'receipt';
    public const NATURE_CONTRA  = 'contra';
    public const NATURE_OPENING = 'opening';

    public const NATURES = [
        self::NATURE_JOURNAL => 'Journal Voucher',
        self::NATURE_PAYMENT => 'Payment Voucher',
        self::NATURE_RECEIPT => 'Receipt Voucher',
        self::NATURE_CONTRA  => 'Contra Voucher',
        self::NATURE_OPENING => 'Opening Voucher',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'voucher_type_id');
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOfNature(Builder $query, string $nature): Builder
    {
        return $query->where('nature', $nature);
    }

    // ---------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------

    public function getNatureLabelAttribute(): string
    {
        return self::NATURES[$this->nature] ?? ucfirst($this->nature);
    }

    // ---------------------------------------------------------------
    // Business Methods
    // ---------------------------------------------------------------

    public function generateNextVoucherNumber(): string
    {
        $locked = self::query()
            ->whereKey($this->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $locked->increment('last_number');
        $locked->refresh();

        $prefix = $locked->prefix ?? strtoupper(substr($locked->code, 0, 3));
        $number = str_pad((string) $locked->last_number, 6, '0', STR_PAD_LEFT);

        $this->setAttribute('last_number', $locked->last_number);

        return "{$prefix}-{$number}";
    }
}