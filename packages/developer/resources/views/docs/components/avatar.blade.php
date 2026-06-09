<x-froxlor-developer::base-layout title="Avatar - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Avatar</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Avatar -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::avatar x-data="{ src: 'https://placehold.co/64x64', fallback: 'MM' }">
                    <x-ui::avatar.image x-bind:src="src"/>
                    <x-ui::avatar.fallback x-text="fallback"/>
                </x-ui::avatar>
                <x-ui::space.y class="py-2"/>
                <x-ui::avatar variant="square" x-data="{ src: 'https://placehold.co/64x64', fallback: 'TN' }">
                    <x-ui::avatar.image x-bind:src="src"/>
                    <x-ui::avatar.fallback variant="square" x-text="fallback"/>
                </x-ui::avatar>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::avatar x-data="{ src: 'https://placehold.co/64x64', fallback: 'MM' }">
                        <x-ui::avatar.image x-bind:src="src"/>
                        <x-ui::avatar.fallback x-text="fallback"/>
                    </x-ui::avatar>
                    
                    <x-ui::avatar variant="square" x-data="{ src: 'https://placehold.co/64x64', fallback: 'TN' }">
                        <x-ui::avatar.image x-bind:src="src"/>
                        <x-ui::avatar.fallback variant="square" x-text="fallback"/>
                    </x-ui::avatar>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
