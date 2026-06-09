@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-zinc-900'])

@php
    $alignmentClasses = match ($align) {
        'left' => 'origin-top-left left-0',
        'top' => 'origin-top',
        'right' => 'origin-top-right right-0',
    };

    $width = match ($width) {
        '48' => 'w-48',
    };
@endphp

<li {{ $attributes->twMerge('relative') }} x-data="{ open: false }" @mouseleave="open = false">
    <div @mouseenter = "open = true" class="flex items-center cursor-pointer space-x-2">
        {{ $slot }}
    </div>

    @isset($content)
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute z-50 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
             style="display: none;">
            <div class="rounded-md ring-1 ring-black ring-opacity-5 px-4 flex flex-col {{ $contentClasses }}">
                {{ $content }}
            </div>
        </div>
    @endisset
</li>
