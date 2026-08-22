@extends('layouts.app')

@section('title','Edit Voucher Type')

@section('page-title','Edit Voucher Type')

@section('page-subtitle','Update company voucher information')

@section('header')

<div class="flex justify-between items-center">

    <h2 class="font-semibold text-2xl text-gray-800">
        Edit Voucher Type
    </h2>

</div>

@endsection

@section('content')

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('voucher-types.update', $voucherType) }}"
                      method="POST">

                    @csrf
                    @method('PUT')

                    <!-- Voucher Name -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">
                            Voucher Name
                        </label>

                        <input type="text"
                               name="name"
                               value="{{ old('name', $voucherType->name) }}"
                               class="w-full border rounded px-3 py-2"
                               required>
                    </div>

                    <!-- Voucher Code -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">
                            Voucher Code
                        </label>

                        <input type="text"
                               name="code"
                               value="{{ old('code', $voucherType->code) }}"
                               class="w-full border rounded px-3 py-2"
                               required>
                    </div>

                    <!-- Nature -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">
                            Nature
                        </label>

                        <select name="nature"
                                class="w-full border rounded px-3 py-2"
                                required>
                            @foreach(\App\Models\VoucherType::NATURES as $value => $label)
                                <option value="{{ $value }}" @selected(old('nature', $voucherType->nature) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Active -->
                    <div class="mb-6">
                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   {{ $voucherType->is_active ? 'checked' : '' }}>

                            <span class="ml-2">Active</span>
                        </label>
                    </div>

                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">
                        Update Voucher Type
                    </button>

                    <a href="{{ route('voucher-types.index') }}"
                       class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded ml-2">
                        Back
                    </a>

                </form>

            </div>

        </div>
    </div>

@endsection