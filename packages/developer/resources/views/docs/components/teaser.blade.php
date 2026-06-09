<x-froxlor-developer::base-layout title="Teaser - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Teaser</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Teaser -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::teaser>Hello World</x-ui::teaser>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::teaser>Hello World</x-ui::teaser>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
