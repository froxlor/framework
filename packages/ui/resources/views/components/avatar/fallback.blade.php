@props(['variant' => 'round'])

@php
    $shapeClass = $variant === 'square' ? 'rounded-lg' : 'rounded-full';
@endphp

<div {{ $attributes->twMerge(['absolute bg-secondary text-sm flex pb-0.5 size-full items-center justify-center', $shapeClass]) }}>
    {{ $slot }}
</div>
