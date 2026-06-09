<x-froxlor-developer::base-layout title="Main - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Main</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Main -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::placeholder :dashed="false">
                    <x-ui::main>
                        <x-ui::placeholder/>
                    </x-ui::main>
                </x-ui::placeholder>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::main>
                        <!-- ... -->
                    </x-ui::main>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
