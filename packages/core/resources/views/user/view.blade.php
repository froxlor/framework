<x-ui::auth-layout>
    <x-ui::main>
        <x-ui::title>
            {{ trans('froxlor-core::user.view') }}
        </x-ui::title>
        <x-ui::card>
            <x-ui::card.body>
                <x-ui::card.content>
                    {{ $user->name }} ({{ $user->email }})
                </x-ui::card.content>
            </x-ui::card.body>
        </x-ui::card>

        <x-ui::card>
            <x-ui::card.header>User tenants</x-ui::card.header>
            <x-ui::card.body>
                <x-ui::card.content>
                    @foreach($user->tenants as $tenant)
                        Tenant: "{{ $tenant->name }}"<br>
                    @endforeach
                </x-ui::card.content>
            </x-ui::card.body>
        </x-ui::card>

        <x-ui::card>
            <x-ui::card.header>User environments</x-ui::card.header>
            <x-ui::card.body>
                <x-ui::card.content>
                    @foreach($user->environments as $env)
                        Environment: "{{ $env->name }}"<br>
                    @endforeach
                </x-ui::card.content>
            </x-ui::card.body>
        </x-ui::card>

        <x-ui::card>
            <x-ui::card.header>User roles</x-ui::card.header>
            <x-ui::card.body>
                <x-ui::card.content>
                    @foreach($user->roles as $role)
                        Role: "{{ $role->name }}"<br>
                        <ul>
                            @foreach($role->permissions as $perm)
                                <li>- {{ $perm->name }}</li>
                            @endforeach
                        </ul>
                    @endforeach
                </x-ui::card.content>
            </x-ui::card.body>
        </x-ui::card>

    </x-ui::main>
</x-ui::auth-layout>
