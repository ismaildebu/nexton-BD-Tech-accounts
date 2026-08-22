<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'account_name',
        'bank_name',
        'account_number',
        'branch_name',
        'balance',
        'is_active',
    ];

    protected $casts = [
        'balance'   => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}