<x-froxlor-developer::base-layout title="Guest Layout - Layouts - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Layouts</x-ui::teaser>
            <x-ui::title>Guest Layout</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Guest Layout -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::guest-layout body-sub-classes="relative aspect-video min-h-full overflow-hidden rounded-lg">
                    <x-ui::placeholder/>
                </x-ui::guest-layout>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::guest-layout>
                        <!-- ... -->
                    </x-ui::guest-layout>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
