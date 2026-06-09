<x-froxlor-developer::base-layout title="Title - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Title</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Title -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::title size="xl">Hello World</x-ui::title>
                <x-ui::title size="2xl">Hello World</x-ui::title>
                <x-ui::title size="3xl">Hello World</x-ui::title>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::title size="xl">Hello World</x-ui::title>
                    <x-ui::title size="2xl">Hello World</x-ui::title>
                    <x-ui::title size="3xl">Hello World</x-ui::title>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
