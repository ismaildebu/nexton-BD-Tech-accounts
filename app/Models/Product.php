<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 *
 * Stock Transfer মডিউলের জন্য প্রয়োজনীয় প্রাথমিক (minimal) মডেল।
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string|null $sku
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'sku',
        'description',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}