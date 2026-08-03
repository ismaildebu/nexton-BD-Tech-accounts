<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Warehouse
 *
 * Stock Transfer মডিউলের জন্য প্রয়োজনীয় প্রাথমিক (minimal) মডেল।
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string|null $location
 */
class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'location',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}