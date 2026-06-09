<x-froxlor-developer::base-layout title="App Layout - Layouts - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Layouts</x-ui::teaser>
            <x-ui::title>App Layout</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- App Layout -->
    <x-ui::space.y>
        <x-ui::alert variant="warning">
            <x-ui::icon name="code"/>
            <x-ui::alert.title>Info</x-ui::alert.title>
            <x-ui::alert.description>By default you can use the <x-ui::code>ui::auth-layout</x-ui::code> layout to use the full layout with navbar and sidebar. If you want to use the layout without navbar and sidebar, you can use the <x-ui::code>ui::app-layout</x-ui::code> layout and place your main content inside the slot section.</x-ui::alert.description>
        </x-ui::alert>

        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::app-layout body-sub-classes="relative aspect-video min-h-full overflow-hidden rounded-lg">
                    <x-ui::main class="relative h-full w-full">
                        <x-ui::placeholder/>
                    </x-ui::main>
                </x-ui::app-layout>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::app-layout>
                        <x-ui::main>
                            <!-- ... -->
                        </x-ui::main>
                    </x-ui::app-layout>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
