<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Company Scope
    |--------------------------------------------------------------------------
    |
    | Transaction সবসময় একটি নির্দিষ্ট কোম্পানির সঙ্গে যুক্ত থাকবে।
    |
    */
    use BelongsToCompany;

    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    |
    | Voucher workflow:
    |
    | Draft
    |   ↓
    | Submitted
    |   ↓
    | Approved
    |   ↓
    | Posted
    |   ↓
    | Ledger
    |
    | Cancelled যেকোনো উপযুক্ত পর্যায়ে হতে পারে,
    | তবে Posted হওয়ার পর আলাদা cancellation policy প্রযোজ্য হবে।
    |
    */

    public const STATUS_DRAFT     = 'Draft';
    public const STATUS_SUBMITTED = 'Submitted';
    public const STATUS_APPROVED  = 'Approved';
    public const STATUS_POSTED    = 'Posted';
    public const STATUS_CANCELLED = 'Cancelled';

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'company_id',
        'financial_year_id',
        'voucher_type_id',

        'voucher_number',
        'voucher_date',
        'reference_type',
        'reference_id',
        'reference_number',

        'total_debit',
        'total_credit',

        /*
        |----------------------------------------------------------------------
        | Legacy columns - backward compatibility
        |----------------------------------------------------------------------
        */
        'transaction_date',
        'voucher_no',
        'transaction_type',
        'account_id',
        'debit_account_id',
        'credit_account_id',
        'amount',

        /*
        |----------------------------------------------------------------------
        | Description
        |----------------------------------------------------------------------
        */
        'narration',
        'description',

        /*
        |----------------------------------------------------------------------
        | Workflow
        |----------------------------------------------------------------------
        */
        'status',

        /*
        |----------------------------------------------------------------------
        | Created
        |----------------------------------------------------------------------
        */
        'created_by',

        /*
        |----------------------------------------------------------------------
        | Approval
        |----------------------------------------------------------------------
        */
        'approved_by',
        'approved_at',
        'approval_note',

        /*
        |----------------------------------------------------------------------
        | Posting
        |----------------------------------------------------------------------
        */
        'posted_by',
        'posted_at',

        /*
        |----------------------------------------------------------------------
        | Cancellation
        |----------------------------------------------------------------------
        */
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'voucher_date'     => 'date',
        'transaction_date' => 'date',

        'approved_at'      => 'datetime',
        'posted_at'        => 'datetime',
        'cancelled_at'     => 'datetime',

        'total_debit'      => 'decimal:4',
        'total_credit'     => 'decimal:4',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Check whether debit and credit are balanced.
     */
    public function getIsBalancedAttribute(): bool
    {
        return bccomp(
            (string) $this->total_debit,
            (string) $this->total_credit,
            4
        ) === 0;
    }

    /**
     * Get debit-credit difference.
     */
    public function getDifferenceAttribute(): float
    {
        return abs(
            (float) $this->total_debit -
            (float) $this->total_credit
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Check whether transaction is Draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check whether transaction has been Submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Check whether transaction has been Approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check whether transaction has been Posted.
     */
    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * Check whether transaction is Cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Financial Year.
     */
    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    /**
     * Voucher Type.
     */
    public function voucherType(): BelongsTo
    {
        return $this->belongsTo(VoucherType::class);
    }

    /**
     * Transaction Details.
     */
    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /**
     * Ledger Entries.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Legacy account relationship.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * User who created the voucher.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who approved the voucher.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who posted the voucher.
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * User who cancelled the voucher.
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Backward compatibility alias.
     */
    public function user(): BelongsTo
    {
        return $this->creator();
    }
}