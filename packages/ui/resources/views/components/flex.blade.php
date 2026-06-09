@props(['grow' => false])

<div {{ $attributes->twMerge(['flex', $grow ? 'min-h-0 flex-1' : '']) }}>
    {{ $slot }}
</div>
