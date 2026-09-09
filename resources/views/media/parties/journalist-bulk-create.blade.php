@extends('layouts.app')

@section('page-title', 'সাংবাদিক — Bulk Entry')
@section('page-subtitle', 'একসাথে একাধিক সাংবাদিক যোগ করুন')

@section('content')
<div x-data="bulkJournalist()" class="space-y-6">

    {{-- Flash --}}
    <div x-show="flash.message" x-cloak
         :class="flash.type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
         class="border px-4 py-3 rounded-lg text-sm flex items-center gap-2">
        <span x-text="flash.message"></span>
        <button @click="flash.message=''" class="ml-auto opacity-60 hover:opacity-100">✕</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b bg-slate-50">
            <div class="flex items-center gap-2">
                <span class="font-medium text-sm">সাংবাদিক তালিকা</span>
                <span class="bg-amber-100 text-amber-700 text-xs px-2 py-0.5 rounded-full">Journalist</span>
                <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full" x-text="rows.length + ' জন'"></span>
            </div>
            <div class="flex gap-2">
                <button @click="addRows(1)" type="button"
                        class="text-xs text-amber-600 border border-dashed border-amber-300 px-3 py-1.5 rounded-lg hover:bg-amber-50">
                    + ১টি যোগ করুন
                </button>
                <button @click="addRows(5)" type="button"
                        class="text-xs text-amber-700 border border-amber-400 px-3 py-1.5 rounded-lg hover:bg-amber-50">
                    + ৫টি একসাথে
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[1100px]">
                <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs w-8">#</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">নাম <span class="text-red-500">*</span></th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">কোড <span class="text-red-500">*</span></th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">পদবি / Beat</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">ফোন</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">ইমেইল</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">জেলা</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">পত্রিকা</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">কমিশন %</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Opening Bal.</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">Bal. Type</th>
                        <th class="text-left px-3 py-2 font-medium text-slate-500 text-xs">AR Account</th>
                        <th class="text-center px-3 py-2 font-medium text-slate-500 text-xs">Active</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="rows.length === 0">
                        <tr><td colspan="14" class="px-4 py-8 text-center text-slate-400 text-sm">কোনো সাংবাদিক নেই — Row যোগ করুন</td></tr>
                    </template>
                    <template x-for="(row, i) in rows" :key="row._id">
                        <tr class="border-b last:border-0 hover:bg-slate-50">
                            <td class="px-3 py-1.5 text-xs text-slate-400" x-text="i + 1"></td>
                            <td class="px-1.5 py-1"><input x-model="row.name" type="text" placeholder="সাংবাদিকের নাম" :class="row._err && !row.name ? 'border-red-400 bg-red-50' : 'border-slate-200'" class="w-full border rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.code" type="text" placeholder="J-001" :class="row._err && !row.code ? 'border-red-400 bg-red-50' : 'border-slate-200'" class="w-full border rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400"></td>
                            <td class="px-1.5 py-1">
                                <select x-model="row.beat" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400">
                                    <option value="">— বেছে নিন —</option>
                                    <option>Reporter</option>
                                    <option>Senior Reporter</option>
                                    <option>Staff Correspondent</option>
                                    <option>District Correspondent</option>
                                    <option>Feature Writer</option>
                                    <option>Photographer</option>
                                    <option>Cameraperson</option>
                                    <option>Sub-Editor</option>
                                    <option>Chief Reporter</option>
                                    <option>Bureau Chief</option>
                                </select>
                            </td>
                            <td class="px-1.5 py-1"><input x-model="row.phone" type="text" placeholder="01XXXXXXXXX" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.email" type="email" placeholder="email@example.com" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400"></td>
                            <td class="px-1.5 py-1">
                                <select x-model="row.district" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400">
                                    <option value="">— জেলা —</option>
                                    <option>ঢাকা</option><option>চট্টগ্রাম</option><option>রাজশাহী</option>
                                    <option>খুলনা</option><option>বরিশাল</option><option>সিলেট</option>
                                    <option>রংপুর</option><option>ময়মনসিংহ</option><option>যশোর</option>
                                    <option>কুমিল্লা</option><option>গাজীপুর</option><option>নারায়ণগঞ্জ</option>
                                    <option>অন্যান্য</option>
                                </select>
                            </td>
                            <td class="px-1.5 py-1"><input x-model="row.media_outlet" type="text" placeholder="পত্রিকা / চ্যানেল" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.commission_percent" type="number" min="0" max="100" step="0.01" placeholder="0.00" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400"></td>
                            <td class="px-1.5 py-1"><input x-model="row.opening_balance" type="number" min="0" step="0.01" placeholder="0.00" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400"></td>
                            <td class="px-1.5 py-1">
                                <select x-model="row.balance_type" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400">
                                    <option value="Receivable">Receivable</option>
                                    <option value="Payable">Payable</option>
                                    <option value="Advance">Advance</option>
                                </select>
                            </td>
                            <td class="px-1.5 py-1">
                                <select x-model="row.account_id" class="w-full border border-slate-200 rounded-md px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400">
                                    <option value="">— বেছে নিন —</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_code }} — {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-1.5 py-1 text-center">
                                <button @click="row.is_active = !row.is_active" type="button"
                                        :class="row.is_active ? 'bg-green-500' : 'bg-slate-300'"
                                        class="relative w-8 h-4 rounded-full transition-colors">
                                    <span :class="row.is_active ? 'translate-x-4' : 'translate-x-0.5'"
                                          class="inline-block w-3 h-3 bg-white rounded-full shadow transition-transform"></span>
                                </button>
                            </td>
                            <td class="px-1.5 py-1 text-center">
                                <button @click="rows.splice(i, 1)" type="button" class="text-slate-400 hover:text-red-500 p-1 rounded">✕</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center px-4 py-2 border-t bg-slate-50">
            <span class="text-xs text-slate-500" x-text="rows.length + ' জন সাংবাদিক প্রস্তুত'"></span>
            <button @click="addRows(1)" type="button" class="text-xs text-amber-600 hover:underline">+ আরও যোগ করুন</button>
        </div>
    </div>

    <div class="flex justify-between items-center">
        <span class="text-sm text-slate-500">মোট: <strong x-text="rows.length"></strong> জন সাংবাদিক</span>
        <div class="flex gap-3">
            <button @click="rows = []" type="button" class="px-4 py-2 border border-slate-300 rounded-lg text-sm hover:bg-slate-50">সব মুছুন</button>
            <a href="{{ route('media.parties.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm hover:bg-slate-50">বাতিল</a>
            <button @click="save()" type="button" :disabled="saving"
                    class="bg-amber-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-amber-700 disabled:opacity-60">
                <span x-show="!saving">সব Save করুন</span>
                <span x-show="saving">সংরক্ষণ হচ্ছে…</span>
            </button>
        </div>
    </div>
</div>

<script>
function bulkJournalist() {
    let _id = 0;
    const blank = () => ({
        _id: ++_id, _err: false,
        name: '', code: '', beat: '', phone: '', email: '',
        alternate_phone: '', area: '', district: '', media_outlet: '',
        commission_percent: '', opening_balance: '',
        balance_type: 'Receivable', account_id: '', is_active: true,
    });

    return {
        rows:  [blank(), blank(), blank()],
        flash: { type: '', message: '' },
        saving: false,

        addRows(n) { for (let i = 0; i < n; i++) this.rows.push(blank()); },

        validate() {
            let ok = true;
            this.rows.forEach(r => {
                r._err = !r.name.trim() || !r.code.trim();
                if (r._err) ok = false;
            });
            const codes = this.rows.map(r => r.code.toUpperCase());
            const dupes = codes.filter((c, i) => c && codes.indexOf(c) !== i);
            if (dupes.length) {
                this.flash = { type: 'error', message: 'কোড পুনরাবৃত্তি: ' + [...new Set(dupes)].join(', ') };
                return false;
            }
            return ok;
        },

        async save() {
            if (this.rows.length === 0) {
                this.flash = { type: 'error', message: 'কমপক্ষে একজন সাংবাদিক যোগ করুন।' };
                return;
            }
            if (!this.validate()) {
                if (!this.flash.message) this.flash = { type: 'error', message: 'নাম ও কোড (*) ফিল্ড পূরণ করুন।' };
                return;
            }

            this.saving = true;
            try {
                const res = await fetch('{{ route('media.parties.journalists.bulk-store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ journalists: this.rows }),
                });

                const data = await res.json();

                if (res.ok) {
                    this.flash = { type: 'success', message: data.message };
                    this.rows = [blank()];
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
