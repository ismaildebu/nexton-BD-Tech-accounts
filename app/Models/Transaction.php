<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{

   protected $fillable = [

    'company_id',

    'financial_year_id',

    'voucher_type_id',

    'transaction_date',

    'voucher_no',

    'transaction_type',

    'account_id',

    'debit_account_id',

    'credit_account_id',

    'amount',

    'narration',

    'description',

    'status',

    'created_by',

];


    /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(
            Company::class
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Financial Year
    |--------------------------------------------------------------------------
    */

    public function financialYear()
    {
        return $this->belongsTo(
            FinancialYear::class
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Voucher Type
    |--------------------------------------------------------------------------
    */

    public function voucherType()
    {
        return $this->belongsTo(
            VoucherType::class
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Transaction Details
    |--------------------------------------------------------------------------
    | Journal Entry Rows
    |--------------------------------------------------------------------------
    */

    public function details()
    {
        return $this->hasMany(
            TransactionDetail::class
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Ledger Entries
    |--------------------------------------------------------------------------
    */

    public function entries()
    {
        return $this->hasMany(
            LedgerEntry::class
        );
    }

/*
|--------------------------------------------------------------------------
| Account
|--------------------------------------------------------------------------
*/

public function account()
{
    return $this->belongsTo(
        Account::class,
        'account_id'
    );
}


   /*
|--------------------------------------------------------------------------
| Created User
|--------------------------------------------------------------------------
*/

public function user()
{
    return $this->belongsTo(
        User::class,
        'created_by'
    );
}

}