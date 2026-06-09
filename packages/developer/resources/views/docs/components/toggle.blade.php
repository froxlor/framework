<x-froxlor-developer::base-layout title="Toggle - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Toggle</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Info -->
    <x-ui::alert variant="info">
        <x-ui::icon name="book"/>
        <x-ui::alert.title>Good to know</x-ui::alert.title>
        <x-ui::alert.description>
            Check out the <x-ui::code>&lt;ui::form&gt;</x-ui::code> component as well, which combines the toggle with a label and optional help text and error message.
        </x-ui::alert.description>
    </x-ui::alert>

    <!-- Toggle -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::input.toggle name="remember" label="Remember me" />
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::input.toggle name="remember" :checked="old('remember')" label="Remember me" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>

        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::input.toggle id="terms" name="terms">
                    <x-ui::label for="terms">I accept the Terms and Conditions</x-ui::label>
                </x-ui::input.toggle>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::input.toggle id="terms" name="terms">
                        <x-ui::label for="terms">I accept the Terms and Conditions</x-ui::label>
                    </x-ui::input.toggle>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
