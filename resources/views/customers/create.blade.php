@extends('layouts.app')

@section('title', 'Bulk Add Customers')
@section('page-title', 'Bulk Add Customers')
@section('page-subtitle', 'Create multiple customers at once')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold">Customer Information</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Add multiple customers and save them together.
                </p>
            </div>

            <a href="{{ route('customers.create') }}"
               class="bg-slate-100 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                Single Customer
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('customers.bulk-store') }}">
            @csrf

            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="border px-3 py-2 text-left min-w-[180px]">Customer Name *</th>
                            <th class="border px-3 py-2 text-left min-w-[130px]">Type</th>
                            <th class="border px-3 py-2 text-left min-w-[140px]">Phone</th>
                            <th class="border px-3 py-2 text-left min-w-[180px]">Email</th>
                            <th class="border px-3 py-2 text-left min-w-[130px]">Credit Limit</th>
                            <th class="border px-3 py-2 text-left min-w-[140px]">Opening Balance</th>
                            <th class="border px-3 py-2 text-left min-w-[130px]">Balance Type</th>
                            <th class="border px-3 py-2 text-left min-w-[180px]">Address</th>
                            <th class="border px-3 py-2 text-left min-w-[150px]">Trade License</th>
                            <th class="border px-3 py-2 text-left min-w-[130px]">TIN</th>
                            <th class="border px-3 py-2 text-left min-w-[180px]">Notes</th>
                            <th class="border px-3 py-2 text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody id="customerRows">
                        <tr class="customer-row">
                            <td class="border p-2">
                                <input type="text"
                                       name="customers[0][name]"
                                       required
                                       class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            </td>

                            <td class="border p-2">
                                <select name="customers[0][customer_type]"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                    <option value="Individual">Individual</option>
                                    <option value="Business">Business</option>
                                </select>
                            </td>

                            <td class="border p-2">
                                <input type="text"
                                       name="customers[0][phone]"
                                       class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            </td>

                            <td class="border p-2">
                                <input type="email"
                                       name="customers[0][email]"
                                       class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            </td>

                            <td class="border p-2">
                                <input type="number"
                                       name="customers[0][credit_limit]"
                                       value="0"
                                       min="0"
                                       step="0.01"
                                       class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            </td>

                            <td class="border p-2">
                                <input type="number"
                                       name="customers[0][opening_balance]"
                                       value="0"
                                       min="0"
                                       step="0.01"
                                       class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            </td>

                            <td class="border p-2">
                                <select name="customers[0][balance_type]"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                    <option value="Receivable">Receivable</option>
                                    <option value="Advance">Advance</option>
                                </select>
                            </td>

                            <td class="border p-2">
                                <textarea name="customers[0][address]"
                                          rows="1"
                                          class="w-full border border-slate-300 rounded-lg px-3 py-2"></textarea>
                            </td>

                            <td class="border p-2">
                                <input type="text"
                                       name="customers[0][trade_license]"
                                       class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            </td>

                            <td class="border p-2">
                                <input type="text"
                                       name="customers[0][tin]"
                                       class="w-full border border-slate-300 rounded-lg px-3 py-2">
                            </td>

                            <td class="border p-2">
                                <textarea name="customers[0][notes]"
                                          rows="1"
                                          class="w-full border border-slate-300 rounded-lg px-3 py-2"></textarea>
                            </td>

                            <td class="border p-2 text-center">
                                <button type="button"
                                        onclick="removeRow(this)"
                                        class="text-red-600 hover:text-red-800 font-medium">
                                    Remove
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-3 mt-5">
                <button type="button"
                        onclick="addRow()"
                        class="bg-emerald-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">
                    + Add Customer
                </button>

                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                    Save All Customers
                </button>

                <a href="{{ route('customers.index') }}"
                   class="bg-slate-100 text-slate-700 px-6 py-2 rounded-lg text-sm font-medium hover:bg-slate-200">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
let rowIndex = 1;

function addRow() {
    const tbody = document.getElementById('customerRows');

    const row = document.createElement('tr');
    row.className = 'customer-row';

    row.innerHTML = `
        <td class="border p-2">
            <input type="text"
                   name="customers[${rowIndex}][name]"
                   required
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </td>

        <td class="border p-2">
            <select name="customers[${rowIndex}][customer_type]"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2">
                <option value="Individual">Individual</option>
                <option value="Business">Business</option>
            </select>
        </td>

        <td class="border p-2">
            <input type="text"
                   name="customers[${rowIndex}][phone]"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </td>

        <td class="border p-2">
            <input type="email"
                   name="customers[${rowIndex}][email]"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </td>

        <td class="border p-2">
            <input type="number"
                   name="customers[${rowIndex}][credit_limit]"
                   value="0"
                   min="0"
                   step="0.01"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </td>

        <td class="border p-2">
            <input type="number"
                   name="customers[${rowIndex}][opening_balance]"
                   value="0"
                   min="0"
                   step="0.01"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </td>

        <td class="border p-2">
            <select name="customers[${rowIndex}][balance_type]"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2">
                <option value="Receivable">Receivable</option>
                <option value="Advance">Advance</option>
            </select>
        </td>

        <td class="border p-2">
            <textarea name="customers[${rowIndex}][address]"
                      rows="1"
                      class="w-full border border-slate-300 rounded-lg px-3 py-2"></textarea>
        </td>

        <td class="border p-2">
            <input type="text"
                   name="customers[${rowIndex}][trade_license]"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </td>

        <td class="border p-2">
            <input type="text"
                   name="customers[${rowIndex}][tin]"
                   class="w-full border border-slate-300 rounded-lg px-3 py-2">
        </td>

        <td class="border p-2">
            <textarea name="customers[${rowIndex}][notes]"
                      rows="1"
                      class="w-full border border-slate-300 rounded-lg px-3 py-2"></textarea>
        </td>

        <td class="border p-2 text-center">
            <button type="button"
                    onclick="removeRow(this)"
                    class="text-red-600 hover:text-red-800 font-medium">
                Remove
            </button>
        </td>
    `;

    tbody.appendChild(row);
    rowIndex++;
}

function removeRow(button) {
    const rows = document.querySelectorAll('.customer-row');

    if (rows.length <= 1) {
        return;
    }

    button.closest('tr').remove();
}
</script>
@endsection