<x-guest-layout>
    <x-slot name="title">{{ __('Welcome back') }}</x-slot>
    <x-slot name="subtitle">{{ __('Sign in to manage your tickets') }}</x-slot>
    <x-slot name="footer">
        {{ __('New here?') }}
        <a href="{{ route('register') }}" class="font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:text-primary-hover focus:outline-none focus:ring-2 focus:ring-focus-ring">{{ __('Create an account') }}</a>
    </x-slot>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <x-form.field name="email" label="{{ __('Email') }}" :required="true">
            <x-form.input name="email" type="email" autocomplete="username" autofocus required class="pe-20"/>
        </x-form.field>

        <div x-data="{ show: false }">
            <x-form.field name="password" label="{{ __('Password') }}" :required="true">
                <div class="relative">
                    <x-form.input name="password" type="password" autocomplete="current-password" required x-bind:type="show ? 'text' : 'password'" class="pe-20" />
                    <x-button type="button" variant="secondary" size="sm" class="absolute end-1 top-1 normal-case tracking-normal" x-on:click="show = ! show" x-bind:aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'">
                        <span x-text="show ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                    </x-button>
                </div>
            </x-form.field>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <x-form.checkbox name="remember" id="remember_me" :label="__('Remember me')" :checked="false" />
            @if (Route::has('password.request'))
                <a class="text-sm text-content-muted underline underline-offset-4 hover:text-content focus:outline-none focus:ring-2 focus:ring-focus-ring" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <x-button type="submit" class="w-full justify-center">{{ __('Sign in') }}</x-button>
    </form>
</x-guest-layout>
