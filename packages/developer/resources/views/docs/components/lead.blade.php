<x-froxlor-developer::base-layout title="Lead - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Lead</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Lead Text -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::lead>Well, let me tell you something, ...</x-ui::lead>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::lead>Well, let me tell you something, ...</x-ui::lead>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
