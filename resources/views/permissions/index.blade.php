{{--
    Nexton Accounts — Role & Permission Module
    Module: Permissions — Index
    Extends: layouts.app
--}}
@extends('layouts.app')

@section('page-title', 'Permission Registry')

@section('content')
<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Permission Registry</h1>
                <p class="text-sm text-gray-500 mt-1">
                    All permissions in the system, grouped by module
                    (module.action convention).
                </p>
            </div>
            @can('permissions.manage')
            <a href="{{ route('system.permissions.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5
                      text-sm font-semibold text-white shadow-sm transition
                      hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2">
                Add Permission
            </a>
            @endcan
        </div>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route('system.permissions.index') }}"
              class="mb-6 flex flex-wrap items-center gap-3">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('system.permissions.index') }}"
                   class="rounded-full border px-3 py-1 text-xs font-semibold
                          {{ request()->is('system/permissions') && !request('group') ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                    All ({{ $permissions->count() }})
                </a>
                @foreach ($groupsWithCounts as $g)
                    <a href="{{ route('system.permissions.index', ['group' => $g['name']]) }}"
                       class="rounded-full border px-3 py-1 text-xs font-semibold
                              {{ request('group') === $g['name'] ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                        {{ ucfirst($g['name']) }} ({{ $g['count'] }})
                    </a>
                @endforeach
            </div>
            <div class="ml-auto">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Search permission..."
                       class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none">
            </div>
        </form>

        {{-- Permissions Grid --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($permissions as $permission)
                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50">
                        <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Z"
                                  clip-rule="evenodd" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-800">{{ $permission->name }}</p>
                        <p class="text-xs text-gray-400">{{ ucfirst((string) $permission->group) }}</p>
                    </div>
                </div>
            @empty
                <p class="col-span-full rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-400">
                    No permissions found.
                </p>
            @endforelse
        </div>
    </div>
</div>
@endsection
