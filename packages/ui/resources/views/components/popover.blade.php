@props([
    'side' => 'top',
    'align' => 'center',
    'width' => 'md',
])

@php
    $position = [
        'top' => 'bottom-full mb-2',
        'left' => 'right-full mr-2',
        'right' => 'left-full ml-2',
        'bottom' => 'top-full mt-2',
    ][$side] ?? 'top-full mt-2';

    $alignment = match ([$side, $align]) {
        ['top', 'start'], ['bottom', 'start'] => 'left-0',
        ['top', 'end'], ['bottom', 'end'] => 'right-0',
        ['left', 'start'], ['right', 'start'] => 'top-0',
        ['left', 'end'], ['right', 'end'] => 'bottom-0',
        default => in_array($side, ['left', 'right'])
            ? 'top-1/2 -translate-y-1/2'
            : 'left-1/2 -translate-x-1/2',
    };

    $widthClass = [
        'sm' => 'w-56',
        'md' => 'w-72',
        'lg' => 'w-96',
        'auto' => 'w-auto max-w-xs',
    ][$width] ?? 'w-72';
@endphp

<div {{ $attributes->twMerge('relative inline-flex') }}
     x-data="{ open: false }"
     @keydown.escape.window="open = false"
     @click.outside="open = false"
>
    {{-- Trigger --}}
    <div class="inline-flex cursor-pointer select-none" @click.stop="open = !open">
        {{ $trigger ?? $slot }}
    </div>

    {{-- Popover Panel --}}
    <div x-show="open"
         x-cloak
         x-transition.opacity.scale.100
         class="absolute z-50 {{ $position }} {{ $alignment }} {{ $widthClass }}"
         @click.stop
    >
        <div class="rounded-md border border-zinc-800 bg-zinc-900 text-white shadow-xl p-3">
            {{ $content ?? '' }}
        </div>
    </div>
</div>
