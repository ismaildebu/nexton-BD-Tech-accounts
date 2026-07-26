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
    | Default Account (Legacy Support)
    |--------------------------------------------------------------------------
    */

    public function account()
    {
        return $this->belongsTo(
            Account::class
        );
    }





    /*
    |--------------------------------------------------------------------------
    | Debit Account
    |--------------------------------------------------------------------------
    */

    public function debitAccount()
    {
        return $this->belongsTo(
            Account::class,
            'debit_account_id'
        );
    }





    /*
    |--------------------------------------------------------------------------
    | Credit Account
    |--------------------------------------------------------------------------
    */

    public function creditAccount()
    {
        return $this->belongsTo(
            Account::class,
            'credit_account_id'
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

public function details()
{
    return $this->hasMany(
        TransactionDetail::class
    );
}


}