<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class StockTransfer
 *
 * এক ওয়্যারহাউজ থেকে অন্য ওয়্যারহাউজে পণ্য স্থানান্তরের রেকর্ড।
 *
 * ✅ Fix: company_id column যোগ করার পর BelongsToCompany trait
 * যুক্ত করা হয়েছে। এখন global scope স্বয়ংক্রিয়ভাবে কাজ করে।
 * Controller-এ আর manual whereHas('product', ...) লিখতে হবে না।
 */
class StockTransfer extends Model
{
    use HasFactory;
    use BelongsToCompany; // ✅ company_id migration এর পর এটি সক্রিয়

    protected $fillable = [
        'company_id',
        'product_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'quantity',
        'transfer_date',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'transfer_date' => 'date',
    ];

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}