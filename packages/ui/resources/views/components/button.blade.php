@props(['as' => 'button', 'variant' => null, 'size' => null, 'icon' => null, 'wire:navigate' => null])

@php
    $variant = match ($variant) {
        'info' => 'border border-sky-500/20 bg-sky-500 text-white shadow-sm hover:bg-sky-500/90 focus-visible:ring-sky-500/20 dark:border-sky-400/20 dark:bg-sky-500/80 dark:hover:bg-sky-500/70',
        'success' => 'border border-emerald-500/20 bg-emerald-500 text-white shadow-sm hover:bg-emerald-500/90 focus-visible:ring-emerald-500/20 dark:border-emerald-400/20 dark:bg-emerald-500/80 dark:hover:bg-emerald-500/70',
        'warning' => 'border border-amber-500/20 bg-amber-500 text-white shadow-sm hover:bg-amber-500/90 focus-visible:ring-amber-500/20 dark:border-amber-400/20 dark:bg-amber-500/80 dark:hover:bg-amber-500/70',
        'danger' => 'border border-rose-500/20 bg-rose-500 text-white shadow-sm hover:bg-rose-500/90 focus-visible:ring-rose-500/20 dark:border-rose-400/20 dark:bg-rose-500/80 dark:hover:bg-rose-500/70',
        'outline' => 'border border-zinc-200/70 bg-white/90 text-zinc-800 shadow-sm hover:bg-zinc-100 focus-visible:ring-primary/15 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-100 dark:hover:bg-white/[0.06]',
        'secondary' => 'border border-zinc-200/70 bg-zinc-100/90 text-zinc-800 shadow-sm hover:bg-zinc-200/80 focus-visible:ring-primary/15 dark:border-white/10 dark:bg-white/[0.05] dark:text-zinc-100 dark:hover:bg-white/[0.08]',
        'ghost' => 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-950 focus-visible:ring-primary/15 dark:text-zinc-300 dark:hover:bg-white/[0.06] dark:hover:text-white',
        'link' => 'text-primary underline-offset-4 hover:underline shadow-none',
        default => 'border border-primary/15 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90 focus-visible:ring-primary/20 dark:border-primary/20',
    };

    $size = match ($size) {
        'xs' => 'h-7 rounded-lg gap-1 px-2 text-xs has-[>svg]:px-2',
        'sm' => 'h-8 rounded-lg gap-1.5 px-3 has-[>svg]:px-2.5',
        'lg' => 'h-10 rounded-lg px-6 has-[>svg]:px-4',
        'icon' => 'size-9',
        default => 'h-9 rounded-lg px-4 py-2 has-[>svg]:px-3',
    };
@endphp

<{{ $as }} {{ $as === 'a' ? 'wire:navigate' : '' }} {{ $attributes->merge(['type' => 'submit']) }} {{ $attributes->twMerge(["cursor-pointer inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none ring-0 focus-visible:ring-2", $variant, $size]) }}>
    @if($icon)
        <x-ui::icon :name="$icon" />
    @endif
    {{ $slot }}
</{{ $as }}>
