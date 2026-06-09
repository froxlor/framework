<x-froxlor-developer::base-layout title="Field - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Field</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Info -->
    <x-ui::alert variant="info">
        <x-ui::icon name="book"/>
        <x-ui::alert.title>Good to know</x-ui::alert.title>
        <x-ui::alert.description>
            Check out the <x-ui::code>&lt;ui::form&gt;</x-ui::code> component as well, which combines the field with a label and optional help text and error message.
        </x-ui::alert.description>
    </x-ui::alert>

    <!-- Field -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::field>
                    <x-ui::label for="example" value="Example"/>
                    <x-ui::input type="text" name="example" value=""/>
                    <x-ui::input.error messages="Woops, something went wrong!"/>
                    <x-ui::input.help message="This is a example input field."/>
                </x-ui::field>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::field>
                        <x-ui::label for="example" :value="trans('froxlor-ui::generic.example')"/>
                        <x-ui::input type="text" name="example" value=""/>
                        <x-ui::input.error :messages="$errors->get('example')"/>
                        <x-ui::input.help :message="trans('froxlor-ui::generic.example_help')"/>
                    </x-ui::field>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
