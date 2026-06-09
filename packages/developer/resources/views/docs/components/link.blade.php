<x-froxlor-developer::base-layout title="Link - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Link</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Link -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::link href="https://www.froxlor.org/" target="_blank">froxlor.org</x-ui::link>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::link href="https://www.froxlor.org" target="_blank">froxlor.org</x-ui::link>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
