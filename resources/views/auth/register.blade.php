<x-guest-layout>
    <x-slot name="title">{{ __('Create your account') }}</x-slot>
    <x-slot name="subtitle">{{ __('Register to submit and track support tickets') }}</x-slot>
    <x-slot name="footer">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:text-primary-hover focus:outline-none focus:ring-2 focus:ring-focus-ring">{{ __('Sign in') }}</a>
    </x-slot>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <x-form.field name="name" label="{{ __('Name') }}" :required="true">
            <x-form.input name="name" autocomplete="name" autofocus required />
        </x-form.field>

        <x-form.field name="email" label="{{ __('Email') }}" :required="true">
            <x-form.input name="email" type="email" autocomplete="username" required />
        </x-form.field>

        <div x-data="{ show: false }">
            <x-form.field name="password" label="{{ __('Password') }}"  :required="true">
                <div class="relative">
                    <x-form.input name="password" type="password" autocomplete="new-password" required x-bind:type="show ? 'text' : 'password'" class="pe-15" />
                    <x-button type="button" variant="secondary" size="sm" class="absolute end-1 top-1 normal-case tracking-normal" x-on:click="show = ! show" x-bind:aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'">
                        <span x-text="show ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                    </x-button>
                </div>
            </x-form.field>
        </div>

        <div x-data="{ show: false }">
            <x-form.field name="password_confirmation" label="{{ __('Confirm Password') }}" :required="true">
                <div class="relative">
                    <x-form.input name="password_confirmation" type="password" autocomplete="new-password" required x-bind:type="show ? 'text' : 'password'" class="pe-15" />
                    <x-button type="button" variant="secondary" size="sm" class="absolute end-1 top-1 normal-case tracking-normal" x-on:click="show = ! show" x-bind:aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'">
                        <span x-text="show ? '{{ __('Hide') }}' : '{{ __('Show') }}'"></span>
                    </x-button>
                </div>
            </x-form.field>
        </div>

        <x-button type="submit" class="w-full justify-center">{{ __('Create account') }}</x-button>
    </form>
</x-guest-layout>
