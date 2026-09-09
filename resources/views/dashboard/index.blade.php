@extends('layouts.app')

@section('title', 'Dashboard — ' . $year)

@section('page-title', 'Dashboard')

@section('page-subtitle', 'Financial overview · FY ' . $year)

@section('content')

    @php

        $currentHour = now()->hour;

        if ($currentHour >= 5 && $currentHour < 12) {
            $greeting = 'শুভ সকাল';
            $greetingIcon = '🌅';
        } elseif ($currentHour >= 12 && $currentHour < 15) {
            $greeting = 'শুভ দুপুর';
            $greetingIcon = '☀️';
        } elseif ($currentHour >= 15 && $currentHour < 18) {
            $greeting = 'শুভ অপরাহ্ন';
            $greetingIcon = '🌤️';
        } else {
            $greeting = 'শুভ রাত্রি';
            $greetingIcon = '🌙';
        }

    @endphp

    <style>
        .dashboard-bg {
            background:
                radial-gradient(circle at 5% 5%, rgba(59, 130, 246, .10), transparent 25%),
                radial-gradient(circle at 95% 8%, rgba(168, 85, 247, .10), transparent 25%),
                radial-gradient(circle at 90% 90%, rgba(20, 184, 166, .08), transparent 25%),
                #f8fafc;
        }

        .dash-card {
            background: rgba(255, 255, 255, .94);
            border: 1px solid rgba(226, 232, 240, .85);
            box-shadow: 0 8px 30px rgba(15, 23, 42, .055);
            transition: all .25s ease;
        }

        .dash-card:hover {
            box-shadow: 0 14px 38px rgba(15, 23, 42, .09);
        }

        .kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 190px;
            border-radius: 24px;
            color: white;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .12);
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 42px rgba(15, 23, 42, .18);
        }

        .kpi-card::before {
            content: "";
            position: absolute;
            width: 170px;
            height: 170px;
            border-radius: 999px;
            right: -65px;
            top: -75px;
            background: rgba(255,255,255,.12);
        }

        .kpi-card::after {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;
            border-radius: 999px;
            left: -55px;
            bottom: -75px;
            background: rgba(255,255,255,.08);
        }

        .kpi-revenue {
            background: linear-gradient(135deg, #059669 0%, #10b981 48%, #14b8a6 100%);
        }

        .kpi-profit {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 52%, #7c3aed 100%);
        }

        .kpi-receivable {
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 50%, #f97316 100%);
        }

        .kpi-assets {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 50%, #ec4899 100%);
        }

        .icon-glass {
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.24);
            backdrop-filter: blur(8px);
        }

        .section-title {
            font-size: .9rem;
            font-weight: 800;
            color: #0f172a;
        }

        .section-subtitle {
            font-size: .7rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        .color-strip {
            height: 4px;
            width: 100%;
        }

        .cash-hero {
            background:
                radial-gradient(circle at 15% 20%, rgba(255,255,255,.16), transparent 25%),
                radial-gradient(circle at 85% 80%, rgba(255,255,255,.14), transparent 28%),
                linear-gradient(135deg, #0f766e, #0891b2 48%, #2563eb);
        }

        .mini-account {
            transition: all .2s ease;
            border: 1px solid transparent;
        }

        .mini-account:hover {
            background: #f8fafc;
            border-color: #dbeafe;
            transform: translateX(3px);
        }

        .transaction-row {
            transition: all .2s ease;
        }

        .transaction-row:hover {
            background: linear-gradient(90deg, #f8fafc, #ffffff);
            padding-left: 1.35rem;
        }

        .bank-row {
            transition: all .2s ease;
        }

        .bank-row:hover {
            background: linear-gradient(90deg, #eff6ff, #faf5ff);
            transform: translateX(3px);
        }

        .financial-bar {
            height: 9px;
            border-radius: 999px;
            overflow: hidden;
            background: #f1f5f9;
        }

        .financial-fill {
            height: 100%;
            border-radius: 999px;
            transition: width .7s ease;
        }

        .invoice-box {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            padding: 18px;
        }

        .invoice-box::after {
            content: "";
            position: absolute;
            width: 75px;
            height: 75px;
            border-radius: 50%;
            right: -30px;
            bottom: -35px;
            background: rgba(255,255,255,.45);
        }

        .dashboard-header {
            background:
                radial-gradient(circle at 90% 20%, rgba(255,255,255,.15), transparent 22%),
                linear-gradient(135deg, #172554, #1d4ed8 52%, #7c3aed);
            border-radius: 26px;
            color: white;
            padding: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 38px rgba(37, 99, 235, .18);
        }

        .dashboard-header::before {
            content: "";
            position: absolute;
            width: 210px;
            height: 210px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
            right: -70px;
            top: -110px;
        }

        .dashboard-header::after {
            content: "";
            position: absolute;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            left: 35%;
            bottom: -100px;
        }

        .quick-btn {
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.24);
            backdrop-filter: blur(8px);
        }

        .quick-btn:hover {
            background: rgba(255,255,255,.23);
        }

        .dashboard-link {
            text-decoration: none;
            transition: opacity .2s ease;
        }

        .dashboard-link:hover {
            opacity: .82;
        }

        .balance-link {
            text-decoration: none;
            display: inline-block;
            transition: all .2s ease;
        }

        .balance-link:hover {
            text-decoration: underline;
            text-underline-offset: 3px;
        }


.dashboard-link,
.balance-link {
    position: relative;
    z-index: 50;
    pointer-events: auto;
    cursor: pointer;
    text-decoration: none;
}

.dashboard-link:hover,
.balance-link:hover {
    text-decoration: underline;
    text-underline-offset: 3px;
}

.kpi-card::before,
.kpi-card::after {
    pointer-events: none;
}

    </style>

    <div class="dashboard-bg rounded-3xl -m-1 p-1 sm:p-2">

        <div class="space-y-5 pb-8">

            {{-- =========================================================
                0. COLORFUL DASHBOARD HEADER
            ========================================================== --}}

            <div class="dashboard-header">

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">

                    <div>

                        <div class="flex items-center gap-3 mb-2">

                            <div class="h-11 w-11 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center backdrop-blur">
                                <i class="bi bi-speedometer2 text-xl"></i>
                            </div>

                            <div>

                                <p class="text-xs font-semibold text-blue-100 uppercase tracking-wider">
                                    Financial Dashboard
                                </p>

                                <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight">
                                    {{ $greeting }} {{ $greetingIcon }}
                                </h1>

                            </div>

                        </div>

                        <p class="text-sm text-blue-100">
                            {{ now()->translatedFormat('l, j F Y') }}

                            <span class="mx-1 opacity-50">•</span>

                            আপনার ব্যবসার বর্তমান আর্থিক চিত্র
                        </p>

                    </div>

                    <div class="relative z-10 flex flex-wrap items-center gap-2">

                        <form method="GET" action="{{ route('dashboard') }}">

                            <select
                                name="year"
                                onchange="this.form.submit()"
                                class="bg-white/95 border-0 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-lg focus:outline-none focus:ring-2 focus:ring-white cursor-pointer">

                                @foreach(range(now()->year, now()->year - 4) as $y)

                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                                        FY {{ $y }}
                                    </option>

                                @endforeach

                            </select>

                        </form>

                        @can('vouchers.create')

                            <a
                                href="{{ route('vouchers.create') }}"
                                class="quick-btn inline-flex items-center gap-2 text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow-lg transition-all">

                                <i class="bi bi-plus-lg"></i>

                                নতুন ভাউচার

                            </a>

                        @endcan

                    </div>

                </div>

            </div>


            {{-- =========================================================
                1. KPI CARDS
            ========================================================== --}}

            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">

                {{-- Revenue --}}

                <div class="kpi-card kpi-revenue p-5">

                    <div class="relative z-10">

                        <div class="flex items-start justify-between mb-5">

                            <div class="h-12 w-12 rounded-2xl icon-glass flex items-center justify-center">
                                <i class="bi bi-graph-up-arrow text-xl"></i>
                            </div>

                            <span class="text-[11px] font-bold bg-white/15 border border-white/20 px-3 py-1.5 rounded-full">
                                FY {{ $year }}
                            </span>

                        </div>

                        <p class="text-xs font-semibold text-emerald-50 mb-1">
                            মোট রাজস্ব
                        </p>

                        <p class="text-2xl sm:text-3xl font-extrabold leading-none">

                            <a
                                href="{{ route('profit-loss.index') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="dashboard-link text-white">

                                ৳{{ number_format($totalRevenue, 0) }}

                            </a>

                        </p>

                        <p class="text-xs text-emerald-100 mt-3">
                            খরচ: ৳{{ number_format($totalExpenses, 0) }}
                        </p>

                    </div>

                </div>


                {{-- Profit --}}

                <div class="kpi-card kpi-profit p-5">

                    <div class="relative z-10">

                        <div class="flex items-start justify-between mb-5">

                            <div class="h-12 w-12 rounded-2xl icon-glass flex items-center justify-center">
                                <i class="bi bi-currency-dollar text-xl"></i>
                            </div>

                            <span class="text-[11px] font-bold bg-white/15 border border-white/20 px-3 py-1.5 rounded-full">
                                {{ $netProfit >= 0 ? 'লাভ' : 'ক্ষতি' }}
                            </span>

                        </div>

                        <p class="text-xs font-semibold text-blue-100 mb-1">
                            নিট মুনাফা
                        </p>

                        <p class="text-2xl sm:text-3xl font-extrabold leading-none">

                            <a
                                href="{{ route('profit-loss.index') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="dashboard-link text-white">

                                ৳{{ number_format(abs($netProfit), 0) }}

                            </a>

                        </p>

                        <p class="text-xs text-blue-100 mt-3">
                            Revenue − Expense
                        </p>

                    </div>

                </div>


                {{-- Receivable --}}

                <div class="kpi-card kpi-receivable p-5">

                    <div class="relative z-10">

                        <div class="flex items-start justify-between mb-5">

                            <div class="h-12 w-12 rounded-2xl icon-glass flex items-center justify-center">
                                <i class="bi bi-clock-history text-xl"></i>
                            </div>

                            @if($pendingOverdueCount > 0)

                                <span class="text-[11px] font-bold bg-white/15 border border-white/20 px-3 py-1.5 rounded-full">
                                    {{ $pendingOverdueCount }} overdue
                                </span>

                            @endif

                        </div>

                        <p class="text-xs font-semibold text-amber-50 mb-1">
                            বকেয়া পাওনা (AR)
                        </p>

                        <p class="text-2xl sm:text-3xl font-extrabold leading-none">

                            <a
                                href="{{ route('customers.index') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="dashboard-link text-white">

                                ৳{{ number_format($totalReceivable, 0) }}

                            </a>

                        </p>

                        <p class="text-xs text-amber-100 mt-3">
                            মোট {{ $pendingTotalCount }}টি অপরিশোধিত
                        </p>

                    </div>

                </div>


                {{-- Assets --}}

                <div class="kpi-card kpi-assets p-5">

                    <div class="relative z-10">

                        <div class="flex items-start justify-between mb-5">

                            <div class="h-12 w-12 rounded-2xl icon-glass flex items-center justify-center">
                                <i class="bi bi-bank2 text-xl"></i>
                            </div>

                            <span class="text-[11px] font-bold bg-white/15 border border-white/20 px-3 py-1.5 rounded-full">
                                Balance Sheet
                            </span>

                        </div>

                        <p class="text-xs font-semibold text-purple-100 mb-1">
                            মোট সম্পদ
                        </p>

                        <p class="text-2xl sm:text-3xl font-extrabold leading-none">

                            <a
                                href="{{ route('balance-sheet.index') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="dashboard-link text-white">

                                ৳{{ number_format($totalAssets, 0) }}

                            </a>

                        </p>

                        <p class="text-xs text-purple-100 mt-3">
                            দায়: ৳{{ number_format($totalLiabilities, 0) }}
                        </p>

                    </div>

                </div>

            </div>


            {{-- =========================================================
                2. REVENUE CHART + CASH & BANK
            ========================================================== --}}

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

                {{-- Revenue chart --}}

                <div class="xl:col-span-2 dash-card rounded-3xl overflow-hidden">

                    <div class="color-strip bg-gradient-to-r from-blue-500 via-violet-500 to-emerald-500"></div>

                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100">

                        <div class="flex items-center gap-3">

                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-violet-600 text-white flex items-center justify-center shadow-md">
                                <i class="bi bi-bar-chart-line-fill"></i>
                            </div>

                            <div>

                                <h3 class="section-title">
                                    রাজস্ব বনাম খরচ
                                </h3>

                                <p class="section-subtitle">
                                    মাসভিত্তিক আর্থিক তুলনা — {{ $year }}
                                </p>

                            </div>

                        </div>

                        <div class="flex items-center gap-3 text-[11px]">

                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-slate-500">রাজস্ব</span>
                            </span>

                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-2 rounded-full bg-red-400"></span>
                                <span class="text-slate-500">খরচ</span>
                            </span>

                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-2 rounded-full bg-blue-500"></span>
                                <span class="text-slate-500">মুনাফা</span>
                            </span>

                        </div>

                    </div>

                    <div class="p-5">

                        <div style="position:relative;height:245px">
                            <canvas id="revenueChart"></canvas>
                        </div>

                    </div>

                </div>


                {{-- Cash & Bank --}}

                <div class="dash-card rounded-3xl overflow-hidden">

                    <div class="color-strip bg-gradient-to-r from-teal-400 via-cyan-500 to-blue-500"></div>

                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">

                        <div class="flex items-center gap-3">

                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 text-white flex items-center justify-center shadow-md">
                                <i class="bi bi-wallet2"></i>
                            </div>

                            <div>

                                <h3 class="section-title">
                                    Cash & Bank
                                </h3>

                                <p class="section-subtitle">
                                    Chart of Accounts ভিত্তিক
                                </p>

                            </div>

                        </div>

                        <a
                            href="{{ route('bank-accounts.index') }}"
                            class="text-xs font-bold text-cyan-600 hover:text-cyan-700">

                            সব দেখুন →

                        </a>

                    </div>

                    <div class="p-5">

                        <div class="cash-hero rounded-2xl text-white p-5 mb-4 shadow-lg">

                            <div class="flex items-center justify-between">

                                <div>

                                    <p class="text-xs text-cyan-100">
                                        মোট তরল সম্পদ
                                    </p>

                                    <p class="text-3xl font-extrabold mt-1">

                                        <a
                                            href="{{ route('bank-accounts.index') }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="dashboard-link text-white">

                                            ৳{{ number_format($totalCash + $totalBank, 0) }}

                                        </a>

                                    </p>

                                </div>

                                <div class="h-12 w-12 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center">
                                    <i class="bi bi-cash-stack text-xl"></i>
                                </div>

                            </div>

                            <div class="grid grid-cols-2 gap-3 mt-5">

                                <div class="bg-white/10 rounded-xl px-3 py-2 border border-white/10">

                                    <p class="text-[10px] text-cyan-100">
                                        নগদ
                                    </p>

                                    <p class="text-sm font-extrabold">

                                        <a
                                            href="{{ route('ledger.index') }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="dashboard-link text-white">

                                            ৳{{ number_format($totalCash, 0) }}

                                        </a>

                                    </p>

                                </div>

                                <div class="bg-white/10 rounded-xl px-3 py-2 border border-white/10">

                                    <p class="text-[10px] text-cyan-100">
                                        ব্যাংক
                                    </p>

                                    <p class="text-sm font-extrabold">

                                        <a
                                            href="{{ route('bank-accounts.index') }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="dashboard-link text-white">

                                            ৳{{ number_format($totalBank, 0) }}

                                        </a>

                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="space-y-1">

                            @forelse($cashBankDetails->take(5) as $acct)

                                <div class="mini-account flex items-center gap-3 p-2.5 rounded-xl">

                                    <div class="h-9 w-9 rounded-xl flex items-center justify-center text-xs font-extrabold flex-shrink-0
                                        {{ $acct->nature === 'Bank'
                                            ? 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white'
                                            : 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white' }}">

                                        {{ strtoupper(substr($acct->name, 0, 2)) }}

                                    </div>

                                    <div class="flex-1 min-w-0">

                                        <p class="text-xs font-bold text-slate-700 truncate">
                                            {{ $acct->name }}
                                        </p>

                                        <p class="text-[10px] text-slate-400">
                                            {{ $acct->nature }}
                                        </p>

                                    </div>

                                    <p class="text-sm font-extrabold flex-shrink-0
                                        {{ $acct->balance >= 0 ? 'text-emerald-600' : 'text-red-500' }}">

                                        @if($acct->nature === 'Bank')

                                            <a
                                                href="{{ route('bank-accounts.index') }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="balance-link {{ $acct->balance >= 0 ? 'text-emerald-600' : 'text-red-500' }}">

                                                ৳{{ number_format($acct->balance, 0) }}

                                            </a>

                                        @else

                                            <a
                                                href="{{ route('ledger.index') }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="balance-link {{ $acct->balance >= 0 ? 'text-emerald-600' : 'text-red-500' }}">

                                                ৳{{ number_format($acct->balance, 0) }}

                                            </a>

                                        @endif

                                    </p>

                                </div>

                            @empty

                                <div class="py-6 text-center">

                                    <i class="bi bi-wallet2 text-3xl text-slate-300"></i>

                                    <p class="text-xs text-slate-400 mt-2">
                                        কোনো Cash / Bank account নেই
                                    </p>

                                </div>

                            @endforelse

                        </div>

                    </div>

                </div>

            </div>


            {{-- =========================================================
                3. EXPENSE + FINANCIAL POSITION + INVOICE
            ========================================================== --}}

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                {{-- Expense --}}

                <div class="dash-card rounded-3xl overflow-hidden">

                    <div class="color-strip bg-gradient-to-r from-orange-400 via-amber-500 to-red-500"></div>

                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">

                        <div class="flex items-center gap-3">

                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-orange-500 to-red-500 text-white flex items-center justify-center shadow-md">
                                <i class="bi bi-pie-chart-fill"></i>
                            </div>

                            <div>

                                <h3 class="section-title">
                                    খরচের বিভাজন
                                </h3>

                                <p class="section-subtitle">
                                    Expense distribution
                                </p>

                            </div>

                        </div>

                        <span class="text-[10px] font-bold text-orange-700 bg-orange-100 border border-orange-200 px-2.5 py-1 rounded-full">
                            {{ $year }}
                        </span>

                    </div>

                    <div class="p-5">

                        <div style="position:relative;height:175px">
                            <canvas id="expenseDonut"></canvas>
                        </div>

                        @php

                            $palette = [
                                '#10B981',
                                '#3B82F6',
                                '#8B5CF6',
                                '#F59E0B',
                                '#EF4444',
                                '#6366F1',
                                '#EC4899',
                                '#14B8A6',
                            ];

                            $expTotal = array_sum($expenseCategories);
                            $ci = 0;

                        @endphp

                        <div class="mt-5 space-y-2.5">

                            @foreach(array_slice($expenseCategories, 0, 5, true) as $cat => $amt)

                                <div class="flex items-center gap-2">

                                    <span
                                        class="w-3 h-3 rounded-md flex-shrink-0 shadow-sm"
                                        style="background:{{ $palette[$ci] ?? '#94A3B8' }}">
                                    </span>

                                    <span class="text-xs text-slate-600 flex-1 truncate">
                                        {{ $cat }}
                                    </span>

                                    <span class="text-xs font-extrabold text-slate-700">
                                        {{ $expTotal > 0 ? number_format(($amt / $expTotal) * 100, 0) : 0 }}%
                                    </span>

                                </div>

                                @php $ci++ @endphp

                            @endforeach

                        </div>

                    </div>

                </div>


                {{-- Financial Position --}}

                <div class="dash-card rounded-3xl overflow-hidden">

                    <div class="color-strip bg-gradient-to-r from-violet-500 via-purple-500 to-fuchsia-500"></div>

                    <div class="px-5 py-4 border-b border-slate-100">

                        <div class="flex items-center gap-3">

                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-600 text-white flex items-center justify-center shadow-md">
                                <i class="bi bi-graph-up"></i>
                            </div>

                            <div>

                                <h3 class="section-title">
                                    আর্থিক অবস্থান
                                </h3>

                                <p class="section-subtitle">
                                    Assets = Liabilities + Equity
                                </p>

                            </div>

                        </div>

                    </div>

                    <div class="p-5 space-y-5">

                        @php

                            $finItems = [

                                [
                                    'label' => 'মোট সম্পদ',
                                    'val' => $totalAssets,
                                    'color' => 'bg-gradient-to-r from-blue-500 to-cyan-500',
                                    'icon' => 'bi-building',
                                    'iconBg' => 'bg-blue-100 text-blue-600'
                                ],

                                [
                                    'label' => 'মোট দায়',
                                    'val' => $totalLiabilities,
                                    'color' => 'bg-gradient-to-r from-red-400 to-rose-500',
                                    'icon' => 'bi-exclamation-circle',
                                    'iconBg' => 'bg-red-100 text-red-600'
                                ],

                                [
                                    'label' => 'মূলধন',
                                    'val' => $totalEquity,
                                    'color' => 'bg-gradient-to-r from-violet-500 to-purple-600',
                                    'icon' => 'bi-shield-check',
                                    'iconBg' => 'bg-purple-100 text-purple-600'
                                ],

                                [
                                    'label' => 'দেনাদার (AR)',
                                    'val' => $totalReceivable,
                                    'color' => 'bg-gradient-to-r from-amber-400 to-orange-500',
                                    'icon' => 'bi-arrow-right-circle',
                                    'iconBg' => 'bg-amber-100 text-amber-600'
                                ],

                                [
                                    'label' => 'পাওনাদার (AP)',
                                    'val' => $totalPayable,
                                    'color' => 'bg-gradient-to-r from-slate-400 to-slate-500',
                                    'icon' => 'bi-arrow-left-circle',
                                    'iconBg' => 'bg-slate-100 text-slate-600'
                                ],

                            ];

                            $maxVal = max(array_column($finItems, 'val') ?: [1]);

                        @endphp

                        @foreach($finItems as $item)

                            <div>

                                <div class="flex items-center gap-2 mb-2">

                                    <div class="h-7 w-7 rounded-lg {{ $item['iconBg'] }} flex items-center justify-center">

                                        <i class="bi {{ $item['icon'] }} text-xs"></i>

                                    </div>

                                    <span class="text-xs font-medium text-slate-600 flex-1">
                                        {{ $item['label'] }}
                                    </span>

                                    <span class="text-xs font-extrabold text-slate-800">

                                        <a
                                            href="{{ route('ledger.index') }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="balance-link text-slate-800">

                                            ৳{{ number_format($item['val'], 0) }}

                                        </a>

                                    </span>

                                </div>

                                <div class="financial-bar">

                                    <div
                                        class="financial-fill {{ $item['color'] }}"
                                        style="width:{{ $maxVal > 0 ? min(100, ($item['val'] / $maxVal) * 100) : 0 }}%">
                                    </div>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>


                {{-- Invoice --}}

                <div class="dash-card rounded-3xl overflow-hidden">

                    <div class="color-strip bg-gradient-to-r from-red-500 via-orange-500 to-amber-400"></div>

                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">

                        <div class="flex items-center gap-3">

                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-red-500 to-orange-500 text-white flex items-center justify-center shadow-md">
                                <i class="bi bi-receipt-cutoff"></i>
                            </div>

                            <div>

                                <h3 class="section-title">
                                    Invoice অবস্থা
                                </h3>

                                <p class="section-subtitle">
                                    Receivable monitoring
                                </p>

                            </div>

                        </div>

                        <a
                            href="{{ route('invoices.index') }}"
                            class="text-xs font-bold text-blue-600 hover:text-blue-700">

                            সব দেখুন →

                        </a>

                    </div>

                    <div class="p-5">

                        <div class="grid grid-cols-2 gap-3">

                            <div class="invoice-box bg-gradient-to-br from-red-50 to-rose-100 border border-red-100">

                                <a
                                    href="{{ route('invoices.index') }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="dashboard-link">

                                    <div class="relative z-10">

                                        <p class="text-3xl font-extrabold text-red-600">
                                            {{ $pendingOverdueCount }}
                                        </p>

                                        <p class="text-xs font-bold text-red-500 mt-1">
                                            Overdue
                                        </p>

                                    </div>

                                </a>

                            </div>

                            <div class="invoice-box bg-gradient-to-br from-amber-50 to-orange-100 border border-amber-100">

                                <a
                                    href="{{ route('invoices.index') }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="dashboard-link">

                                    <div class="relative z-10">

                                        <p class="text-3xl font-extrabold text-amber-600">
                                            {{ $pendingDueCount }}
                                        </p>

                                        <p class="text-xs font-bold text-amber-500 mt-1">
                                            Due Soon
                                        </p>

                                    </div>

                                </a>

                            </div>

                        </div>

                        @php

                            $totalInvoices = max($pendingTotalCount + 50, 1);

                            $collPct = $totalRevenue + $totalReceivable > 0
                                ? round(($totalRevenue / ($totalRevenue + $totalReceivable)) * 100)
                                : 0;

                            $overduePct = round(
                                ($pendingOverdueCount / $totalInvoices) * 100
                            );

                        @endphp

                        <div class="mt-6 space-y-5">

                            <div>

                                <div class="flex justify-between items-center text-xs mb-2">

                                    <span class="font-medium text-slate-500">
                                        সংগ্রহের হার
                                    </span>

                                    <span class="font-extrabold text-emerald-600">
                                        {{ $collPct }}%
                                    </span>

                                </div>

                                <div class="financial-bar">

                                    <div
                                        class="financial-fill bg-gradient-to-r from-emerald-400 to-green-600"
                                        style="width:{{ $collPct }}%">
                                    </div>

                                </div>

                            </div>

                            <div>

                                <div class="flex justify-between items-center text-xs mb-2">

                                    <span class="font-medium text-slate-500">
                                        বকেয়া হার
                                    </span>

                                    <span class="font-extrabold text-red-600">
                                        {{ $pendingTotalCount }}টি
                                    </span>

                                </div>

                                <div class="financial-bar">

                                    <div
                                        class="financial-fill bg-gradient-to-r from-red-400 to-rose-600"
                                        style="width:{{ min(100, $overduePct) }}%">
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =========================================================
                4. RECENT TRANSACTIONS + BANK ACCOUNTS
            ========================================================== --}}

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

                {{-- Recent Transactions --}}

                <div class="dash-card rounded-3xl overflow-hidden">

                    <div class="color-strip bg-gradient-to-r from-slate-700 via-blue-600 to-violet-600"></div>

                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">

                        <div class="flex items-center gap-3">

                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-slate-700 to-blue-600 text-white flex items-center justify-center shadow-md">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>

                            <div>

                                <h3 class="section-title">
                                    সাম্প্রতিক লেনদেন
                                </h3>

                                <p class="section-subtitle">
                                    সর্বশেষ ৮টি Ledger Entry
                                </p>

                            </div>

                        </div>

                        <a
                            href="{{ route('ledger.index') }}"
                            class="text-xs font-bold text-blue-600 hover:text-blue-700">

                            সব দেখুন →

                        </a>

                    </div>

                    <div class="divide-y divide-slate-100">

                        @forelse($recentActivity as $txn)

                            <div class="transaction-row flex items-center gap-3 px-5 py-3.5">

                                <div class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm
                                    {{ $txn->type === 'Credit'
                                        ? 'bg-gradient-to-br from-emerald-400 to-green-600 text-white'
                                        : 'bg-gradient-to-br from-red-400 to-rose-600 text-white' }}">

                                    <i class="bi
                                        {{ $txn->type === 'Credit'
                                            ? 'bi-arrow-down-left'
                                            : 'bi-arrow-up-right' }}">
                                    </i>

                                </div>

                                <div class="flex-1 min-w-0">

                                    <p class="text-xs font-extrabold text-slate-700 truncate">
                                        {{ $txn->ref }}
                                    </p>

                                    <p class="text-[10px] text-slate-400 mt-0.5">

                                        {{ \Carbon\Carbon::parse($txn->date)->format('d M Y') }}

                                        @if($txn->desc)

                                            <span class="mx-1">•</span>

                                            {{ Str::limit($txn->desc, 30) }}

                                        @endif

                                    </p>

                                </div>

                                <p class="text-sm font-extrabold flex-shrink-0
                                    {{ $txn->type === 'Credit'
                                        ? 'text-emerald-600'
                                        : 'text-red-500' }}">

                                    <a
                                        href="{{ route('ledger.index') }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="balance-link {{ $txn->type === 'Credit'
                                            ? 'text-emerald-600'
                                            : 'text-red-500' }}">

                                        {{ $txn->type === 'Credit' ? '+' : '−' }}৳{{ number_format($txn->amount, 0) }}

                                    </a>

                                </p>

                            </div>

                        @empty

                            <div class="px-5 py-12 text-center">

                                <div class="h-14 w-14 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center">
                                    <i class="bi bi-inbox text-2xl text-slate-300"></i>
                                </div>

                                <p class="text-sm text-slate-400 mt-3">
                                    কোনো লেনদেন নেই
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>


                {{-- Bank Accounts --}}

                <div class="dash-card rounded-3xl overflow-hidden">

                    <div class="color-strip bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-600"></div>

                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">

                        <div class="flex items-center gap-3">

                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center shadow-md">
                                <i class="bi bi-bank2"></i>
                            </div>

                            <div>

                                <h3 class="section-title">
                                    Bank Accounts
                                </h3>

                                <p class="section-subtitle">
                                    সক্রিয় ব্যাংক হিসাব সমূহ
                                </p>

                            </div>

                        </div>

                        <a
                            href="{{ route('bank-accounts.index') }}"
                            class="text-xs font-bold text-blue-600 hover:text-blue-700">

                            সব দেখুন →

                        </a>

                    </div>

                    <div class="p-5">

                        @forelse($bankAccounts as $bank)

                            <div class="bank-row flex items-center gap-3 p-3.5 rounded-2xl mb-2 last:mb-0">

                                <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 text-white flex items-center justify-center text-xs font-extrabold flex-shrink-0 shadow-md">

                                    {{ strtoupper(substr(
                                        $bank->bank_name
                                            ?? $bank->account_name
                                            ?? 'B',
                                        0,
                                        2
                                    )) }}

                                </div>

                                <div class="flex-1 min-w-0">

                                    <p class="text-sm font-extrabold text-slate-700 truncate">
                                        {{ $bank->bank_name ?? $bank->account_name }}
                                    </p>

                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ $bank->account_number ?? 'A/C' }}
                                    </p>

                                </div>

                                <div class="text-right flex-shrink-0">

                                    <p class="text-base font-extrabold
                                        {{ $bank->current_balance >= 0
                                            ? 'text-emerald-600'
                                            : 'text-red-500' }}">

                                        <a
                                            href="{{ route('bank-accounts.index') }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="balance-link {{ $bank->current_balance >= 0
                                                ? 'text-emerald-600'
                                                : 'text-red-500' }}">

                                            ৳{{ number_format($bank->current_balance, 0) }}

                                        </a>

                                    </p>

                                    <p class="text-[10px] text-slate-400">
                                        Current Balance
                                    </p>

                                </div>

                            </div>

                        @empty

                            <div class="py-12 text-center">

                                <div class="h-14 w-14 mx-auto rounded-2xl bg-blue-50 flex items-center justify-center">
                                    <i class="bi bi-bank text-2xl text-blue-300"></i>
                                </div>

                                <p class="text-sm text-slate-400 mt-3">
                                    কোনো Bank Account যোগ করা হয়নি
                                </p>

                                <a
                                    href="{{ route('bank-accounts.index') }}"
                                    class="mt-2 inline-block text-xs font-bold text-blue-600 hover:text-blue-700">

                                    + Bank Account যোগ করুন

                                </a>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection


@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const fmt = v => '৳' + (
        v >= 1e5
            ? (v / 1e5).toFixed(1) + 'L'
            : v >= 1e3
                ? (v / 1e3).toFixed(1) + 'K'
                : Number(v).toLocaleString('bn-BD')
    );


    const tooltipPlugin = {

        backgroundColor: '#0F172A',
        titleColor: '#CBD5E1',
        bodyColor: '#F8FAFC',
        padding: 12,
        cornerRadius: 10,
        borderColor: '#334155',
        borderWidth: 1,

        callbacks: {

            label: ctx => '  ' + fmt(ctx.raw),

        },

    };


    const gridColor = '#EEF2FF';


    const tickStyle = {

        color: '#94A3B8',

        font: {
            size: 10,
            family: 'inherit'
        }

    };


    /* =========================================================
       REVENUE / EXPENSE / PROFIT CHART
    ========================================================== */

    const rCtx = document.getElementById('revenueChart');

    if (rCtx) {

        new Chart(rCtx, {

            type: 'line',

            data: {

                labels: @json($months),

                datasets: [

                    {

                        label: 'রাজস্ব',

                        data: @json($revenueTrend),

                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16,185,129,.10)',
                        borderWidth: 3,
                        fill: true,
                        tension: .45,
                        pointRadius: 3,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#10B981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,

                    },

                    {

                        label: 'খরচ',

                        data: @json($expenseTrend),

                        borderColor: '#F43F5E',
                        backgroundColor: 'rgba(244,63,94,.03)',
                        borderWidth: 2.5,
                        borderDash: [6, 5],
                        fill: false,
                        tension: .45,
                        pointRadius: 3,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#F43F5E',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,

                    },

                    {

                        label: 'মুনাফা',

                        data: @json($netProfitTrend),

                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59,130,246,.08)',
                        borderWidth: 3,
                        fill: true,
                        tension: .45,
                        pointRadius: 3,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#3B82F6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,

                    },

                ],

            },


            options: {

                responsive: true,

                maintainAspectRatio: false,

                interaction: {
                    mode: 'index',
                    intersect: false
                },

                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: tooltipPlugin,

                },

                scales: {

                    x: {

                        grid: {
                            display: false
                        },

                        ticks: tickStyle,

                    },

                    y: {

                        grid: {
                            color: gridColor
                        },

                        ticks: {

                            ...tickStyle,

                            callback: fmt

                        },

                        border: {
                            display: false
                        },

                    },

                },

            },

        });

    }


    /* =========================================================
       EXPENSE DONUT
    ========================================================== */

    const dCtx = document.getElementById('expenseDonut');

    if (dCtx) {

        const palette = [

            '#10B981',
            '#3B82F6',
            '#8B5CF6',
            '#F59E0B',
            '#EF4444',
            '#6366F1',
            '#EC4899',
            '#14B8A6',

        ];


        new Chart(dCtx, {

            type: 'doughnut',

            data: {

                labels: @json(array_keys($expenseCategories)),

                datasets: [{

                    data: @json(array_values($expenseCategories)),

                    backgroundColor:
                        palette.slice(
                            0,
                            {{ count($expenseCategories) }}
                        ),

                    borderWidth: 4,
                    borderColor: '#ffffff',
                    hoverOffset: 10,

                }],

            },


            options: {

                responsive: true,

                maintainAspectRatio: false,

                cutout: '68%',


                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: {

                        ...tooltipPlugin,

                        callbacks: {

                            label: ctx =>
                                '  ' +
                                fmt(ctx.raw) +
                                '  (' +
                                ctx.label +
                                ')',

                        },

                    },

                },

            },

        });

    }

});

</script>

@endpush