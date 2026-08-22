@php
    $badges = [
        'draft'     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        'posted'    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    ];
    $icons = [
        'draft'     => 'bi-pencil-square',
        'posted'    => 'bi-check-circle-fill',
        'cancelled' => 'bi-x-circle-fill',
    ];
    $class = $badges[$status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
    $icon  = $icons[$status]  ?? 'bi-question-circle';
    $label = ucfirst($status ?? 'unknown');
@endphp
<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $class }}">
    <i class="bi {{ $icon }}"></i>
    {{ $label }}
</span>