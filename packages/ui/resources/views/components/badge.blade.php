@props(['label' => null, 'variant' => null, 'size' => null])

@php
    $variant = match($variant) {
        'info' => 'bg-info text-white shadow-xs focus-visible:ring-info/20 dark:focus-visible:ring-info/40 dark:bg-info/60',
        'success' => 'bg-success text-white shadow-xs focus-visible:ring-success/20 dark:focus-visible:ring-success/40 dark:bg-success/60',
        'warning' => 'bg-warning text-white shadow-xs focus-visible:ring-warning/20 dark:focus-visible:ring-warning/40 dark:bg-warning/60',
        'danger' => 'bg-danger text-white shadow-xs focus-visible:ring-danger/20 dark:focus-visible:ring-danger/40 dark:bg-danger/60',
        'outline' => 'inset-ring bg-background shadow-xs text-accent-foreground dark:bg-input/30 dark:inset-ring-input',
        'secondary' => 'bg-secondary text-secondary-foreground shadow-xs',
        default => 'bg-primary text-primary-foreground shadow-xs',
    };

    $size = match($size) {
        'sm' => 'px-1.5 py-0.5 text-[0.625rem] leading-4',
        'icon' => 'size-9',
        default => 'px-2 py-1 text-xs leading-4',
    };
@endphp

<span {{ $attributes->twMerge(["text-xs rounded", $variant, $size]) }}>
    {{ $label ?? $slot }}
</span>
