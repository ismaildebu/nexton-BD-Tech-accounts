<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'location'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }
}