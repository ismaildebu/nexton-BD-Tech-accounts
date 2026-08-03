<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StockTransfer
 *
 * পূর্বের প্রজেক্ট স্ট্যান্ডার্ড অনুসরণ করে তৈরি করা মডেল।
 * এটি এক ওয়্যারহাউজ থেকে অন্য ওয়্যারহাউজে পণ্য স্থানান্তরের রেকর্ড সংরক্ষণ করে।
 *
 * @property int $id
 * @property int $product_id
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property int $quantity
 * @property string $transfer_date
 */
class StockTransfer extends Model
{
    use HasFactory;

    /**
     * ম্যাস অ্যাসাইনমেন্টের জন্য অনুমোদিত ফিল্ড।
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'quantity',
        'transfer_date',
    ];

    /**
     * টাইপ কাস্টিং।
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity'      => 'integer',
        'transfer_date' => 'date',
    ];

    /**
     * এই ট্রান্সফারের সাথে সম্পর্কিত প্রোডাক্ট।
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * যে ওয়্যারহাউজ থেকে স্টক পাঠানো হয়েছে।
     */
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * যে ওয়্যারহাউজে স্টক পাঠানো হয়েছে।
     */
    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}

/**
 * ------------------------------------------------------------------
 * ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
 * ------------------------------------------------------------------
 * উদ্দেশ্য:
 *   এই মডেলটি stock_transfers টেবিলের সাথে ইন্টারঅ্যাক্ট করে এবং
 *   Product ও Warehouse মডেলের সাথে সম্পর্ক স্থাপন করে।
 *
 * টেস্টিং ধাপ:
 *   1. php artisan tinker চালু করুন।
 *   2. StockTransfer::create([...]) দিয়ে একটি রেকর্ড তৈরি করুন।
 *   3. $transfer->product, $transfer->fromWarehouse, $transfer->toWarehouse
 *      কল করে রিলেশনশিপ যাচাই করুন।
 *   4. নিশ্চিত করুন quantity সবসময় integer হিসেবে রিটার্ন হচ্ছে।
 * ------------------------------------------------------------------
 */