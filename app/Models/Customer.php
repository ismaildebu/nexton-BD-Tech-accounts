<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'phone', 'email', 'address',
        'trade_license', 'tin', 'customer_type', 'credit_limit',
        'opening_balance', 'balance_type', 'is_active', 'notes',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function totalInvoiced()
    {
        return $this->invoices()->sum('total_amount');
    }

    public function totalPaid()
    {
        return $this->invoices()->sum('paid_amount');
    }

    public function totalDue()
    {
        return $this->totalInvoiced() - $this->totalPaid();
    }
}