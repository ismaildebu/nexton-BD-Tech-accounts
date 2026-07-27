<nav class="fixed left-0 top-0 w-64 h-screen bg-white border-r shadow-sm p-5">

    <div class="text-center mb-8">
        <img src="{{ asset('images/logo.png') }}" 
             class="mx-auto w-32">
    </div>


    <ul class="space-y-3">


        <li>
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-lg bg-blue-600 text-white">

                🏠
                Dashboard

            </a>
        </li>


        <li>
            <a href="{{ route('companies.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100">

                🏢
                Companies

            </a>
        </li>


        <li>
            <a href="#"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100">

                📒
                Accounts

            </a>
        </li>


        <li>
            <a href="#"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100">

                💰
                Transactions

            </a>
        </li>


        <li>
            <a href="#"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-100">

                📊
                Reports

            </a>
        </li>


    </ul>


    <div class="absolute bottom-5 left-5 right-5">


        <form method="POST" action="{{ route('logout') }}">

            @csrf

            <button class="w-full bg-red-600 text-white py-3 rounded-lg">

                Logout

            </button>

        </form>


    </div>


</nav>