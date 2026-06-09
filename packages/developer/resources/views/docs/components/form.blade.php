{{-- Status: Essentials,info --}}
<x-froxlor-developer::base-layout title="From - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>From</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Info -->
    <x-ui::alert variant="info">
        <x-ui::icon name="book"/>
        <x-ui::alert.title>Good to know</x-ui::alert.title>
        <x-ui::alert.description>
            This page describes the <x-ui::code>&lt;ui::form&gt;</x-ui::code> component, which combines various input types (like text inputs, selects, textareas, etc.) with a label, optional help text, and error messages into a single, reusable component.
        </x-ui::alert.description>
    </x-ui::alert>

    <!-- From -->
    <x-ui::space.y>
        <x-ui::alert variant="warning">
            <x-ui::icon name="code"/>
            <x-ui::alert.title>The code may change during development.</x-ui::alert.title>
        </x-ui::alert>

        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::form cols="6">
                    <x-ui::field col-span="3">
                        <x-ui::label for="example" value="Example"/>
                        <x-ui::input type="text" name="example" value=""/>
                        <x-ui::input.error messages="Woops, something went wrong!"/>
                        <x-ui::input.help message="This is a example input field."/>
                    </x-ui::field>

                    <x-ui::field col-span="3">
                        <x-ui::label for="example" value="Example"/>
                        <x-ui::input type="text" name="example" value=""/>
                        <x-ui::input.help message="This is a example input field."/>
                    </x-ui::field>

                    <x-ui::field>
                        <x-ui::label for="example" value="Example"/>
                        <x-ui::input type="text" name="example" value=""/>
                        <x-ui::input.help message="This is a example input field."/>
                    </x-ui::field>

                    <x-ui::section>
                        <x-ui::button>Save</x-ui::button>
                    </x-ui::section>
                </x-ui::form>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::form cols="6">
                        <x-ui::field col-span="3">
                            <x-ui::label for="example" :value="trans('froxlor-ui::generic.example')"/>
                            <x-ui::input type="text" name="example" value=""/>
                            <x-ui::input.error :messages="$errors->get('example')"/>
                            <x-ui::input.help :message="trans('froxlor-ui::generic.example_help')"/>
                        </x-ui::field>

                        <x-ui::field col-span="3">
                            <x-ui::label for="example" :value="trans('froxlor-ui::generic.example')"/>
                            <x-ui::input type="text" name="example" value=""/>
                            <x-ui::input.error :messages="$errors->get('example')"/>
                            <x-ui::input.help :message="trans('froxlor-ui::generic.example_help')"/>
                        </x-ui::field>

                        <x-ui::field>
                            <x-ui::label for="example" :value="trans('froxlor-ui::generic.example')"/>
                            <x-ui::input type="text" name="example" value=""/>
                            <x-ui::input.error :messages="$errors->get('example')"/>
                            <x-ui::input.help :message="trans('froxlor-ui::generic.example_help')"/>
                        </x-ui::field>

                        <x-ui::section>
                            <x-ui::button>Save</x-ui::button>
                        </x-ui::section>
                    </x-ui::form>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
