<x-froxlor-developer::base-layout title="Collapsible - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Collapsible</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Collapsible -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::space.x>
                    <x-ui::collapsible x-data="{ open: false }">
                        <x-ui::collapsible.trigger>
                            Toggle Content
                        </x-ui::collapsible.trigger>
                        <x-ui::collapsible.content>
                            Hello World!
                        </x-ui::collapsible.content>
                    </x-ui::collapsible>
                </x-ui::space.x>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::collapsible x-data="{ open: false }">
                        <x-ui::collapsible.trigger>
                            Toggle Content
                        </x-ui::collapsible.trigger>
                        <x-ui::collapsible.content>
                            Hello World!
                        </x-ui::collapsible.content>
                    </x-ui::collapsible>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
