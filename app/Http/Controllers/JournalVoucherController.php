<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\VoucherType;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Transaction;
use App\Models\TransactionDetail;


class JournalVoucherController extends Controller
{

    /**
     * Journal Voucher List
     */
    public function index()
    {
        return view('vouchers.journal.index');
    }



    /**
     * Create Journal Voucher Form
     */
    public function create()
    {

        $voucherType = VoucherType::where(
            'voucher_code',
            'JV'
        )->first();


        $accounts = Account::where(
            'is_active',
            true
        )->get();


        $financialYear = FinancialYear::where(
            'is_active',
            true
        )->first();



        return view(
            'vouchers.journal.create',
            compact(
                'voucherType',
                'accounts',
                'financialYear'
            )
        );

    }




    /**
     * Store Journal Voucher
     */
    public function store(Request $request)
    {

        $request->validate([

            'transaction_date' => 'required|date',

            'accounts' => 'required|array|min:2',

            'debits' => 'required|array',

            'credits' => 'required|array',

        ]);



        $totalDebit = array_sum($request->debits);

        $totalCredit = array_sum($request->credits);



        if(round($totalDebit,2) != round($totalCredit,2))
        {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Debit and Credit must be equal.'
                );

        }





        DB::transaction(function () use ($request) {



            $voucherType = VoucherType::where(
                'voucher_code',
                'JV'
            )->first();



            $financialYear = FinancialYear::where(
                'is_active',
                true
            )->first();




            if(!$voucherType)
            {
                throw new \Exception(
                    'Journal Voucher Type not found'
                );
            }




            if(!$financialYear)
            {
                throw new \Exception(
                    'Active Financial Year not found'
                );
            }





            $lastTransaction = Transaction::where(
                'voucher_type_id',
                $voucherType->id
            )
            ->latest('id')
            ->first();




            $nextId = $lastTransaction
                ? $lastTransaction->id + 1
                : 1;



            $voucherNo = 'JV-' .
                str_pad(
                    $nextId,
                    5,
                    '0',
                    STR_PAD_LEFT
                );






            /*
            |--------------------------------------------------------------------------
            | Transaction Header
            |--------------------------------------------------------------------------
            */


            $transaction = Transaction::create([


                'company_id' => session('company_id'),


                'financial_year_id' => $financialYear->id,


                'voucher_type_id' => $voucherType->id,


                'voucher_no' => $voucherNo,


                'transaction_date' => $request->transaction_date,


                'transaction_type' => 'journal',


                'narration' => $request->narration,


                'status' => 'posted',


                'created_by' => auth()->id(),


            ]);







            foreach($request->accounts as $index=>$accountId)
            {


                if(!$accountId)
                {
                    continue;
                }




                $debit =
                    $request->debits[$index] ?? 0;



                $credit =
                    $request->credits[$index] ?? 0;





                /*
                |--------------------------------------------------------------------------
                | Transaction Details
                |--------------------------------------------------------------------------
                */


                $detail = TransactionDetail::create([


                    'transaction_id' => $transaction->id,


                    'account_id' => $accountId,


                    'debit' => $debit,


                    'credit' => $credit,


                ]);






                /*
                |--------------------------------------------------------------------------
                | Ledger Posting
                |--------------------------------------------------------------------------
                */


                DB::table('ledger_entries')->insert([


                    'company_id' => $transaction->company_id,


                    'financial_year_id' => $transaction->financial_year_id,


                    'transaction_id' => $transaction->id,


                    'transaction_detail_id' => $detail->id,


                    'account_id' => $accountId,


                    'entry_date' => $transaction->transaction_date,


                    'debit' => $debit,


                    'credit' => $credit,


                    'narration' => $transaction->narration,


                    'created_at' => now(),


                    'updated_at' => now(),


                ]);



            }



    });





        return redirect()

            ->route('journal-vouchers.index')

            ->with(
                'success',
                'Journal Voucher created successfully.'
            );


    }


}