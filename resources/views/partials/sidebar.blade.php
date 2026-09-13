{{-- ============================================================
     SIDEBAR — Nexton Accounts ERP
     - Dark teal gradient background
     - Collapsible sections with Alpine.js
     - Permission & module aware
     - Active route indicators
     - Bootstrap Icons
     - Company badge
     - User profile footer
============================================================ --}}

@php
    $navLink = fn(string $route, string $pattern = '') =>
        'flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium transition-all duration-150 '
        . (request()->routeIs($pattern ?: $route)
            ? 'bg-white/15 text-white shadow-sm'
            : 'text-slate-400 hover:bg-white/10 hover:text-slate-100');

    $groupActive = fn(array $patterns) =>
        request()->routeIs($patterns)
            ? 'bg-white/15 text-white shadow-sm'
            : 'text-slate-300 hover:bg-white/8 hover:text-white';
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed lg:static lg:translate-x-0 z-30 flex flex-col w-64 shrink-0
           bg-gradient-to-b from-slate-900 via-nexton-teal-950 to-nexton-teal-900
           text-slate-50 h-screen transition-transform duration-300 ease-in-out
           shadow-2xl border-r border-white/5"
>

    {{-- ── Logo ─────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 px-5 py-5 border-b border-white/8 shrink-0">
        <div class="h-9 w-9 rounded-xl bg-emerald-400 flex items-center justify-center
                    font-extrabold text-slate-900 text-base shadow-lg shadow-emerald-500/30
                    shrink-0">
            N
        </div>
        <div class="min-w-0">
            <p class="font-extrabold text-sm text-white leading-tight tracking-tight">
                Nexton Accounts
            </p>
            <p class="text-xs text-emerald-400/70 leading-tight mt-0.5">
                ERP · BD Tech
            </p>
        </div>
    </div>

    {{-- ── Company badge ────────────────────────────────────── --}}
    @if($activeCompany)
    <div class="mx-3 mt-3 shrink-0">
        <a href="{{ route('companies.index') }}"
           class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-white/6
                  border border-white/8 hover:bg-white/10 transition-colors group">
            <div class="h-7 w-7 rounded-lg bg-emerald-500/20 border border-emerald-400/30
                        flex items-center justify-center shrink-0">
                <i class="bi bi-building text-emerald-400 text-xs"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-white truncate leading-tight">
                    {{ $activeCompany->name }}
                </p>
                <p class="text-[10px] text-slate-500 leading-tight mt-0.5">
                    সক্রিয় প্রতিষ্ঠান
                </p>
            </div>
            <i class="bi bi-chevron-right text-[10px] text-slate-600
                       group-hover:text-slate-400 transition-colors shrink-0"></i>
        </a>
    </div>
    @endif


    {{-- ── Navigation ──────────────────────────────────────── --}}
    <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5
                scrollbar-thin scrollbar-thumb-white/10 scrollbar-track-transparent">


        {{-- ════════════════════════════════════════════════
             MAIN
        ════════════════════════════════════════════════ --}}
        @canany(['dashboard.view','companies.view','accounts.view','vouchers.view','ledger.view'])
        <div x-data="{
            open: {{ request()->routeIs('dashboard','companies.*','accounts.*','vouchers.*','ledger.*') ? 'true' : 'false' }}
        }">

            {{-- Section label --}}
            <p class="px-3 pt-3 pb-1 text-[10px] font-bold text-slate-600
                       uppercase tracking-widest select-none">
                Main
            </p>

            @can('dashboard.view')
            <a href="{{ route('dashboard') }}"
               class="{{ $navLink('dashboard') }}">
                <i class="bi bi-speedometer2 text-sm w-4 text-center shrink-0"></i>
                <span>Dashboard</span>
                @if(request()->routeIs('dashboard'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                @endif
            </a>
            @endcan

            @can('accounts.view')
            <a href="{{ route('accounts.index') }}"
               class="{{ $navLink('accounts.index', 'accounts.*') }}">
                <i class="bi bi-journal-bookmark text-sm w-4 text-center shrink-0"></i>
                <span>Chart of Accounts</span>
            </a>
            @endcan

            @can('vouchers.view')
            <a href="{{ route('vouchers.index') }}"
               class="{{ $navLink('vouchers.index', 'vouchers.*') }}">
                <i class="bi bi-receipt-cutoff text-sm w-4 text-center shrink-0"></i>
                <span>Vouchers</span>
            </a>
            @endcan

            @can('ledger.view')
            <a href="{{ route('ledger.index') }}"
               class="{{ $navLink('ledger.index', 'ledger.*') }}">
                <i class="bi bi-book text-sm w-4 text-center shrink-0"></i>
                <span>General Ledger</span>
            </a>
            @endcan

        </div>
        @endcanany


        {{-- ════════════════════════════════════════════════
             SALES
        ════════════════════════════════════════════════ --}}
        @canany(['customers.view','sales-orders.view','invoices.view'])
        <div x-data="{
            open: {{ request()->routeIs('customers.*','sales-orders.*','invoices.*') ? 'true' : 'false' }}
        }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl
                           text-sm font-semibold transition-all duration-150
                           {{ $groupActive(['customers.*','sales-orders.*','invoices.*']) }}">
                <i class="bi bi-cart3 text-sm w-4 text-center shrink-0"></i>
                <span class="flex-1 text-left">Sales</span>
                <i class="bi text-[10px] text-slate-500 transition-transform duration-200"
                   :class="open ? 'bi-chevron-up rotate-0' : 'bi-chevron-down'"></i>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-0.5 ml-3.5 pl-3 border-l border-white/8 space-y-0.5">

                @can('customers.view')
                <a href="{{ route('customers.index') }}"
                   class="{{ $navLink('customers.index', 'customers.*') }}">
                    <i class="bi bi-people text-xs w-4 text-center shrink-0"></i>
                    <span>Customers</span>
                </a>
                @endcan

                @if($activeCompany?->hasModule('sales-orders'))
                    @can('sales-orders.view')
                    <a href="{{ route('sales-orders.index') }}"
                       class="{{ $navLink('sales-orders.index', 'sales-orders.*') }}">
                        <i class="bi bi-box-seam text-xs w-4 text-center shrink-0"></i>
                        <span>Sales Orders</span>
                    </a>
                    @endcan
                @endif

                @can('invoices.view')
                <a href="{{ route('invoices.index') }}"
                   class="{{ $navLink('invoices.index', 'invoices.*') }}">
                    <i class="bi bi-file-text text-xs w-4 text-center shrink-0"></i>
                    <span>Invoices</span>
                </a>
                @endcan

            </div>
        </div>
        @endcanany


        {{-- ════════════════════════════════════════════════
             PURCHASE
        ════════════════════════════════════════════════ --}}
        @canany(['vendors.view','purchase-orders.view','purchase-bills.view'])
        <div x-data="{
            open: {{ request()->routeIs('vendors.*','purchase-orders.*','purchase-bills.*') ? 'true' : 'false' }}
        }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl
                           text-sm font-semibold transition-all duration-150
                           {{ $groupActive(['vendors.*','purchase-orders.*','purchase-bills.*']) }}">
                <i class="bi bi-bag-check text-sm w-4 text-center shrink-0"></i>
                <span class="flex-1 text-left">Purchase</span>
                <i class="bi text-[10px] text-slate-500 transition-transform duration-200"
                   :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-0.5 ml-3.5 pl-3 border-l border-white/8 space-y-0.5">

                @can('vendors.view')
                <a href="{{ route('vendors.index') }}"
                   class="{{ $navLink('vendors.index', 'vendors.*') }}">
                    <i class="bi bi-shop text-xs w-4 text-center shrink-0"></i>
                    <span>Vendors</span>
                </a>
                @endcan

                @can('purchase-orders.view')
                <a href="{{ route('purchase-orders.index') }}"
                   class="{{ $navLink('purchase-orders.index', 'purchase-orders.*') }}">
                    <i class="bi bi-cart-check text-xs w-4 text-center shrink-0"></i>
                    <span>Purchase Orders</span>
                </a>
                @endcan

                @can('purchase-bills.view')
                <a href="{{ route('purchase-bills.index') }}"
                   class="{{ $navLink('purchase-bills.index', 'purchase-bills.*') }}">
                    <i class="bi bi-file-earmark-check text-xs w-4 text-center shrink-0"></i>
                    <span>Purchase Bills</span>
                </a>
                @endcan

            </div>
        </div>
        @endcanany


        {{-- ════════════════════════════════════════════════
             INVENTORY
        ════════════════════════════════════════════════ --}}
        @if($activeCompany?->hasModule('inventory'))
        @canany(['inventory.view','stock-transfers.view'])
        <div x-data="{
            open: {{ request()->routeIs('inventory.*','stock-transfers.*') ? 'true' : 'false' }}
        }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl
                           text-sm font-semibold transition-all duration-150
                           {{ $groupActive(['inventory.*','stock-transfers.*']) }}">
                <i class="bi bi-boxes text-sm w-4 text-center shrink-0"></i>
                <span class="flex-1 text-left">Inventory</span>
                <i class="bi text-[10px] text-slate-500 transition-transform duration-200"
                   :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-0.5 ml-3.5 pl-3 border-l border-white/8 space-y-0.5">

                @can('inventory.view')
                <a href="{{ route('inventory.products') }}"
                   class="{{ $navLink('inventory.products') }}">
                    <i class="bi bi-archive text-xs w-4 text-center shrink-0"></i>
                    <span>Products</span>
                </a>
                <a href="{{ route('inventory.stock-in') }}"
                   class="{{ $navLink('inventory.stock-in') }}">
                    <i class="bi bi-arrow-down-circle text-xs w-4 text-center shrink-0"></i>
                    <span>Stock In</span>
                </a>
                <a href="{{ route('inventory.stock-out') }}"
                   class="{{ $navLink('inventory.stock-out') }}">
                    <i class="bi bi-arrow-up-circle text-xs w-4 text-center shrink-0"></i>
                    <span>Stock Out</span>
                </a>
                <a href="{{ route('inventory.movements') }}"
                   class="{{ $navLink('inventory.movements') }}">
                    <i class="bi bi-arrow-left-right text-xs w-4 text-center shrink-0"></i>
                    <span>Movements</span>
                </a>
                <a href="{{ route('inventory.stock-report') }}"
                   class="{{ $navLink('inventory.stock-report') }}">
                    <i class="bi bi-graph-up text-xs w-4 text-center shrink-0"></i>
                    <span>Stock Report</span>
                </a>
                <a href="{{ route('inventory.warehouses') }}"
                   class="{{ $navLink('inventory.warehouses') }}">
                    <i class="bi bi-shop-window text-xs w-4 text-center shrink-0"></i>
                    <span>Warehouses</span>
                </a>
                @endcan

                @can('stock-transfers.view')
                <a href="{{ route('stock-transfers.index') }}"
                   class="{{ $navLink('stock-transfers.index', 'stock-transfers.*') }}">
                    <i class="bi bi-shuffle text-xs w-4 text-center shrink-0"></i>
                    <span>Stock Transfers</span>
                </a>
                @endcan

            </div>
        </div>
        @endcanany
        @endif


        {{-- ════════════════════════════════════════════════
             MEDIA
        ════════════════════════════════════════════════ --}}
        @if($activeCompany?->hasModule('media'))
        @canany([
            'media-publications.view','media-parties.view',
            'media-print-plans.view','media-print-orders.view',
            'media-distributions.view','media-returns.view','media-collections.view'
        ])
        <div x-data="{
            open: {{ request()->routeIs('media.*') ? 'true' : 'false' }}
        }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl
                           text-sm font-semibold transition-all duration-150
                           {{ $groupActive(['media.*']) }}">
                <i class="bi bi-newspaper text-sm w-4 text-center shrink-0"></i>
                <span class="flex-1 text-left">Media</span>
                {{-- Active dot --}}
                @if(request()->routeIs('media.*'))
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                @endif
                <i class="bi text-[10px] text-slate-500 transition-transform duration-200"
                   :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-0.5 ml-3.5 pl-3 border-l border-white/8 space-y-0.5">

                @can('media-publications.view')
                <a href="{{ route('media.publications.index') }}"
                   class="{{ $navLink('media.publications.index', 'media.publications.*') }}">
                    <i class="bi bi-book-half text-xs w-4 text-center shrink-0"></i>
                    <span>Publications</span>
                </a>
                @endcan

                @can('media-parties.view')
                <a href="{{ route('media.parties.index') }}"
                   class="{{ $navLink('media.parties.index', 'media.parties.*') }}">
                    <i class="bi bi-people text-xs w-4 text-center shrink-0"></i>
                    <span>Agents &amp; Hawkers</span>
                </a>
                @endcan

                @can('media-print-plans.view')
                <a href="{{ route('media.print-plans.index') }}"
                   class="{{ $navLink('media.print-plans.index', 'media.print-plans.*') }}">
                    <i class="bi bi-pencil-square text-xs w-4 text-center shrink-0"></i>
                    <span>Print Planning</span>
                </a>
                @endcan

                @can('media-print-orders.view')
                <a href="{{ route('media.print-orders.index') }}"
                   class="{{ $navLink('media.print-orders.index', 'media.print-orders.*') }}">
                    <i class="bi bi-printer text-xs w-4 text-center shrink-0"></i>
                    <span>Print Orders</span>
                </a>
                @endcan

                @can('media-distributions.view')
                <a href="{{ route('media.distributions.index') }}"
                   class="{{ $navLink('media.distributions.index', 'media.distributions.*') }}">
                    <i class="bi bi-send text-xs w-4 text-center shrink-0"></i>
                    <span>Distribution</span>
                </a>
                @endcan

                @can('media-returns.view')
                <a href="{{ route('media.returns.index') }}"
                   class="{{ $navLink('media.returns.index', 'media.returns.*') }}">
                    <i class="bi bi-arrow-return-left text-xs w-4 text-center shrink-0"></i>
                    <span>Returns</span>
                </a>
                @endcan

                @can('media-collections.view')
                <a href="{{ route('media.collections.index') }}"
                   class="{{ $navLink('media.collections.index', 'media.collections.*') }}">
                    <i class="bi bi-collection text-xs w-4 text-center shrink-0"></i>
                    <span>Collections</span>
                </a>
                @endcan

                {{-- Media Reports sub-group --}}
                <div x-data="{ ropen: {{ request()->routeIs('media.reports.*') ? 'true' : 'false' }} }">
                    <button type="button" @click="ropen = !ropen"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-xl
                                   text-xs font-medium transition-all duration-150
                                   {{ request()->routeIs('media.reports.*')
                                       ? 'text-white bg-white/10'
                                       : 'text-slate-500 hover:text-slate-300 hover:bg-white/6' }}">
                        <i class="bi bi-bar-chart text-xs w-4 text-center shrink-0"></i>
                        <span class="flex-1 text-left">Reports</span>
                        <i class="bi text-[9px] text-slate-600"
                           :class="ropen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                    <div x-show="ropen"
                         class="mt-0.5 ml-3 pl-3 border-l border-white/6 space-y-0.5">
                        <a href="{{ route('media.reports.stock') }}"
                           class="{{ $navLink('media.reports.stock') }} text-xs">
                            <i class="bi bi-boxes text-[10px] w-4 text-center shrink-0"></i>
                            <span>Stock</span>
                        </a>
                        <a href="{{ route('media.reports.distribution-summary') }}"
                           class="{{ $navLink('media.reports.distribution-summary') }} text-xs">
                            <i class="bi bi-list-ul text-[10px] w-4 text-center shrink-0"></i>
                            <span>Distribution</span>
                        </a>
                        <a href="{{ route('media.reports.return-summary') }}"
                           class="{{ $navLink('media.reports.return-summary') }} text-xs">
                            <i class="bi bi-arrow-return-left text-[10px] w-4 text-center shrink-0"></i>
                            <span>Return</span>
                        </a>
                        <a href="{{ route('media.reports.collection-summary') }}"
                           class="{{ $navLink('media.reports.collection-summary') }} text-xs">
                            <i class="bi bi-cash text-[10px] w-4 text-center shrink-0"></i>
                            <span>Collection</span>
                        </a>
                        <a href="{{ route('media.reports.party-ledger') }}"
                           class="{{ $navLink('media.reports.party-ledger') }} text-xs">
                            <i class="bi bi-journal-text text-[10px] w-4 text-center shrink-0"></i>
                            <span>Party Ledger</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
        @endcanany
        @endif


        {{-- ════════════════════════════════════════════════
             FINANCE
        ════════════════════════════════════════════════ --}}
        @canany(['expenses.view','banking.view','bank-accounts.view','legal-documents.view'])
        <div x-data="{
            open: {{ request()->routeIs('expenses.*','banking.*','bank-accounts.*','legal-documents.*') ? 'true' : 'false' }}
        }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl
                           text-sm font-semibold transition-all duration-150
                           {{ $groupActive(['expenses.*','banking.*','bank-accounts.*','legal-documents.*']) }}">
                <i class="bi bi-wallet2 text-sm w-4 text-center shrink-0"></i>
                <span class="flex-1 text-left">Finance</span>
                <i class="bi text-[10px] text-slate-500 transition-transform duration-200"
                   :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-0.5 ml-3.5 pl-3 border-l border-white/8 space-y-0.5">

                @can('expenses.view')
                <a href="{{ route('expenses.index') }}"
                   class="{{ $navLink('expenses.index', 'expenses.*') }}">
                    <i class="bi bi-cash-coin text-xs w-4 text-center shrink-0"></i>
                    <span>Expenses</span>
                </a>
                @endcan

                @can('banking.view')
                <a href="{{ route('banking.index') }}"
                   class="{{ $navLink('banking.index', 'banking.*') }}">
                    <i class="bi bi-credit-card text-xs w-4 text-center shrink-0"></i>
                    <span>Banking</span>
                </a>
                @endcan

                @can('bank-accounts.view')
                <a href="{{ route('bank-accounts.index') }}"
                   class="{{ $navLink('bank-accounts.index', 'bank-accounts.*') }}">
                    <i class="bi bi-bank text-xs w-4 text-center shrink-0"></i>
                    <span>Bank Accounts</span>
                </a>
                @endcan

                @can('legal-documents.view')
                <a href="{{ route('legal-documents.index') }}"
                   class="{{ $navLink('legal-documents.index', 'legal-documents.*') }}">
                    <i class="bi bi-file-pdf text-xs w-4 text-center shrink-0"></i>
                    <span>Legal Documents</span>
                </a>
                @endcan

            </div>
        </div>
        @endcanany


        {{-- ════════════════════════════════════════════════
             HUMAN RESOURCES
        ════════════════════════════════════════════════ --}}
        @canany(['employees.view','salaries.view'])
        <div x-data="{
            open: {{ request()->routeIs('employees.*','salaries.*') ? 'true' : 'false' }}
        }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl
                           text-sm font-semibold transition-all duration-150
                           {{ $groupActive(['employees.*','salaries.*']) }}">
                <i class="bi bi-person-badge text-sm w-4 text-center shrink-0"></i>
                <span class="flex-1 text-left">HR &amp; Payroll</span>
                <i class="bi text-[10px] text-slate-500 transition-transform duration-200"
                   :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-0.5 ml-3.5 pl-3 border-l border-white/8 space-y-0.5">

                @can('employees.view')
                <a href="{{ route('employees.index') }}"
                   class="{{ $navLink('employees.index', 'employees.*') }}">
                    <i class="bi bi-person-lines-fill text-xs w-4 text-center shrink-0"></i>
                    <span>Employees</span>
                </a>
                @endcan

                @can('salaries.view')
                <a href="{{ route('salaries.index') }}"
                   class="{{ $navLink('salaries.index', 'salaries.*') }}">
                    <i class="bi bi-cash-stack text-xs w-4 text-center shrink-0"></i>
                    <span>Salaries</span>
                </a>
                @endcan

            </div>
        </div>
        @endcanany


        {{-- ════════════════════════════════════════════════
             REPORTS
        ════════════════════════════════════════════════ --}}
        @canany(['trial-balance.view','profit-loss.view','balance-sheet.view','cash-flow.view'])
        <div x-data="{
            open: {{ request()->routeIs('trial-balance.*','profit-loss.*','balance-sheet.*','cash-flow.*') ? 'true' : 'false' }}
        }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl
                           text-sm font-semibold transition-all duration-150
                           {{ $groupActive(['trial-balance.*','profit-loss.*','balance-sheet.*','cash-flow.*']) }}">
                <i class="bi bi-bar-chart-line text-sm w-4 text-center shrink-0"></i>
                <span class="flex-1 text-left">Reports</span>
                <i class="bi text-[10px] text-slate-500 transition-transform duration-200"
                   :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-0.5 ml-3.5 pl-3 border-l border-white/8 space-y-0.5">

                @can('trial-balance.view')
                <a href="{{ route('trial-balance.index') }}"
                   class="{{ $navLink('trial-balance.index', 'trial-balance.*') }}">
                    <i class="bi bi-list-check text-xs w-4 text-center shrink-0"></i>
                    <span>Trial Balance</span>
                </a>
                @endcan

                @can('balance-sheet.view')
                <a href="{{ route('balance-sheet.index') }}"
                   class="{{ $navLink('balance-sheet.index', 'balance-sheet.*') }}">
                    <i class="bi bi-diagram-3 text-xs w-4 text-center shrink-0"></i>
                    <span>Balance Sheet</span>
                </a>
                @endcan

                @can('profit-loss.view')
                <a href="{{ route('profit-loss.index') }}"
                   class="{{ $navLink('profit-loss.index', 'profit-loss.*') }}">
                    <i class="bi bi-graph-up-arrow text-xs w-4 text-center shrink-0"></i>
                    <span>Profit &amp; Loss</span>
                </a>
                @endcan

                @can('cash-flow.view')
                <a href="{{ route('cash-flow.index') }}"
                   class="{{ $navLink('cash-flow.index', 'cash-flow.*') }}">
                    <i class="bi bi-water text-xs w-4 text-center shrink-0"></i>
                    <span>Cash Flow</span>
                </a>
                @endcan

            </div>
        </div>
        @endcanany


        {{-- ════════════════════════════════════════════════
             SYSTEM
        ════════════════════════════════════════════════ --}}
        @canany(['voucher-types.view','financial-years.view','users.view','roles.view','permissions.view','settings.view'])
        <div x-data="{
            open: {{ request()->routeIs(
                'voucher-types.*','financial-years.*',
                'system.users.*','system.roles.*','system.permissions.*','settings.*'
            ) ? 'true' : 'false' }}
        }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2 mt-1 rounded-xl
                           text-sm font-semibold transition-all duration-150
                           {{ $groupActive([
                               'voucher-types.*','financial-years.*',
                               'system.users.*','system.roles.*',
                               'system.permissions.*','settings.*'
                           ]) }}">
                <i class="bi bi-shield-lock text-sm w-4 text-center shrink-0"></i>
                <span class="flex-1 text-left">System</span>
                <i class="bi text-[10px] text-slate-500 transition-transform duration-200"
                   :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-0.5 ml-3.5 pl-3 border-l border-white/8 space-y-0.5">

                @can('voucher-types.view')
                <a href="{{ route('voucher-types.index') }}"
                   class="{{ $navLink('voucher-types.index', 'voucher-types.*') }}">
                    <i class="bi bi-tags text-xs w-4 text-center shrink-0"></i>
                    <span>Voucher Types</span>
                </a>
                @endcan

                @can('financial-years.view')
                <a href="{{ route('financial-years.index') }}"
                   class="{{ $navLink('financial-years.index', 'financial-years.*') }}">
                    <i class="bi bi-calendar-week text-xs w-4 text-center shrink-0"></i>
                    <span>Financial Years</span>
                </a>
                @endcan

                @can('users.view')
                <a href="{{ route('system.users.index') }}"
                   class="{{ $navLink('system.users.index', 'system.users.*') }}">
                    <i class="bi bi-people-fill text-xs w-4 text-center shrink-0"></i>
                    <span>Users</span>
                </a>
                @endcan

                @can('roles.view')
                <a href="{{ route('system.roles.index') }}"
                   class="{{ $navLink('system.roles.index', 'system.roles.*') }}">
                    <i class="bi bi-shield-check text-xs w-4 text-center shrink-0"></i>
                    <span>Roles</span>
                </a>
                @endcan

                @can('permissions.view')
                <a href="{{ route('system.permissions.index') }}"
                   class="{{ $navLink('system.permissions.index', 'system.permissions.*') }}">
                    <i class="bi bi-key text-xs w-4 text-center shrink-0"></i>
                    <span>Permissions</span>
                </a>
                @endcan

                @can('settings.view')
                <a href="{{ route('settings.index') }}"
                   class="{{ $navLink('settings.index', 'settings.*') }}">
                    <i class="bi bi-gear text-xs w-4 text-center shrink-0"></i>
                    <span>Settings</span>
                </a>
                @endcan

            </div>
        </div>
        @endcanany

        {{-- Bottom padding --}}
        <div class="h-4"></div>

    </nav>


    {{-- ── User profile footer ─────────────────────────────── --}}
    <div class="shrink-0 px-3 py-3 border-t border-white/8 bg-black/10">

        {{-- Profile card --}}
        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/5
                    border border-white/8 mb-2">
            <div class="h-9 w-9 rounded-full shrink-0 flex items-center justify-center
                        font-bold text-sm text-slate-900 shadow-md
                        bg-gradient-to-br from-emerald-300 to-emerald-500">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white leading-tight truncate">
                    {{ auth()->user()->name ?? 'Admin' }}
                </p>
                <p class="text-[10px] text-slate-500 leading-tight mt-0.5 truncate">
                    {{ auth()->user()->email ?? '' }}
                </p>
            </div>
            <a href="{{ route('settings.index') }}"
               class="h-7 w-7 rounded-lg flex items-center justify-center shrink-0
                      text-slate-500 hover:text-white hover:bg-white/10 transition-colors">
                <i class="bi bi-gear text-xs"></i>
            </a>
        </div>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5
                           rounded-xl bg-white/6 border border-white/8
                           text-slate-400 hover:text-white hover:bg-red-600/80
                           hover:border-red-500/50 text-sm font-medium
                           transition-all duration-200 group">
                <i class="bi bi-box-arrow-right group-hover:translate-x-0.5
                           transition-transform duration-200"></i>
                <span>Logout</span>
            </button>
        </form>

    </div>

</aside>