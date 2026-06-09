<x-froxlor-developer::base-layout title="Middle - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Middle</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Middle -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::placeholder :dashed="false">
                    <x-ui::middle>
                        <x-ui::placeholder/>
                    </x-ui::middle>
                </x-ui::placeholder>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::middle>
                        <!-- ... -->
                    </x-ui::middle>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
