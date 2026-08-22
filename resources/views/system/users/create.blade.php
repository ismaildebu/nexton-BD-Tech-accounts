{{--
    Nexton BD Tech Accounting Software
    Module: User Management — Create
--}}
@extends('layouts.app')

@section('title', 'Add New User')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-teal-950 py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-teal-900 dark:text-white">Add New User</h1>
            <p class="text-sm text-teal-600 dark:text-teal-300 mt-1">
                Create a new user account for the Nexton BD Tech system.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800
                        dark:border-red-800 dark:bg-red-950 dark:text-red-300">
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl border border-teal-100 bg-white p-6 shadow-sm dark:border-teal-800 dark:bg-teal-900">
            <form action="{{ route('system.users.store') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-semibold text-teal-800 dark:text-teal-200">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="mt-1.5 block w-full rounded-lg border border-teal-200 bg-white px-3.5 py-2.5 text-sm
                                  text-teal-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500
                                  dark:border-teal-700 dark:bg-teal-950 dark:text-white">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-teal-800 dark:text-teal-200">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           class="mt-1.5 block w-full rounded-lg border border-teal-200 bg-white px-3.5 py-2.5 text-sm
                                  text-teal-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500
                                  dark:border-teal-700 dark:bg-teal-950 dark:text-white">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-teal-800 dark:text-teal-200">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="password" name="password" required
                               class="mt-1.5 block w-full rounded-lg border border-teal-200 bg-white px-3.5 py-2.5 text-sm
                                      text-teal-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500
                                      dark:border-teal-700 dark:bg-teal-950 dark:text-white">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-teal-800 dark:text-teal-200">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                               class="mt-1.5 block w-full rounded-lg border border-teal-200 bg-white px-3.5 py-2.5 text-sm
                                      text-teal-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500
                                      dark:border-teal-700 dark:bg-teal-950 dark:text-white">
                    </div>
                </div>

                {{-- Role --}}
                <div>
                    <label for="role" class="block text-sm font-semibold text-teal-800 dark:text-teal-200">
                        Role <span class="text-red-500">*</span>
                    </label>
                    <select id="role" name="role" required
                            class="mt-1.5 block w-full rounded-lg border border-teal-200 bg-white px-3.5 py-2.5 text-sm
                                   text-teal-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500
                                   dark:border-teal-700 dark:bg-teal-950 dark:text-white">
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Company — only Super Admin picks; Admin's own company is used automatically --}}
                @if ($companies->isNotEmpty())
                    <div>
                        <label for="company_id" class="block text-sm font-semibold text-teal-800 dark:text-teal-200">
                            Company <span class="text-red-500">*</span>
                        </label>
                        <select id="company_id" name="company_id"
                                class="mt-1.5 block w-full rounded-lg border border-teal-200 bg-white px-3.5 py-2.5 text-sm
                                       text-teal-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500
                                       dark:border-teal-700 dark:bg-teal-950 dark:text-white">
                            <option value="">— Select Company —</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" {{ (string) old('company_id') === (string) $company->id ? 'selected' : '' }}>
                                    {{ $company->company_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Status --}}
                <div class="flex items-center gap-3 rounded-lg border border-teal-100 bg-teal-50/50 px-4 py-3
                            dark:border-teal-800 dark:bg-teal-950/50">
                    <input type="checkbox" id="status" name="status" value="1"
                           {{ old('status', true) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-teal-300 text-emerald-600 focus:ring-emerald-500">
                    <label for="status" class="text-sm font-medium text-teal-800 dark:text-teal-200">
                        Active account (user can log in immediately)
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 border-t border-teal-100 pt-5 dark:border-teal-800">
                    <a href="{{ route('system.users.index') }}"
                       class="rounded-lg border border-teal-200 bg-white px-4 py-2.5 text-sm font-semibold text-teal-700
                              shadow-sm transition hover:bg-teal-50
                              dark:border-teal-700 dark:bg-teal-800 dark:text-teal-100 dark:hover:bg-teal-700">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm
                                   transition hover:bg-emerald-500 focus:outline-none focus:ring-2
                                   focus:ring-emerald-400 focus:ring-offset-2">
                        Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
