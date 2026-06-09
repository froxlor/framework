@props(['active' => false, 'href', 'navigate' => true, 'external' => false])

@php
    $active = filter_var($active, FILTER_VALIDATE_BOOLEAN)
        ? 'bg-primary text-white hover:text-white'
        : '';
    $ariaCurrent = $active ? 'aria-current="page"' : '';
    $externalClasses = $external ? 'inline-flex items-center' : '';
    $navigate = $external ? false : $navigate;
@endphp

<a {{ $navigate ? 'wire:navigate' : '' }} href="{{ $href }}" role="link" {!! $ariaCurrent !!} {{ $attributes->twMerge(['text-primary hover:underline hover:text-primary/80', $active, $externalClasses]) }}>
    {{ $slot }}@if($external)<x-ui::icon name="external-link" class="ms-1 h-3 w-3"/>@endif
</a>
