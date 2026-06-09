@props([
    'as' => 'body',
    'subClasses' => null,
])

<{{ $as }} {{ $attributes->twMerge(['font-sans antialiased']) }}>
    <div class="{{ twMerge(['flex h-dvh flex-col overflow-hidden bg-zinc-100 dark:bg-zinc-900 dark:text-zinc-200', $subClasses]) }}">
        {{ $slot }}
    </div>
</{{ $as }}>
