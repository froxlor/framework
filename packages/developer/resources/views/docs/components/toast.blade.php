{{-- Status: Experimental,danger --}}
<x-froxlor-developer::base-layout title="Toast - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Toast</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Toast -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="flex flex-wrap items-center gap-4" x-data>
                    <x-ui::toast title="Saved" description="The changes have been saved." />

                    <x-ui::button @click="$dispatch('show-toast')">
                        Show toast
                    </x-ui::button>
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::toast title="Saved" description="The changes have been saved." />

                    <x-ui::button @click="$dispatch('show-toast')">
                        Show toast
                    </x-ui::button>

                    <!-- Variants -->
                    <x-ui::toast title="Info" variant="info" />
                    <x-ui::toast title="Success" variant="success" />
                    <x-ui::toast title="Warning" variant="warning" />
                    <x-ui::toast title="Error" variant="danger" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
