<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Nexton Accounts ERP') }}</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>
<body class="bg-slate-100 font-[Inter]">
    <div class="min-h-screen flex">

        {{-- Sidebar --}}
        @include('layouts.navigation')

        {{-- Main Area --}}
       <div class="flex-1 flex flex-col ml-64">

            {{-- Top Header --}}
            @isset($header)
            <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-40">
                <div class="h-20 px-8 flex items-center justify-between">
                    <div>
                        {{ $header }}
                    </div>

                    <div class="flex items-center space-x-6">
                        {{-- Search Bar --}}
                        <div class="hidden lg:block">
                            <input type="text"
                                   placeholder="Search..."
                                   class="w-72 px-4 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-slate-50 text-slate-800 placeholder-slate-400 transition">
                        </div>

                        {{-- Notification Bell --}}
                        <button class="relative w-11 h-11 rounded-xl bg-slate-100 hover:bg-slate-200 transition flex items-center justify-center text-xl"
                                title="Notifications">
                            🔔
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center font-bold">
                                3
                            </span>
                        </button>

                        {{-- User Profile --}}
                        <div class="flex items-center space-x-3 pl-6 border-l border-slate-200">
                            <div class="text-right">
                                <h4 class="font-semibold text-slate-800 text-sm">
                                    {{ Auth::user()->name }}
                                </h4>
                                <p class="text-xs text-slate-500">
                                    Administrator
                                </p>
                            </div>
                            <button class="w-11 h-11 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold hover:bg-blue-700 transition text-sm"
                                    title="User Menu">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            @endisset

            {{-- Main Content --}}
            <main class="flex-1 overflow-y-auto bg-slate-100">
                <div class="p-8 bg-slate-100 min-h-screen">
                    {{ $slot }}
                </div>
            </main>

        </div>
    </div>

    {{-- Scroll to Top Button (Optional) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add any global scripts here
            console.log('Nexton Accounts ERP Loaded');
        });
    </script>
</body>
</html>