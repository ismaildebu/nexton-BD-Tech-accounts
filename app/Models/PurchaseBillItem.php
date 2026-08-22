<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseBillItem extends Model
{
    protected $fillable = [
        'purchase_bill_id',
        'account_id',
        'item_name',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity'   => 'decimal:4',
        'unit_price' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}