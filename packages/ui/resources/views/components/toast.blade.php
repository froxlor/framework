@props([
    'show' => false,
    'autoHide' => true,
    'timeout' => 3000,
    'variant' => null, // info, success, warning, danger, primary, secondary
    'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
    'title' => null,
    'description' => null,
])

@php
    $positionClass = match ($position) {
        'top-left' => 'top-4 left-4',
        'top-right' => 'top-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
        default => 'bottom-4 right-4', // bottom-right
    };

    $variantClass = match ($variant) {
        'info' => 'border-blue-400/40 text-blue-200',
        'success' => 'border-green-400/40 text-green-200',
        'warning' => 'border-yellow-400/40 text-yellow-200',
        'danger' => 'border-red-400/40 text-red-200',
        'primary' => 'border-indigo-400/40 text-indigo-200',
        'secondary' => 'border-zinc-600 text-zinc-200',
        default => 'border-zinc-700 text-zinc-100',
    };
@endphp

<div class="pointer-events-none fixed z-50 {{ $positionClass }}">
    <div
        x-data="{ open: @js($show), autoHide: @js($autoHide), timeout: @js($timeout), hideTimer: null, show() { this.open = true; if (this.autoHide) { clearTimeout(this.hideTimer); this.hideTimer = setTimeout(() => this.open = false, this.timeout); } }, close() { this.open = false; clearTimeout(this.hideTimer); } }"
        x-on:show-toast.window="show()"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="pointer-events-auto"
        style="display: none;"
    >
        <div class="min-w-64 max-w-xs rounded-md border bg-zinc-900/95 backdrop-blur text-sm shadow-xl {{ $variantClass }}">
            <div class="flex items-start gap-3 p-3">
                <div class="flex-1">
                    @if($title)
                        <div class="font-medium leading-5">{{ $title }}</div>
                    @endif
                    @if($description)
                        <div class="mt-0.5 text-xs/5 text-zinc-300">{{ $description }}</div>
                    @endif
                    {{ $slot }}
                </div>
                <button type="button" class="shrink-0 rounded p-1 text-zinc-400 hover:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-zinc-700" @click="close()" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

