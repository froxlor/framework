{{-- Status: Dev,warning --}}
<x-froxlor-developer::base-layout title="Section - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Section</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Teaser -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::section>Hello World</x-ui::section>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::section>Hello World</x-ui::section>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
