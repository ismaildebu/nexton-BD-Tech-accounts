<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id', 'item_name', 'description',
        'quantity', 'unit', 'unit_price', 'total',
    ];
}