@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-zinc-900', 'closeOnContentClick' => true])

@php
    // Determine horizontal alignment and transform origin
    $alignmentClasses = match ($align) {
        'left' => 'left-0 origin-top-left',
        'top' => 'right-0 origin-bottom-right', // when placed above, anchor to the right by default
        default => 'right-0 origin-top-right',
    };

    // Determine vertical placement relative to trigger
    $positionClasses = $align === 'top'
        ? 'bottom-full mb-2'
        : 'top-full mt-2';

    $width = match ($width) {
        '48' => 'w-48',
        default => $width,
    };
@endphp

<div class="relative inline-flex" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false" @keydown.escape.window="open = false">
    <div @click="open = ! open" class="flex items-center cursor-pointer space-x-2 select-none">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-50 {{ $positionClasses }} {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
            style="display: none;"
            @if($closeOnContentClick)
                @click="open = false"
            @else
                @click.stop
            @endif
    >
        <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
