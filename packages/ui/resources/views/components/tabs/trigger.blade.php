@props(['name'])

<div {{ $attributes->merge([
    'x-on:click' => "open = '{$name}'",
    'x-on:keydown.enter' => "open = '{$name}'",
    'role' => 'button',
    'tabindex' => '0'
])->twMerge('cursor-pointer rounded-t-lg border border-transparent px-4 py-3 text-sm font-medium transition-colors outline-none focus-visible:ring-2 focus-visible:ring-primary/15') }} x-bind:class="open === '{{ $name }}'
    ? 'border-zinc-200/70 border-b-white bg-white/90 text-zinc-950 shadow-sm dark:border-white/10 dark:border-b-[rgba(24,24,27,1)] dark:bg-white/[0.03] dark:text-white'
    : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200'">
    {{ $slot }}
</div>
