<x-ui::app-layout>
    <x-ui::flex class="flex-1 justify-between flex-col lg:flex-row">
        <!-- Intro Section -->
        <x-ui::middle class="relative lg:order-2 flex-1 bg-zinc-800 bg-gradient-to-br from-primary-500 via-primary-800 to-primary-800">
            <x-ui::logo :src="asset('vendor/froxlor/ui/assets/img/logo_white.png')" class="h-16 w-auto"/>

            <x-ui::lead>
                Welcome to {{ config('app.name', 'Laravel') }}! Please complete the setup by creating your administrator account.
            </x-ui::lead>

            <x-ui::button variant="outline">Read Documentation</x-ui::button>
            <x-ui::button variant="ghost" href="https://discord.gg/your-discord-invite" target="_blank" rel="noopener">
                Join Discord
            </x-ui::button>
        </x-ui::middle>

        <!-- Form Section -->
        <x-ui::middle class="flex-1">
            <x-slot name="before">
                <x-ui::logo class="h-20 w-auto"/>
            </x-slot>

            <x-ui::card>
                <x-ui::card.content>
                    <!-- Session Status -->
                    <x-ui::alert.status class="mb-4" :status="session('status')"/>

                    <form method="POST" action="{{ route('init.store') }}">
                        @csrf

                        <!-- Name -->
                        <x-ui::grid cols="2">
                            <!-- First name -->
                            <div>
                                <x-ui::label for="first_name" :value="trans('First name')"/>
                                <x-ui::input id="first_name" class="block mt-1 w-full"
                                             type="text" name="first_name" :value="old('first_name')" required
                                             autofocus autocomplete="first_name"/>
                                <x-ui::input.error :messages="$errors->get('first_name')" class="mt-2"/>
                            </div>

                            <!-- Last name -->
                            <div>
                                <x-ui::label for="last_name" :value="trans('Last name')"/>
                                <x-ui::input id="last_name" class="block mt-1 w-full"
                                             type="text" name="last_name" :value="old('last_name')" required
                                             autofocus autocomplete="last_name"/>
                                <x-ui::input.error :messages="$errors->get('last_name')" class="mt-2"/>
                            </div>
                        </x-ui::grid>

                        <!-- Email Address -->
                        <div class="mt-4">
                            <x-ui::label for="email" :value="trans('Email')"/>
                            <x-ui::input id="email" class="block mt-1 w-full"
                                         type="email" name="email" :value="old('email')"
                                         required autocomplete="username"/>
                            <x-ui::input.error :messages="$errors->get('email')" class="mt-2"/>
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-ui::label for="password" :value="trans('Password')"/>

                            <x-ui::input id="password" class="block mt-1 w-full"
                                         type="password" name="password"
                                         required autocomplete="new-password"/>

                            <x-ui::input.error :messages="$errors->get('password')" class="mt-2"/>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mt-4">
                            <x-ui::label for="password_confirmation" :value="trans('Confirm Password')"/>

                            <x-ui::input id="password_confirmation" class="block mt-1 w-full"
                                         type="password"
                                         name="password_confirmation" required autocomplete="new-password"/>

                            <x-ui::input.error :messages="$errors->get('password_confirmation')" class="mt-2"/>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-ui::button type="submit">
                                {{ trans('Complete setup') }}
                            </x-ui::button>
                        </div>
                    </form>
                </x-ui::card.content>
            </x-ui::card>
        </x-ui::middle>
    </x-ui::flex>
</x-ui::app-layout>
