@props(['variant' => 'round'])

@php
    $shapeClass = $variant === 'square' ? 'rounded-lg' : 'rounded-full';
@endphp

<div {{ $attributes->twMerge(['relative flex bg-muted-foreground dark:bg-muted-foreground size-8 shrink-0 overflow-hidden', $shapeClass]) }}>
    {{ $slot }}
</div>
