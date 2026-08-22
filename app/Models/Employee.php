<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'designation', 'department',
        'phone', 'joining_date', 'basic_salary', 'is_active',
    ];

    protected $casts = [
        'joining_date'  => 'date',
        'basic_salary'  => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }
}