{{--
    Nexton BD Tech Accounting Software
    Module: User Management — Index
    NOTE: This view extends "layouts.app". Adjust the @extends / @section
    names below if your application's master layout uses a different name
    (e.g. an <x-app-layout> Blade component).
--}}
@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-teal-950 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-teal-900 dark:text-white">User Management</h1>
                <p class="text-sm text-teal-600 dark:text-teal-300 mt-1">
                    Manage system users, roles, and account status.
                </p>
            </div>

            <a href="{{ route('system.users.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5
                      text-sm font-semibold text-white shadow-sm transition
                      hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                </svg>
                Add New User
            </a>
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50
                        px-4 py-3 text-sm font-medium text-emerald-800
                        dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 flex items-center gap-2 rounded-lg border border-red-300 bg-red-50
                        px-4 py-3 text-sm font-medium text-red-800
                        dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-7-4a1 1 0 1 0-2 0v4a1 1 0 0 0 2 0V6Zm-1 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Search --}}
        <form method="GET" action="{{ route('system.users.index') }}" class="mb-4">
            <div class="relative max-w-sm">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by name or email..."
                       class="w-full rounded-lg border border-teal-200 bg-white px-4 py-2 text-sm text-teal-900
                              shadow-sm focus:border-emerald-500 focus:ring-emerald-500
                              dark:border-teal-800 dark:bg-teal-900 dark:text-white dark:placeholder-teal-400">
                <button type="submit"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-teal-400 hover:text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </form>

        {{-- Table Card --}}
        <div class="overflow-hidden rounded-xl border border-teal-100 bg-white shadow-sm
                    dark:border-teal-800 dark:bg-teal-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-teal-100 dark:divide-teal-800">
                    <thead class="bg-teal-900 dark:bg-teal-950">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-teal-100">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-teal-100">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-teal-100">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-teal-100">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-teal-100">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-teal-50 dark:divide-teal-800">
                        @forelse ($users as $user)
                            <tr class="transition hover:bg-emerald-50/50 dark:hover:bg-teal-800/40">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full
                                                    bg-emerald-100 text-sm font-semibold text-emerald-700
                                                    dark:bg-emerald-900 dark:text-emerald-300">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <span class="text-sm font-medium text-teal-900 dark:text-white">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-teal-600 dark:text-teal-300">
                                    {{ $user->email }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @php
                                        $roleClasses = match ($user->role) {
                                            'Admin'   => 'bg-teal-900 text-white dark:bg-teal-700',
                                            'Manager' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300',
                                            default   => 'bg-gray-100 text-gray-700 dark:bg-teal-800 dark:text-teal-200',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $roleClasses }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($user->status)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1
                                                     text-xs font-semibold text-emerald-700
                                                     dark:bg-emerald-950 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1
                                                     text-xs font-semibold text-red-700
                                                     dark:bg-red-950 dark:text-red-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('system.users.edit', $user) }}"
                                           class="inline-flex items-center gap-1 rounded-lg border border-teal-200
                                                  bg-white px-3 py-1.5 text-xs font-semibold text-teal-700 shadow-sm
                                                  transition hover:bg-teal-50
                                                  dark:border-teal-700 dark:bg-teal-800 dark:text-teal-100 dark:hover:bg-teal-700">
                                            Edit
                                        </a>

                                        <form action="{{ route('system.users.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete {{ $user->name }}? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-red-200
                                                           bg-white px-3 py-1.5 text-xs font-semibold text-red-600 shadow-sm
                                                           transition hover:bg-red-50
                                                           dark:border-red-900 dark:bg-teal-800 dark:text-red-400 dark:hover:bg-red-950">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-teal-500 dark:text-teal-400">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-teal-100 px-6 py-4 dark:border-teal-800">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection