<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerController extends Controller
{
    public function index()
    {
        $company_id = session('company_id');
        $customers = Customer::where('company_id', $company_id)
                        ->withCount('invoices')
                        ->orderBy('name')
                        ->get();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        Customer::create([
            'company_id'      => session('company_id'),
            'name'            => $request->name,
            'phone'           => $request->phone,
            'email'           => $request->email,
            'address'         => $request->address,
            'trade_license'   => $request->trade_license,
            'tin'             => $request->tin,
            'customer_type'   => $request->customer_type ?? 'Individual',
            'credit_limit'    => $request->credit_limit ?? 0,
            'opening_balance' => $request->opening_balance ?? 0,
            'balance_type'    => $request->balance_type ?? 'Receivable',
            'notes'           => $request->notes,
            'is_active'       => true,
        ]);

        return redirect()->route('customers.index')
                         ->with('success', 'Customer created successfully!');
    }

    public function show(Customer $customer)
    {
        $this->authorizeCompany($customer);

        $customer->load('invoices', 'salesOrders');
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $this->authorizeCompany($customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeCompany($customer);

        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $customer->update($request->only([
            'name', 'phone', 'email', 'address', 'trade_license',
            'tin', 'customer_type', 'credit_limit', 'opening_balance',
            'balance_type', 'notes',
        ]));

        return redirect()->route('customers.index')
                         ->with('success', 'Customer updated!');
    }

    public function destroy(Customer $customer)
    {
        $this->authorizeCompany($customer);

        $customer->delete();
        return redirect()->route('customers.index')
                         ->with('success', 'Customer deleted!');
    }

    /**
     * Guard against IDOR: a customer from another company must never
     * be viewable/editable/deletable via the currently selected company.
     */
    private function authorizeCompany(Customer $customer): void
    {
        if ((int) $customer->company_id !== (int) session('company_id')) {
            throw new NotFoundHttpException();
        }
    }
}