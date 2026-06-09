@props(['disabled' => false, 'required' => false, 'value' => ''])

<textarea
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->twMerge('w-full rounded-lg border border-zinc-200/70 bg-white/90 px-3 py-2 text-zinc-900 shadow-sm outline-none transition-colors placeholder:text-zinc-400 focus:border-primary/50 focus:ring-2 focus:ring-primary/15 disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-primary/60 dark:focus:ring-primary/20') }}
>{{ $value ?: $slot }}</textarea>
