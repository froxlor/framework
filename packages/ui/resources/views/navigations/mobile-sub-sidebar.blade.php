@inject('ui', 'Froxlor\UI\Support\UI')

@php
    $items = array_values(array_filter($ui::stack('sub-sidebar'), fn ($item) => !empty($item->visible)));
@endphp

@if(!empty($items))
    <div class="border-b border-zinc-200/70 bg-card/90 px-4 py-3 shadow-sm lg:hidden dark:border-white/10 dark:bg-white/[0.02]">
        <div class="-mx-4 overflow-x-auto px-4">
            <nav class="flex min-w-max items-center gap-2">
                @foreach($items as $item)
                    <a
                        wire:navigate
                        href="{{ $item->href }}"
                        @class([
                            'inline-flex min-h-10 items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors duration-150',
                            'border-primary bg-primary text-white' => $item->active,
                            'border-zinc-200/70 bg-white/90 text-zinc-700 hover:bg-zinc-100 dark:border-white/10 dark:bg-white/[0.03] dark:text-zinc-200 dark:hover:bg-white/[0.06]' => !$item->active,
                        ])
                    >
                        @if(!empty($item->icon?->name))
                            <x-ui::icon :name="$item->icon->name" :variant="$item->icon->variant ?? null" class="size-4"/>
                        @endif
                        <span>{{ $item->label }}</span>
                        @if(!empty($item->badge?->label))
                            <x-ui::badge size="sm" :variant="$item->badge->variant ?? null">{{ $item->badge->label }}</x-ui::badge>
                        @endif
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
@endif
