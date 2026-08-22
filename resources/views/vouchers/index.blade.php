@extends('layouts.app')

@section('title', 'Vouchers')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Vouchers</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage all accounting vouchers</p>
        </div>
        <a href="{{ route('vouchers.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-plus-lg"></i>
            New Voucher
        </a>
    </div>

    {{-- Flash Messages --}}
    @include('partials.flash')

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('vouchers.index') }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Voucher Number</label>
                    <input type="text"
                           name="voucher_number"
                           value="{{ request('voucher_number') }}"
                           placeholder="Search voucher no..."
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Voucher Type</label>
                    <select name="voucher_type_id"
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                        <option value="">All Types</option>
                        @foreach($voucherTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('voucher_type_id') == $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                    <select name="status"
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                            
                            <option value="">All Status</option>
                            <option value="Draft" @selected(request('status') === 'Draft')>Draft</option>
                            <option value="Submitted" @selected(request('status') === 'Submitted')>Submitted</option>
                            <option value="Approved" @selected(request('status') === 'Approved')>Approved</option>
                            <option value="Posted" @selected(request('status') === 'Posted')>Posted</option>
                            <option value="Cancelled" @selected(request('status') === 'Cancelled')>Cancelled</option>
                            
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Financial Year</label>
                    <select name="financial_year_id"
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                        <option value="">All Years</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" @selected(request('financial_year_id') == $fy->id)>
                                {{ $fy->name ?? ($fy->start_date . ' - ' . $fy->end_date) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Date From</label>
                    <input type="date"
                           name="date_from"
                           value="{{ request('date_from') }}"
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Date To</label>
                    <input type="date"
                           name="date_to"
                           value="{{ request('date_to') }}"
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Reference No</label>
                    <input type="text"
                           name="reference_number"
                           value="{{ request('reference_number') }}"
                           placeholder="Search reference..."
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="bi bi-search"></i>
                        Search
                    </button>
                    <a href="{{ route('vouchers.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
         x-data="{ selected: [], selectAll: false }"
         x-init="$watch('selectAll', v => selected = v ? [...document.querySelectorAll('.row-check')].map(el => el.value) : [])">

        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Showing {{ $vouchers->firstItem() ?? 0 }} – {{ $vouchers->lastItem() ?? 0 }}
                of {{ $vouchers->total() }} vouchers
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left w-10">
                            <input type="checkbox" x-model="selectAll"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase w-12">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Voucher No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Narration</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Created By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($vouchers as $index => $voucher)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                            <td class="px-4 py-3">
                                <input type="checkbox"
                                       class="row-check rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                       value="{{ $voucher->id }}"
                                       x-model="selected">
                            </td>

                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                {{ $vouchers->firstItem() + $index }}
                            </td>

                            <td class="px-4 py-3">
                                <a href="{{ route('vouchers.show', $voucher) }}"
                                   class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 transition-colors">
                                    {{ $voucher->voucher_number }}
                                </a>
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $voucher->voucher_date?->format('d M Y') }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $voucher->voucherType?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $voucher->reference_number ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                {{ $voucher->narration ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @include('vouchers.partials.status-badge', ['status' => $voucher->status])
                            </td>

                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $voucher->creator?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $voucher->created_at?->format('d M Y') }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">

                                    <a href="{{ route('vouchers.show', $voucher) }}"
                                       title="View"
                                       class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                        <i class="bi bi-eye text-base"></i>
                                    </a>

                                    @if($voucher->isDraft())
                                        <a href="{{ route('vouchers.edit', $voucher) }}"
                                           title="Edit"
                                           class="p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors">
                                            <i class="bi bi-pencil text-base"></i>
                                        </a>
                                    @endif

                                    <a href="{{ route('vouchers.print', $voucher) }}"
                                       title="Print"
                                       target="_blank"
                                       class="p-1.5 rounded-lg text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors">
                                        <i class="bi bi-printer text-base"></i>
                                    </a>

                                    {{-- Submit for Approval --}}
@if($voucher->isDraft())
    <form method="POST"
          action="{{ route('vouchers.submit', $voucher) }}"
          x-data
          @submit.prevent="if(confirm('Submit this voucher for approval?')) $el.submit()">
        @csrf
        <button type="submit"
                title="Submit for Approval"
                class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
            <i class="bi bi-send text-base"></i>
        </button>
    </form>
@endif

{{-- Approve --}}
@if($voucher->isSubmitted())
    <form method="POST"
          action="{{ route('vouchers.approve', $voucher) }}"
          x-data
          @submit.prevent="if(confirm('Approve this voucher?')) $el.submit()">
        @csrf
        <button type="submit"
                title="Approve"
                class="p-1.5 rounded-lg text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors">
            <i class="bi bi-check-circle text-base"></i>
        </button>
    </form>
@endif

{{-- Post Approved Voucher --}}
@if($voucher->isApproved())
    <form method="POST"
          action="{{ route('vouchers.post', $voucher) }}"
          x-data
          @submit.prevent="if(confirm('Post this approved voucher to the Ledger?')) $el.submit()">
        @csrf
        <button type="submit"
                title="Post to Ledger"
                class="p-1.5 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
            <i class="bi bi-journal-check text-base"></i>
        </button>
    </form>
@endif
                                    

                                    @if($voucher->isPosted())
                                        <button type="button" title="Cancel"
                                                x-data
                                                @click="$dispatch('open-cancel-modal', { action: '{{ route('vouchers.cancel', $voucher) }}' })"
                                                class="p-1.5 rounded-lg text-gray-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors">
                                            <i class="bi bi-x-circle text-base"></i>
                                        </button>
                                    @endif

                                    @if($voucher->isDraft())
                                        <form method="POST"
                                              action="{{ route('vouchers.destroy', $voucher) }}"
                                              x-data
                                              @submit.prevent="if(confirm('Permanently delete this draft voucher?')) $el.submit()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Delete"
                                                    class="p-1.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                                <i class="bi bi-trash text-base"></i>
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="bi bi-journal-x text-5xl text-gray-300 dark:text-gray-600"></i>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">No vouchers found</p>
                                    <a href="{{ route('vouchers.create') }}"
                                       class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 transition-colors">
                                        Create your first voucher
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vouchers->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $vouchers->links() }}
            </div>
        @endif

    </div>

</div>

{{-- Cancel Modal --}}
<div x-data="{ open: false, action: '' }"
     @open-cancel-modal.window="open = true; action = $event.detail.action"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center">

    <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

    <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md mx-4 p-6 z-10" @click.stop>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cancel Voucher</h3>
        <form method="POST" :action="action">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Cancellation Reason <span class="text-red-500">*</span>
                </label>
                <textarea name="cancellation_reason"
                          rows="3"
                          required
                          minlength="5"
                          placeholder="Enter reason for cancellation..."
                          class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-red-500 outline-none transition"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg transition-colors">
                    Close
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Confirm Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection