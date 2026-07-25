<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800">
                Account Details
            </h2>

            <a href="{{ route('accounts.index') }}"
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-6">

                <table class="w-full border border-gray-300">

                    <tr>
                        <th class="border p-3 bg-gray-100 text-left w-1/3">
                            Company
                        </th>
                        <td class="border p-3">
                            {{ $account->company->company_name ?? '-' }}
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Account Code
                        </th>
                        <td class="border p-3">
                            {{ $account->account_code }}
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Account Name
                        </th>
                        <td class="border p-3">
                            {{ $account->account_name }}
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Account Type
                        </th>
                        <td class="border p-3">
                            {{ $account->account_type }}
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Parent Account
                        </th>
                        <td class="border p-3">
                            {{ $account->parent->account_name ?? 'None' }}
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Nature
                        </th>
                        <td class="border p-3">
                            {{ $account->nature }}
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Opening Balance
                        </th>
                        <td class="border p-3">
                            {{ number_format($account->opening_balance, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Balance Type
                        </th>
                        <td class="border p-3">
                            {{ $account->balance_type }}
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Status
                        </th>
                        <td class="border p-3">
                            @if($account->is_active)
                                <span class="text-green-600 font-semibold">
                                    Active
                                </span>
                            @else
                                <span class="text-red-600 font-semibold">
                                    Inactive
                                </span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Created At
                        </th>
                        <td class="border p-3">
                            {{ $account->created_at->format('d M Y h:i A') }}
                        </td>
                    </tr>

                    <tr>
                        <th class="border p-3 bg-gray-100">
                            Updated At
                        </th>
                        <td class="border p-3">
                            {{ $account->updated_at->format('d M Y h:i A') }}
                        </td>
                    </tr>

                </table>

                <div class="mt-6 flex gap-3">

                    <a href="{{ route('accounts.edit', $account) }}"
                       class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded">
                        Edit
                    </a>

                    <a href="{{ route('accounts.index') }}"
                       class="bg-gray-600 hover:bg-gray-700 text-white px-5 py-2 rounded">
                        Back
                    </a>

                </div>

            </div>

        </div>
    </div>

</x-app-layout>