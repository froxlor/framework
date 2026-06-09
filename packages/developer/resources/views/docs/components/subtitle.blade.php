<x-froxlor-developer::base-layout title="Subtitle - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Subtitle</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Subtitle -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::subtitle>Hello World</x-ui::subtitle>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::subtitle>Hello World</x-ui::subtitle>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
