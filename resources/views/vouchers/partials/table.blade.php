{{--
    resources/views/vouchers/partials/table.blade.php
    Reusable voucher detail table for show/print views.
    Props:
        $details  (Collection of TransactionDetail with account relation loaded)
        $showTotals (bool) — default true
--}}
@php $showTotals = $showTotals ?? true; @endphp

<div class="overflow-x-auto">
    <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Account</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Debit</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Credit</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
            @foreach($details as $i => $detail)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500">{{ $i + 1 }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                        {{ $detail->account?->account_name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                        {{ $detail->description ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white">
                        @if($detail->debit_amount > 0)
                            {{ number_format($detail->debit_amount, 2) }}
                        @else
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white">
                        @if($detail->credit_amount > 0)
                            {{ number_format($detail->credit_amount, 2) }}
                        @else
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if($showTotals)
            <tfoot class="bg-gray-50 dark:bg-gray-700/50">
                <tr class="font-semibold">
                    <td colspan="3" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 text-right uppercase tracking-wide">
                        Total
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white">
                        {{ number_format($details->sum('debit_amount'), 2) }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white">
                        {{ number_format($details->sum('credit_amount'), 2) }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>