@props([
    'text' => 'Verified',
    'color' => 'emerald-600',
    'textColor' => 'text-emerald-800',
    'darkTextColor' => 'dark:text-emerald-100',
])

<div
    {{ $attributes->merge([
        'class' =>
            'inline-flex items-center justify-center gap-1.5 px-3 py-3 text-sm font-semibold border rounded-full shadow transition-all w-full ' .
            $textColor .
            ' ' .
            $darkTextColor .
            ' ' .
            'bg-emerald-50 border-emerald-200 ' .
            'dark:bg-emerald-900/30 dark:border-emerald-700/50',
    ]) }}>
    {{-- SVG Centang --}}
    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
        stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"></polyline>
    </svg>

    {{-- Teks Tengah --}}
    <span class="leading-none mt-0.5">
        {{ $text }}
    </span>
</div>
