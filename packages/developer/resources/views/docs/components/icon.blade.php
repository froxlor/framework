<x-froxlor-developer::base-layout title="Icon - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Icon</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Icon -->
    <x-ui::space.y>
        <x-ui::text>The icon component utilizes the <x-ui::link href="https://lucide.dev/icons/" :external="true" target="_blank">Lucide</x-ui::link> icon library.</x-ui::text>

        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::space.x>
                    <x-ui::icon name="database"/>
                    <x-ui::icon name="server"/>
                    <x-ui::icon name="flame"/>
                    <x-ui::icon name="sparkles"/>
                    <x-ui::icon name="message-circle-question-mark"/>
                    <x-ui::icon name="shredder"/>
                    <x-ui::icon name="mail"/>
                    <x-ui::icon name="plus"/>
                </x-ui::space.x>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::icon name="database"/>
                    <x-ui::icon name="server"/>
                    <x-ui::icon name="flame"/>
                    <x-ui::icon name="sparkles"/>
                    <x-ui::icon name="message-circle-question-mark"/>
                    <x-ui::icon name="shredder"/>
                    <x-ui::icon name="mail"/>
                    <x-ui::icon name="message-circle-question-mark"/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Icon Variants -->
    <x-ui::title size="2xl">Variants</x-ui::title>
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::space.x>
                    <x-ui::icon name="user" variant="info"/>
                    <x-ui::icon name="user" variant="primary"/>
                    <x-ui::icon name="user" variant="success"/>
                    <x-ui::icon name="user" variant="danger"/>
                    <x-ui::icon name="user" variant="warning"/>
                    <x-ui::icon name="user" variant="subtle"/>
                </x-ui::space.x>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::icon name="user" variant="info"/>
                    <x-ui::icon name="user" variant="primary"/>
                    <x-ui::icon name="user" variant="success"/>
                    <x-ui::icon name="user" variant="danger"/>
                    <x-ui::icon name="user" variant="warning"/>
                    <x-ui::icon name="user" variant="subtle"/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Icon Sizes -->
    <x-ui::title size="2xl">Sizes</x-ui::title>
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::space.x>
                    <x-ui::icon name="user" size="1.5"/>
                    <x-ui::icon name="user" size="2"/>
                    <x-ui::icon name="user" size="3"/>
                </x-ui::space.x>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::icon name="user" size="1.5"/>
                    <x-ui::icon name="user" size="2"/>
                    <x-ui::icon name="user" size="3"/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
