@props(['value', 'max' => 100, 'height' => 'md'])

@php
    $height = match ($height) {
        'sm' => 'h-1',
        'md' => 'h-2',
        'lg' => 'h-3',
    };
@endphp

<div {{ $attributes->twMerge('inline-flex w-full rounded-lg bg-zinc-200 dark:bg-zinc-700 [&>*]:rounded-none [&>*:first-child]:rounded-l-md [&>*:last-child]:rounded-r-md', $height) }}>
    {{ $slot }}
</div>
