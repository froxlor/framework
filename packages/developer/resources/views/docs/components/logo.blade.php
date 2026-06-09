<x-froxlor-developer::base-layout title="Logo - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Logo</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Logo -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::logo class="h-20 w-auto"/>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::logo class="h-20 w-auto"/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
