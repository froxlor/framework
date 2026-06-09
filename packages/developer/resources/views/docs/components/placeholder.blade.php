<x-froxlor-developer::base-layout title="Placeholder - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Placeholder</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Placeholder -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::placeholder class="h-12" />
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::placeholder class="h-12" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
