<x-app-layout>

<div style="padding:30px">

    <h2>Invoices</h2>

    <p>
        Manage your invoices here.
    </p>

    @if(isset($invoices) && $invoices->count())

        <table border="1" cellpadding="10">
            <tr>
                <th>ID</th>
                <th>Total Amount</th>
                <th>Status</th>
            </tr>

            @foreach($invoices as $invoice)

            <tr>
                <td>{{ $invoice->id }}</td>
                <td>{{ $invoice->total_amount }}</td>
                <td>{{ $invoice->status }}</td>
            </tr>

            @endforeach

        </table>

    @else

        <p>No invoices found.</p>

    @endif

</div>

</x-app-layout>