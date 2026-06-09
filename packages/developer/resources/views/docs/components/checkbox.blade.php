<x-froxlor-developer::base-layout title="Checkbox - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Checkbox</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Info -->
    <x-ui::alert variant="info">
        <x-ui::icon name="book"/>
        <x-ui::alert.title>Good to know</x-ui::alert.title>
        <x-ui::alert.description>
            Use <x-ui::code>&lt;x-ui::input.checkbox /&gt;</x-ui::code> for both labeled form fields and compact selection controls such as table bulk actions.
        </x-ui::alert.description>
    </x-ui::alert>

    <!-- Checkbox -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::input.checkbox name="remember" label="Remember me" />
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::input.checkbox name="remember" :checked="old('remember')" label="Remember me" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>

        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::input.checkbox id="terms" name="terms">
                    <x-ui::label for="terms">Ich akzeptiere die AGB</x-ui::label>
                </x-ui::input.checkbox>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::input.checkbox id="terms" name="terms">
                        <x-ui::label for="terms">Ich akzeptiere die AGB</x-ui::label>
                    </x-ui::input.checkbox>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>

        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-600 dark:text-zinc-300">Compact selection checkbox</span>
                        <x-ui::input.checkbox container-class="justify-center" />
                    </div>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::input.checkbox container-class="justify-center" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
