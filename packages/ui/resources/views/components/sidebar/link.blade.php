@props(['active' => false, 'href', 'icon' => null, 'iconVariant' => null, 'badge' => null, 'badgeVariant' => null, 'navigate' => true])

@php
    $key = Str::slug($slot);
    $hasChildren = isset($children);
    $iconSlot = isset($icon) && $icon instanceof \Illuminate\View\ComponentSlot ? $icon : null;
    $iconName = $iconSlot ? null : $icon;
@endphp

<li
    class="relative mb-2 group"
    x-data="{
        open: false,
        keepOpen: {{ $active ? 'true' : 'false' }},
        init() {
            this.open = this.keepOpen && (!collapsed || !desktop);
        }
    }"
    @sidebar-child-clicked="if (collapsed && desktop) { open = false; keepOpen = false }"
>
    @php
        $linkClasses = trim(implode(' ', [
            'flex rounded-lg border py-2 text-sm xl:text-base transition-colors duration-150',
            $active
                ? 'border-zinc-200/70 bg-white/90 text-zinc-950 shadow-sm dark:border-white/10 dark:bg-white/[0.06] dark:text-white'
                : 'border-transparent text-zinc-600 hover:bg-white/70 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/[0.04] dark:hover:text-zinc-100',
        ]));
    @endphp
    <a {{ $navigate ? 'wire:navigate' : '' }}
       {{ $attributes->merge(['class' => $linkClasses]) }}
       :class="collapsed && desktop ? 'justify-center px-2' : 'justify-between px-4'"
       href="{{ !isset($children) ? $href : '#' }}"
       @if($hasChildren)
           @keydown.enter.prevent="open = !open"
           @click.prevent="open = !open"
           :aria-expanded="open"
           aria-controls="dropdown-{{ $key }}"
           @keydown.escape="open = false"
        @endif
    >
        <div class="flex items-center" :class="collapsed && desktop ? '' : 'space-x-3'">
            @if($iconSlot && !$iconSlot->isEmpty())
                {{ $iconSlot }}
            @elseif(!empty($iconName))
                <x-ui::icon size="1.5" :name="$iconName" :variant="$iconVariant"/>
            @endif
            <span x-show="!collapsed || !desktop" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="whitespace-nowrap">
                {{ $slot }}
            </span>
        </div>
        <div class="flex" x-show="!collapsed || !desktop">
            @if($badge)
                <span>
                    <x-ui::badge size="sm" :variant="$badgeVariant">{{ $badge }}</x-ui::badge>
                </span>
            @endif
            @isset($children)
                <button type="button" class="ml-auto px-2 -me-2 focus:outline-none">
                    <x-ui::icon name="chevron-down"/>
                </button>
            @endif
        </div>
    </a>
    @isset($children)
        <ul
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            :class="collapsed && desktop
                ? 'absolute top-0 left-full z-50 ml-2 w-56 rounded-xl border border-zinc-200/70 bg-card/95 p-2 shadow-xl backdrop-blur-sm dark:border-white/10 dark:bg-white/[0.04]'
                : 'mt-1 space-y-1 rounded-xl border border-zinc-200/70 bg-card/80 p-2 backdrop-blur-sm dark:border-white/10 dark:bg-white/[0.03]'"
            data-sidebar-flyout
            id="dropdown-{{ $key }}"
            @click.away="if (collapsed && desktop) { open = false; keepOpen = false } else if (keepOpen === false) { open = false }"
        >
            {{ $children }}
        </ul>
    @endif
</li>
