<x-froxlor-developer::base-layout title="Popover - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Popover</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Popover -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="flex flex-wrap items-start gap-6">
                    <x-ui::popover>
                        <x-slot:trigger>
                            <x-ui::button>Open</x-ui::button>
                        </x-slot:trigger>
                        <x-slot:content>
                            <div class="space-y-1">
                                <div class="font-medium">Popover Title</div>
                                <div class="text-sm text-zinc-300">Any content in the popover.</div>
                                <x-ui::button size="sm" variant="secondary">Action</x-ui::button>
                            </div>
                        </x-slot:content>
                    </x-ui::popover>

                    <x-ui::popover side="right" align="start" width="sm">
                        <x-slot:trigger>
                            <x-ui::button variant="secondary">Right</x-ui::button>
                        </x-slot:trigger>
                        <x-slot:content>
                            <div class="text-sm">Popover on the right, start aligned.</div>
                        </x-slot:content>
                    </x-ui::popover>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::popover>
                        <x-slot:trigger>
                            <x-ui::button>Open</x-ui::button>
                        </x-slot:trigger>
                        <x-slot:content>
                            <div class="space-y-1">
                                <div class="font-medium">Popover Title</div>
                                <div class="text-sm text-zinc-300">Any content in the popover.</div>
                                <x-ui::button size="sm" variant="secondary">Action</x-ui::button>
                            </div>
                        </x-slot:content>
                    </x-ui::popover>

                    <x-ui::popover side="right" align="start" width="sm">
                        <x-slot:trigger>
                            <x-ui::button variant="secondary">Right</x-ui::button>
                        </x-slot:trigger>
                        <x-slot:content>
                            <div class="text-sm">Popover on the right, start aligned.</div>
                        </x-slot:content>
                    </x-ui::popover>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
