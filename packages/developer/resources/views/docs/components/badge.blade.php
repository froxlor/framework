<x-froxlor-developer::base-layout title="Badge - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Badge</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Badge -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::badge>Badge</x-ui::badge>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::badge>Badge</x-ui::badge>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Badge Variants -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Variants</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="flex flex-wrap gap-2">
                    <x-ui::badge>Badge</x-ui::badge>
                    <x-ui::badge variant="info">Badge</x-ui::badge>
                    <x-ui::badge variant="success">Badge</x-ui::badge>
                    <x-ui::badge variant="warning">Badge</x-ui::badge>
                    <x-ui::badge variant="danger">Badge</x-ui::badge>
                    <x-ui::badge variant="outline">Badge</x-ui::badge>
                    <x-ui::badge variant="secondary">Badge</x-ui::badge>
                    <x-ui::badge variant="ghost">Badge</x-ui::badge>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::badge>Badge</x-ui::badge>
                    <x-ui::badge variant="primary">Badge</x-ui::badge>
                    <x-ui::badge variant="info">Badge</x-ui::badge>
                    <x-ui::badge variant="success">Badge</x-ui::badge>
                    <x-ui::badge variant="warning">Badge</x-ui::badge>
                    <x-ui::badge variant="danger">Badge</x-ui::badge>
                    <x-ui::badge variant="outline">Badge</x-ui::badge>
                    <x-ui::badge variant="secondary">Badge</x-ui::badge>
                    <x-ui::badge variant="ghost">Badge</x-ui::badge>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
