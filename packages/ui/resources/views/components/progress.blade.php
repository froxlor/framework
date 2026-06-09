@props(['value', 'max' => 100, 'height' => 'md', 'variant' => null])

@php
    $height = match ($height) {
        'sm' => 'h-1',
        'md' => 'h-2',
        'lg' => 'h-3',
    };
@endphp

<div {{ $attributes->twMerge('w-full rounded-lg overflow-hidden bg-zinc-200 dark:bg-zinc-700', $height) }}>
    <x-ui::progress.item :value="$value" :max="$max" :variant="$variant"/>
</div>
