@props(['active', 'icon' => null, 'iconVariant' => null, 'navigate' => true])

@php
    $classes = ($active ?? false)
                ? 'inline-flex items-center rounded-t-lg border border-zinc-200/70 border-b-white bg-white/90 px-3 py-3 font-medium leading-none text-zinc-950 shadow-sm transition-colors duration-150 ease-in-out focus:outline-none dark:border-white/10 dark:border-b-[rgba(24,24,27,1)] dark:bg-white/[0.03] dark:text-white'
                : 'inline-flex items-center rounded-t-lg border border-transparent px-3 py-3 font-medium leading-none text-zinc-500 transition-colors duration-150 ease-in-out hover:text-zinc-800 focus:outline-none dark:text-zinc-400 dark:hover:text-zinc-200';
@endphp

<a {{ $navigate ? 'wire:navigate' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <x-ui::icon :name="$icon" :variant="$iconVariant" class="me-2 h-4 w-4"/>
    @endif
    {{ $slot }}
</a>
