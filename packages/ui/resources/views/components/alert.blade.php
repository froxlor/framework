@props(['layout' => null, 'variant' => null, 'format' => 'rounded'])

@php
    $layout = match($layout) {
        'solid' => !in_array($variant, [null])
            ? 'bg-current [&>svg]:text-white *:data-[slot=alert-title]:text-white *:data-[slot=alert-description]:text-white/90'
            : 'bg-current [&>svg]:text-black *:data-[slot=alert-title]:text-black *:data-[slot=alert-description]:text-black/90',
        default => 'bg-card',
    };

    $variant = match($variant) {
        'info' => 'text-info [&>svg]:text-current *:data-[slot=alert-description]:text-info',
        'primary' => 'text-primary [&>svg]:text-current *:data-[slot=alert-description]:text-primary',
        'success' => 'text-success [&>svg]:text-current *:data-[slot=alert-description]:text-success',
        'danger', 'error' => 'text-danger [&>svg]:text-current *:data-[slot=alert-description]:text-danger',
        'warning' => 'text-warning [&>svg]:text-current *:data-[slot=alert-description]:text-warning',
        default => 'text-card-foreground',
    };

    $format = match ($format) {
        'square' => 'rounded-none',
        'rounded' => 'rounded-lg',
    };
@endphp

<div {{ $attributes->twMerge('relative w-full rounded-lg border px-4 py-3 text-sm grid has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] grid-cols-[0_1fr] has-[>svg]:gap-x-3 gap-y-0.5 items-start [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current', $variant, $format, $layout) }}>
    {{ $slot }}
</div>
