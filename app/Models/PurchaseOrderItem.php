<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
   protected $fillable = [
    'purchase_order_id',
    'product_id',
    'item_name',
    'description',
    'quantity',
    'unit',
    'unit_price',
    'total',
];

public function product()
{
    return $this->belongsTo(Product::class);
}
}