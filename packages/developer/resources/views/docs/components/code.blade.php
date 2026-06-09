<x-froxlor-developer::base-layout title="Code - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Code</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Code -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Code</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::code>
                    &lt;!-- ... --&gt;
                </x-ui::code>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::code>
                        <!-- ... -->
                    </x-ui::code>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Code Pre -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Pre</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::code.fullwidth>&lt;!-- ... --&gt;</x-ui::code.fullwidth>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::code.pre>
                        <!-- ... -->
                    </x-ui::code.pre>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
