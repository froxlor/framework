<x-froxlor-developer::base-layout title="Navbar - Navigations - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Navigations</x-ui::teaser>
            <x-ui::title>Navbar</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Navbar -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::stacked-layout body-sub-classes="relative aspect-video min-h-full overflow-hidden rounded-lg">
                    <x-ui::main class="relative h-full w-full">
                        <x-ui::placeholder/>
                    </x-ui::main>
                </x-ui::stacked-layout>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <livewire:ui::navigations.navbar navigation="primary" user-navigation="user"/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
