<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{

    public function index()
    {
        $expenses = Expense::where(
            'company_id',
            session('company_id')
        )
        ->latest()
        ->get();


        return view('expenses.index', compact('expenses'));
    }



    public function create()
    {
        return view('expenses.create');
    }




    public function store(Request $request)
    {

        $request->validate([

            'expense_date' => [
                'required',
                'date'
            ],

            'category' => [
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0'
            ],

        ]);



        Expense::create([

            'company_id' => session('company_id'),

            'expense_date' => $request->expense_date,

            'category' => $request->category,

            'description' => $request->description,

            'amount' => $request->amount,

            'status' => 'approved',

        ]);



        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Expense created successfully.'
            );

    }





    public function show(Expense $expense)
    {
        return view(
            'expenses.show',
            compact('expense')
        );
    }





    public function edit(Expense $expense)
    {
        return view(
            'expenses.edit',
            compact('expense')
        );
    }





    public function update(Request $request, Expense $expense)
    {

        $request->validate([

            'expense_date'=>'required|date',

            'category'=>'required',

            'amount'=>'required|numeric'

        ]);



        $expense->update($request->all());



        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Expense updated successfully.'
            );

    }





    public function destroy(Expense $expense)
    {

        $expense->delete();


        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Expense deleted successfully.'
            );

    }

}