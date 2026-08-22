@php
    $isEdit  = isset($transaction) && $transaction !== null;
    $details = $isEdit ? $transaction->details : collect();
@endphp

<div x-data="voucherForm({{ json_encode([
    'details' => $isEdit
        ? $details->map(fn($d) => [
            'account_id'    => $d->account_id,
            'description'   => $d->description,
            'debit_amount'  => $d->debit_amount,
            'credit_amount' => $d->credit_amount,
        ])->values()->toArray()
        : [
            ['account_id' => '', 'description' => '', 'debit_amount' => 0, 'credit_amount' => 0],
            ['account_id' => '', 'description' => '', 'debit_amount' => 0, 'credit_amount' => 0],
        ]
]) }})">

    <form method="POST"
          action="{{ $formAction }}"
          id="voucher-form">

        @csrf
        @if($formMethod === 'PUT')
            @method('PUT')
        @endif

        <input type="hidden" name="save_mode"   x-model="saveMode">
        <input type="hidden" name="company_id"  value="{{ session('company_id') }}">

        {{-- ============================================================ --}}
        {{-- HEADER --}}
        {{-- ============================================================ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">

            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-5 flex items-center gap-2">
                <i class="bi bi-journal-text text-blue-600"></i>
                Voucher Information
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

                {{-- Voucher Number --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Voucher Number</label>
                    <input type="text"
                           value="{{ $isEdit ? $transaction->voucher_number : 'Auto Generated' }}"
                           readonly
                           class="w-full text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 px-3 py-2 cursor-not-allowed">
                </div>

                {{-- Voucher Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Voucher Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date"
                           name="voucher_date"
                           value="{{ old('voucher_date', $isEdit ? $transaction->voucher_date?->format('Y-m-d') : now()->format('Y-m-d')) }}"
                           required
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                    @error('voucher_date')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Voucher Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Voucher Type <span class="text-red-500">*</span>
                    </label>
                    <select name="voucher_type_id" required
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                        <option value="">— Select Type —</option>
                        @foreach($voucherTypes as $type)
                            <option value="{{ $type->id }}"
                                @selected(old('voucher_type_id', $isEdit ? $transaction->voucher_type_id : '') == $type->id)>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('voucher_type_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Financial Year --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Financial Year <span class="text-red-500">*</span>
                    </label>
                    <select name="financial_year_id" required
                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                        <option value="">— Select Year —</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}"
                                @selected(old('financial_year_id', $isEdit ? $transaction->financial_year_id : '') == $fy->id)>
                                {{ $fy->year_name ?? ($fy->start_date . ' – ' . $fy->end_date) }}
                            </option>
                        @endforeach
                    </select>
                    @error('financial_year_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Reference Number --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference Number</label>
                    <input type="text"
                           name="reference_number"
                           value="{{ old('reference_number', $isEdit ? $transaction->reference_number : '') }}"
                           placeholder="Cheque no, Bill no, etc."
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

                {{-- Narration --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Narration</label>
                    <input type="text"
                           name="narration"
                           value="{{ old('narration', $isEdit ? $transaction->narration : '') }}"
                           placeholder="Brief description..."
                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition">
                </div>

            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- DETAIL GRID --}}
        {{-- ============================================================ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-table text-blue-600"></i>
                    Ledger Lines
                </h2>
                <button type="button" @click="addRow()"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 rounded-lg transition-colors">
                    <i class="bi bi-plus-lg"></i>
                    Add Row
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-12">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase min-w-56">Account <span class="text-red-400">*</span></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase min-w-48">Description</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase min-w-36">Debit</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase min-w-36">Credit</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-16">Remove</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="(row, index) in details" :key="index">
                            <tr>
                                <td class="px-4 py-2 text-gray-400 text-xs" x-text="index + 1"></td>

                                <td class="px-4 py-2">
                                    <select :name="`details[${index}][account_id]`"
                                            x-model="row.account_id"
                                            class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none transition">
                                        <option value="">— Select Account —</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="px-4 py-2">
                                    <input type="text"
                                           :name="`details[${index}][description]`"
                                           x-model="row.description"
                                           placeholder="Optional"
                                           class="w-full text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </td>

                                <td class="px-4 py-2">
                                    <input type="number"
                                           :name="`details[${index}][debit_amount]`"
                                           x-model.number="row.debit_amount"
                                           @input="onDebitInput(index)"
                                           min="0"
                                           step="0.01"
                                           placeholder="0.00"
                                           class="w-full text-sm text-right rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </td>

                                <td class="px-4 py-2">
                                    <input type="number"
                                           :name="`details[${index}][credit_amount]`"
                                           x-model.number="row.credit_amount"
                                           @input="onCreditInput(index)"
                                           min="0"
                                           step="0.01"
                                           placeholder="0.00"
                                           class="w-full text-sm text-right rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </td>

                                <td class="px-4 py-2 text-center">
                                    <button type="button"
                                            @click="removeRow(index)"
                                            x-show="details.length > 2"
                                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <i class="bi bi-trash text-sm"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-4">
                    <div class="flex items-center gap-6">
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-0.5">Total Debit</p>
                            <p class="text-base font-semibold text-gray-900 dark:text-white font-mono" x-text="formatAmount(totalDebit)"></p>
                        </div>
                        <div class="w-px h-10 bg-gray-200 dark:bg-gray-700"></div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-0.5">Total Credit</p>
                            <p class="text-base font-semibold text-gray-900 dark:text-white font-mono" x-text="formatAmount(totalCredit)"></p>
                        </div>
                        <div class="w-px h-10 bg-gray-200 dark:bg-gray-700"></div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-0.5">Difference</p>
                            <p class="text-base font-semibold font-mono"
                               :class="isBalanced ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                               x-text="formatAmount(Math.abs(difference))"></p>
                        </div>
                    </div>
                </div>

                <div x-show="!isBalanced && (totalDebit > 0 || totalCredit > 0)"
                     x-cloak
                     class="mt-3 flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Debit and Credit totals must be equal before posting.</span>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- BUTTONS --}}
        {{-- ============================================================ --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">

            <a href="{{ route('vouchers.index') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                <i class="bi bi-arrow-left"></i>
                Cancel
            </a>

            <div class="flex items-center gap-3 w-full sm:w-auto">

                <button type="button"
                        @click="saveMode = 'draft'; $nextTick(() => document.getElementById('voucher-form').submit())"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                    <i class="bi bi-floppy"></i>
                    Save Draft
                </button>

                <button type="button"
                        @click="submitPost()"
                        :disabled="!isBalanced"
                        :class="isBalanced ? 'bg-green-600 hover:bg-green-700 text-white cursor-pointer' : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium rounded-lg transition-colors">
                    <i class="bi bi-check2-circle"></i>
                    Post Voucher
                </button>

            </div>
        </div>

    </form>

</div>

<script>
function voucherForm(config) {
    return {
        details: config.details,
        saveMode: 'draft',
        totalDebit: 0,
        totalCredit: 0,
        difference: 0,
        isBalanced: false,

        init() {
            this.recalculate();
        },

        addRow() {
            this.details.push({
                account_id:    '',
                description:   '',
                debit_amount:  0,
                credit_amount: 0,
            });
        },

        removeRow(index) {
            if (this.details.length > 2) {
                this.details.splice(index, 1);
                this.recalculate();
            }
        },

        onDebitInput(index) {
            const val = parseFloat(this.details[index].debit_amount) || 0;
            if (val > 0) {
                this.details[index].credit_amount = 0;
            }
            this.recalculate();
        },

        onCreditInput(index) {
            const val = parseFloat(this.details[index].credit_amount) || 0;
            if (val > 0) {
                this.details[index].debit_amount = 0;
            }
            this.recalculate();
        },

        recalculate() {
            let debit  = 0;
            let credit = 0;
            this.details.forEach(row => {
                debit  += parseFloat(row.debit_amount  || 0);
                credit += parseFloat(row.credit_amount || 0);
            });
            this.totalDebit  = Math.round(debit  * 10000) / 10000;
            this.totalCredit = Math.round(credit * 10000) / 10000;
            this.difference  = Math.round((debit - credit) * 10000) / 10000;
            this.isBalanced  = this.totalDebit > 0 && this.difference === 0;
        },

        formatAmount(value) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 4,
            }).format(value || 0);
        },

        submitPost() {
            if (!this.isBalanced) return;
            if (!confirm('Post this voucher? Posted vouchers cannot be edited.')) return;
            this.saveMode = 'post';
            this.$nextTick(() => document.getElementById('voucher-form').submit());
        },
    };
}
</script>