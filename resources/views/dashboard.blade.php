<x-app-layout>

<style>

body{
    font-family: Inter, sans-serif;
    background:#f5f7fb;
}


.dashboard-wrapper{
    display:flex;
    width:100%;
}


.dashboard-content{
    width:100%;
}


/* Cards */

.card-box{
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}


.card-title{
    color:#6b7280;
    font-size:14px;
}


.card-number{
    font-size:30px;
    font-weight:700;
    margin-top:10px;
}



/* Table */

.table-box{
    background:#fff;
    border-radius:12px;
    padding:20px;
}


table{
    width:100%;
    border-collapse:collapse;
}


table th{
    background:#f1f5f9;
    padding:12px;
}


table td{
    padding:12px;
    border-bottom:1px solid #eee;
}


.dashboard-main-area{
    margin-left:0;
    width:100%;
}


.dashboard-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
}


.balance-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
}


.dashboard-box{
    background:white;
    border-radius:12px;
    padding:20px;
    box-shadow:0 3px 10px rgba(0,0,0,.08);
}


@media(max-width:1000px){

.dashboard-grid{
    grid-template-columns:repeat(2,1fr);
}


.balance-grid{
    grid-template-columns:1fr;
}

.card-box{
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);
}

.dashboard-box{
    background:white;
    border-radius:12px;
    padding:20px;
    box-shadow:0 3px 10px rgba(0,0,0,.08);
}

}



</style>

<x-slot name="header">
    Dashboard
</x-slot>


<div class="space-y-6">


    {{-- Summary Cards --}}

    <div class="dashboard-grid">


       <div class="dashboard-box">

            <p class="text-gray-500 text-sm">
                Total Revenue
            </p>

            <h2 class="text-3xl font-bold mt-2">
                ৳ {{ number_format($totalIncome,2) }}
            </h2>

        </div>



        <div class="dashboard-box">

            <p class="text-gray-500 text-sm">
                Total Expenses
            </p>

            <h2 class="text-3xl font-bold mt-2">
                ৳ {{ number_format($totalExpense,2) }}
            </h2>

        </div>



       <div class="dashboard-box">

            <p class="text-gray-500 text-sm">
                Net Profit
            </p>

            <h2 class="text-3xl font-bold mt-2">
                ৳ {{ number_format($netProfit,2) }}
            </h2>

        </div>



        <div class="card-box">

            <p class="text-gray-500 text-sm">
                Receivable
            </p>

            <h2 class="text-3xl font-bold mt-2">
                ৳ {{ number_format($receivable,2) }}
            </h2>

        </div>


    </div>





    {{-- Balance Cards --}}

    <div class="balance-grid">


        <div class="card-box">

            <h3 class="font-semibold">
                Cash Balance
            </h3>

            <p class="text-2xl font-bold mt-3">
                ৳ {{ number_format($cashBalance,2) }}
            </p>

        </div>



        <div class="card-box">

            <h3 class="font-semibold">
                Bank Balance
            </h3>

            <p class="text-2xl font-bold mt-3">
                ৳ {{ number_format($bankBalance,2) }}
            </p>

        </div>



        <div class="card-box">

            <h3 class="font-semibold">
                Payable
            </h3>

            <p class="text-2xl font-bold mt-3">
                ৳ {{ number_format($payable,2) }}
            </p>

        </div>


    </div>





    {{-- Recent Transactions --}}

    <div class="table-box">


        <div class="p-5 border-b">

            <h3 class="font-bold text-lg">
                Recent Transactions
            </h3>

        </div>



        <div class="overflow-x-auto">


            <table class="min-w-full">


                <thead class="bg-gray-100">

                    <tr>

                        <th class="px-6 py-3 text-left">
                            Date
                        </th>

                        <th class="px-6 py-3 text-left">
                            Voucher No
                        </th>

                        <th class="px-6 py-3 text-left">
                            Narration
                        </th>

                    </tr>

                </thead>



                <tbody>


                @foreach($recentTransactions as $transaction)


                    <tr class="border-t">


                        <td class="px-6 py-3">

                            {{ $transaction->transaction_date }}

                        </td>



                        <td class="px-6 py-3">

                            {{ $transaction->voucher_no }}

                        </td>



                        <td class="px-6 py-3">

                            {{ $transaction->narration }}

                        </td>


                    </tr>


                @endforeach


                </tbody>


            </table>


        </div>


    </div>






    {{-- Recent Activities --}}

    <div class="card-box">


        <h3 class="font-bold text-lg mb-4">

            Recent Activity

        </h3>



        @foreach($recentActivities as $activity)

        <div class="border-b py-3">


            <p class="font-medium">

                {{ $activity->voucher_no }}

            </p>


            <p class="text-gray-500 text-sm">

                {{ $activity->narration }}

            </p>


        </div>


        @endforeach


    </div>



</div>


</x-app-layout>