@extends('layouts.app')

@section('page-title', 'Add Vendors')

@section('page-subtitle', 'Create multiple vendors/suppliers')

@section('content')

<div class="max-w-full mx-auto">

    <div class="bg-white rounded-xl shadow-sm p-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">
                    Vendor Information
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Add one or more vendors at once.
                </p>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-5">
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('vendors.bulk-store') }}">
            @csrf

            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full min-w-[1500px] text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-3 text-left font-semibold text-slate-700">
                                Vendor Name *
                            </th>

                            <th class="px-3 py-3 text-left font-semibold text-slate-700">
                                Accounting Account *
                            </th>

                            <th class="px-3 py-3 text-left font-semibold text-slate-700">
                                Phone
                            </th>

                            <th class="px-3 py-3 text-left font-semibold text-slate-700">
                                Email
                            </th>

                            <th class="px-3 py-3 text-left font-semibold text-slate-700">
                                Opening Balance
                            </th>

                            <th class="px-3 py-3 text-left font-semibold text-slate-700">
                                Balance Type
                            </th>

                            <th class="px-3 py-3 text-left font-semibold text-slate-700">
                                Address
                            </th>

                            <th class="px-3 py-3 text-left font-semibold text-slate-700">
                                Trade License
                            </th>

                            <th class="px-3 py-3 text-left font-semibold text-slate-700">
                                TIN
                            </th>

                            <th class="px-3 py-3 text-center font-semibold text-slate-700">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody id="vendor-rows">

                        <tr class="vendor-row border-b border-slate-200">

                            <td class="px-3 py-3 align-top">
                                <input
                                    type="text"
                                    name="vendors[0][name]"
                                    required
                                    class="w-full min-w-[180px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </td>

                            <td class="px-3 py-3 align-top">
                                <select
                                    name="vendors[0][account_id]"
                                    required
                                    class="w-full min-w-[220px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">
                                        -- Select Accounting Account --
                                    </option>

                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->account_code }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td class="px-3 py-3 align-top">
                                <input
                                    type="text"
                                    name="vendors[0][phone]"
                                    class="w-full min-w-[130px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </td>

                            <td class="px-3 py-3 align-top">
                                <input
                                    type="email"
                                    name="vendors[0][email]"
                                    class="w-full min-w-[180px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </td>

                            <td class="px-3 py-3 align-top">
                                <input
                                    type="number"
                                    name="vendors[0][opening_balance]"
                                    value="0"
                                    min="0"
                                    step="0.01"
                                    class="w-full min-w-[130px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </td>

                            <td class="px-3 py-3 align-top">
                                <select
                                    name="vendors[0][balance_type]"
                                    class="w-full min-w-[160px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="Payable">
                                        Payable (আমরা দেব)
                                    </option>

                                    <option value="Advance">
                                        Advance (আমরা পাব)
                                    </option>
                                </select>
                            </td>

                            <td class="px-3 py-3 align-top">
                                <textarea
                                    name="vendors[0][address]"
                                    rows="1"
                                    class="w-full min-w-[180px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                ></textarea>
                            </td>

                            <td class="px-3 py-3 align-top">
                                <input
                                    type="text"
                                    name="vendors[0][trade_license]"
                                    class="w-full min-w-[150px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </td>

                            <td class="px-3 py-3 align-top">
                                <input
                                    type="text"
                                    name="vendors[0][tin]"
                                    class="w-full min-w-[130px] border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </td>

                            <td class="px-3 py-3 text-center align-top">
                                <button
                                    type="button"
                                    class="remove-vendor text-sm text-red-600 hover:text-red-700 font-medium hidden"
                                >
                                    Remove
                                </button>
                            </td>

                        </tr>

                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-5">

                <button
                    type="button"
                    id="add-vendor"
                    class="bg-slate-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-800 transition"
                >
                    + Add Vendor
                </button>

                <button
                    type="submit"
                    class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition"
                >
                    Save All Vendors
                </button>

                <a
                    href="{{ route('vendors.index') }}"
                    class="bg-slate-100 text-slate-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-slate-200 transition"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

<script>
    let vendorIndex = 1;

    document.getElementById('add-vendor').addEventListener('click', function () {

        const container = document.getElementById('vendor-rows');
        const firstRow = document.querySelector('.vendor-row');

        const newRow = firstRow.cloneNode(true);

        newRow.querySelectorAll('input, textarea, select').forEach(function (field) {

            field.name = field.name.replace(
                /\[0\]/,
                '[' + vendorIndex + ']'
            );

            if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
            } else if (field.name.includes('[opening_balance]')) {
                field.value = '0';
            } else {
                field.value = '';
            }
        });

        newRow.querySelector('.remove-vendor').classList.remove('hidden');

        container.appendChild(newRow);

        vendorIndex++;
    });

    document.addEventListener('click', function (event) {

        if (!event.target.classList.contains('remove-vendor')) {
            return;
        }

        const row = event.target.closest('.vendor-row');

        if (!row) {
            return;
        }

        row.remove();

        updateVendorRemoveButtons();
    });

    function updateVendorRemoveButtons() {

        const rows = document.querySelectorAll('.vendor-row');
        const removeButtons = document.querySelectorAll('.remove-vendor');

        removeButtons.forEach(function (button) {
            button.classList.toggle('hidden', rows.length <= 1);
        });
    }
</script>

@endsection
