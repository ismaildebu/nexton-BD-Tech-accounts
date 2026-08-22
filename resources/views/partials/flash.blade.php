@if(session('success'))
    <div x-data="{ show: true }"
         x-show="show"
         x-init="setTimeout(() => show = false, 5000)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="flex items-start gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
        <i class="bi bi-check-circle-fill text-green-500 text-lg shrink-0 mt-0.5"></i>
        <div class="flex-1">
            <p class="text-sm font-medium text-green-700 dark:text-green-400">
                {{ session('success') }}
            </p>
        </div>
        <button @click="show = false"
                class="text-green-400 hover:text-green-600 dark:hover:text-green-300 transition-colors shrink-0">
            <i class="bi bi-x-lg text-sm"></i>
        </button>
    </div>
@endif

@if(session('error'))
    <div x-data="{ show: true }"
         x-show="show"
         x-init="setTimeout(() => show = false, 8000)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
        <i class="bi bi-exclamation-triangle-fill text-red-500 text-lg shrink-0 mt-0.5"></i>
        <div class="flex-1">
            <p class="text-sm font-medium text-red-700 dark:text-red-400">
                {{ session('error') }}
            </p>
        </div>
        <button @click="show = false"
                class="text-red-400 hover:text-red-600 dark:hover:text-red-300 transition-colors shrink-0">
            <i class="bi bi-x-lg text-sm"></i>
        </button>
    </div>
@endif

@if(session('warning'))
    <div x-data="{ show: true }"
         x-show="show"
         x-init="setTimeout(() => show = false, 6000)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
        <i class="bi bi-exclamation-circle-fill text-amber-500 text-lg shrink-0 mt-0.5"></i>
        <div class="flex-1">
            <p class="text-sm font-medium text-amber-700 dark:text-amber-400">
                {{ session('warning') }}
            </p>
        </div>
        <button @click="show = false"
                class="text-amber-400 hover:text-amber-600 dark:hover:text-amber-300 transition-colors shrink-0">
            <i class="bi bi-x-lg text-sm"></i>
        </button>
    </div>
@endif

@if(session('info'))
    <div x-data="{ show: true }"
         x-show="show"
         x-init="setTimeout(() => show = false, 5000)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
        <i class="bi bi-info-circle-fill text-blue-500 text-lg shrink-0 mt-0.5"></i>
        <div class="flex-1">
            <p class="text-sm font-medium text-blue-700 dark:text-blue-400">
                {{ session('info') }}
            </p>
        </div>
        <button @click="show = false"
                class="text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 transition-colors shrink-0">
            <i class="bi bi-x-lg text-sm"></i>
        </button>
    </div>
@endif