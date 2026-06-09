<x-froxlor-developer::base-layout title="Button - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Button</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Button -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::button>Button</x-ui::button>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::button>Button</x-ui::button>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Button Variants -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Variants</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="flex flex-wrap gap-2">
                    <x-ui::button>Button</x-ui::button>
                    <x-ui::button variant="info">Button</x-ui::button>
                    <x-ui::button variant="success">Button</x-ui::button>
                    <x-ui::button variant="warning">Button</x-ui::button>
                    <x-ui::button variant="danger">Button</x-ui::button>
                    <x-ui::button variant="outline">Button</x-ui::button>
                    <x-ui::button variant="secondary">Button</x-ui::button>
                    <x-ui::button variant="ghost">Button</x-ui::button>
                    <x-ui::button variant="link">Button</x-ui::button>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::button>Button</x-ui::button>
                    <x-ui::button variant="primary">Button</x-ui::button>
                    <x-ui::button variant="info">Button</x-ui::button>
                    <x-ui::button variant="success">Button</x-ui::button>
                    <x-ui::button variant="warning">Button</x-ui::button>
                    <x-ui::button variant="danger">Button</x-ui::button>
                    <x-ui::button variant="outline">Button</x-ui::button>
                    <x-ui::button variant="secondary">Button</x-ui::button>
                    <x-ui::button variant="ghost">Button</x-ui::button>
                    <x-ui::button variant="link">Button</x-ui::button>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
