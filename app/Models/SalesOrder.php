<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'customer_id', 'so_number',
        'order_date', 'delivery_date', 'status',
        'subtotal', 'tax', 'discount', 'total', 'notes',
    ];

    protected $casts = [
        'order_date'    => 'date',
        'delivery_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}