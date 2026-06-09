<x-froxlor-developer::base-layout title="Accordion - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Accordion</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Accordion -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::space.x>
                    <x-ui::accordion x-data="{ open: 'item-1' }">
                        <x-ui::accordion.item name="item-1" :collapsible="true">
                            <x-ui::accordion.trigger>
                                Section 1 - Title
                            </x-ui::accordion.trigger>
                            <x-ui::accordion.content>
                                Hello World!
                            </x-ui::accordion.content>
                        </x-ui::accordion.item>
                        <x-ui::accordion.item name="item-2">
                            <x-ui::accordion.trigger>
                                Section 2 - Title
                            </x-ui::accordion.trigger>
                            <x-ui::accordion.content>
                                Hello World!
                            </x-ui::accordion.content>
                        </x-ui::accordion.item>
                        <x-ui::accordion.item name="item-3">
                            <x-ui::accordion.trigger>
                                Section 3 - Title
                            </x-ui::accordion.trigger>
                            <x-ui::accordion.content>
                                Hello World!
                            </x-ui::accordion.content>
                        </x-ui::accordion.item>
                    </x-ui::accordion>
                </x-ui::space.x>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::accordion x-data="{ open: 'item-1' }">
                        <x-ui::accordion.item name="item-1" :collapsible="true">
                            <x-ui::accordion.trigger>
                                Section 1 - Title
                            </x-ui::accordion.trigger>
                            <x-ui::accordion.content>
                                Hello World!
                            </x-ui::accordion.content>
                        </x-ui::accordion.item>
                        <x-ui::accordion.item name="item-2">
                            <x-ui::accordion.trigger>
                                Section 2 - Title
                            </x-ui::accordion.trigger>
                            <x-ui::accordion.content>
                                Hello World!
                            </x-ui::accordion.content>
                        </x-ui::accordion.item>
                        <x-ui::accordion.item name="item-3">
                            <x-ui::accordion.trigger>
                                Section 3 - Title
                            </x-ui::accordion.trigger>
                            <x-ui::accordion.content>
                                Hello World!
                            </x-ui::accordion.content>
                        </x-ui::accordion.item>
                    </x-ui::accordion>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
