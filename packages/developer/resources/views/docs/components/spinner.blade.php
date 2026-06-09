<x-froxlor-developer::base-layout title="Spinner - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Spinner</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Info -->
    <x-ui::alert variant="info">
        <x-ui::icon name="book"/>
        <x-ui::alert.title>Good to know</x-ui::alert.title>
        <x-ui::alert.description>
            The spinner component is built using the <x-ui::code>&lt;ui::icon ...&gt;</x-ui::code> component with the loader-circle as icon and applies a spinning animation.
            So you can customize it using the same properties as the icon component.
        </x-ui::alert.description>
    </x-ui::alert>

    <!-- Spinner -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::spinner/>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::spinner/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
