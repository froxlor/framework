<x-froxlor-developer::base-layout title="Card - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Card</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Card -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::card>
                    <x-ui::card.header>
                        <x-ui::card.title>Title</x-ui::card.title>
                        <x-ui::card.description>Description</x-ui::card.description>
                    </x-ui::card.header>
                    <x-ui::card.content>
                        Content
                    </x-ui::card.content>
                </x-ui::card>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::card>
                        <x-ui::card.header>
                            <x-ui::card.title>Title</x-ui::card.title>
                            <x-ui::card.description>Description</x-ui::card.description>
                        </x-ui::card.header>
                        <x-ui::card.content>
                            Content
                        </x-ui::card.content>
                    </x-ui::card>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>

        <x-ui::title size="2xl">Header</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::card>
                    <x-ui::card.header>
                        <x-ui::card.title>Title</x-ui::card.title>
                        <x-ui::card.description>Description</x-ui::card.description>
                    </x-ui::card.header>
                </x-ui::card>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::card>
                        <x-ui::card.header>
                            <x-ui::card.title>Title</x-ui::card.title>
                            <x-ui::card.description>Description</x-ui::card.description>
                        </x-ui::card.header>
                    </x-ui::card>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>

        <x-ui::title size="2xl">Content</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::card>
                    <x-ui::card.content>
                        Content
                    </x-ui::card.content>
                </x-ui::card>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::card>
                        <x-ui::card.content>
                            Content
                        </x-ui::card.content>
                    </x-ui::card>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>

        <x-ui::title size="2xl">Footer</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::card>
                    <x-ui::card.footer>
                        Footer
                    </x-ui::card.footer>
                </x-ui::card>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::card>
                        <x-ui::card.footer>
                            Footer
                        </x-ui::card.footer>
                    </x-ui::card>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
