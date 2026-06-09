<x-froxlor-developer::base-layout title="Progress - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Progress</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Progress -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::progress value="70" max="100"/>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::progress value="70" max="100"/> <!-- max is optional here, fallback is 100 -->
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Progress Variants -->
    <x-ui::title size="2xl">Variants</x-ui::title>
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::progress value="70" max="100" height="sm"/>
                <x-ui::progress value="70" max="100"/>
                <x-ui::progress value="70" max="100" height="lg"/>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::progress value="70" max="100" height="sm"/>
                    <x-ui::progress value="70" max="100"/>
                    <x-ui::progress value="70" max="100" height="lg"/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
