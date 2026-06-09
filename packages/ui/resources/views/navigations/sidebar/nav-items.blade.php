<x-ui::sidebar.nav>
    @foreach($items as $item)
        @continue(!$item->visible)
        <x-ui::sidebar.link
            :href="$item->href"
            :navigate="!count($item->children)"
            :active="$item->active"
            :icon="$item->icon->name ?? null"
            :icon-variant="$item->icon->variant ?? null"
            :badge="$item->badge->label ?? null"
            :badge-variant="$item->badge->variant ?? null"
        >
            {{ $item->label }}

            @if(count($item->children))
                <x-slot:children>
                    @foreach($item->children as $child)
                        @continue(!$child->visible)
                        <x-ui::sidebar.child
                            :href="$child->href"
                            :active="$child->active"
                            :badge="$child->badge->label ?? null"
                            :badge-variant="$child->badge->variant ?? null"
                        >
                            {{ $child->label }}
                        </x-ui::sidebar.child>
                    @endforeach
                </x-slot:children>
            @endif
        </x-ui::sidebar.link>
    @endforeach
</x-ui::sidebar.nav>
