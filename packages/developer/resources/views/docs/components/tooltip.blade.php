<x-froxlor-developer::base-layout title="Tooltip - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Tooltip</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Tooltip -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="flex flex-wrap items-center gap-4">
                    <x-ui::tooltip text="More information">
                        <x-ui::button>Hover me</x-ui::button>
                    </x-ui::tooltip>

                    <x-ui::tooltip side="left" align="center">
                        <x-slot:content>
                            <div class="font-medium">Rich Tooltip</div>
                            <div class="mt-0.5 text-xs text-zinc-300">With any content, links, and more.</div>
                        </x-slot:content>

                        <x-ui::button variant="secondary">Rich content</x-ui::button>
                    </x-ui::tooltip>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <!-- Simple text tooltip -->
                    <x-ui::tooltip text="More information">
                        <x-ui::button>Hover me</x-ui::button>
                    </x-ui::tooltip>

                    <!-- Rich Content Tooltip -->
                    <x-ui::tooltip side="left" align="center">
                        <x-slot:content>
                            <div class="font-medium">Rich Tooltip</div>
                            <div class="mt-0.5 text-xs text-zinc-300">With any content, links, and more.</div>
                        </x-slot:content>
                    </x-ui::tooltip>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
