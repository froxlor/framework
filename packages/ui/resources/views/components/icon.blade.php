@props(['name', 'variant' => null, 'size' => 1])

@php
    // Normalize icon payloads (can be string or object/array with name+variant).
    if (is_object($name)) {
        $variant ??= $name->variant ?? null;
        $name = $name->name ?? '';
    } elseif (is_array($name)) {
        $variant ??= $name['variant'] ?? null;
        $name = $name['name'] ?? '';
    }

    $size = is_numeric($size) ? $size : 1;
    $variant = match ($variant) {
        'info' => 'text-info [&>svg]:text-current *:data-[slot=alert-description]:text-info',
        'primary' => 'text-primary [&>svg]:text-current *:data-[slot=alert-description]:text-primary',
        'success' => 'text-success [&>svg]:text-current *:data-[slot=alert-description]:text-success',
        'danger' => 'text-danger [&>svg]:text-current *:data-[slot=alert-description]:text-danger',
        'warning' => 'text-warning [&>svg]:text-current *:data-[slot=alert-description]:text-warning',
        'secondary' => 'text-secondary [&>svg]:text-current *:data-[slot=alert-description]:text-secondary',
        'subtle' => 'opacity-60',
        default => '',
    };
@endphp

<i
    data-lucide="{{ $name }}"
    {{ $attributes->twMerge(['inline-block', '[&>svg]:h-full', '[&>svg]:w-full', $variant]) }}
    style="width: calc(1em * {{ $size }}); height: calc(1em * {{ $size }});"
></i>
