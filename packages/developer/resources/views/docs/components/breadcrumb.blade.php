<x-froxlor-developer::base-layout title="Breadcrumb - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Breadcrumb</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Breadcrumb -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="flex flex-wrap gap-2">
                    <x-ui::breadcrumb>
                        <x-ui::breadcrumb.link href="#">
                            Home
                        </x-ui::breadcrumb.link>
                        <x-ui::breadcrumb.separator/>
                        <x-ui::breadcrumb.link href="#">
                            <x-ui::icon name="ellipsis"/>
                        </x-ui::breadcrumb.link>
                        <x-ui::breadcrumb.separator/>
                        <x-ui::breadcrumb.link href="#">
                            Tenants
                        </x-ui::breadcrumb.link>
                        <x-ui::breadcrumb.separator/>
                        <x-ui::breadcrumb.link href="#">
                            Environments
                        </x-ui::breadcrumb.link>
                    </x-ui::breadcrumb>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::breadcrumb>
                        <x-ui::breadcrumb.link href="#">
                            Home
                        </x-ui::breadcrumb.link>
                        <x-ui::breadcrumb.separator/>
                        <x-ui::breadcrumb.link href="#">
                            <x-ui::icon name="ellipsis"/>
                        </x-ui::breadcrumb.link>
                        <x-ui::breadcrumb.separator/>
                        <x-ui::breadcrumb.link href="#">
                            Tenants
                        </x-ui::breadcrumb.link>
                        <x-ui::breadcrumb.separator/>
                        <x-ui::breadcrumb.link href="#">
                            Environments
                        </x-ui::breadcrumb.link>
                    </x-ui::breadcrumb>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
