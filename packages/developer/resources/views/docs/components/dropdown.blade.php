<x-froxlor-developer::base-layout title="Dropdown - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Dropdown</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Dropdown: Basic -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::dropdown>
                    <x-slot:trigger>
                        <x-ui::button>Open menu</x-ui::button>
                    </x-slot:trigger>

                    <x-slot:content>
                        <x-ui::dropdown.link href="#">Profile</x-ui::dropdown.link>
                        <x-ui::dropdown.link href="#">Settings</x-ui::dropdown.link>
                        <x-ui::dropdown.divider />
                        <x-ui::dropdown.link href="#">Sign out</x-ui::dropdown.link>
                    </x-slot:content>
                </x-ui::dropdown>
            </x-slot:preview>

            <x-slot:code>
                @verbatim

                    <x-ui::dropdown>
                        <x-slot:trigger>
                            <x-ui::button>Open menu</x-ui::button>
                        </x-slot:trigger>

                        <x-slot:content>
                            <x-ui::dropdown.link href="#">Profile</x-ui::dropdown.link>
                            <x-ui::dropdown.link href="#">Settings</x-ui::dropdown.link>
                            <x-ui::dropdown.divider />
                            <x-ui::dropdown.link href="#">Sign out</x-ui::dropdown.link>
                        </x-slot:content>
                    </x-ui::dropdown>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Dropdown: Alignment -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Alignment</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="flex flex-wrap gap-6">
                    <x-ui::dropdown align="left">
                        <x-slot:trigger>
                            <x-ui::button variant="secondary">Left</x-ui::button>
                        </x-slot:trigger>
                        <x-slot:content>
                            <x-ui::dropdown.link href="#">Item A</x-ui::dropdown.link>
                            <x-ui::dropdown.link href="#">Item B</x-ui::dropdown.link>
                        </x-slot:content>
                    </x-ui::dropdown>

                    <x-ui::dropdown>
                        <x-slot:trigger>
                            <x-ui::button variant="secondary">Right (default)</x-ui::button>
                        </x-slot:trigger>
                        <x-slot:content>
                            <x-ui::dropdown.link href="#">Item A</x-ui::dropdown.link>
                            <x-ui::dropdown.link href="#">Item B</x-ui::dropdown.link>
                        </x-slot:content>
                    </x-ui::dropdown>

                    <x-ui::dropdown align="top">
                        <x-slot:trigger>
                            <x-ui::button variant="secondary">Top</x-ui::button>
                        </x-slot:trigger>
                        <x-slot:content>
                            <x-ui::dropdown.link href="#">Item A</x-ui::dropdown.link>
                            <x-ui::dropdown.link href="#">Item B</x-ui::dropdown.link>
                        </x-slot:content>
                    </x-ui::dropdown>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::dropdown align="left">…</x-ui::dropdown>
                    <x-ui::dropdown>…</x-ui::dropdown>
                    <x-ui::dropdown align="top">…</x-ui::dropdown>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Dropdown: Custom content styling -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Custom content styling</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::dropdown :content-classes="'p-0 bg-transparent'">
                    <x-slot:trigger>
                        <x-ui::button variant="secondary">Custom content</x-ui::button>
                    </x-slot:trigger>
                    <x-slot:content>
                        <div class="overflow-hidden rounded-md border border-zinc-800">
                            <x-ui::dropdown.link href="#">First</x-ui::dropdown.link>
                            <x-ui::dropdown.divider />
                            <x-ui::dropdown.link href="#">Second</x-ui::dropdown.link>
                        </div>
                    </x-slot:content>
                </x-ui::dropdown>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::dropdown :content-classes="'p-0 bg-transparent'">
                        <x-slot:trigger>
                            <x-ui::button variant="outline">Custom content</x-ui::button>
                        </x-slot:trigger>
                        <x-slot:content>
                            <div class="overflow-hidden rounded-md border border-zinc-800">
                                <x-ui::dropdown.link href="#">First</x-ui::dropdown.link>
                                <x-ui::dropdown.divider />
                                <x-ui::dropdown.link href="#">Second</x-ui::dropdown.link>
                            </div>
                        </x-slot:content>
                    </x-ui::dropdown>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
