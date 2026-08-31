<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MediaCollection
 * -----------------
 * A cash/bank receipt from an Agent or Hawker against their dues.
 * `account_id` reuses the existing Account (chart of accounts) model —
 * no new ledger/account model is created here.
 *
 * `transaction_id` is reserved for a future LedgerPostingService hook
 * (Phase 1 does not post to the ledger — see accounting integration
 * notes in the audit report).
 */
class MediaCollection extends Model
{
    use SoftDeletes;
    use BelongsToCompany;

    public const METHOD_CASH           = 'Cash';
    public const METHOD_BANK           = 'Bank';
    public const METHOD_MOBILE_BANKING = 'Mobile Banking';
    public const METHOD_CHEQUE         = 'Cheque';
    public const METHOD_OTHER          = 'Other';

    protected $fillable = [
        'company_id',
        'media_party_id',
        'account_id',
        'transaction_id',
        'amount',
        'payment_method',
        'collection_date',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'collection_date' => 'date',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(MediaParty::class, 'media_party_id');
    }

    /**
     * The receiving Account (Cash/Bank/...) — reuses the existing
     * chart-of-accounts Account model.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Not populated in Phase 1 — reserved for future accounting
     * integration via LedgerPostingService.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
