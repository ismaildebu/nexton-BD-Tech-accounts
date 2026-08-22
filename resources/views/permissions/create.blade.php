{{--
    Nexton Accounts — Role & Permission Module
    Module: Permissions — Create
    Extends: layouts.app
--}}
@extends('layouts.app')

@section('page-title', 'Add Permission')

@section('content')
<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl mx-auto">

        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('system.permissions.index') }}"
               class="text-blue-600 hover:text-blue-800 font-medium">&larr; Back to Permissions</a>
        </div>

        <form method="POST" action="{{ route('system.permissions.store') }}"
              class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Permission Name</label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       placeholder="e.g. sales-orders.approve"
                       required
                       pattern="[a-z][a-z0-9\-\.]+"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-400">
                    Follow the <code>module.action</code> convention, e.g.
                    <code>vouchers.post</code>, <code>payroll.run</code>.
                </p>
            </div>

            <div>
                <label for="group" class="block text-sm font-medium text-gray-700 mb-1">Group (Module)</label>
                <input type="text"
                       id="group"
                       name="group"
                       value="{{ old('group') }}"
                       list="group-list"
                       placeholder="e.g. sales"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none">
                <datalist id="group-list">
                    @foreach ($groups as $g)
                        <option value="{{ $g }}">
                    @endforeach
                </datalist>
                @error('group')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('system.permissions.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                    Save Permission
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
