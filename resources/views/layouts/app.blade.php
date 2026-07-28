<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · Nexton Accounts</title>

    {{-- Tailwind CDN for quick preview. In production, compile via Vite:
         npm install -D tailwindcss @tailwindcss/vite  and import in resources/css/app.css --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'nexton-teal': {
                            950: '#04211d',
                            900: '#062b25',
                            800: '#0a3a32',
                            700: '#0f4d42',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                },
            },
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Chart.js for all dashboard visualizations --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #0a3a32; border-radius: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
    </style>

    @stack('styles')
</head>
<body class="bg-slate-100 text-slate-800 antialiased">
<div class="flex min-h-screen">

    {{-- =================== SIDEBAR =================== --}}
    <aside class="hidden lg:flex lg:flex-col w-64 shrink-0 bg-gradient-to-b from-nexton-teal-950 via-nexton-teal-900 to-nexton-teal-800 text-emerald-50">
        <div class="flex items-center gap-3 px-6 py-6 border-b border-emerald-900/40">
            <div class="h-9 w-9 rounded-xl bg-emerald-400 flex items-center justify-center font-extrabold text-nexton-teal-950 shadow-lg shadow-emerald-500/30">
                N
            </div>
            <div>
                <p class="font-bold text-lg leading-tight tracking-tight">Nexton</p>
                <p class="text-xs text-emerald-300/70 -mt-0.5">Accounts</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-6 space-y-1">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-emerald-400/60">Main</p>

            <a href="{{ route('dashboard.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                      {{ request()->routeIs('dashboard.*') ? 'bg-emerald-500/15 text-emerald-300 shadow-inner shadow-emerald-500/10' : 'text-emerald-100/80 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h4a1 1 0 001-1V10"/></svg>
                Dashboard
            </a>

            <a href="{{ route('invoices.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                      {{ request()->routeIs('invoices.*') ? 'bg-emerald-500/15 text-emerald-300' : 'text-emerald-100/80 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"/></svg>
                Invoices
            </a>

            <a href="{{ route('expenses.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                      {{ request()->routeIs('expenses.*') ? 'bg-emerald-500/15 text-emerald-300' : 'text-emerald-100/80 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a4 4 0 00-8 0v2M5 9h14l-1 11H6L5 9z"/></svg>
                Expenses
            </a>

            <a href="{{ route('reports.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                      {{ request()->routeIs('reports.*') ? 'bg-emerald-500/15 text-emerald-300' : 'text-emerald-100/80 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3v18M3 11h8M3 17h8m4-14h4v14h-4z"/></svg>
                Reports
            </a>

            <a href="{{ route('banking.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                      {{ request()->routeIs('banking.*') ? 'bg-emerald-500/15 text-emerald-300' : 'text-emerald-100/80 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4 10h16M5 10V7l7-4 7 4v3M6 21v-7m4 7v-7m4 7v-7m4 7v-7"/></svg>
                Banking
            </a>

            <p class="px-3 pt-6 pb-2 text-[11px] font-semibold uppercase tracking-wider text-emerald-400/60">System</p>

            <a href="{{ route('settings.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                      {{ request()->routeIs('settings.*') ? 'bg-emerald-500/15 text-emerald-300' : 'text-emerald-100/80 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Settings
            </a>
        </nav>

        <div class="px-4 py-4 border-t border-emerald-900/40">
            <div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-3">
                <div class="h-9 w-9 rounded-full bg-emerald-400/90 flex items-center justify-center text-nexton-teal-950 font-bold text-sm">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <div class="text-sm">
                    <p class="font-semibold leading-tight">{{ auth()->user()->name ?? 'Admin User' }}</p>
                    <p class="text-emerald-300/60 text-xs">{{ auth()->user()->email ?? 'admin@nexton.test' }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- =================== MAIN CONTENT =================== --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-800">@yield('page-title', 'Dashboard')</h1>
                <p class="text-sm text-slate-400">@yield('page-subtitle', 'Welcome back — here is your financial overview.')</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center gap-2 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-3 py-1.5">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live data
                </span>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
