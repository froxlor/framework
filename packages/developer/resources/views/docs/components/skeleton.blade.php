<x-froxlor-developer::base-layout title="Skeleton - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Skeleton</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Skeleton: Basic -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <x-ui::skeleton />
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::skeleton />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Skeleton: Variants -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Variants</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="space-y-6">
                    <div>
                        <x-ui::subtitle>Rectangle sizes</x-ui::subtitle>
                        <div class="flex items-end gap-4">
                            <x-ui::skeleton width="w-24" height="h-3" />
                            <x-ui::skeleton width="w-32" height="h-4" rounded="lg" />
                            <x-ui::skeleton width="w-40" height="h-6" :animate="false" />
                        </div>
                    </div>

                    <div>
                        <x-ui::subtitle>Text lines</x-ui::subtitle>
                        <x-ui::skeleton variant="text" :lines="4" />
                    </div>

                    <div>
                        <x-ui::subtitle>Circle (avatar)</x-ui::subtitle>
                        <div class="flex items-center gap-4">
                            <x-ui::skeleton variant="circle" width="w-10" height="h-10" />
                            <x-ui::skeleton variant="circle" width="w-14" height="h-14" />
                            <x-ui::skeleton variant="circle" width="w-20" height="h-20" :animate="false" />
                        </div>
                    </div>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <!-- Rectangle sizes -->
                    <x-ui::skeleton width="w-24" height="h-3" />
                    <x-ui::skeleton width="w-32" height="h-4" rounded="lg" />
                    <x-ui::skeleton width="w-40" height="h-6" :animate="false" />

                    <!-- Text lines -->
                    <x-ui::skeleton variant="text" :lines="4" />

                    <!-- Circle (avatar) -->
                    <x-ui::skeleton variant="circle" width="w-10" height="h-10" />
                    <x-ui::skeleton variant="circle" width="w-14" height="h-14" />
                    <x-ui::skeleton variant="circle" width="w-20" height="h-20" :animate="false" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Skeleton: Composite examples -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Composite examples</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="space-y-10">
                    <!-- List item -->
                    <div class="flex items-center gap-4">
                        <x-ui::skeleton variant="circle" width="w-12" height="h-12" />
                        <div class="flex-1">
                            <x-ui::skeleton variant="text" :lines="2" />
                        </div>
                    </div>

                    <!-- Card -->
                    <div class="w-80 space-y-3">
                        <x-ui::skeleton width="w-full" height="h-40" rounded="lg" />
                        <x-ui::skeleton variant="text" :lines="3" />
                    </div>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <!-- List item -->
                    <div class="flex items-center gap-4">
                        <x-ui::skeleton variant="circle" width="w-12" height="h-12" />
                        <div class="flex-1">
                            <x-ui::skeleton variant="text" :lines="2" />
                        </div>
                    </div>

                    <!-- Card -->
                    <div class="w-80 space-y-3">
                        <x-ui::skeleton width="w-full" height="h-40" rounded="lg" />
                        <x-ui::skeleton variant="text" :lines="3" />
                    </div>
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
