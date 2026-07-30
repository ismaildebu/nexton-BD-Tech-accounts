@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-title', 'Accounting Dashboard')

@section('page-subtitle', 'Welcome back — here is your financial overview for '.$year)

@section('content')

<div class="space-y-6">

    {{-- ======================= 1. TOP SUMMARY CARDS ======================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">

        {{-- Total Revenue --}}
        <div class="relative overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-emerald-400/10"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Total Revenue</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-800">
                        ৳{{ number_format($totalRevenue, 2) }}
                    </p>
                </div>
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v-2m0-8c-1.11 0-2.08.402-2.599 1"/></svg>
                </span>
            </div>
            <div class="mt-4 h-14"><canvas id="chart-revenue"></canvas></div>
        </div>

        {{-- Total Expenses --}}
        <div class="relative overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
            <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-rose-400/10"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Total Expenses</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-800">
                        ৳{{ number_format($totalExpenses, 2) }}
                    </p>
                </div>
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-rose-500 text-white shadow-lg shadow-rose-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a4 4 0 00-8 0v2M5 9h14l-1 11H6L5 9z"/></svg>
                </span>
            </div>
            <div class="mt-4 h-14"><canvas id="chart-expenses"></canvas></div>
        </div>

        {{-- Net Profit (jewel-toned highlight card) --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 via-fuchsia-600 to-indigo-700 shadow-lg shadow-violet-500/30 p-5 text-white">
            <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10"></div>
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-violet-100/90">Net Profit</p>
                    <p class="mt-1 text-2xl font-extrabold">
                        ৳{{ number_format($netProfit, 2) }}
                    </p>
                </div>
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-white/15 backdrop-blur text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-9 9-4-4-6 6"/></svg>
                </span>
            </div>
            <div class="mt-4 h-14"><canvas id="chart-profit"></canvas></div>
        </div>

        {{-- Pending Invoices (donut: Overdue vs Due) --}}
        <div class="relative overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">Pending Invoices</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ $pendingTotalCount }}</p>
                </div>
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-amber-400 text-white shadow-lg shadow-amber-400/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3A9 9 0 113 12a9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-2 flex items-center gap-4">
                <div class="h-20 w-20 shrink-0"><canvas id="chart-pending"></canvas></div>
                <div class="text-xs space-y-1.5">
                    <p class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> Overdue <span class="font-semibold text-slate-700">{{ $pendingOverdueCount }}</span></p>
                    <p class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-cyan-500"></span> Due <span class="font-semibold text-slate-700">{{ $pendingDueCount }}</span></p>
                </div>
            </div>

            
        </div>
    </div>

    {{-- ======================= 2. MIDDLE SECTION CHARTS ======================= --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- Revenue vs Expenses --}}
        <div class="xl:col-span-2 rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-slate-700">Revenue vs. Expenses</h3>
                <span class="text-xs font-medium text-slate-400">Jan – Dec {{ $year }}</span>
            </div>
            <div class="h-72"><canvas id="chart-revenue-vs-expenses"></canvas></div>
        </div>

        {{-- Top Expense Categories --}}
        <div class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 mb-3">Top Expense Categories</h3>
            <div class="h-56"><canvas id="chart-expense-categories"></canvas></div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-slate-500">
                @foreach($expenseCategories as $label => $value)
                    <p class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full" style="background:{{ ['#8b5cf6','#f59e0b','#06b6d4','#64748b'][$loop->index % 4] }}"></span>
                        {{ $label }}
                    </p>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        {{-- Cash Flow Trend --}}
        <div class="xl:col-span-3 rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-slate-700">Cash Flow Trend</h3>
                <div class="flex items-center gap-4 text-xs font-medium">
                    <span class="flex items-center gap-1.5 text-cyan-600"><span class="h-2.5 w-2.5 rounded-full bg-cyan-500"></span>Inflows</span>
                    <span class="flex items-center gap-1.5 text-rose-600"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>Outflows</span>
                </div>
            </div>
            <div class="h-64"><canvas id="chart-cashflow"></canvas></div>
        </div>
    </div>

    {{-- ======================= 3. BOTTOM SECTION TABLES ======================= --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

        {{-- Bank Balance Summary --}}
        <div class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 mb-4">Bank Balance Summary</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-400 text-xs uppercase tracking-wider">
                            <th class="pb-3 font-medium">Bank Account</th>
                            <th class="pb-3 font-medium">Balance</th>
                            <th class="pb-3 font-medium">Trend</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($bankAccounts as $account)
                            <tr>
                                <td class="py-3">
                                    <p class="font-semibold text-slate-700">{{ $account->account_name }}</p>
                                    <p class="text-xs text-slate-400">{{ $account->bank_name }}</p>
                                </td>
                                <td class="py-3 font-semibold text-slate-700">৳{{ number_format($account->balance, 2) }}</td>
                                <td class="py-3">
                                    <canvas class="sparkline" data-values="{{ json_encode($account->sparkline) }}" width="100" height="30"></canvas>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-slate-400">No bank accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="rounded-2xl bg-white shadow-sm border border-slate-100 p-5">
            <h3 class="font-bold text-slate-700 mb-4">Recent Activity</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-400 text-xs uppercase tracking-wider">
                            <th class="pb-3 font-medium">Date</th>
                            <th class="pb-3 font-medium">Type</th>
                            <th class="pb-3 font-medium text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentActivity as $activity)
                            <tr>
                                <td class="py-3 text-slate-500">{{ \Carbon\Carbon::parse($activity->transaction_date)->format('d M, Y') }}</td>
                                

<td class="py-3">

        <strong>{{ $activity->voucher_no }}</strong><br>

        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
    @if($activity->transaction_type=='Income')
    bg-emerald-50 text-emerald-600
    @elseif($activity->transaction_type=='Expense')
    bg-rose-50 text-rose-600
    @else
    bg-blue-50 text-blue-600
    @endif">
            {{ $activity->transaction_type }}
        </span>

        <br>

        <small class="text-slate-400">
            {{ $activity->description }}
        </small>

</td>

                                <td class="py-3 text-right font-semibold {{ $activity->type === 'inflow' ? 'text-emerald-600' : 'text-rose-600' }}">
                                    @if($activity->transaction_type=='Income')
+
@elseif($activity->transaction_type=='Expense')
-
@else
±
@endif

৳{{ number_format($activity->amount,2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-slate-400">No recent activity.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const months = @json($months);

    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.font.size = 11;

    // ---- Total Revenue mini bar chart ----
    new Chart(document.getElementById('chart-revenue'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                data: @json($revenueTrend),
                backgroundColor: '#34d399',
                borderRadius: 4,
                barThickness: 6,
            }]
        },
        options: miniOptions()
    });

    // ---- Total Expenses mini line chart ----
    new Chart(document.getElementById('chart-expenses'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                data: @json($expenseTrend),
                borderColor: '#fb7185',
                backgroundColor: 'rgba(251,113,133,0.15)',
                borderWidth: 2.5,
                pointRadius: 0,
                tension: 0.4,
                fill: true,
            }]
        },
        options: miniOptions()
    });

    // ---- Net Profit mini growth chart ----
    new Chart(document.getElementById('chart-profit'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                data: @json($netProfitTrend),
                borderColor: '#fef3c7',
                backgroundColor: 'rgba(255,255,255,0.18)',
                borderWidth: 2.5,
                pointRadius: 0,
                tension: 0.4,
                fill: true,
            }]
        },
        options: miniOptions()
    });

    // ---- Pending Invoices donut ----
    new Chart(document.getElementById('chart-pending'), {
        type: 'doughnut',
        data: {
            labels: ['Overdue', 'Due'],
            datasets: [{
                data: [{{ $pendingOverdueCount }}, {{ $pendingDueCount }}],
                backgroundColor: ['#f43f5e', '#06b6d4'],
                borderWidth: 0,
            }]
        },
        options: {
            cutout: '70%',
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
            responsive: true,
            maintainAspectRatio: false,
        }
    });

    // ---- Revenue vs Expenses combo chart ----
    new Chart(document.getElementById('chart-revenue-vs-expenses'), {
        data: {
            labels: months,
            datasets: [
                {
                    type: 'bar',
                    label: 'Revenue',
                    data: @json($revenueTrend),
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                    order: 2,
                },
                {
                    type: 'bar',
                    label: 'Expenses',
                    data: @json($expenseTrend),
                    backgroundColor: '#fb923c',
                    borderRadius: 6,
                    order: 2,
                },
                {
                    type: 'line',
                    label: 'Net Profit',
                    data: @json($netProfitTrend),
                    borderColor: '#8b5cf6',
                    backgroundColor: '#8b5cf6',
                    borderWidth: 2.5,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#8b5cf6',
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#f1f5f9' }, ticks: { callback: (v) => '৳' + v } },
            },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
        }
    });

    // ---- Top Expense Categories donut ----
    new Chart(document.getElementById('chart-expense-categories'), {
        type: 'doughnut',
        data: {
            labels: @json(array_keys($expenseCategories)),
            datasets: [{
                data: @json(array_values($expenseCategories)),
                backgroundColor: ['#8b5cf6', '#f59e0b', '#06b6d4', '#64748b'],
                borderWidth: 2,
                borderColor: '#ffffff',
            }]
        },
        options: {
            cutout: '65%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
        }
    });

    // ---- Cash Flow dual line chart ----
    new Chart(document.getElementById('chart-cashflow'), {
        type: 'line',
        data: {
            labels: @json($cashFlowLabels),
            datasets: [
                {
                    label: 'Inflows',
                    data: @json($cashFlowInflow),
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6,182,212,0.12)',
                    borderWidth: 2.5,
                    tension: 0.4,
                    pointRadius: 3,
                    fill: true,
                },
                {
                    label: 'Outflows',
                    data: @json($cashFlowOutflow),
                    borderColor: '#f43f5e',
                    backgroundColor: 'rgba(244,63,94,0.10)',
                    borderWidth: 2.5,
                    tension: 0.4,
                    pointRadius: 3,
                    fill: true,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#f1f5f9' } },
            },
            plugins: { legend: { display: false } },
        }
    });

    // ---- Sparklines for bank balances ----
    document.querySelectorAll('.sparkline').forEach(function (canvas) {
        const values = JSON.parse(canvas.dataset.values || '[]');
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: values.map((_, i) => i),
                datasets: [{
                    data: values,
                    borderColor: '#0f9d84',
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.4,
                    fill: false,
                }]
            },
            options: {
                responsive: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } },
            }
        });
    });

    function miniOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: {
                x: { display: false },
                y: { display: false },
            },
        };
    }
});
</script>
@endpush

@endsection

