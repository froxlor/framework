@props(['value', 'max' => 100, 'variant' => null])

@php
    $variant = match ($variant) {
        'info' => 'bg-info dark:bg-info/60',
        'success' => 'bg-success dark:bg-success/60',
        'warning', 'amber' => 'bg-warning dark:bg-warning/60',
        'danger', 'red' => 'bg-danger dark:bg-danger/60',
        'purple' => 'bg-purple-500 dark:bg-purple-500/60',
        default => 'bg-primary',
    };
@endphp

<div {{ $attributes->twMerge('h-full rounded-lg bg-primary transition-all duration-300 ease-in-out', $variant) }} style="width: {{ ($value / $max) * 100 }}%"></div>
