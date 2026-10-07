<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseBillPaymentItem extends Model
{
    protected $fillable = [
        'purchase_bill_payment_id',
        'account_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseBillPayment::class,
            'purchase_bill_payment_id'
        );
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}