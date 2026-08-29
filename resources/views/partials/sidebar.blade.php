
{{-- =========================================================
     PROFESSIONAL SIDEBAR NAVIGATION
     Nexton Accounts ERP
     
     Features:
     - Permission-aware navigation
     - Module-aware navigation
     - All major sections collapsible
     - Existing route names preserved
     - Active route indicators
     - Bootstrap Icons
     - Mobile responsive
     - Enterprise-grade appearance
========================================================= --}}

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed lg:static lg:translate-x-0 z-30 flex flex-col w-64 shrink-0
           bg-gradient-to-b from-nexton-teal-950 via-nexton-teal-900 to-nexton-teal-800
           text-slate-50 min-h-screen transition-transform duration-300 ease-in-out shadow-xl"
>

    {{-- =====================================================
         HEADER
    ====================================================== --}}
    <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">

        <div
            class="h-9 w-9 rounded-xl bg-emerald-400 flex items-center justify-center
                   font-extrabold text-nexton-teal-950 shadow-lg text-sm"
        >
            N
        </div>

        <div class="flex-1 min-w-0">
            <p class="font-bold text-sm leading-tight">
                Nexton
            </p>

            <p class="text-xs text-emerald-300/70 -mt-0.5">
                Accounts ERP
            </p>
        </div>

    </div>


    {{-- =====================================================
         NAVIGATION
    ====================================================== --}}
    <nav class="flex-1 px-2 py-4 overflow-y-auto space-y-3">


        {{-- =================================================
             MAIN
        ================================================== --}}
        @canany([
            'dashboard.view',
            'companies.view',
            'accounts.view',
            'vouchers.view',
            'ledger.view'
        ])

        <div
            x-data="{
                open: {{ request()->routeIs(
                    'dashboard',
                    'companies.*',
                    'accounts.*',
                    'vouchers.*',
                    'ledger.*'
                ) ? 'true' : 'false' }}
            }"
        >

            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg
                       font-medium transition-all duration-200
                       {{ request()->routeIs(
                            'dashboard',
                            'companies.*',
                            'accounts.*',
                            'vouchers.*',
                            'ledger.*'
                       )
                            ? 'bg-white/15 text-white shadow-sm'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
            >

                <div class="flex items-center gap-3">
                    <i class="bi bi-grid-1x2 flex-shrink-0"></i>
                    <span>Main</span>
                </div>

                <i
                    class="bi transition-transform duration-200"
                    :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                ></i>

            </button>


            <div
                x-show="open"
                x-transition
                class="mt-1 ml-2 space-y-0.5 border-l border-white/10 pl-3"
            >

                @can('dashboard.view')
                <a
                    href="{{ route('dashboard') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('dashboard')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-speedometer2 flex-shrink-0 text-xs"></i>
                    <span>Dashboard</span>
                </a>
                @endcan


                @can('companies.view')
                <a
                    href="{{ route('companies.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('companies.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-building flex-shrink-0 text-xs"></i>
                    <span>Companies</span>
                </a>
                @endcan


                @can('accounts.view')
                <a
                    href="{{ route('accounts.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('accounts.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-journal-bookmark flex-shrink-0 text-xs"></i>
                    <span>Chart of Accounts</span>
                </a>
                @endcan


                @can('vouchers.view')
                <a
                    href="{{ route('vouchers.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('vouchers.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-receipt-cutoff flex-shrink-0 text-xs"></i>
                    <span>Vouchers</span>
                </a>
                @endcan


                @can('ledger.view')
                <a
                    href="{{ route('ledger.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('ledger.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-book flex-shrink-0 text-xs"></i>
                    <span>General Ledger</span>
                </a>
                @endcan

            </div>

        </div>

        @endcanany


        {{-- =================================================
             SALES
        ================================================== --}}
        @canany([
            'customers.view',
            'sales-orders.view',
            'invoices.view'
        ])

        <div
            x-data="{
                open: {{ request()->routeIs(
                    'customers.*',
                    'sales-orders.*',
                    'invoices.*'
                ) ? 'true' : 'false' }}
            }"
        >

            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg
                       font-medium transition-all duration-200
                       {{ request()->routeIs(
                            'customers.*',
                            'sales-orders.*',
                            'invoices.*'
                       )
                            ? 'bg-white/15 text-white shadow-sm'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
            >

                <div class="flex items-center gap-3">
                    <i class="bi bi-cart3 flex-shrink-0"></i>
                    <span>Sales</span>
                </div>

                <i
                    class="bi transition-transform duration-200"
                    :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                ></i>

            </button>


            <div
                x-show="open"
                x-transition
                class="mt-1 ml-2 space-y-0.5 border-l border-white/10 pl-3"
            >

                @can('customers.view')
                <a
                    href="{{ route('customers.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('customers.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-people flex-shrink-0 text-xs"></i>
                    <span>Customers</span>
                </a>
                @endcan


                @if($activeCompany?->hasModule('sales-orders'))

                    @can('sales-orders.view')
                    <a
                        href="{{ route('sales-orders.index') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('sales-orders.*')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-box-seam flex-shrink-0 text-xs"></i>
                        <span>Sales Orders</span>
                    </a>
                    @endcan

                @endif


                @can('invoices.view')
                <a
                    href="{{ route('invoices.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('invoices.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-file-text flex-shrink-0 text-xs"></i>
                    <span>Invoices</span>
                </a>
                @endcan

            </div>

        </div>

        @endcanany


        {{-- =================================================
             PURCHASE
        ================================================== --}}
        @canany([
            'vendors.view',
            'purchase-orders.view',
            'purchase-bills.view'
        ])

        <div
            x-data="{
                open: {{ request()->routeIs(
                    'vendors.*',
                    'purchase-orders.*',
                    'purchase-bills.*'
                ) ? 'true' : 'false' }}
            }"
        >

            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg
                       font-medium transition-all duration-200
                       {{ request()->routeIs(
                            'vendors.*',
                            'purchase-orders.*',
                            'purchase-bills.*'
                       )
                            ? 'bg-white/15 text-white shadow-sm'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
            >

                <div class="flex items-center gap-3">
                    <i class="bi bi-bag-check flex-shrink-0"></i>
                    <span>Purchase</span>
                </div>

                <i
                    class="bi transition-transform duration-200"
                    :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                ></i>

            </button>


            <div
                x-show="open"
                x-transition
                class="mt-1 ml-2 space-y-0.5 border-l border-white/10 pl-3"
            >

                @can('vendors.view')
                <a
                    href="{{ route('vendors.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('vendors.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-shop flex-shrink-0 text-xs"></i>
                    <span>Vendors</span>
                </a>
                @endcan


                @can('purchase-orders.view')
                <a
                    href="{{ route('purchase-orders.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('purchase-orders.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-cart-check flex-shrink-0 text-xs"></i>
                    <span>Purchase Orders</span>
                </a>
                @endcan


                @can('purchase-bills.view')
                <a
                    href="{{ route('purchase-bills.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('purchase-bills.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-file-earmark-check flex-shrink-0 text-xs"></i>
                    <span>Purchase Bills</span>
                </a>
                @endcan

            </div>

        </div>

        @endcanany


        {{-- =================================================
             INVENTORY
        ================================================== --}}
        @if($activeCompany?->hasModule('inventory'))

            @canany([
                'inventory.view',
                'stock-transfers.view'
            ])

            <div
                x-data="{
                    open: {{ request()->routeIs(
                        'inventory.*',
                        'stock-transfers.*'
                    ) ? 'true' : 'false' }}
                }"
            >

                <button
                    type="button"
                    @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg
                           font-medium transition-all duration-200
                           {{ request()->routeIs(
                                'inventory.*',
                                'stock-transfers.*'
                           )
                                ? 'bg-white/15 text-white shadow-sm'
                                : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
                >

                    <div class="flex items-center gap-3">
                        <i class="bi bi-boxes flex-shrink-0"></i>
                        <span>Inventory</span>
                    </div>

                    <i
                        class="bi transition-transform duration-200"
                        :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                    ></i>

                </button>


                <div
                    x-show="open"
                    x-transition
                    class="mt-1 ml-2 space-y-0.5 border-l border-white/10 pl-3"
                >

                    @can('inventory.view')

                    <a
                        href="{{ route('inventory.products') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('inventory.products')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-archive flex-shrink-0 text-xs"></i>
                        <span>Products</span>
                    </a>


                    <a
                        href="{{ route('inventory.stock-in') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('inventory.stock-in')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-arrow-down-circle flex-shrink-0 text-xs"></i>
                        <span>Stock In</span>
                    </a>


                    <a
                        href="{{ route('inventory.stock-out') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('inventory.stock-out')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-arrow-up-circle flex-shrink-0 text-xs"></i>
                        <span>Stock Out</span>
                    </a>


                    <a
                        href="{{ route('inventory.movements') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('inventory.movements')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-arrow-left-right flex-shrink-0 text-xs"></i>
                        <span>Movements</span>
                    </a>


                    <a
                        href="{{ route('inventory.stock-report') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('inventory.stock-report')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-graph-up flex-shrink-0 text-xs"></i>
                        <span>Stock Report</span>
                    </a>


                    <a
                        href="{{ route('inventory.warehouses') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('inventory.warehouses')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-shop-window flex-shrink-0 text-xs"></i>
                        <span>Warehouses</span>
                    </a>

                    @endcan


                    @can('stock-transfers.view')

                    <a
                        href="{{ route('stock-transfers.index') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('stock-transfers.*')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-shuffle flex-shrink-0 text-xs"></i>
                        <span>Stock Transfers</span>
                    </a>

                    @endcan

                </div>

            </div>

            @endcanany

        @endif


        {{-- =================================================
             MEDIA
        ================================================== --}}
        @if($activeCompany?->hasModule('media'))

            @canany([
                'media.view',
                'media-publications.view',
                'media-parties.view',
                'media-print-plans.view',
                'media-print-orders.view',
                'media-distributions.view',
                'media-returns.view',
                'media-collections.view'
            ])

            <div
                x-data="{
                    open: {{ request()->routeIs('media.*') ? 'true' : 'false' }}
                }"
            >

                <button
                    type="button"
                    @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg
                           font-medium transition-all duration-200
                           {{ request()->routeIs('media.*')
                                ? 'bg-white/15 text-white shadow-sm'
                                : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
                >

                    <div class="flex items-center gap-3">
                        <i class="bi bi-newspaper flex-shrink-0"></i>
                        <span>Media</span>
                    </div>

                    <i
                        class="bi transition-transform duration-200"
                        :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                    ></i>

                </button>


                <div
                    x-show="open"
                    x-transition
                    class="mt-1 ml-2 space-y-0.5 border-l border-white/10 pl-3"
                >

                    @can('media-publications.view')
                    <a
                        href="{{ route('media.publications.index') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('media.publications.*')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-book-half flex-shrink-0 text-xs"></i>
                        <span>Publications</span>
                    </a>
                    @endcan


                    @can('media-parties.view')
                    <a
                        href="{{ route('media.parties.index') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('media.parties.*')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-people flex-shrink-0 text-xs"></i>
                        <span>Agents &amp; Hawkers</span>
                    </a>
                    @endcan


                    @can('media-print-plans.view')
                    <a
                        href="{{ route('media.print-plans.index') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('media.print-plans.*')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-pencil-square flex-shrink-0 text-xs"></i>
                        <span>Print Planning</span>
                    </a>
                    @endcan


                    @can('media-print-orders.view')
                    <a
                        href="{{ route('media.print-orders.index') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('media.print-orders.*')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-printer flex-shrink-0 text-xs"></i>
                        <span>Print Orders</span>
                    </a>
                    @endcan


                    @can('media-distributions.view')
                    <a
                        href="{{ route('media.distributions.index') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('media.distributions.*')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-box2-heart flex-shrink-0 text-xs"></i>
                        <span>Distribution</span>
                    </a>
                    @endcan


                    @can('media-returns.view')
                    <a
                        href="{{ route('media.returns.index') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('media.returns.*')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-arrow-return-left flex-shrink-0 text-xs"></i>
                        <span>Returns</span>
                    </a>
                    @endcan


                    @can('media-collections.view')
                    <a
                        href="{{ route('media.collections.index') }}"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                               {{ request()->routeIs('media.collections.*')
                                    ? 'bg-white/15 text-white'
                                    : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                    >
                        <i class="bi bi-collection flex-shrink-0 text-xs"></i>
                        <span>Collections</span>
                    </a>
                    @endcan

                </div>

            </div>

            @endcanany

        @endif


        {{-- =================================================
             FINANCE
        ================================================== --}}
        @canany([
            'expenses.view',
            'banking.view',
            'bank-accounts.view',
            'legal-documents.view'
        ])

        <div
            x-data="{
                open: {{ request()->routeIs(
                    'expenses.*',
                    'banking.*',
                    'bank-accounts.*',
                    'legal-documents.*'
                ) ? 'true' : 'false' }}
            }"
        >

            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg
                       font-medium transition-all duration-200
                       {{ request()->routeIs(
                            'expenses.*',
                            'banking.*',
                            'bank-accounts.*',
                            'legal-documents.*'
                       )
                            ? 'bg-white/15 text-white shadow-sm'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
            >

                <div class="flex items-center gap-3">
                    <i class="bi bi-wallet2 flex-shrink-0"></i>
                    <span>Finance</span>
                </div>

                <i
                    class="bi transition-transform duration-200"
                    :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                ></i>

            </button>


            <div
                x-show="open"
                x-transition
                class="mt-1 ml-2 space-y-0.5 border-l border-white/10 pl-3"
            >

                @can('expenses.view')
                <a
                    href="{{ route('expenses.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('expenses.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-cash-coin flex-shrink-0 text-xs"></i>
                    <span>Expenses</span>
                </a>
                @endcan


                @can('banking.view')
                <a
                    href="{{ route('banking.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('banking.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-credit-card flex-shrink-0 text-xs"></i>
                    <span>Banking</span>
                </a>
                @endcan


                @can('bank-accounts.view')
                <a
                    href="{{ route('bank-accounts.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('bank-accounts.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-bank flex-shrink-0 text-xs"></i>
                    <span>Bank Accounts</span>
                </a>
                @endcan


                @can('legal-documents.view')
                <a
                    href="{{ route('legal-documents.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('legal-documents.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-file-pdf flex-shrink-0 text-xs"></i>
                    <span>Legal Documents</span>
                </a>
                @endcan

            </div>

        </div>

        @endcanany


        {{-- =================================================
             HUMAN RESOURCES
        ================================================== --}}
        @canany([
            'employees.view',
            'salaries.view'
        ])

        <div
            x-data="{
                open: {{ request()->routeIs(
                    'employees.*',
                    'salaries.*'
                ) ? 'true' : 'false' }}
            }"
        >

            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg
                       font-medium transition-all duration-200
                       {{ request()->routeIs(
                            'employees.*',
                            'salaries.*'
                       )
                            ? 'bg-white/15 text-white shadow-sm'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
            >

                <div class="flex items-center gap-3">
                    <i class="bi bi-person-badge flex-shrink-0"></i>
                    <span>Human Resources</span>
                </div>

                <i
                    class="bi transition-transform duration-200"
                    :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                ></i>

            </button>


            <div
                x-show="open"
                x-transition
                class="mt-1 ml-2 space-y-0.5 border-l border-white/10 pl-3"
            >

                @can('employees.view')
                <a
                    href="{{ route('employees.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('employees.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-person-badge flex-shrink-0 text-xs"></i>
                    <span>Employees</span>
                </a>
                @endcan


                @can('salaries.view')
                <a
                    href="{{ route('salaries.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('salaries.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-wallet2 flex-shrink-0 text-xs"></i>
                    <span>Salaries</span>
                </a>
                @endcan

            </div>

        </div>

        @endcanany


        {{-- =================================================
             REPORTS
        ================================================== --}}
        @canany([
            'trial-balance.view',
            'profit-loss.view',
            'balance-sheet.view',
            'cash-flow.view'
        ])

        <div
            x-data="{
                open: {{ request()->routeIs(
                    'trial-balance.*',
                    'profit-loss.*',
                    'balance-sheet.*',
                    'cash-flow.*'
                ) ? 'true' : 'false' }}
            }"
        >

            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg
                       font-medium transition-all duration-200
                       {{ request()->routeIs(
                            'trial-balance.*',
                            'profit-loss.*',
                            'balance-sheet.*',
                            'cash-flow.*'
                       )
                            ? 'bg-white/15 text-white shadow-sm'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
            >

                <div class="flex items-center gap-3">
                    <i class="bi bi-bar-chart-line flex-shrink-0"></i>
                    <span>Reports</span>
                </div>

                <i
                    class="bi transition-transform duration-200"
                    :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                ></i>

            </button>


            <div
                x-show="open"
                x-transition
                class="mt-1 ml-2 space-y-0.5 border-l border-white/10 pl-3"
            >

                @can('trial-balance.view')
                <a
                    href="{{ route('trial-balance.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('trial-balance.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-list-check flex-shrink-0 text-xs"></i>
                    <span>Trial Balance</span>
                </a>
                @endcan


                @can('balance-sheet.view')
                <a
                    href="{{ route('balance-sheet.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('balance-sheet.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-diagram-3 flex-shrink-0 text-xs"></i>
                    <span>Balance Sheet</span>
                </a>
                @endcan


                @can('profit-loss.view')
                <a
                    href="{{ route('profit-loss.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('profit-loss.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-graph-up-arrow flex-shrink-0 text-xs"></i>
                    <span>Profit &amp; Loss</span>
                </a>
                @endcan


                @can('cash-flow.view')
                <a
                    href="{{ route('cash-flow.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('cash-flow.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-cash-stack flex-shrink-0 text-xs"></i>
                    <span>Cash Flow</span>
                </a>
                @endcan

            </div>

        </div>

        @endcanany


        {{-- =================================================
             SYSTEM
        ================================================== --}}
        @canany([
            'voucher-types.view',
            'financial-years.view',
            'users.view',
            'roles.view',
            'permissions.view',
            'settings.view'
        ])

        <div
            x-data="{
                open: {{ request()->routeIs(
                    'voucher-types.*',
                    'financial-years.*',
                    'system.users.*',
                    'system.roles.*',
                    'system.permissions.*',
                    'settings.*'
                ) ? 'true' : 'false' }}
            }"
        >

            <button
                type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg
                       font-medium transition-all duration-200
                       {{ request()->routeIs(
                            'voucher-types.*',
                            'financial-years.*',
                            'system.users.*',
                            'system.roles.*',
                            'system.permissions.*',
                            'settings.*'
                       )
                            ? 'bg-white/15 text-white shadow-sm'
                            : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
            >

                <div class="flex items-center gap-3">
                    <i class="bi bi-shield-lock flex-shrink-0"></i>
                    <span>System</span>
                </div>

                <i
                    class="bi transition-transform duration-200"
                    :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"
                ></i>

            </button>


            <div
                x-show="open"
                x-transition
                class="mt-1 ml-2 space-y-0.5 border-l border-white/10 pl-3"
            >

                {{-- Voucher Types --}}
                @can('voucher-types.view')
                <a
                    href="{{ route('voucher-types.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('voucher-types.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-tags flex-shrink-0 text-xs"></i>
                    <span>Voucher Types</span>
                </a>
                @endcan


                {{-- Financial Years --}}
                @can('financial-years.view')
                <a
                    href="{{ route('financial-years.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('financial-years.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-calendar-week flex-shrink-0 text-xs"></i>
                    <span>Financial Years</span>
                </a>
                @endcan


                {{-- Users --}}
                @can('users.view')
                <a
                    href="{{ route('system.users.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('system.users.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-people-fill flex-shrink-0 text-xs"></i>
                    <span>Users</span>
                </a>
                @endcan


                {{-- Roles --}}
                @can('roles.view')
                <a
                    href="{{ route('system.roles.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('system.roles.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-shield-check flex-shrink-0 text-xs"></i>
                    <span>Roles</span>
                </a>
                @endcan


                {{-- Permissions --}}
                @can('permissions.view')
                <a
                    href="{{ route('system.permissions.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('system.permissions.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-key flex-shrink-0 text-xs"></i>
                    <span>Permissions</span>
                </a>
                @endcan


                {{-- Settings --}}
                @can('settings.view')
                <a
                    href="{{ route('settings.index') }}"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                           {{ request()->routeIs('settings.*')
                                ? 'bg-white/15 text-white'
                                : 'text-slate-400 hover:bg-white/10 hover:text-white' }}"
                >
                    <i class="bi bi-gear flex-shrink-0 text-xs"></i>
                    <span>Settings</span>
                </a>
                @endcan

            </div>

        </div>

        @endcanany

    </nav>


    {{-- =====================================================
         FOOTER / USER PROFILE
    ====================================================== --}}
    <div class="px-4 py-4 border-t border-white/10">

        <div class="flex items-center gap-3 rounded-xl bg-white/5 px-3 py-3 mb-3">

            <div
                class="h-9 w-9 rounded-full bg-emerald-400/90 flex items-center
                       justify-center text-nexton-teal-950 font-bold text-sm flex-shrink-0"
            >
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>

            <div class="flex-1 min-w-0">

                <p class="text-sm font-medium leading-tight truncate">
                    {{ auth()->user()->name ?? 'Admin' }}
                </p>

                <p class="text-emerald-300/70 text-xs truncate">
                    {{ auth()->user()->email ?? '' }}
                </p>

            </div>

        </div>


        {{-- Logout --}}
        <form
            method="POST"
            action="{{ route('logout') }}"
            class="w-full"
        >

            @csrf

            <button
                type="submit"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5
                       rounded-lg bg-red-600/90 text-white text-sm font-medium
                       hover:bg-red-700 transition-colors duration-200"
            >
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </button>

        </form>

    </div>

</aside>
