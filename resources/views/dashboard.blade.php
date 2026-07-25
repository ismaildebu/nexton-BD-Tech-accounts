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

</x-app-layout>