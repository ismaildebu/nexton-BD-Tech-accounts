<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $transaction->voucher_number }} — {{ $transaction->voucherType?->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            @page { size: A4; margin: 15mm 15mm 20mm 15mm; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 font-sans text-sm">

    {{-- Print Button --}}
    <div class="no-print flex justify-end gap-3 p-4 border-b border-gray-200 bg-gray-50">
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Print
        </button>
        <a href="{{ route('vouchers.show', $transaction) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg transition-colors">
            Back
        </a>
    </div>

    {{-- A4 Page --}}
    <div class="max-w-4xl mx-auto p-8 print:p-0">

        {{-- ============================================================ --}}
        {{-- ERP HEADER --}}
        {{-- ============================================================ --}}
        <div class="border-b-2 border-gray-800 pb-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ $transaction->company?->company_name ?? config('app.name') }}
                    </h1>
                    @if($transaction->company?->address)
                        <p class="text-xs text-gray-500 mt-1">{{ $transaction->company->address }}</p>
                    @endif
                    @if($transaction->company?->phone)
                        <p class="text-xs text-gray-500">Tel: {{ $transaction->company->phone }}</p>
                    @endif
                    @if($transaction->company?->email)
                        <p class="text-xs text-gray-500">{{ $transaction->company->email }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <div class="inline-block border-2 border-gray-800 px-4 py-2 rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-widest">{{ $transaction->voucherType?->name }}</p>
                        <p class="text-xl font-bold text-gray-900 mt-0.5">{{ $transaction->voucher_number }}</p>
                    </div>
                    <div class="mt-2">
                        @php
                            $statusColors = [
                                'draft'     => 'bg-amber-100 text-amber-800',
                                'posted'    => 'bg-green-100 text-green-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                            ];
                            $statusClass = $statusColors[$transaction->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">
                            {{ ucfirst($transaction->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- VOUCHER META --}}
        {{-- ============================================================ --}}
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div class="space-y-2">
                <div class="flex gap-4">
                    <span class="text-xs font-medium text-gray-500 w-28 shrink-0">Voucher Number</span>
                    <span class="text-xs font-semibold text-gray-900">{{ $transaction->voucher_number }}</span>
                </div>
                <div class="flex gap-4">
                    <span class="text-xs font-medium text-gray-500 w-28 shrink-0">Voucher Date</span>
                    <span class="text-xs text-gray-900">{{ $transaction->voucher_date?->format('d F Y') }}</span>
                </div>
                <div class="flex gap-4">
                    <span class="text-xs font-medium text-gray-500 w-28 shrink-0">Voucher Type</span>
                    <span class="text-xs text-gray-900">{{ $transaction->voucherType?->name }}</span>
                </div>
                <div class="flex gap-4">
                    <span class="text-xs font-medium text-gray-500 w-28 shrink-0">Financial Year</span>
                    <span class="text-xs text-gray-900">{{ $transaction->financialYear?->name ?? '—' }}</span>
                </div>
            </div>
            <div class="space-y-2">
                @if($transaction->reference_number)
                    <div class="flex gap-4">
                        <span class="text-xs font-medium text-gray-500 w-28 shrink-0">Reference No</span>
                        <span class="text-xs text-gray-900">{{ $transaction->reference_number }}</span>
                    </div>
                @endif
                <div class="flex gap-4">
                    <span class="text-xs font-medium text-gray-500 w-28 shrink-0">Company</span>
                    <span class="text-xs text-gray-900">{{ $transaction->company?->company_name }}</span>
                </div>
                @if($transaction->isPosted())
                    <div class="flex gap-4">
                        <span class="text-xs font-medium text-gray-500 w-28 shrink-0">Posted On</span>
                        <span class="text-xs text-gray-900">{{ $transaction->posted_at?->format('d F Y') }}</span>
                    </div>
                @endif
            </div>
        </div>

        @if($transaction->narration)
            <div class="mb-6 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Narration: </span>
                <span class="text-xs text-gray-800">{{ $transaction->narration }}</span>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- VOUCHER DETAIL TABLE --}}
        {{-- ============================================================ --}}
        <table class="w-full text-xs border-collapse mb-8">
            <thead>
                <tr class="bg-gray-800 text-white">
                    <th class="border border-gray-700 px-3 py-2 text-left font-semibold w-10">#</th>
                    <th class="border border-gray-700 px-3 py-2 text-left font-semibold">Account</th>
                    <th class="border border-gray-700 px-3 py-2 text-left font-semibold">Description</th>
                    <th class="border border-gray-700 px-3 py-2 text-right font-semibold w-28">Debit</th>
                    <th class="border border-gray-700 px-3 py-2 text-right font-semibold w-28">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->details as $i => $detail)
                    <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                        <td class="border border-gray-200 px-3 py-2 text-gray-500">{{ $i + 1 }}</td>
                        <td class="border border-gray-200 px-3 py-2 font-medium text-gray-900">
                            {{ $detail->account?->account_name ?? '—' }}
                        </td>
                        <td class="border border-gray-200 px-3 py-2 text-gray-600">
                            {{ $detail->description ?? '—' }}
                        </td>
                        <td class="border border-gray-200 px-3 py-2 text-right font-mono text-gray-900">
                            @if($detail->debit_amount > 0)
                                {{ number_format($detail->debit_amount, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="border border-gray-200 px-3 py-2 text-right font-mono text-gray-900">
                            @if($detail->credit_amount > 0)
                                {{ number_format($detail->credit_amount, 2) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-100 font-semibold">
                    <td colspan="3" class="border border-gray-300 px-3 py-2 text-right text-xs uppercase tracking-wide text-gray-700">
                        Total
                    </td>
                    <td class="border border-gray-300 px-3 py-2 text-right font-mono text-gray-900">
                        {{ number_format($transaction->total_debit, 2) }}
                    </td>
                    <td class="border border-gray-300 px-3 py-2 text-right font-mono text-gray-900">
                        {{ number_format($transaction->total_credit, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        {{-- ============================================================ --}}
        {{-- SIGNATURE AREA --}}
        {{-- ============================================================ --}}
        <div class="grid grid-cols-3 gap-8 mt-16">

            <div class="text-center">
                <div class="border-t border-gray-400 pt-2">
                    <p class="text-xs font-semibold text-gray-700">Prepared By</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $transaction->creator?->name ?? '—' }}</p>
                    <p class="text-xs text-gray-400">{{ $transaction->created_at?->format('d M Y') }}</p>
                </div>
            </div>

            <div class="text-center">
                <div class="border-t border-gray-400 pt-2">
                    <p class="text-xs font-semibold text-gray-700">Checked By</p>
                    <p class="text-xs text-gray-400 mt-4">&nbsp;</p>
                </div>
            </div>

            <div class="text-center">
                <div class="border-t border-gray-400 pt-2">
                    <p class="text-xs font-semibold text-gray-700">Approved By</p>
                    @if($transaction->isPosted())
                        <p class="text-xs text-gray-500 mt-0.5">{{ $transaction->poster?->name ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ $transaction->posted_at?->format('d M Y') }}</p>
                    @else
                        <p class="text-xs text-gray-400 mt-4">&nbsp;</p>
                    @endif
                </div>
            </div>

        </div>

        {{-- ============================================================ --}}
        {{-- PRINT FOOTER --}}
        {{-- ============================================================ --}}
        <div class="mt-12 pt-4 border-t border-gray-200 flex items-center justify-between">
            <p class="text-xs text-gray-400">
                Printed on {{ now()->format('d M Y, h:i A') }}
            </p>
            <p class="text-xs text-gray-400">
                {{ config('app.name') }} — Accounting ERP
            </p>
        </div>

    </div>

</body>
</html>