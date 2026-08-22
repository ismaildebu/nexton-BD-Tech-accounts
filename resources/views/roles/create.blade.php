{{--
    Nexton Accounts — Role & Permission Module
    Module: Roles — Create
    Extends: layouts.app
--}}
@extends('layouts.app')

@section('page-title', 'Create Role')

@section('content')
<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">

        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('system.roles.index') }}"
               class="text-blue-600 hover:text-blue-800 font-medium">&larr; Back to Roles</a>
        </div>

        <form method="POST" action="{{ route('system.roles.store') }}" class="space-y-6">
            @csrf

            {{-- Basic Info --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Basic Information</h2>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Role Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="e.g. branch_manager"
                           required
                           pattern="[a-z][a-z0-9_\-]+"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-400">
                        Lowercase, letters/numbers/underscores/hyphens. Spaces are not allowed.
                    </p>
                </div>
            </div>

            {{-- Permission Assignment --}}
            @include('roles.partials.form', [
                'groups' => $groups,
                'permissions' => $permissions,
                'selectedPermissions' => old('permissions', []),
            ])

            <div class="flex justify-end gap-3">
                <a href="{{ route('system.roles.index') }}"
                   class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                    Create Role
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
