<!-- বাম পাশের সাইডবার: কোনো fixed বা ml- এর ঝামেলা ছাড়াই সরাসরি লেআউটে থাকবে -->
<aside class="w-64 bg-white border-r border-gray-200 min-h-screen flex flex-col justify-between p-4 shrink-0">
    
    <!-- উপরের লোগো এবং মেনু লিস্ট -->
    <div class="flex flex-col space-y-6">
        <div class="flex items-center px-2 py-2">
            <span class="text-xl font-bold text-gray-800">Your App</span>
        </div>

        <nav class="flex flex-col space-y-1">
            <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium">
                Dashboard
            </a>
           <a href="{{ route('companies.index') }}" 
   class="flex items-center px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
    Companies
</a>


<a href="{{ route('accounts.index') }}" 
   class="flex items-center px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
    Accounts
</a>


<a href="{{ route('transactions.index') }}" 
   class="flex items-center px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
    Transactions
</a>


<div x-data="{ openReports: false }">

    <button
        @click="openReports = !openReports"
        class="w-full flex items-center justify-between px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">

        <span>Reports</span>

        <svg class="w-4 h-4 transition-transform"
             :class="{ 'rotate-180': openReports }"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 9l-7 7-7-7"/>
        </svg>

    </button>

    <div x-show="openReports" x-transition class="ml-4 mt-1 space-y-1">

        <a href="{{ route('trial-balance.index') }}"
   class="block px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100">
    Trial Balance
</a>

<a href="{{ route('profit-loss.index') }}"
   class="block px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100">
    Profit &amp; Loss
</a>

<a href="{{ route('balance-sheet.index') }}"
   class="block px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100">
    Balance Sheet
</a>

<a href="{{ route('cash-flow.index') }}"
   class="block px-4 py-2 rounded-lg text-gray-600 hover:bg-gray-100">
    Cash Flow
</a>

    </div>

</div>
        

        </nav>
    </div>

    <!-- একদম নিচে লগআউট বাটন -->
    <div class="pb-2">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center px-4 py-2.5 rounded-lg bg-red-600 text-white font-medium hover:bg-red-700 transition">
                Logout
            </button>
        </form>
    </div>

</aside>