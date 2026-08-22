@extends('layouts.app')

@section('title', 'Voucher — ' . $transaction->voucher_number)

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $transaction->voucher_number }}
            </h1>
            <nav class="flex items-center gap-2 mt-1 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('vouchers.index') }}" class="hover:text-blue-600 transition-colors">Vouchers</a>
                <i class="bi bi-chevron-right text-xs"></i>
                <span>{{ $transaction->voucher_number }}</span>
            </nav>
        </div>

        <div class="flex items-center gap-2 flex-wrap">

            {{-- Status Badge --}}
            @include('vouchers.partials.status-badge', ['status' => $transaction->status])

            {{-- Print --}}
            <a href="{{ route('vouchers.print', $transaction) }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <i class="bi bi-printer"></i>
                Print
            </a>

            
            {{-- Download PDF --}}
            <a href="{{ route('vouchers.pdf', $transaction) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <i class="bi bi-file-earmark-pdf"></i>
                Download PDF
            </a>

                        {{-- Edit (Draft only) --}}
            @if($transaction->isDraft() && auth()->user()->can('vouchers.edit'))
                <a href="{{ route('vouchers.edit', $transaction) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/40 rounded-lg transition-colors">
                    <i class="bi bi-pencil"></i>
                    Edit
                </a>
            @endif

            {{-- Draft → Submitted --}}
            @if($transaction->isDraft() && auth()->user()->can('vouchers.submit'))
                <form method="POST"
                      action="{{ route('vouchers.submit', $transaction) }}"
                      x-data
                      @submit.prevent="if(confirm('Submit this voucher for approval?')) $el.submit()">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                        <i class="bi bi-send"></i>
                        Submit
                    </button>
                </form>
            @endif

            {{-- Submitted → Approved --}}
            @if($transaction->isSubmitted() && auth()->user()->can('vouchers.approve'))
                <form method="POST"
                      action="{{ route('vouchers.approve', $transaction) }}"
                      x-data
                      @submit.prevent="if(confirm('Approve this voucher?')) $el.submit()">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                        <i class="bi bi-check-circle"></i>
                        Approve
                    </button>
                </form>
            @endif

            {{-- Approved → Posted --}}
            @if($transaction->isApproved() && auth()->user()->can('vouchers.post'))
                <form method="POST"
                      action="{{ route('vouchers.post', $transaction) }}"
                      x-data
                      @submit.prevent="if(confirm('Post this approved voucher? This action cannot be undone.')) $el.submit()">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                        <i class="bi bi-journal-check"></i>
                        Post
                    </button>
                </form>
            @endif

            {{-- Cancel (Posted only) --}}
            @if($transaction->isPosted() && auth()->user()->can('vouchers.cancel'))
                <button type="button"
                        x-data
                        @click="$dispatch('open-cancel-modal', {
                            id: {{ $transaction->id }},
                            action: '{{ route('vouchers.cancel', $transaction) }}'
                        })"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    <i class="bi bi-x-circle"></i>
                    Cancel Voucher
                </button>
            @endif

            {{-- Delete (Draft only) --}}
            @if($transaction->isDraft() && auth()->user()->can('vouchers.delete'))
                <form method="POST"
                      action="{{ route('vouchers.destroy', $transaction) }}"
                      x-data
                      @submit.prevent="if(confirm('Permanently delete this draft voucher?')) $el.submit()">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                        <i class="bi bi-trash"></i>
                        Delete
                    </button>
                </form>
            @endif
            

        </div>
    </div>

    

    {{-- Flash Messages --}}
    @include('partials.flash')

    {{-- Cancellation Notice --}}
    @if($transaction->isCancelled())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 flex items-start gap-3">
            <i class="bi bi-x-circle-fill text-red-500 mt-0.5 text-lg"></i>
            <div>
                <p class="text-sm font-semibold text-red-700 dark:text-red-400">This voucher has been cancelled.</p>
                @if($transaction->cancellation_reason)
                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                        <span class="font-medium">Reason:</span> {{ $transaction->cancellation_reason }}
                    </p>
                @endif
                <p class="text-xs text-red-500 mt-1">
                    Cancelled by {{ $transaction->canceller?->name ?? '—' }}
                    on {{ $transaction->cancelled_at?->format('d M Y, h:i A') }}
                </p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Voucher Details Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-table text-blue-600"></i>
                        Ledger Lines
                    </h2>
                </div>

                @include('vouchers.partials.table', [
                    'details'     => $transaction->details,
                    'showTotals'  => true,
                ])

                {{-- Balance Check --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-end gap-2">
                        @if($transaction->is_balanced)
                            <span class="inline-flex items-center gap-1.5 text-sm text-green-600 dark:text-green-400 font-medium">
                                <i class="bi bi-check-circle-fill"></i>
                                Balanced
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-sm text-red-600 dark:text-red-400 font-medium">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                Unbalanced (Difference: {{ number_format(abs($transaction->difference), 2) }})
                            </span>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">

            {{-- Voucher Info Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="bi bi-info-circle text-blue-600"></i>
                    Voucher Information
                </h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Voucher No</dt>
                        <dd class="text-xs font-semibold text-gray-900 dark:text-white">{{ $transaction->voucher_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Date</dt>
                        <dd class="text-xs text-gray-900 dark:text-white">{{ $transaction->voucher_date?->format('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Type</dt>
                        <dd class="text-xs text-gray-900 dark:text-white">{{ $transaction->voucherType?->name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Financial Year</dt>
                        <dd class="text-xs text-gray-900 dark:text-white">
                            {{ $transaction->financialYear?->name ?? '—' }}
                        </dd>
                    </div>
                    @if($transaction->reference_number)
                        <div class="flex justify-between">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Reference</dt>
                            <dd class="text-xs text-gray-900 dark:text-white">{{ $transaction->reference_number }}</dd>
                        </div>
                    @endif
                    @if($transaction->narration)
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">Narration</dt>
                            <dd class="text-xs text-gray-900 dark:text-white">{{ $transaction->narration }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Totals Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="bi bi-calculator text-blue-600"></i>
                    Totals
                </h3>
                <dl class="space-y-3">
                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Total Debit</dt>
                        <dd class="text-sm font-semibold font-mono text-gray-900 dark:text-white">
                            {{ number_format($transaction->total_debit, 2) }}
                        </dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Total Credit</dt>
                        <dd class="text-sm font-semibold font-mono text-gray-900 dark:text-white">
                            {{ number_format($transaction->total_credit, 2) }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Audit Trail Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="bi bi-clock-history text-blue-600"></i>
                    Audit Trail
                </h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Created By</dt>
                        <dd class="text-xs font-medium text-gray-900 dark:text-white mt-0.5">
                            {{ $transaction->creator?->name ?? '—' }}
                        </dd>
                        <dd class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $transaction->created_at?->format('d M Y, h:i A') }}
                        </dd>
                    </div>
                    @if($transaction->isPosted())
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Posted By</dt>
                            <dd class="text-xs font-medium text-gray-900 dark:text-white mt-0.5">
                                {{ $transaction->poster?->name ?? '—' }}
                            </dd>
                            <dd class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $transaction->posted_at?->format('d M Y, h:i A') }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

        </div>
    </div>

</div>

{{-- Cancel Modal --}}
@if($transaction->isPosted())
    <div x-data="{
             open: false,
             action: '',
         }"
         @open-cancel-modal.window="open = true; action = $event.detail.action"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center">

        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 p-6 z-10"
             @click.stop>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cancel Voucher</h3>

            <form method="POST" :action="action">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Reason for Cancellation <span class="text-red-500">*</span>
                    </label>
                    <textarea name="cancellation_reason"
                              rows="3"
                              required
                              minlength="5"
                              placeholder="Provide a reason for cancelling this voucher..."
                              class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button"
                            @click="open = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        Close
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                        Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection