{{--
    Nexton Accounts — Role & Permission Module
    Module: Users — Role Assignment
    Extends: layouts.app

    This view shows which roles/permissions a user currently holds.
    It can be reached from the user show/edit page. Controller wiring
    is added in UserController::show() — data variables below:
      $user, $availableRoles, $userRoles (collection),
      $groups, $permissions, $directPermissions
--}}
@extends('layouts.app')

@section('page-title', 'User Roles — ' . $user->name)

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('system.users.index') }}"
           class="text-blue-600 hover:text-blue-800 font-medium">&larr; Back to Users</a>
    </div>

    {{-- User identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-4">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-lg font-bold text-white shadow">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </span>
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ $user->name }}</h2>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    @forelse ($userRoles as $r)
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-0.5 text-xs font-semibold text-blue-800">
                            {{ ucfirst($r->name) }}
                        </span>
                    @empty
                        <span class="text-xs italic text-gray-400">no role assigned</span>
                    @endforelse
                    @if ($directPermissions->isNotEmpty())
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-0.5 text-xs font-semibold text-amber-800">
                            +{{ $directPermissions->count() }} direct permission(s)
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Effective permissions grouped by module --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-bold text-gray-800">Effective Permissions</h3>

        <div class="space-y-6">
            @foreach ($groups as $group)
                @php $groupPermissions = $permissions->where('group', $group); @endphp
                @if ($groupPermissions->isNotEmpty())
                    <div>
                        <h4 class="mb-2 text-sm font-bold uppercase tracking-wide text-gray-500">{{ ucfirst($group) }}</h4>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-2 md:grid-cols-3">
                            @foreach ($groupPermissions as $permission)
                                @php $has = $userPermissions->contains('name', $permission->name); @endphp
                                <span class="flex items-center gap-2 text-sm {{ $has ? 'text-emerald-700' : 'text-gray-300' }}">
                                    @if ($has)
                                        <svg class="h-4 w-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4 text-gray-200" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                                        </svg>
                                    @endif
                                    {{ $permission->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Assign / revoke role buttons (admin only) --}}
    @can('roles.manage')
    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-bold text-gray-800">Assign Role</h3>
        <form method="POST" action="{{ route('system.users.roles.update', $user) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            @method('PUT')

            <div class="min-w-48">
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" id="role"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none">
                    @foreach ($availableRoles as $r)
                        <option value="{{ $r->name }}" {{ $user->hasRole($r->name) ? 'selected' : '' }}>
                            {{ ucfirst($r->name) }} — {{ $r->permissions->count() }} permission(s)
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Update Role
            </button>
        </form>
    </div>
    @endcan
</div>
@endsection
