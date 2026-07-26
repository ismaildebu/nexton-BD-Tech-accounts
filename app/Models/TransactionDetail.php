<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{

    protected $fillable = [

        'transaction_id',
        'account_id',
        'debit',
        'credit',

    ];



    /*
    |--------------------------------------------------------------------------
    | Transaction
    |--------------------------------------------------------------------------
    */

    public function transaction()
    {
        return $this->belongsTo(
            Transaction::class
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
            Account::class
        );
    }

}