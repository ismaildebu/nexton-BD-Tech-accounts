<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDetail extends Model
{
    use HasFactory;

    protected $table = 'transaction_details';

    protected $fillable = [
        'transaction_id',
        'account_id',
        'sort_order',
        'description',
        // ✅ শুধু এই দুটো — পুরাতন 'debit'/'credit' fillable থেকে বাদ
        'debit_amount',
        'credit_amount',
    ];

    protected $casts = [
        'debit_amount'  => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'sort_order'    => 'integer',
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

    // ---------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------

    public function getIsDebitAttribute(): bool
    {
        return bccomp((string) $this->debit_amount, '0', 4) > 0;
    }

    public function getIsCreditAttribute(): bool
    {
        return bccomp((string) $this->credit_amount, '0', 4) > 0;
    }

    public function getEffectiveAmountAttribute(): string
    {
        return $this->is_debit
            ? (string) $this->debit_amount
            : (string) $this->credit_amount;
    }

    // ---------------------------------------------------------------
    // Business Rules
    // ---------------------------------------------------------------

    /**
     * একটি line-এ একসাথে debit ও credit থাকতে পারবে না।
     */
    public function hasMixedEntry(): bool
    {
        return bccomp((string) $this->debit_amount, '0', 4) > 0
            && bccomp((string) $this->credit_amount, '0', 4) > 0;
    }
}