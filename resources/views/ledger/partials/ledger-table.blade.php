<div class="overflow-x-auto">
    <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase w-12">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Voucher No</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Description</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Debit</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Credit</th>
                {{-- ✅ Balance column header: Dr/Cr label দেখা যাবে --}}
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Balance</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">

            {{-- ✅ Opening Balance Row --}}
            @if(isset($openingBalance) && $openingBalance != 0)
                <tr class="bg-blue-50 dark:bg-blue-900/20">
                    <td class="px-4 py-2 text-gray-400 text-xs">—</td>
                    <td class="px-4 py-2 text-gray-500 dark:text-gray-400 text-xs italic" colspan="6">
                        Opening Balance
                    </td>
                    <td class="px-4 py-2 text-right font-mono font-semibold text-xs
                        {{ $openingBalance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        @php
                            // ✅ Opening balance-এ Dr/Cr label account type অনুযায়ী
                            $isDebitNormal = $selectedAccount?->isDebitNormal() ?? true;
                            $obLabel = $openingBalance >= 0
                                ? ($isDebitNormal ? 'Dr' : 'Cr')
                                : ($isDebitNormal ? 'Cr' : 'Dr');
                        @endphp
                        {{ number_format(abs($openingBalance), 2) }} {{ $obLabel }}
                    </td>
                </tr>
            @endif

            @foreach($ledger as $i => $entry)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500 text-xs">
                        {{ $i + 1 }}
                    </td>

                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                        @php
                            $date = $entry->voucher_date ?? $entry->entry_date;
                        @endphp
                        {{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '—' }}
                    </td>

                    <td class="px-4 py-3">
                        @if($entry->transaction_id)
                            <a href="{{ route('vouchers.show', $entry->transaction_id) }}"
                               class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 transition-colors">
                                {{ $entry->effective_voucher_number }}
                            </a>
                        @else
                            <span class="text-gray-500">{{ $entry->effective_voucher_number }}</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                        {{ $entry->transaction?->voucherType?->name ?? $entry->voucherType?->name ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">
                        {{ $entry->description ?? $entry->transaction?->narration ?? '—' }}
                    </td>

                    {{-- ✅ Fix: পুরাতন fallback ($entry->debit) বাদ — শুধু debit_amount --}}
                    <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white">
                        @if($entry->debit_amount > 0)
                            {{ number_format($entry->debit_amount, 2) }}
                        @else
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white">
                        @if($entry->credit_amount > 0)
                            {{ number_format($entry->credit_amount, 2) }}
                        @else
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </td>

                    {{--
                        ✅ Fix: running balance এখন account type-aware।
                        Controller থেকে $entry->running_balance সঠিক sign-এ আসছে।

                        ❌ পুরাতন: $balance >= 0 ? 'Dr' : 'Cr'  ← সব account-এ একই!
                        ✅ নতুন:   isDebitNormal() দিয়ে Dr/Cr label নির্ধারণ
                    --}}
                    <td class="px-4 py-3 text-right font-mono font-semibold whitespace-nowrap">
                        @php
                            $balance       = $entry->running_balance ?? 0;
                            $isDebitNormal = $selectedAccount?->isDebitNormal() ?? true;

                            // balance positive মানে normal side-এ আছে
                            // Debit normal account: positive = Dr side
                            // Credit normal account: positive = Cr side
                            if ($balance > 0) {
                                $balanceLabel = $isDebitNormal ? 'Dr' : 'Cr';
                                $colorClass   = 'text-green-600 dark:text-green-400';
                            } elseif ($balance < 0) {
                                $balanceLabel = $isDebitNormal ? 'Cr' : 'Dr';
                                $colorClass   = 'text-red-600 dark:text-red-400';
                            } else {
                                $balanceLabel = '—';
                                $colorClass   = 'text-gray-400 dark:text-gray-500';
                            }
                        @endphp
                        <span class="{{ $colorClass }}">
                            {{ number_format(abs($balance), 2) }}
                            @if($balance != 0)
                                <span class="text-xs font-bold ml-0.5">{{ $balanceLabel }}</span>
                            @endif
                        </span>
                    </td>

                </tr>
            @endforeach
        </tbody>

        <tfoot class="bg-gray-50 dark:bg-gray-700/50">
            <tr class="font-semibold">
                <td colspan="5" class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 text-right uppercase tracking-wide">
                    Total
                </td>

                {{-- ✅ Fix: পুরাতন fallback বাদ — শুধু debit_amount/credit_amount --}}
                <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white">
                    {{ number_format($runningDebit, 2) }}
                </td>
                <td class="px-4 py-3 text-right font-mono text-gray-900 dark:text-white">
                    {{ number_format($runningCredit, 2) }}
                </td>

                <td class="px-4 py-3 text-right font-mono font-bold">
                    @php
                        // ✅ Closing balance: শেষ entry-র running_balance নিন
                        $closingBalance = $ledger->last()?->running_balance ?? 0;
                        $isDebitNormal  = $selectedAccount?->isDebitNormal() ?? true;

                        if ($closingBalance > 0) {
                            $label = $isDebitNormal ? 'Dr' : 'Cr';
                            $cls   = 'text-green-600 dark:text-green-400';
                        } elseif ($closingBalance < 0) {
                            $label = $isDebitNormal ? 'Cr' : 'Dr';
                            $cls   = 'text-red-600 dark:text-red-400';
                        } else {
                            $label = '—';
                            $cls   = 'text-gray-400';
                        }
                    @endphp
                    <span class="{{ $cls }}">
                        {{ number_format(abs($closingBalance), 2) }}
                        @if($closingBalance != 0)
                            <span class="text-xs font-bold ml-0.5">{{ $label }}</span>
                        @endif
                    </span>
                </td>
            </tr>
        </tfoot>
    </table>
</div>