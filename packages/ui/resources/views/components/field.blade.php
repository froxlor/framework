@props(['colSpan' => null])

@php
    $colSpan = match ((int)$colSpan) {
        2 => 'col-span-2',
        3 => 'col-span-3',
        4 => 'col-span-4',
        5 => 'col-span-5',
        6 => 'col-span-6',
        default => 'col-span-full',
    };
@endphp

<div {{ $attributes->twMerge('flex flex-col space-y-2', $colSpan) }}>
    {{ $slot }}
</div>
