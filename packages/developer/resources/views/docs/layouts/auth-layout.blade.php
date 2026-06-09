<x-froxlor-developer::base-layout title="Auth Layout - Layouts - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Layouts</x-ui::teaser>
            <x-ui::title>Auth Layout</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Auth Layout -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::auth-layout body-sub-classes="relative aspect-video min-h-full overflow-hidden rounded-lg">
                    <x-ui::main class="relative h-full w-full">
                        <x-ui::placeholder/>
                    </x-ui::main>
                </x-ui::auth-layout>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::auth-layout>
                        <x-ui::main>
                            <!-- ... -->
                        </x-ui::main>
                    </x-ui::auth-layout>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
