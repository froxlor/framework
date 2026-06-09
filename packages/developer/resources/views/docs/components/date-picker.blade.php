<x-froxlor-developer::base-layout title="Date Picker - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Date Picker</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Date Picker: Basic -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="max-w-sm">
                    <x-ui::date-picker name="date_basic" placeholder="YYYY-MM-DD" />
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::date-picker name="date_basic" placeholder="YYYY-MM-DD" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Date Picker: Custom formats -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Custom formats</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="grid gap-4 md:grid-cols-2 max-w-3xl">
                    <x-ui::date-picker name="date_de" displayFormat="DD.MM.YYYY" modelFormat="YYYY-MM-DD" placeholder="TT.MM.JJJJ" />
                    <x-ui::date-picker name="date_us" displayFormat="MM/DD/YYYY" modelFormat="YYYY-MM-DD" placeholder="MM/DD/YYYY" />
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::date-picker name="date_de" displayFormat="DD.MM.YYYY" modelFormat="YYYY-MM-DD" placeholder="TT.MM.JJJJ" />
                    <x-ui::date-picker name="date_us" displayFormat="MM/DD/YYYY" modelFormat="YYYY-MM-DD" placeholder="MM/DD/YYYY" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Date Picker: Min/Max + week start -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Min/Max and week start</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="grid gap-4 md:grid-cols-2 max-w-3xl">
                    <x-ui::date-picker name="date_range" :value="'2025-06-15'" min="2025-01-01" max="2025-12-31" />
                    <x-ui::date-picker name="date_weekstart_sun" :value="'2025-06-15'" weekStart="0" placeholder="Week starts Sunday" />
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::date-picker name="date_range" :value="'2025-06-15'" min="2025-01-01" max="2025-12-31" />
                    <x-ui::date-picker name="date_weekstart_sun" :value="'2025-06-15'" weekStart="0" placeholder="Week starts Sunday" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>

    <!-- Date Picker: Disabled -->
    <x-ui::space.y>
        <x-ui::title size="2xl">Disabled</x-ui::title>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <div class="max-w-sm">
                    <x-ui::date-picker name="date_disabled" :disabled="true" placeholder="Disabled" />
                </div>
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <x-ui::date-picker name="date_disabled" :disabled="true" placeholder="Disabled" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
