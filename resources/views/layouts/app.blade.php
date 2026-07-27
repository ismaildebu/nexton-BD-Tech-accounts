<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <!-- মূল কন্টেইনার: flex দিয়ে পুরো পেজ দুই ভাগে বিভক্ত -->
        <div class="min-h-screen flex flex-row bg-gray-100">
            
            <!-- ১. বাম পাশের সাইডবার (নির্দিষ্ট চওড়া w-64) -->
            @include('layouts.navigation')

            <!-- ২. ডান পাশের মূল কন্টেন্ট এলাকা (বাকি জায়গা জুড়ে থাকবে) -->
            <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
                
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- ড্যাশবোর্ডের মূল কার্ড ও টেবিলগুলো এখানে থাকবে -->
                <main class="flex-1 p-6">
                    {{ $slot }}
                </main>
            </div>

        </div>
    </body>
</html>