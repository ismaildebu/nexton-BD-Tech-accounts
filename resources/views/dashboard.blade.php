<x-app-layout>

<div class="container-fluid">

    <div class="row g-4">

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Cash Balance</h6>
                    <h3>৳ {{ number_format($cashBalance,2) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Bank Balance</h6>
                    <h3>৳ {{ number_format($bankBalance,2) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Total Income</h6>
                    <h3 class="text-success">
                        ৳ {{ number_format($totalIncome,2) }}
                    </h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Total Expense</h6>
                    <h3 class="text-danger">
                        ৳ {{ number_format($totalExpense,2) }}
                    </h3>
                </div>
            </div>
        </div>

    </div>

    <div class="row g-4 mt-1">

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Net Profit / Loss</h6>
                    <h3>৳ {{ number_format($netProfit,2) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Receivable</h6>
                    <h3>৳ {{ number_format($receivable,2) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Payable</h6>
                    <h3>৳ {{ number_format($payable,2) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6>Total Companies</h6>
                    <h3>{{ $companyCount }}</h3>
                </div>
            </div>
        </div>

    </div>

</div>

<div class="row mt-4">

    <div class="col-md-3 mb-3">
        <a href="{{ route('journal-vouchers.index') }}" class="text-decoration-none">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h2>📒</h2>
                    <h5>Journal Voucher</h5>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3 mb-3">
        <a href="{{ route('trial-balance.index') }}" class="text-decoration-none">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h2>📊</h2>
                    <h5>Trial Balance</h5>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3 mb-3">
        <a href="{{ route('profit-loss.index') }}" class="text-decoration-none">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h2>💰</h2>
                    <h5>Profit & Loss</h5>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3 mb-3">
        <a href="{{ route('balance-sheet.index') }}" class="text-decoration-none">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h2>🏦</h2>
                    <h5>Balance Sheet</h5>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3 mb-3">
        <a href="{{ route('cash-flow.index') }}" class="text-decoration-none">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h2>💵</h2>
                    <h5>Cash Flow</h5>
                </div>
            </div>
        </a>
    </div>

</div>

</x-app-layout>