@props(['cols' => 1, 'gap' => 6])

@php
    $gridCols = match ((int)$cols) {
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-3',
        4 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4',
        5 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3',
        6 => 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6',
        default => 'grid-cols-1',
    };

    $gap = is_numeric($gap) ? "gap-{$gap}" : $gap;
@endphp

<div {{ $attributes->twMerge(['grid', $gridCols, $gap]) }}>
    {{ $slot }}
</div>
