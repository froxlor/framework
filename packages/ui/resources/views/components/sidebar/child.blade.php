@props(['active' => false, 'href', 'badge' => null, 'badgeVariant' => null, 'navigate' => true])

<a {{ $navigate ? 'wire:navigate' : '' }}
   @class([
       'flex items-center justify-between rounded-lg border px-4 py-2 text-sm transition-colors duration-150',
       'border-zinc-200/70 bg-white/90 text-zinc-950 shadow-sm dark:border-white/10 dark:bg-white/[0.06] dark:text-white' => $active,
       'border-transparent text-zinc-600 hover:bg-white/70 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/[0.04] dark:hover:text-zinc-100' => !$active,
   ])
   @click="$dispatch('sidebar-child-clicked')"
   href="{{ $href ?: '#' }}">
    <span
        x-show="!collapsed || !desktop || !!$el.closest('[data-sidebar-flyout]')"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        {{ $slot }}
    </span>
    @if($badge)
        <x-ui::badge size="sm" :variant="$badgeVariant">{{ $badge }}</x-ui::badge>
    @endif
</a>
