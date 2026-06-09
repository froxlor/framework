@inject('api', 'Froxlor\Core\Support\Api')

<x-ui::auth-layout>
    <x-ui::main>
        <x-ui::heading>
            <div>
                <x-ui::title>Package Repositories</x-ui::title>
                <x-ui::subtitle>Here you can add new repositories and modify existing ones.</x-ui::subtitle>
            </div>
            <x-slot:actions>
                <x-ui::button as="a" :href="route('packages.repositories.create')">{{ trans('froxlor-packages::generic.add') }}</x-ui::button>
            </x-slot>
        </x-ui::heading>

        @include('froxlor-packages::shared.warning')

        <!-- Available Repositories -->
        <x-ui::title size="2xl">
            {{ trans('froxlor-packages::generic.repositories') }}
        </x-ui::title>

        <livewire:ui::schema.table
            :columns="[
                'name' => ['label' => trans('froxlor-packages::generic.name')],
                'type' => ['label' => trans('froxlor-packages::generic.type')],
                'url' => ['label' => trans('froxlor-packages::generic.url')],
                'enabled' => ['label' => trans('froxlor-packages::generic.enabled')],
            ]"
            :data="$api->request('GET', route('api.repositories.index'))->data()->toArray()"
        />
    </x-ui::main>
</x-ui::auth-layout>
