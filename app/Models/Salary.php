<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'employee_id', 'month', 'year',
        'basic_salary', 'allowances', 'deductions', 'net_salary',
        'status', 'paid_date',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances'   => 'decimal:2',
        'deductions'   => 'decimal:2',
        'net_salary'   => 'decimal:2',
        'paid_date'    => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}