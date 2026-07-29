<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'company_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'total_amount',
    ];


    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'total_amount' => 'decimal:2',
    ];


    /**
     * Invoice belongs to company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}