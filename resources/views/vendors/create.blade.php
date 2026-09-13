@extends('layouts.app')

@section('page-title', 'Add Vendors')

@section('page-subtitle', 'Create multiple vendors/suppliers')

@section('content')

<div class="max-w-5xl mx-auto">

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

            <div id="vendor-rows">

                <!-- Vendor Row -->
                <div class="vendor-row border border-slate-200 rounded-xl p-5 mb-5 bg-slate-50">

                    <div class="flex items-center justify-between mb-4">
                        <h3 class="vendor-title text-base font-semibold text-slate-700">
                            Vendor #1
                        </h3>

                        <button type="button"
                                class="remove-vendor hidden text-sm text-red-600 hover:text-red-700 font-medium">
                            Remove
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Vendor Name *
                            </label>

                            <input type="text"
                                   name="vendors[0][name]"
                                   required
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Accounting Account *
                            </label>

                            <select name="vendors[0][account_id]"
                                    required
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                                <option value="">
                                    -- Select Accounting Account --
                                </option>

                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->account_code }} - {{ $account->account_name }}
                                    </option>
                                @endforeach

                            </select>

                            <p class="text-xs text-slate-500 mt-1">
                                Select the accounting account used for this vendor.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Phone
                            </label>

                            <input type="text"
                                   name="vendors[0][phone]"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Email
                            </label>

                            <input type="email"
                                   name="vendors[0][email]"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Address
                            </label>

                            <textarea name="vendors[0][address]"
                                      rows="2"
                                      class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                             focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Trade License
                            </label>

                            <input type="text"
                                   name="vendors[0][trade_license]"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                TIN
                            </label>

                            <input type="text"
                                   name="vendors[0][tin]"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Opening Balance (৳)
                            </label>

                            <input type="number"
                                   name="vendors[0][opening_balance]"
                                   value="0"
                                   min="0"
                                   step="0.01"
                                   class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Balance Type
                            </label>

                            <select name="vendors[0][balance_type]"
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                                <option value="Payable">
                                    Payable (আমরা দেব)
                                </option>

                                <option value="Advance">
                                    Advance (আমরা পাব)
                                </option>

                            </select>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Actions -->
            <div class="flex flex-wrap items-center gap-3 pt-2">

                <button type="button"
                        id="add-vendor"
                        class="bg-slate-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium
                               hover:bg-slate-800 transition">
                    + Add Vendor
                </button>

                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium
                               hover:bg-blue-700 transition">
                    Save Vendors
                </button>

                <a href="{{ route('vendors.index') }}"
                   class="bg-slate-100 text-slate-700 px-6 py-2.5 rounded-lg text-sm font-medium
                          hover:bg-slate-200 transition">
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

    newRow.querySelector('.vendor-title').textContent =
        'Vendor #' + (vendorIndex + 1);

    newRow.querySelector('.remove-vendor').classList.remove('hidden');

    newRow.querySelectorAll('input, textarea, select').forEach(function (field) {

        field.name = field.name.replace(
            /\[0\]/,
            '[' + vendorIndex + ']'
        );

        if (field.tagName === 'SELECT') {
            field.selectedIndex = 0;
        } else if (field.name.includes('[opening_balance]')) {
            field.value = 0;
        } else {
            field.value = '';
        }
    });

    container.appendChild(newRow);

    vendorIndex++;
});

document.addEventListener('click', function (event) {

    if (!event.target.classList.contains('remove-vendor')) {
        return;
    }

    const row = event.target.closest('.vendor-row');

    if (row) {
        row.remove();
        updateVendorTitles();
    }
});

function updateVendorTitles() {

    document.querySelectorAll('.vendor-row').forEach(function (row, index) {

        const title = row.querySelector('.vendor-title');

        if (title) {
            title.textContent = 'Vendor #' + (index + 1);
        }

    });
}
</script>

@endsection