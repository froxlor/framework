@props(['cols' => null])

@php
    $gridCols = match ((int)$cols) {
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
        4 => 'grid-cols-4',
        5 => 'grid-cols-5',
        6 => 'grid-cols-6',
        default => 'grid-cols-1',
    };
@endphp

<form {{ $attributes->twMerge('grid gap-6', $gridCols) }}>
    {{ $slot }}
</form>
