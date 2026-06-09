{{-- Status: Experimental,danger --}}
<x-froxlor-developer::base-layout title="Pagination - Components - froxlor Development Kit">
    <x-ui::heading>
        <div>
            <x-ui::teaser>Components</x-ui::teaser>
            <x-ui::title>Pagination</x-ui::title>
        </div>
    </x-ui::heading>

    <!-- Info -->
    <x-ui::alert variant="info">
        <x-ui::icon name="book"/>
        <x-ui::alert.title>Good to know</x-ui::alert.title>
        <x-ui::alert.description>
            The <x-ui::code>&lt;x-ui::pagination&gt;</x-ui::code> component supports two modes:<br>
            1) Pass a Laravel paginator via <x-ui::code>:paginator</x-ui::code><br>
            2) Pass manual props <x-ui::code>:current</x-ui::code> and <x-ui::code>:total</x-ui::code>.<br>
            Optional: <x-ui::code>surround</x-ui::code> controls how many pages appear around the current, and <x-ui::code>pageName</x-ui::code> customizes the query key (default: <x-ui::code>page</x-ui::code>).
        </x-ui::alert.description>
    </x-ui::alert>

    <!-- Pagination -->
    <x-ui::space.y>
        <x-ui::code.playground language="blade">
            <x-slot:preview>
                <!-- Manual example (preview) -->
                <x-ui::pagination :current="3" :total="12" :surround="2" class="mt-2" />
            </x-slot:preview>

            <x-slot:code>
                @verbatim
                    <!-- Manual usage -->
                    <x-ui::pagination :current="3" :total="12" :surround="2" />

                    <!-- With a Laravel paginator (LengthAwarePaginator, CursorPaginator, etc.) -->
                    <x-ui::pagination :paginator="$users" />

                    <!-- Custom pageName (useful when rendering multiple paginations on a page) -->
                    <x-ui::pagination :current="1" :total="8" pageName="users_page" />
                @endverbatim
            </x-slot:code>
        </x-ui::code.playground>
    </x-ui::space.y>
</x-froxlor-developer::base-layout>
