<x-froxlor-developer::base-layout title="Tabs - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Tabs</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Stepper -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::tabs x-data="{ open: 'tab1' }">
                    <x-ui::tabs.list>
                        <x-ui::tabs.trigger name="tab1">Overview</x-ui::tabs.trigger>
                        <x-ui::tabs.trigger name="tab2">Domains</x-ui::tabs.trigger>
                        <x-ui::tabs.trigger name="tab3">Databases</x-ui::tabs.trigger>
                    </x-ui::tabs.list>
                    <x-ui::tabs.content name="tab1">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed
                        euismod, nunc ut laoreet aliquam, nunc nisl aliquet nunc, eu
                        aliquam nisl nunc euismod nunc.
                    </x-ui::tabs.content>
                    <x-ui::tabs.content name="tab2">
                        Duis aute irure dolor in reprehenderit in voluptate velit esse
                        cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat
                        cupidatat non proident, sunt in culpa qui officia deserunt mollit
                        anim id est laborum.
                    </x-ui::tabs.content>
                    <x-ui::tabs.content name="tab3">
                        Et harum quidem rerum facilis est et expedita distinctio. Nam
                        libero tempore, cum soluta nobis est eligendi optio cumque nihil
                        impedit quo minus id quod maxime placeat facere possimus, omnis
                        voluptas assumenda est, omnis dolor repellendus.
                    </x-ui::tabs.content>
                </x-ui::tabs>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::tabs x-data="{ open: 'tab1' }">
                        <x-ui::tabs.list>
                            <x-ui::tabs.trigger name="tab1">Overview</x-ui::tabs.trigger>
                            <x-ui::tabs.trigger name="tab2">Domains</x-ui::tabs.trigger>
                            <x-ui::tabs.trigger name="tab3">Databases</x-ui::tabs.trigger>
                        </x-ui::tabs.list>
                        <x-ui::tabs.content name="tab1">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed
                            euismod, nunc ut laoreet aliquam, nunc nisl aliquet nunc, eu
                            aliquam nisl nunc euismod nunc.
                        </x-ui::tabs.content>
                        <x-ui::tabs.content name="tab2">
                            Duis aute irure dolor in reprehenderit in voluptate velit esse
                            cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat
                            cupidatat non proident, sunt in culpa qui officia deserunt mollit
                            anim id est laborum.
                        </x-ui::tabs.content>
                        <x-ui::tabs.content name="tab3">
                            Et harum quidem rerum facilis est et expedita distinctio. Nam
                            libero tempore, cum soluta nobis est eligendi optio cumque nihil
                            impedit quo minus id quod maxime placeat facere possimus, omnis
                            voluptas assumenda est, omnis dolor repellendus.
                        </x-ui::tabs.content>
                    </x-ui::tabs>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
