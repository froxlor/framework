<x-froxlor-developer::base-layout title="Alert - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Alert</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Alert -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::alert>
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    <x-ui::alert.description>This is a test.</x-ui::alert.description>
                </x-ui::alert>

                <x-ui::alert variant="primary">
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    <x-ui::alert.description>This is a test.</x-ui::alert.description>
                </x-ui::alert>

                <x-ui::alert variant="info">
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    <x-ui::alert.description>This is a test.</x-ui::alert.description>
                </x-ui::alert>

                <x-ui::alert variant="success">
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    <x-ui::alert.description>This is a test.</x-ui::alert.description>
                </x-ui::alert>

                <x-ui::alert variant="warning">
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    <x-ui::alert.description>This is a test.</x-ui::alert.description>
                </x-ui::alert>

                <x-ui::alert variant="danger">
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    <x-ui::alert.description>This is a test.</x-ui::alert.description>
                </x-ui::alert>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::alert>
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                        <x-ui::alert.description>This is a test.</x-ui::alert.description>
                    </x-ui::alert>

                    <x-ui::alert variant="primary">
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                        <x-ui::alert.description>This is a test.</x-ui::alert.description>
                    </x-ui::alert>

                    <x-ui::alert variant="info">
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                        <x-ui::alert.description>This is a test.</x-ui::alert.description>
                    </x-ui::alert>

                    <x-ui::alert variant="success">
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                        <x-ui::alert.description>This is a test.</x-ui::alert.description>
                    </x-ui::alert>

                    <x-ui::alert variant="warning">
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                        <x-ui::alert.description>This is a test.</x-ui::alert.description>
                    </x-ui::alert>

                    <x-ui::alert variant="danger">
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                        <x-ui::alert.description>This is a test.</x-ui::alert.description>
                    </x-ui::alert>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>

        <!-- Alert Variants -->
        <x-ui::title size="2xl">Variants</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::alert variant="primary" format="square">
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                </x-ui::alert>

                <x-ui::alert variant="primary" format="square" layout="solid">
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                </x-ui::alert>

                <x-ui::alert variant="primary" layout="solid">
                    <x-ui::icon name="user"/>
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                </x-ui::alert>

                <x-ui::alert variant="primary" layout="solid">
                    <x-ui::icon name="user"/>
                    <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    <x-ui::alert.description>This is a test.</x-ui::alert.description>
                </x-ui::alert>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::alert variant="primary" format="square">
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    </x-ui::alert>

                    <x-ui::alert variant="primary" format="square" layout="solid">
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    </x-ui::alert>

                    <x-ui::alert variant="primary" layout="solid">
                        <x-ui::icon name="user"/>
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                    </x-ui::alert>

                    <x-ui::alert variant="primary" layout="solid">
                        <x-ui::icon name="user"/>
                        <x-ui::alert.title>Heads up!</x-ui::alert.title>
                        <x-ui::alert.description>This is a test.</x-ui::alert.description>
                    </x-ui::alert>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
