<x-froxlor-developer::base-layout title="Heading - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Heading</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Heading -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::heading>
                    <div>
                        <x-ui::title>Hello World</x-ui::title>
                        <x-ui::subtitle>Hello World</x-ui::subtitle>
                    </div>
                    <x-slot:actions>
                        <x-ui::button>Action</x-ui::button>
                    </x-slot>
                </x-ui::heading>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::heading>
                        <div>
                            <x-ui::title>Hello World</x-ui::title>
                            <x-ui::subtitle>Hello World</x-ui::subtitle>
                        </div>
                        <x-slot:actions>
                            <x-ui::button>Action</x-ui::button>
                        </x-slot>
                    </x-ui::heading>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
