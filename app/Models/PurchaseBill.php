<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseBill extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'purchase_order_id',
        'transaction_id',
        'bill_number',
        'bill_date',
        'due_date',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'paid_amount',
        'due_amount',
        'notes',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date'  => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseBillItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchaseBillPayment::class);
    }
}