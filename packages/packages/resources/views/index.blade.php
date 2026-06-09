<x-ui::auth-layout>
    <x-ui::main>
        <x-ui::heading>
            <div>
                <x-ui::title>{{ trans('froxlor-packages::generic.packages') }}</x-ui::title>
                <x-ui::subtitle>
                    Here you can see your installed packages and those available from the Froxlor Repository. Use the form below to add more.
                </x-ui::subtitle>
            </div>
        </x-ui::heading>

        <!-- Installed Packages -->
        <x-ui::title size="2xl">
            {{ trans('froxlor-packages::generic.installed_packages') }}
        </x-ui::title>
        <livewire:ui::schema.table
            :columns="[
                'name' => ['label' => trans('froxlor-packages::generic.package')],
                'version' => ['label' => trans('froxlor-packages::generic.version')],
                'description' => ['label' => trans('froxlor-packages::generic.description')],
            ]"
            :data="$packages"
        />

        <!-- Available Packages -->
        <x-ui::title size="2xl">
            {{ trans('froxlor-packages::generic.available_packages') }}
        </x-ui::title>
        <livewire:ui::schema.table
            :columns="[
                'name' => ['label' => trans('froxlor-packages::generic.name')],
                'package' => ['label' => trans('froxlor-packages::generic.package')],
                'version' => ['label' => trans('froxlor-packages::generic.version')],
                'description' => ['label' => trans('froxlor-packages::generic.description')],
            ]"
            :data="$marketplacePackages"
        />
    </x-ui::main>
</x-ui::auth-layout>
