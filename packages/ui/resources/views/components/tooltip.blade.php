@props([
    'text' => null,
    'side' => 'top', // top, right, bottom, left
    'align' => 'center', // start, center, end
    'showDelay' => 150,
    'hideDelay' => 100,
])

@php
    $position = match ($side) {
        'bottom' => 'top-full mt-2',
        'left' => 'right-full mr-2',
        'right' => 'left-full ml-2',
        default => 'bottom-full mb-2',
    };

    $alignment = match ([$side, $align]) {
        ['top', 'start'], ['bottom', 'start'] => 'left-0',
        ['top', 'end'], ['bottom', 'end'] => 'right-0',
        ['left', 'start'], ['right', 'start'] => 'top-0',
        ['left', 'end'], ['right', 'end'] => 'bottom-0',
        default => in_array($side, ['left', 'right'])
            ? 'top-1/2 -translate-y-1/2'
            : 'left-1/2 -translate-x-1/2',
    };

    // Tooltip-Content: Slot oder Text
    $tooltipContent = trim((string) ($content ?? $text ?? ''));
@endphp

<span
    {{ $attributes->twMerge('relative inline-flex') }}
    x-data="{
        open: false,
        showTimeout: null,
        hideTimeout: null,
        show() {
            clearTimeout(this.hideTimeout);
            this.showTimeout = setTimeout(() => this.open = true, {{ (int) $showDelay }});
        },
        hide() {
            clearTimeout(this.showTimeout);
            this.hideTimeout = setTimeout(() => this.open = false, {{ (int) $hideDelay }});
        }
    }"
    @mouseenter="show()"
    @mouseleave="hide()"
    @focusin="show()"
    @focusout="hide()"
>
    <span class="inline-flex" :aria-describedby="$id('tooltip')">
        {{ $slot }}
    </span>

    @if ($tooltipContent)
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
            class="absolute z-50 {{ $position }} {{ $alignment }} pointer-events-none"
            role="tooltip"
            :id="$id('tooltip')"
        >
            <div class="rounded-md border border-zinc-700 bg-zinc-900 text-white text-xs px-3 py-2 shadow-xl backdrop-blur-sm max-w-sm whitespace-normal">
                {!! $tooltipContent !!}
            </div>
        </div>
    @endif
</span>
