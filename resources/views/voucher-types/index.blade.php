@extends('layouts.app')

@section('title','VoucherTypes')

@section('page-title','VoucherTypes')

@section('page-subtitle','Manage your company Vouchers')

@section('content')
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-semibold text-2xl text-gray-800">
                        Voucher Types
                    </h2>

                    <a href="{{ route('voucher-types.create') }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        + Add Voucher Type
                    </a>
                </div>

                <table class="w-full border border-gray-300">

                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">Company</th>
                            <th class="border p-2">Voucher Name</th>
                            <th class="border p-2">Code</th>
                            <th class="border p-2">Nature</th>
                            <th class="border p-2">Active</th>
                            <th class="border p-2 text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    @forelse($voucherTypes as $voucherType)

                        <tr>

                            <td class="border p-2">
                                {{ $voucherType->company->company_name ?? '' }}
                            </td>

                            <td class="border p-2">
                                {{ $voucherType->name }}
                            </td>

                            <td class="border p-2">
                                {{ $voucherType->code }}
                            </td>

                            <td class="border p-2">
                                {{ $voucherType->nature_label }}
                            </td>

                            <td class="border p-2 text-center">
                                {{ $voucherType->is_active ? 'Yes' : 'No' }}
                            </td>

                    <td class="border p-2 text-center">

    <a href="{{ route('voucher-types.show', $voucherType) }}"
       class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
        View
    </a>

    <a href="{{ route('voucher-types.edit', $voucherType) }}"
       class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 mx-1">
        Edit
    </a>

    <form action="{{ route('voucher-types.destroy', $voucherType) }}"
          method="POST"
          class="inline">

        @csrf
        @method('DELETE')

        <button type="submit"
                class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700"
                onclick="return confirm('Are you sure you want to delete this Voucher Type?')">
            Delete
        </button>

    </form>

</td>       


                            

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="border p-4 text-center">
                                No Voucher Type Found
                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>
    </div>

@endsection