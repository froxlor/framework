<x-froxlor-developer::base-layout title="Button Group - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Button Group</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Button Group -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="flex flex-wrap gap-2">
                    <x-ui::button-group>
                        <x-ui::button>123</x-ui::button>
                    </x-ui::button-group>

                    <x-ui::button-group>
                        <x-ui::button>Button</x-ui::button>
                        <x-ui::button>Button</x-ui::button>
                    </x-ui::button-group>

                    <x-ui::button-group>
                        <x-ui::button variant="success">Button</x-ui::button>
                        <x-ui::button variant="danger">Button</x-ui::button>
                    </x-ui::button-group>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::button-group>
                        <x-ui::button>Button</x-ui::button>
                    </x-ui::button-group>

                    <x-ui::button-group>
                        <x-ui::button>Button</x-ui::button>
                        <x-ui::button>Button</x-ui::button>
                    </x-ui::button-group>

                    <x-ui::button-group>
                        <x-ui::button variant="secondary">Button</x-ui::button>
                        <x-ui::button variant="danger">Button</x-ui::button>
                    </x-ui::button-group>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
