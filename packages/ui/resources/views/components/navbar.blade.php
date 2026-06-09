<div {{ $attributes->twMerge('min-h-16 z-30 flex items-center justify-between px-8 border-b border-zinc-200/70 bg-card/90 text-zinc-900 shadow-sm dark:border-white/10 dark:bg-white/[0.02] dark:text-zinc-200') }}>
    {{ $slot }}

    @isset($center)
        <div class="flex space-x-4">
            {{ $center }}
        </div>
    @endisset

    @isset($left)
        <div class="flex space-x-4">
            {{ $left }}
        </div>
    @endisset
</div>
