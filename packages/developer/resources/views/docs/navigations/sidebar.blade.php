<x-froxlor-developer::base-layout title="Sidebar - Navigations - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Navigations</x-ui::teaser>
            <x-ui::title>Sidebar</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Sidebar -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::sidebar-layout body-sub-classes="relative aspect-video min-h-full overflow-hidden rounded-lg">
                    <x-ui::main class="relative h-full w-full">
                        <x-ui::placeholder/>
                    </x-ui::main>
                </x-ui::sidebar-layout>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <livewire:ui::navigations.sidebar navigation="sidebar"/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
