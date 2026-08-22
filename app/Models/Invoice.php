<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'total_amount',
        'paid_amount',
        'paid_at',
    ];


    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'paid_at'      => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount'  => 'decimal:2',
    ];


    /**
     * Invoice belongs to company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Invoice belongs to a customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getDueAmountAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'unpaid'
            && $this->due_date !== null
            && $this->due_date->isPast();
    }
}