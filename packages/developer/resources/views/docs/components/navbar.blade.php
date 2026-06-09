<x-froxlor-developer::base-layout title="Navbar - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Navbar</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Low-Level Alert -->
    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Attention!</x-ui::alert.title>
        <x-ui::alert.description>
            You might want to use the <x-ui::code>&lt;livewire:ui::navbar navigation="primary" user-navigation="user"/&gt;</x-ui::code> component for most use-cases, <x-ui::link href="/developers/docs/navigations/navbar">check the docs for more info.</x-ui::link> The <x-ui::code>&lt;x-ui::navbar&gt;</x-ui::code> component is a low-level building block for custom navigation bars.
        </x-ui::alert.description>
    </x-ui::alert>

    <!-- Navbar -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::navbar>
                    <!-- Logo / Title -->
                    <x-ui::link href="#" class="flex items-center space-x-4 text-inherit dark:text-inherit hover:no-underline">
                        <x-ui::logo class="h-8 w-auto"/>
                        <span class="font-medium text-xl">Title</span>
                    </x-ui::link>

                    <!-- Left Side -->
                    <x-slot name="left">
                        <x-ui::navigation>
                            <x-ui::navigation.list>
                                <x-ui::navigation.item>
                                    <x-ui::navigation.link href="#" :active="true">
                                        One
                                    </x-ui::navigation.link>
                                    <x-ui::navigation.link href="#">
                                        Two
                                    </x-ui::navigation.link>
                                    <x-ui::navigation.link href="#" icon="bell" icon-variant="warning">
                                        Three
                                    </x-ui::navigation.link>
                                </x-ui::navigation.item>
                            </x-ui::navigation.list>
                        </x-ui::navigation>
                    </x-slot>
                </x-ui::navbar>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::navbar>
                        <!-- Logo / Title -->
                        <x-ui::link href="#" class="flex items-center space-x-4 text-inherit dark:text-inherit hover:no-underline">
                            <x-ui::logo class="h-8 w-auto"/>
                            <span class="font-medium text-xl">Title</span>
                        </x-ui::link>

                        <!-- Left Side -->
                        <x-slot name="left">
                            <x-ui::navigation>
                                <x-ui::navigation.list>
                                    <x-ui::navigation.item>
                                        <x-ui::navigation.link href="#" :active="true">
                                            One
                                        </x-ui::navigation.link>
                                        <x-ui::navigation.link href="#">
                                            Two
                                        </x-ui::navigation.link>
                                        <x-ui::navigation.link href="#" icon="bell" icon-variant="warning">
                                            Three
                                        </x-ui::navigation.link>
                                    </x-ui::navigation.item>
                                </x-ui::navigation.list>
                            </x-ui::navigation>
                        </x-slot>
                    </x-ui::navbar>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
