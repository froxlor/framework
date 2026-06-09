@inject('api', 'Froxlor\Core\Support\Api')

<x-ui::auth-layout>
    <x-ui::main>
        <x-ui::heading>
            <div>
                <x-ui::title>Package Repositories</x-ui::title>
                <x-ui::subtitle>Here you can add new repositories and modify existing ones.</x-ui::subtitle>
            </div>
        </x-ui::heading>

        <!-- Add Repository -->
        <x-ui::title size="2xl">
            {{ trans('froxlor-packages::generic.add_repository') }}
        </x-ui::title>

        @include('froxlor-packages::shared.warning')

        <form method="POST" action="{{ route('api.packages.store') }}">
            <x-ui::space.y>
                @csrf

                <div>
                    <x-ui::label for="repository" :value="trans('froxlor-packages::generic.repository')" />
                    <x-ui::input class="mt-2 w-full" type="text" name="repository" value=""/>
                    <x-ui::input.error :messages="$errors->get('repository')" class="mt-2" />
                    <x-ui::input.help :message="trans('froxlor-packages::generic.repository_help')" class="mt-2" />
                </div>

                <x-ui::flex>
                    <x-ui::button type="submit" variant="warning">{{ trans('froxlor-packages::generic.add') }}</x-ui::button>
                </x-ui::flex>
            </x-ui::space.y>
        </form>
    </x-ui::main>
</x-ui::auth-layout>
