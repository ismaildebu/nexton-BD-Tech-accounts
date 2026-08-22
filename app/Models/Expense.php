<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'expense_date',
        'category',
        'description',
        'amount',
        'status',
    ];


    protected $casts = [
        'expense_date'=>'date',
        'amount'=>'decimal:2',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}