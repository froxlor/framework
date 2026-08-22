<x-ui::guest-layout>
    <!-- Session Status -->
    <x-ui::alert.status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <x-ui::label for="email" :value="trans('Email')" />
            <x-ui::input id="email" class="mt-2 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-ui::input.error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-ui::label for="password" :value="trans('Password')" />
            <x-ui::input id="password" class="mt-2 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-ui::input.error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" name="remember">
            <label for="remember_me" class="ml-2 text-sm text-gray-600">{{ trans('Remember me') }}</label>
        </div>

        <div class="flex items-center justify-between gap-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" href="{{ route('password.request') }}">
                    {{ trans('Forgot your password?') }}
                </a>
            @endif

            <x-ui::button>
                {{ trans('Log in') }}
            </x-ui::button>
        </div>
    </form>
</x-ui::guest-layout>
