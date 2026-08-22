<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    /**
     * List invoices for the current company.
     */
    public function index()
    {
        $invoices = Invoice::with('customer')
            ->where('company_id', session('company_id'))
            ->latest('invoice_date')
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create()
    {
        $customers = Customer::where('company_id', session('company_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('invoices.create', compact('customers'));
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $companyId = session('company_id');

        $validated = $request->validate([
            'customer_id'   => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],
            'invoice_date'  => ['required', 'date'],
            'due_date'      => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'total_amount'  => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $companyId) {
            Invoice::create([
                'company_id'     => $companyId,
                'customer_id'    => $validated['customer_id'],
                'invoice_number' => $this->nextInvoiceNumber($companyId),
                'invoice_date'   => $validated['invoice_date'],
                'due_date'       => $validated['due_date'] ?? null,
                'total_amount'   => $validated['total_amount'],
                'paid_amount'    => 0,
                'status'         => 'unpaid',
            ]);
        });

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load('customer');

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice)
    {
        $customers = Customer::where('company_id', session('company_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('invoices.edit', compact('invoice', 'customers'));
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $companyId = session('company_id');

        $validated = $request->validate([
            'customer_id'  => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],
            'invoice_date' => ['required', 'date'],
            'due_date'     => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'paid_amount'  => ['required', 'numeric', 'min:0', 'lte:total_amount'],
        ]);

        $status = (float) $validated['paid_amount'] >= (float) $validated['total_amount']
            ? 'paid'
            : 'unpaid';

        $invoice->update([
            'customer_id'   => $validated['customer_id'],
            'invoice_date'  => $validated['invoice_date'],
            'due_date'      => $validated['due_date'] ?? null,
            'total_amount'  => $validated['total_amount'],
            'paid_amount'   => $validated['paid_amount'],
            'status'        => $status,
            'paid_at'       => $status === 'paid' ? ($invoice->paid_at ?? now()) : null,
        ]);

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Paid invoices cannot be deleted.');
        }

        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Generate the next sequential invoice number for this company,
     * e.g. INV-2026-000123.
     */
    private function nextInvoiceNumber(int $companyId): string
    {
        $year  = now()->year;
        $count = Invoice::where('company_id', $companyId)
            ->whereYear('created_at', $year)
            ->count();

        return sprintf('INV-%d-%06d', $year, $count + 1);
    }
}