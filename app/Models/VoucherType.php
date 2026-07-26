<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherType extends Model
{
    protected $fillable = [
    'company_id',
    'voucher_name',
    'voucher_code',
    'is_active',
];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}