@props(['size' => '3xl'])

@php
    $size = match ($size) {
        'xl' => 'text-xl',
        '2xl' => 'text-2xl',
        '3xl' => 'text-3xl',
    };
@endphp

<div {{ $attributes->twMerge(['font-bold', $size]) }}>
    {{ $slot }}
</div>
