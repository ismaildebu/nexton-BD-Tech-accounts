@extends('layouts.app')

@section('page-title', 'Bulk Party Entry')
@section('page-subtitle', 'একসাথে একাধিক Agent ও Hawker যোগ করুন')

@section('content')
<div x-data="bulkParty()" class="space-y-6">

    {{-- Flash messages --}}
    <div x-show="flash.message" x-cloak
         :class="flash.type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
         class="border px-4 py-3 rounded-lg text-sm flex items-center gap-2">
        <span x-text="flash.message"></span>
        <button @click="flash.message=''" class="ml-auto opacity-60 hover:opacity-100">✕</button>
    </div>

    {{-- ── AGENTS ── --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b bg-slate-50">
            <div class="flex items-center gap-2">
                <span class="font-medium text-sm">Agents</span>
                <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">Agent</span>
                <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full" x-text="agents.length + ' টি'"></span>
            </div>
            <button @click="addRow('agents')" type="button"
                    class="text-xs text-blue-600 border border-dashed border-blue-300 px-3 py-1.5 rounded-lg hover:bg-blue-50">
                + Row যোগ করুন
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[860px]">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs w-8">#</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">নাম <span class="text-red-500">*</span></th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">কোড <span class="text-red-500">*</span></th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">ফোন</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Alt. ফোন</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">এলাকা</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Free %</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Opening Bal.</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Bal. Type</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">AR Account</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="agents.length === 0">
                        <tr><td colspan="11" class="px-4 py-6 text-center text-slate-400 text-sm">কোনো Agent নেই — Row যোগ করুন</td></tr>
                    </template>
                    <template x-for="(row, i) in agents" :key="row._id">
                        <tr class="border-b last:border-0 hover:bg-slate-50">
                            <td class="px-3 py-1.5 text-xs text-slate-400" x-text="i + 1"></td>
                            <td class="px-1.5 py-1"><input x-model="row.name" type="text" placeholder="নাম" :class="row._err && !row.name ? 'border-red-400 bg-red-50' : 'border-slate-200'" class="w-full border rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.code" type="text" placeholder="A-001" :class="row._err && !row.code ? 'border-red-400 bg-red-50' : 'border-slate-200'" class="w-full border rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.phone" type="text" placeholder="01XXXXXXXXX" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.alternate_phone" type="text" placeholder="Alt phone" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.area" type="text" placeholder="এলাকা" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.free_percentage" type="number" min="0" max="100" step="0.01" placeholder="0.00" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.opening_balance" type="number" min="0" step="0.01" placeholder="0.00" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400"></td>
                            <td class="px-1.5 py-1">
                                <select x-model="row.balance_type" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                                    <option value="Receivable">Receivable</option>
                                    <option value="Payable">Payable</option>
                                    <option value="Advance">Advance</option>
                                </select>
                            </td>
                            <td class="px-1.5 py-1">
                                <select x-model="row.account_id" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400">
                                    <option value="">— বেছে নিন —</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} — {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-1.5 py-1 text-center">
                                <button @click="removeRow('agents', i)" type="button" class="text-slate-400 hover:text-red-500 p-1 rounded">✕</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center px-4 py-2 border-t bg-slate-50">
            <span class="text-xs text-slate-500" x-text="agents.length + ' Agent প্রস্তুত'"></span>
            <button @click="addRow('agents')" type="button" class="text-xs text-blue-600 hover:underline">+ আরও যোগ করুন</button>
        </div>
    </div>

    {{-- ── HAWKERS ── --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b bg-slate-50">
            <div class="flex items-center gap-2">
                <span class="font-medium text-sm">Hawkers</span>
                <span class="bg-purple-100 text-purple-700 text-xs px-2 py-0.5 rounded-full">Hawker</span>
                <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full" x-text="hawkers.length + ' টি'"></span>
            </div>
            <button @click="addRow('hawkers')" type="button"
                    class="text-xs text-purple-600 border border-dashed border-purple-300 px-3 py-1.5 rounded-lg hover:bg-purple-50">
                + Row যোগ করুন
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[860px]">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs w-8">#</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">নাম <span class="text-red-500">*</span></th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">কোড <span class="text-red-500">*</span></th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">ফোন</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Alt. ফোন</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">এলাকা</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Free %</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Opening Bal.</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Bal. Type</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">AR Account</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="hawkers.length === 0">
                        <tr><td colspan="11" class="px-4 py-6 text-center text-slate-400 text-sm">কোনো Hawker নেই — Row যোগ করুন</td></tr>
                    </template>
                    <template x-for="(row, i) in hawkers" :key="row._id">
                        <tr class="border-b last:border-0 hover:bg-slate-50">
                            <td class="px-3 py-1.5 text-xs text-slate-400" x-text="i + 1"></td>
                            <td class="px-1.5 py-1"><input x-model="row.name" type="text" placeholder="নাম" :class="row._err && !row.name ? 'border-red-400 bg-red-50' : 'border-slate-200'" class="w-full border rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.code" type="text" placeholder="H-001" :class="row._err && !row.code ? 'border-red-400 bg-red-50' : 'border-slate-200'" class="w-full border rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.phone" type="text" placeholder="01XXXXXXXXX" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.alternate_phone" type="text" placeholder="Alt phone" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.area" type="text" placeholder="এলাকা" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.free_percentage" type="number" min="0" max="100" step="0.01" placeholder="0.00" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.opening_balance" type="number" min="0" step="0.01" placeholder="0.00" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400"></td>
                            <td class="px-1.5 py-1">
                                <select x-model="row.balance_type" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400">
                                    <option value="Receivable">Receivable</option>
                                    <option value="Payable">Payable</option>
                                    <option value="Advance">Advance</option>
                                </select>
                            </td>
                            <td class="px-1.5 py-1">
                                <select x-model="row.account_id" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400">
                                    <option value="">— বেছে নিন —</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} — {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-1.5 py-1 text-center">
                                <button @click="removeRow('hawkers', i)" type="button" class="text-slate-400 hover:text-red-500 p-1 rounded">✕</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center px-4 py-2 border-t bg-slate-50">
            <span class="text-xs text-slate-500" x-text="hawkers.length + ' Hawker প্রস্তুত'"></span>
            <button @click="addRow('hawkers')" type="button" class="text-xs text-purple-600 hover:underline">+ আরও যোগ করুন</button>
        </div>
    </div>

    {{-- ── Footer actions ── --}}
    <div class="flex justify-between items-center">
        <span class="text-sm text-slate-500">মোট: <strong x-text="agents.length + hawkers.length"></strong> party</span>
        <div class="flex gap-3">
            <a href="{{ route('media.parties.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm hover:bg-slate-50">বাতিল</a>
            <button @click="save()" type="button" :disabled="saving"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-60">
                <span x-show="!saving">সব Save করুন</span>
                <span x-show="saving">সংরক্ষণ হচ্ছে…</span>
            </button>
        </div>
    </div>
</div>

<script>
function bulkParty() {
    let _id = 0;
    const blank = (extra = {}) => ({
        _id: ++_id, _err: false,
        name: '', code: '', phone: '', alternate_phone: '',
        area: '', free_percentage: '', opening_balance: '',
        balance_type: 'Receivable', account_id: '',
        ...extra,
    });

    return {
        agents:  [blank()],
        hawkers: [blank()],
        flash:   { type: '', message: '' },
        saving:  false,

        addRow(list) { this[list].push(blank()); },
        removeRow(list, i) { this[list].splice(i, 1); },

        validate() {
            let ok = true;
            ['agents', 'hawkers'].forEach(list => {
                this[list].forEach(r => {
                    r._err = !r.name.trim() || !r.code.trim();
                    if (r._err) ok = false;
                });
            });
            return ok;
        },

        async save() {
            if (!this.validate()) {
                this.flash = { type: 'error', message: 'নাম ও কোড (*) ফিল্ড পূরণ করুন।' };
                return;
            }
            if (!this.agents.length && !this.hawkers.length) {
                this.flash = { type: 'error', message: 'কমপক্ষে একটি row যোগ করুন।' };
                return;
            }

            this.saving = true;
            try {
                const res = await fetch('{{ route('media.parties.bulk-store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ agents: this.agents, hawkers: this.hawkers }),
                });

                const data = await res.json();

                if (res.ok) {
                    this.flash = { type: 'success', message: data.message };
                    this.agents  = [blank()];
                    this.hawkers = [blank()];
                } else {
                    const dupes = data.duplicate_codes ? ' (Duplicate: ' + data.duplicate_codes.join(', ') + ')' : '';
                    this.flash = { type: 'error', message: data.message + dupes };
                }
            } catch (e) {
                this.flash = { type: 'error', message: 'Network error। আবার চেষ্টা করুন।' };
            } finally {
                this.saving = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    };
}
</script>
@endsection
