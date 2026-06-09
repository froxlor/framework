<x-ui::sidebar.group>
    <x-ui::sidebar.group-content>
        @if(!empty($items))
            @include('ui::navigations.sidebar.nav-items', ['items' => $items])
        @endif

        @if($collapsible)
            <x-ui::sidebar.nav x-show="desktop">
                <x-ui::sidebar.link href="#" @click.prevent="if (desktop) collapsed = !collapsed" :navigate="false">
                    <x-slot:icon>
                        <span x-show="!collapsed || !desktop"><x-ui::icon name="arrow-left-to-line" size="1.5"/></span>
                        <span x-show="collapsed && desktop"><x-ui::icon name="arrow-right-to-line" size="1.5"/></span>
                    </x-slot:icon>
                    <span x-show="desktop && !collapsed">Collapse</span>
                </x-ui::sidebar.link>
            </x-ui::sidebar.nav>
        @endif
    </x-ui::sidebar.group-content>
</x-ui::sidebar.group>
