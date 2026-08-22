{{--
    Nexton Accounts — Role & Permission Module
    Module: Roles — Show
    Extends: layouts.app
--}}
@extends('layouts.app')

@section('page-title', 'Role: ' . ucfirst($role->name))

@section('content')
<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">

        <div class="mb-6 flex items-center gap-3">
            <a href="{{ route('system.roles.index') }}"
               class="text-blue-600 hover:text-blue-800 font-medium">&larr; Back to Roles</a>
        </div>

        {{-- Header Card --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1
                                 text-xs font-semibold text-blue-800">
                        {{ ucfirst($role->name) }}
                    </span>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ $role->permissions->count() }} permission(s) &middot;
                        {{ $members }} user(s) assigned
                    </p>
                </div>
                @can('roles.manage')
                    <a href="{{ route('system.roles.edit', $role) }}"
                       class="inline-flex items-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">
                        Edit Role
                    </a>
                @endcan
            </div>
        </div>

        {{-- Permissions grouped by module --}}
        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Permissions</h2>

            <div class="space-y-6">
                @forelse ($groups as $group)
                    @php $groupPermissions = $role->permissions->where('group', $group) @endphp
                    @if ($groupPermissions->isNotEmpty())
                        <div>
                            <h3 class="mb-2 text-sm font-bold uppercase tracking-wide text-gray-500">
                                {{ ucfirst($group) }}
                            </h3>
                            <div class="grid grid-cols-2 gap-x-6 gap-y-2 md:grid-cols-3">
                                @foreach ($groupPermissions as $permission)
                                    <span class="flex items-center gap-2 text-sm text-emerald-700">
                                        <svg class="h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                  d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z"
                                                  clip-rule="evenodd" />
                                        </svg>
                                        {{ $permission->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @empty
                    <p class="text-sm text-gray-400">No permissions assigned.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
