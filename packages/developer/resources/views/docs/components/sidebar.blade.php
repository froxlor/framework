<x-froxlor-developer::base-layout title="Sidebar - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Sidebar</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Low-Level Alert -->
    <x-ui::alert variant="warning">
        <x-ui::icon name="code"/>
        <x-ui::alert.title>Attention!</x-ui::alert.title>
        <x-ui::alert.description>
            You might want to use the <x-ui::code>&lt;livewire:ui::navigations.sidebar navigation="primary"/&gt;</x-ui::code> component for most use-cases, <x-ui::link href="/developers/docs/navigations/sidebar">check the docs for more info.</x-ui::link> The <x-ui::code>&lt;x-ui::sidebar&gt;</x-ui::code> component is a low-level building block for custom navigation sidebars.
        </x-ui::alert.description>
    </x-ui::alert>

    <!-- Sidebar -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::body as="div" class="relative aspect-video overflow-hidden rounded-lg" subClasses="min-h-full">
                    <div class="flex flex-grow">
                        <x-ui::sidebar name="sidebar-name">
                            <!-- Header -->
                            <x-ui::sidebar.header>
                                <x-ui::logo class="h-8 w-auto" />
                            </x-ui::sidebar.header>

                            <!-- Content -->
                            <x-ui::sidebar.content>
                                <x-ui::sidebar.group>
                                    <x-ui::sidebar.group-label>Application</x-ui::sidebar.group-label>
                                    <x-ui::sidebar.group-content>
                                        <x-ui::sidebar.nav>
                                            <x-ui::sidebar.link href="#" icon="home">Home</x-ui::sidebar.link>
                                            <x-ui::sidebar.link href="#" icon="server">Node</x-ui::sidebar.link>
                                            <x-ui::sidebar.link href="#" icon="menu" :navigate="false">
                                                Other
                                                <x-slot:children>
                                                    <x-ui::sidebar.child href="#">First</x-ui::sidebar.child>
                                                    <x-ui::sidebar.child href="#">Second</x-ui::sidebar.child>
                                                </x-slot:children>
                                            </x-ui::sidebar.link>
                                        </x-ui::sidebar.nav>
                                    </x-ui::sidebar.group-content>
                                </x-ui::sidebar.group>
                            </x-ui::sidebar.content>

                            <!-- Footer -->
                            <x-ui::sidebar.footer>
                                Footer
                            </x-ui::sidebar.footer>
                        </x-ui::sidebar>
                    </div>
                </x-ui::body>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::sidebar name="sidebar-name">
                        <!-- Header -->
                        <x-ui::sidebar.header>
                            <x-ui::logo class="h-8 w-auto" />
                        </x-ui::sidebar.header>

                        <!-- Content -->
                        <x-ui::sidebar.content>
                            <x-ui::sidebar.group>
                                <x-ui::sidebar.group-label>Application</x-ui::sidebar.group-label>
                                <x-ui::sidebar.group-content>
                                    <x-ui::sidebar.nav>
                                        <x-ui::sidebar.link href="#" icon="home">Home</x-ui::sidebar.link>
                                        <x-ui::sidebar.link href="#" icon="server">Node</x-ui::sidebar.link>
                                        <x-ui::sidebar.link href="#" icon="menu" :navigate="false">
                                            Other
                                            <x-slot:children>
                                                <x-ui::sidebar.child href="#">First</x-ui::sidebar.child>
                                                <x-ui::sidebar.child href="#">Second</x-ui::sidebar.child>
                                            </x-slot:children>
                                        </x-ui::sidebar.link>
                                    </x-ui::sidebar.nav>
                                </x-ui::sidebar.group-content>
                            </x-ui::sidebar.group>
                        </x-ui::sidebar.content>

                        <!-- Footer -->
                        <x-ui::sidebar.footer>
                            Footer
                        </x-ui::sidebar.footer>
                    </x-ui::sidebar>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Sidebar -->
    <x-ui::title size="2xl">Trigger</x-ui::title>
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::sidebar.trigger name="sidebar-name"/>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::sidebar.trigger name="sidebar-name"/>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
