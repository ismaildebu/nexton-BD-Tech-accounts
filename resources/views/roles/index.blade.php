{{--
    Nexton Accounts — Role & Permission Module
    Module: Roles — Index
    Extends: layouts.app
--}}
@extends('layouts.app')

@section('page-title', 'Roles & Permissions')

@section('content')
<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Roles &amp; Permissions</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Define roles and control what each role can do across the system.
                </p>
            </div>
            @can('roles.manage')
            <a href="{{ route('system.roles.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5
                      text-sm font-semibold text-white shadow-sm transition
                      hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2">
                Create New Role
            </a>
            @endcan
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3
                        text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3
                        text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Roles Table --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">#</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Role</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Permissions</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-600">Created</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($roles as $role)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1
                                             text-xs font-semibold text-blue-800">
                                    {{ ucfirst($role->name) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ $role->permissions_count }} permission(s)
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $role->created_at->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('system.roles.show', $role) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                                @can('roles.manage')
                                    &middot;
                                    <a href="{{ route('system.roles.edit', $role) }}"
                                       class="text-amber-600 hover:text-amber-800 font-medium">Edit</a>
                                    @if (!in_array($role->name, ['admin', 'manager', 'accountant', 'cashier', 'sales', 'auditor', 'viewer'], true))
                                        &middot;
                                        <form method="POST" action="{{ route('system.roles.destroy', $role) }}"
                                              class="inline"
                                              onsubmit="return confirm('Delete this role?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                No roles found. Run the seeder first:
                                <code class="rounded bg-gray-100 px-2 py-1 text-xs">
                                    php artisan db:seed --class=RoleAndPermissionSeeder
                                </code>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
