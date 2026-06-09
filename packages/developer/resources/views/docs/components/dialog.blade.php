<x-froxlor-developer::base-layout title="Dialog - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Dialog</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Dialog -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div>
                    <x-ui::button x-on:click="$dispatch('open-dialog', 'settings')">
                        Open
                    </x-ui::button>

                    <x-ui::dialog name="settings">
                        <x-ui::card>
                            <x-ui::card.header>
                                <x-ui::card.title>Title</x-ui::card.title>
                                <x-ui::card.description>Description</x-ui::card.description>
                            </x-ui::card.header>
                            <x-ui::card.content>
                                <x-ui::text>
                                    Consectetur adipiscing elit, sed do eiusmod tempor incididunt
                                    ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis
                                    nostrud exercitation ullamco laboris nisi ut aliquip ex ea
                                    commodo consequat.
                                </x-ui::text>
                            </x-ui::card.content>
                            <x-ui::card.footer>
                                <x-ui::button x-on:click="$dispatch('close', 'settings')">
                                    Close
                                </x-ui::button>
                            </x-ui::card.footer>
                        </x-ui::card>
                    </x-ui::dialog>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::button x-on:click="$dispatch('open-dialog', 'settings')">
                        Open
                    </x-ui::button>

                    <x-ui::dialog name="settings">
                        <x-ui::card>
                            <x-ui::card.header>
                                <x-ui::card.title>Title</x-ui::card.title>
                                <x-ui::card.description>Description</x-ui::card.description>
                            </x-ui::card.header>
                            <x-ui::card.content>
                                <x-ui::text>
                                    Consectetur adipiscing elit, sed do eiusmod tempor incididunt
                                    ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis
                                    nostrud exercitation ullamco laboris nisi ut aliquip ex ea
                                    commodo consequat.
                                </x-ui::text>
                            </x-ui::card.content>
                            <x-ui::card.footer>
                                <x-ui::button x-on:click="$dispatch('close', 'settings')">
                                    Close
                                </x-ui::button>
                            </x-ui::card.footer>
                        </x-ui::card>
                    </x-ui::dialog>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
