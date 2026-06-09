<x-froxlor-developer::base-layout title="Body - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Body</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Body -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::body as="div" class="relative aspect-video overflow-hidden rounded-lg" subClasses="min-h-full">

                </x-ui::body>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::body>
                        <!-- ... -->
                    </x-ui::body>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
