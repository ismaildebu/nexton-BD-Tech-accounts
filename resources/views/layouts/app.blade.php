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

    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-emerald-400/60">
        Main
    </p>

    <a href="{{ route('dashboard.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">Dashboard</a>

    <a href="{{ route('companies.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">Companies</a>

    <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">Accounts</a>

    <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">Transactions</a>

    <a href="{{ route('ledger.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">Ledger</a>

    <a href="{{ route('journal-vouchers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">Journal Vouchers</a>

    <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">Invoices</a>

    <a href="{{ route('expenses.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">Expenses</a>

    <a href="{{ route('banking.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">Banking</a>

    <div x-data="{ openReports: true }">

        <button @click="openReports=!openReports"
            class="w-full flex justify-between items-center px-3 py-2.5 rounded-xl text-sm font-medium">
            <span>Reports</span>
            <span x-text="openReports ? '−' : '+'"></span>
        </button>

        <div x-show="openReports" class="ml-4 mt-1 space-y-1">

            <a href="{{ route('trial-balance.index') }}" class="block px-3 py-2">
                Trial Balance
            </a>

            <a href="{{ route('profit-loss.index') }}" class="block px-3 py-2">
                Profit &amp; Loss
            </a>

            <a href="{{ route('balance-sheet.index') }}" class="block px-3 py-2">
                Balance Sheet
            </a>

            <a href="{{ route('cash-flow.index') }}" class="block px-3 py-2">
                Cash Flow
            </a>

        </div>

    </div>

    <p class="px-3 pt-5 pb-2 text-[11px] font-semibold uppercase tracking-wider text-emerald-400/60">
        System
    </p>

    <a href="{{ route('voucher-types.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
        Voucher Types
    </a>

    <a href="{{ route('financial-years.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
        Financial Years
    </a>

    <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium">
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
                    
                    <form action="{{ route('switch.company') }}" method="POST">
    @csrf

    <select
        name="company_id"
        onchange="this.form.submit()"
        class="border border-slate-300 rounded-lg px-3 py-2 text-sm"
    >
        @foreach(\App\Models\Company::orderBy('company_name')->get() as $company)
            <option value="{{ $company->id }}"
                {{ session('company_id') == $company->id ? 'selected' : '' }}>
                {{ $company->company_name }}
            </option>
        @endforeach
    </select>
</form>
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live data
                </span>
            </div>
        </header>

                            <main class="flex-1 p-4 sm:p-6">

                                    @hasSection('header')
                                        <div class="mb-6">
                                            @yield('header')
                                        </div>
                                    @endif

                                    @yield('content')
                             </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
