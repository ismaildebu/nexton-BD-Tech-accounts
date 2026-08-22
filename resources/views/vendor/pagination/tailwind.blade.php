@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">

        {{-- Mobile --}}
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg cursor-not-allowed">
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Previous
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Next
                </a>
            @else
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg cursor-not-allowed">
                    Next
                </span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Showing
                <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->firstItem() }}</span>
                to
                <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->lastItem() }}</span>
                of
                <span class="font-semibold text-gray-900 dark:text-white">{{ $paginator->total() }}</span>
                results
            </p>

            <div class="flex items-center gap-1">

                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-300 dark:text-gray-600 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg cursor-not-allowed">
                        <i class="bi bi-chevron-left"></i>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}"
                       class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-blue-600 transition-colors">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                @endif

                {{-- Page Numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-400 dark:text-gray-500">
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex items-center justify-center w-9 h-9 text-sm font-semibold text-white bg-blue-600 border border-blue-600 rounded-lg cursor-default">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}"
                                   class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-300 transition-colors">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}"
                       class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-blue-600 transition-colors">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <span class="inline-flex items-center justify-center w-9 h-9 text-sm text-gray-300 dark:text-gray-600 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg cursor-not-allowed">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                @endif

            </div>
        </div>
    </nav>
@endif