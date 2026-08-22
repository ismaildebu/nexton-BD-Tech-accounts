<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $this->authorizeCompany($expense);

        return view(
            'expenses.show',
            compact('expense')
        );
    }





    public function edit(Expense $expense)
    {
        $this->authorizeCompany($expense);

        return view(
            'expenses.edit',
            compact('expense')
        );
    }





    public function update(Request $request, Expense $expense)
    {
        $this->authorizeCompany($expense);

        $request->validate([

            'expense_date'=>'required|date',

            'category'=>'required',

            'amount'=>'required|numeric'

        ]);



        $expense->update($request->only([
            'expense_date',
            'category',
            'description',
            'amount',
        ]));



        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Expense updated successfully.'
            );

    }





    public function destroy(Expense $expense)
    {
        $this->authorizeCompany($expense);

        $expense->delete();


        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Expense deleted successfully.'
            );

    }

    /**
     * Guard against IDOR: an expense from another company must never
     * be viewable/editable/deletable via the currently selected company.
     * Also prevents 'company_id' and 'status' from being changed via
     * mass assignment on update (see $expense->update() above, which
     * now uses an explicit whitelist instead of $request->all()).
     */
    private function authorizeCompany(Expense $expense): void
    {
        if ((int) $expense->company_id !== (int) session('company_id')) {
            throw new NotFoundHttpException();
        }
    }

}