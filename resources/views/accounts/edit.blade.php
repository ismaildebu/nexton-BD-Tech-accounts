<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800">
            Edit Account
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('accounts.update',$account->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Company -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Company</label>

                        <input
                            type="text"
                            class="w-full border rounded px-3 py-2 bg-gray-100"
                            value="{{ $account->company->company_name ?? '' }}"
                            readonly>
                    </div>

                    <!-- Account Code -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Account Code</label>

                        <input
                            type="text"
                            class="w-full border rounded px-3 py-2 bg-gray-100"
                            value="{{ $account->account_code }}"
                            readonly>
                    </div>

                    <!-- Account Name -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Account Name</label>

                        <input
                            type="text"
                            name="account_name"
                            class="w-full border rounded px-3 py-2"
                            value="{{ old('account_name',$account->account_name) }}"
                            required>
                    </div>

                    <!-- Account Type -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Account Type</label>

                        <input
                            type="text"
                            class="w-full border rounded px-3 py-2 bg-gray-100"
                            value="{{ $account->account_type }}"
                            readonly>
                    </div>

                    <!-- Nature -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Nature</label>

                        <select name="nature" class="w-full border rounded px-3 py-2">

                            <option value="General" {{ $account->nature=='General'?'selected':'' }}>
                                General
                            </option>

                            <option value="Cash" {{ $account->nature=='Cash'?'selected':'' }}>
                                Cash
                            </option>

                            <option value="Bank" {{ $account->nature=='Bank'?'selected':'' }}>
                                Bank
                            </option>

                        </select>
                    </div>

                    <!-- Opening Balance -->
                    @if(!$hasTransactions)

                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Opening Balance</label>

                        <input
                            type="number"
                            step="0.01"
                            name="opening_balance"
                            value="{{ old('opening_balance',$account->opening_balance) }}"
                            class="w-full border rounded px-3 py-2">
                    </div>

                    @endif

                    <!-- Color -->
                    <div class="mb-4">
                        <label class="block font-semibold mb-2">Color</label>

                        <input
                            type="color"
                            name="color"
                            value="{{ old('color',$account->color ?? '#2563eb') }}">
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <label class="block font-semibold mb-2">Status</label>

                        <select name="is_active" class="w-full border rounded px-3 py-2">

                            <option value="1" {{ $account->is_active ? 'selected':'' }}>
                                Active
                            </option>

                            <option value="0" {{ !$account->is_active ? 'selected':'' }}>
                                Inactive
                            </option>

                        </select>
                    </div>

                    <div class="flex gap-3">

                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">
                            Update Account
                        </button>

                        <a href="{{ route('accounts.index') }}"
                           class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded">
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </div>
    </div>

</x-app-layout>