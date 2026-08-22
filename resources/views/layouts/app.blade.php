<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · Nexton Accounts</title>
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #0a3a32; border-radius: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-100 text-slate-800 antialiased">

<div x-data="{ sidebarOpen: false }" class="flex min-h-screen">

    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/50 z-20 lg:hidden"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed lg:static lg:translate-x-0 z-30 flex flex-col w-64 shrink-0
                  bg-gradient-to-b from-nexton-teal-950 via-nexton-teal-900 to-nexton-teal-800
                  text-emerald-50 min-h-screen transition-transform duration-300 ease-in-out">

        <div class="flex items-center gap-3 px-6 py-5 border-b border-emerald-900/40">
            <div class="h-9 w-9 rounded-xl bg-emerald-400 flex items-center justify-center font-extrabold text-nexton-teal-950 shadow-lg">N</div>
            <div>
                <p class="font-bold text-lg leading-tight">Nexton</p>
                <p class="text-xs text-emerald-300/70 -mt-0.5">Accounts ERP</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 overflow-y-auto text-sm space-y-0.5">
            @php $activeCompany = \App\Models\Company::find(session('company_id')); @endphp

            {{-- MAIN --}}
            <p class="px-3 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400/60">Main</p>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('dashboard') ? 'bg-white/10' : '' }}">🏠 Dashboard</a>
            <a href="{{ route('companies.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('companies.*') ? 'bg-white/10' : '' }}">🏢 Companies</a>
            <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('accounts.*') ? 'bg-white/10' : '' }}">📒 Accounts</a>
            <a href="{{ route('vouchers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('vouchers.*') ? 'bg-white/10' : '' }}">📝 Vouchers</a>
            <a href="{{ route('ledger.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('ledger.*') ? 'bg-white/10' : '' }}">📖 Ledger</a>

            {{-- SALES --}}
            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400/60">Sales</p>
            <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('customers.*') ? 'bg-white/10' : '' }}">👥 Customers</a>
            @if($activeCompany?->hasModule('sales-orders'))
            <a href="{{ route('sales-orders.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('sales-orders.*') ? 'bg-white/10' : '' }}">📦 Sales Orders</a>
            @endif
            <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('invoices.*') ? 'bg-white/10' : '' }}">🧾 Invoices</a>

            {{-- PURCHASE --}}
            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400/60">Purchase</p>
            <a href="{{ route('vendors.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('vendors.*') ? 'bg-white/10' : '' }}">🏪 Vendors</a>
            <a href="{{ route('purchase-orders.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('purchase-orders.*') ? 'bg-white/10' : '' }}">🛒 Purchase Orders</a>
            <a href="{{ route('purchase-bills.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('purchase-bills.*') ? 'bg-white/10' : '' }}">📋 Purchase Bills</a>

            {{-- INVENTORY --}}
            @if($activeCompany?->hasModule('inventory'))
            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400/60">Inventory</p>
            <div x-data="{ openInventory: {{ request()->routeIs('inventory.*') ? 'true' : 'false' }} }">
                <button @click="openInventory = !openInventory"
                        class="w-full flex justify-between items-center px-3 py-2 rounded-xl font-medium hover:bg-white/10">
                    <span>📦 Inventory</span>
                    <span x-text="openInventory ? '−' : '+'"></span>
                </button>
                <div x-show="openInventory" x-cloak class="ml-3 mt-0.5 space-y-0.5">
                    <a href="{{ route('inventory.products') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Products</a>
                    <a href="{{ route('inventory.stock-in') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Stock In</a>
                    <a href="{{ route('inventory.stock-out') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Stock Out</a>
                    <a href="{{ route('inventory.movements') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Movements</a>
                    <a href="{{ route('inventory.stock-report') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Stock Report</a>
                    <a href="{{ route('inventory.warehouses') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Warehouses</a>
                    <a href="{{ route('stock-transfers.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Stock Transfers</a>
                </div>
            </div>
            @endif

            {{-- MEDIA --}}
            @if($activeCompany?->hasModule('media'))
            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400/60">Media</p>
            <div x-data="{ openMedia: {{ request()->routeIs('media.*') ? 'true' : 'false' }} }">
                <button @click="openMedia = !openMedia"
                        class="w-full flex justify-between items-center px-3 py-2 rounded-xl font-medium hover:bg-white/10">
                    <span>📰 Media</span>
                    <span x-text="openMedia ? '−' : '+'"></span>
                </button>
                <div x-show="openMedia" x-cloak class="ml-3 mt-0.5 space-y-0.5">
                    <a href="{{ route('media.publications.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10 {{ request()->routeIs('media.publications.*') ? 'bg-white/10' : '' }}">Publications</a>
                    <a href="{{ route('media.parties.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10 {{ request()->routeIs('media.parties.*') ? 'bg-white/10' : '' }}">Agents &amp; Hawkers</a>
                    <a href="{{ route('media.print-plans.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10 {{ request()->routeIs('media.print-plans.*') ? 'bg-white/10' : '' }}">Print Planning</a>
                    <a href="{{ route('media.print-orders.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10 {{ request()->routeIs('media.print-orders.*') ? 'bg-white/10' : '' }}">Print Orders</a>
                    <a href="{{ route('media.distributions.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10 {{ request()->routeIs('media.distributions.*') ? 'bg-white/10' : '' }}">Distribution</a>
                    <a href="{{ route('media.returns.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10 {{ request()->routeIs('media.returns.*') ? 'bg-white/10' : '' }}">Returns</a>
                    <a href="{{ route('media.collections.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10 {{ request()->routeIs('media.collections.*') ? 'bg-white/10' : '' }}">Collections</a>
                </div>
            </div>
            @endif

            {{-- FINANCE --}}
            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400/60">Finance</p>
            <a href="{{ route('expenses.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('expenses.*') ? 'bg-white/10' : '' }}">💸 Expenses</a>
            <a href="{{ route('banking.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('banking.*') ? 'bg-white/10' : '' }}">🏦 Banking</a>
            <a href="{{ route('bank-accounts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('bank-accounts.*') ? 'bg-white/10' : '' }}">💳 Bank Accounts</a>
            <a href="{{ route('legal-documents.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('legal-documents.*') ? 'bg-white/10' : '' }}">📄 Legal Documents</a>

            {{-- HR --}}
            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400/60">HR</p>
            <a href="{{ route('employees.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('employees.*') ? 'bg-white/10' : '' }}">👤 Employees</a>
            <a href="{{ route('salaries.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('salaries.*') ? 'bg-white/10' : '' }}">💰 Salaries</a>

            {{-- REPORTS --}}
            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400/60">Reports</p>
            <div x-data="{ openReports: {{ request()->routeIs('trial-balance.*','profit-loss.*','balance-sheet.*','cash-flow.*') ? 'true' : 'false' }} }">
                <button @click="openReports = !openReports"
                        class="w-full flex justify-between items-center px-3 py-2 rounded-xl font-medium hover:bg-white/10">
                    <span>📊 Reports</span>
                    <span x-text="openReports ? '−' : '+'"></span>
                </button>
                <div x-show="openReports" x-cloak class="ml-3 mt-0.5 space-y-0.5">
                    <a href="{{ route('trial-balance.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Trial Balance</a>
                    <a href="{{ route('profit-loss.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Profit &amp; Loss</a>
                    <a href="{{ route('balance-sheet.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Balance Sheet</a>
                    <a href="{{ route('cash-flow.index') }}" class="block px-3 py-1.5 rounded-lg text-xs hover:bg-white/10">Cash Flow</a>
                </div>
            </div>

            {{-- SYSTEM --}}
            <p class="px-3 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-wider text-emerald-400/60">System</p>
            <a href="{{ route('voucher-types.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('voucher-types.*') ? 'bg-white/10' : '' }}">🏷️ Voucher Types</a>
            <a href="{{ route('financial-years.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('financial-years.*') ? 'bg-white/10' : '' }}">📅 Financial Years</a>
            <a href="{{ route('system.users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('system.users.*') ? 'bg-white/10' : '' }}">👥 Users</a>
            <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl font-medium hover:bg-white/10 {{ request()->routeIs('settings.*') ? 'bg-white/10' : '' }}">⚙️ Settings</a>

        </nav>

        <div class="px-4 py-4 border-t border-emerald-900/40">
            <div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-3 mb-3">
                <div class="h-9 w-9 rounded-full bg-emerald-400/90 flex items-center justify-center text-nexton-teal-950 font-bold text-sm">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <div class="text-sm overflow-hidden">
                    <p class="font-semibold leading-tight truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="text-emerald-300/60 text-xs truncate">{{ auth()->user()->email ?? '' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700">
                    Logout
                </button>
            </form>
        </div>

    </aside>

    <div class="flex-1 flex flex-col min-w-0">

        <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b border-slate-200 px-4 py-4 flex items-center justify-between gap-4">
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg bg-slate-100 hover:bg-slate-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex-1 min-w-0">
                <h1 class="text-lg font-bold text-slate-800 truncate">@yield('page-title', 'Dashboard')</h1>
                <p class="text-xs text-slate-400">@yield('page-subtitle', '')</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                @php $companies = \App\Models\Company::all(); @endphp
                @if($companies->isNotEmpty())
                    <form action="{{ route('switch.company') }}" method="POST">
                        @csrf
                        <select name="company_id" onchange="this.form.submit()"
                                class="border border-slate-300 rounded-lg px-2 py-1.5 text-xs bg-white text-slate-700 outline-none">
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ session('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name ?? $company->company_name ?? 'Company #'.$company->id }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <span class="hidden sm:flex items-center gap-1 text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2 py-1">
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Live
                </span>
            </div>
        </header>

        <div class="px-4 sm:px-6 pt-4">
            @include('partials.flash')
        </div>

        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>

    </div>
</div>

@stack('scripts')
</body>
</html>