<x-froxlor-developer::base-layout title="Select - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Select</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Info -->
    <x-ui::alert variant="info">
        <x-ui::icon name="book"/>
        <x-ui::alert.title>Good to know</x-ui::alert.title>
        <x-ui::alert.description>
            Check out the <x-ui::code>&lt;ui::form&gt;</x-ui::code> component as well, which combines the select with a label and optional help text and error message.
        </x-ui::alert.description>
    </x-ui::alert>

    <!-- Select -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::input.select name="example" :value="1" :options="['One', 'Two', 'Three']"/>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::input.select name="example" :value="2" :options="['One', 'Two', 'Three']"/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
