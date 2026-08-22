@props([
    'autoHide' => true,
    'timeout' => 5000,
    'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
])

@php
    $positionClass = match ($position) {
        'top-left' => 'top-4 left-4',
        'top-right' => 'top-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
        default => 'bottom-4 right-4', // bottom-right
    };
@endphp

<div class="pointer-events-none fixed z-50 {{ $positionClass }}">
    <div
        x-data="{
            open: false,
            autoHide: @js($autoHide),
            timeout: @js($timeout),
            hideTimer: null,
            title: null,
            description: null,
            variant: 'success',
            variantClass() {
                return ({
                    info: 'border-sky-400/40 text-sky-700 dark:text-sky-200',
                    success: 'border-emerald-400/40 text-emerald-700 dark:text-emerald-200',
                    warning: 'border-amber-400/40 text-amber-700 dark:text-amber-200',
                    danger: 'border-rose-400/40 text-rose-700 dark:text-rose-200',
                    primary: 'border-primary/40 text-primary',
                    secondary: 'border-zinc-300 text-zinc-700 dark:border-zinc-600 dark:text-zinc-200',
                })[this.variant] ?? 'border-zinc-200 text-zinc-900 dark:border-zinc-700 dark:text-zinc-100';
            },
            show(detail) {
                this.title = detail?.title ?? null;
                this.description = detail?.description ?? null;
                this.variant = detail?.variant ?? 'success';
                this.open = true;
                if (this.autoHide) {
                    clearTimeout(this.hideTimer);
                    this.hideTimer = setTimeout(() => this.open = false, this.timeout);
                }
            },
            close() {
                this.open = false;
                clearTimeout(this.hideTimer);
            },
        }"
        x-on:show-toast.window="show($event.detail)"
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
        <div class="min-w-64 max-w-xs rounded-md border bg-white/95 dark:bg-zinc-900/95 backdrop-blur text-sm shadow-xl" :class="variantClass()">
            <div class="flex items-start gap-3 p-3">
                <div class="flex-1">
                    <div class="font-medium leading-5" x-show="title" x-text="title"></div>
                    <div class="mt-0.5 text-xs/5 text-zinc-500 dark:text-zinc-300" x-show="description" x-text="description"></div>
                </div>
                <button type="button" class="shrink-0 rounded p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 focus:outline-none focus:ring-1 focus:ring-zinc-300 dark:focus:ring-zinc-700" @click="close()" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
