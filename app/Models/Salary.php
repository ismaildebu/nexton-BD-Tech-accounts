<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Salary
 *
 * পূর্বের প্রজেক্ট স্ট্যান্ডার্ড অনুসরণ করে তৈরি করা মডেল।
 * এটি একজন কর্মচারীর নির্দিষ্ট মাসের বেতন রেকর্ড সংরক্ষণ করে।
 *
 * @property int $id
 * @property int $employee_id
 * @property float $basic_salary
 * @property float $allowances
 * @property float $deductions
 * @property float $net_salary
 * @property string $salary_month
 */
class Salary extends Model
{
    use HasFactory;

    /**
     * ম্যাস অ্যাসাইনমেন্টের জন্য অনুমোদিত ফিল্ড।
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'basic_salary',
        'allowances',
        'deductions',
        'net_salary',
        'salary_month',
    ];

    /**
     * টাইপ কাস্টিং।
     *
     * @var array<string, string>
     */
    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances'   => 'decimal:2',
        'deductions'   => 'decimal:2',
        'net_salary'   => 'decimal:2',
        'salary_month' => 'date',
    ];

    /**
     * এই বেতন রেকর্ডের সাথে সম্পর্কিত কর্মচারী।
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * basic_salary + allowances - deductions থেকে net_salary গণনা করে।
     * Controller স্তরে save করার আগে এই মেথড ব্যবহার করা হয়।
     */
    public static function calculateNetSalary(float $basicSalary, float $allowances, float $deductions): float
    {
        return round($basicSalary + $allowances - $deductions, 2);
    }
}

/**
 * ------------------------------------------------------------------
 * ডকুমেন্টেশন ও টেস্টিং নির্দেশনা
 * ------------------------------------------------------------------
 * উদ্দেশ্য:
 *   এই মডেলটি salaries টেবিলের সাথে ইন্টারঅ্যাক্ট করে এবং Employee
 *   মডেলের সাথে সম্পর্ক স্থাপন করে। net_salary গণনার হেল্পার মেথডও
 *   এখানে রাখা হয়েছে যাতে Controller ও ভবিষ্যতে অন্য কোথাও (যেমন
 *   Report/Export ফিচারে) একই লজিক পুনরায় ব্যবহার করা যায়।
 *
 * টেস্টিং ধাপ:
 *   1. php artisan tinker চালু করুন।
 *   2. Salary::calculateNetSalary(30000, 5000, 2000) কল করে ফলাফল
 *      33000.00 আসছে কিনা যাচাই করুন।
 *   3. Salary::create([...]) দিয়ে একটি রেকর্ড তৈরি করুন এবং
 *      $salary->employee সম্পর্ক সঠিকভাবে কাজ করছে কিনা দেখুন।
 * ------------------------------------------------------------------
 */