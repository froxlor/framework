<x-froxlor-developer::base-layout title="Progress Group - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Progress Group</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Progress -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::progress-group>
                    <x-ui::progress.item value="10" variant="primary"/>
                    <x-ui::progress.item value="4" variant="warning"/>
                    <x-ui::progress.item value="15" variant="danger"/>
                </x-ui::progress-group>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::progress-group>
                        <x-ui::progress.item value="10" variant="primary"/>
                        <x-ui::progress.item value="4" variant="warning"/>
                        <x-ui::progress.item value="15" variant="danger"/>
                    </x-ui::progress-group>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
