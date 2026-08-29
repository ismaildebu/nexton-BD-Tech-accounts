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

    @php $activeCompany = \App\Models\Company::find(session('company_id')); @endphp
    @include('partials.sidebar')

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