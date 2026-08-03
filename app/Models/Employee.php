<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Employee
 *
 * Salary মডিউলের জন্য প্রয়োজনীয় প্রাথমিক (minimal) মডেল।
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string|null $email
 * @property string|null $designation
 */
class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'designation',
        'phone',
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