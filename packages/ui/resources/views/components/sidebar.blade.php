@props(['name' => 'sidebar'])

<div
    x-data="{
        mobileOpen: false,
        desktop: window.matchMedia('(min-width: 1024px)').matches,
        syncViewport() {
            const wasDesktop = this.desktop;
            this.desktop = window.matchMedia('(min-width: 1024px)').matches;

            // Reset the off-canvas state only when crossing from desktop into mobile.
            if (wasDesktop && !this.desktop) {
                this.mobileOpen = false;
            }
        }
    }"
    x-init="syncViewport(); window.addEventListener('resize', () => syncViewport())"
    x-on:open-sidebar.window="if ($event.detail === '{{ $name }}' && !desktop) mobileOpen = true"
    x-on:close-sidebar.window="if ($event.detail === '{{ $name }}') mobileOpen = false"
    x-on:toggle-sidebar.window="if ($event.detail === '{{ $name }}' && !desktop) mobileOpen = !mobileOpen"
    class="flex"
>
    <!-- Backdrop -->
    <div
        x-show="mobileOpen && !desktop"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-75"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-75"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black dark:bg-zinc-900 opacity-75 z-40"
        @click="mobileOpen = false"
    ></div>

    <!-- Sidebar -->
    <div
        x-show="desktop || mobileOpen"
        x-transition:enter="transition-transform duration-300 ease-out"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform duration-300 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        @keydown.escape.window="mobileOpen = false"
        {{ $attributes->twMerge('w-64 flex flex-col p-4 border-r border-zinc-200/70 bg-card text-zinc-900 dark:border-white/10 dark:bg-zinc-950 dark:text-zinc-200 lg:bg-card/90 lg:dark:bg-white/[0.02]',
            'overflow-hidden overflow-x-visible z-40 lg:translate-x-0 shadow-lg lg:shadow-none fixed lg:static top-0 left-0 bottom-0 h-dvh lg:h-full') }}
    >
        {{ $slot }}
    </div>
</div>
