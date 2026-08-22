<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseBillPayment extends Model
{
    protected $fillable = [
        'purchase_bill_id', 'payment_date', 'amount',
        'payment_method', 'reference', 'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];
}