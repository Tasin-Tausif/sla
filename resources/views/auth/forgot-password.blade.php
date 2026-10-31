<x-guest-layout>
    <x-slot name="title">{{ __('Forgot password?') }}</x-slot>
    <x-slot name="subtitle">{{ __('Reset your password and get back to managing your tickets.') }}</x-slot>
    <x-slot name="footer">
        <a href="{{ route('login') }}" class="font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:text-primary-hover focus:outline-none focus:ring-2 focus:ring-focus-ring">{{ __('Back to sign in') }}</a>
    </x-slot>

    <x-auth-session-status class="mb-5" :status="session('status')" />
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <x-form.field name="email" label="{{ __('Email') }}" :required="true">
            <x-form.input name="email" type="email" autocomplete="username" autofocus required />
        </x-form.field>
        <x-button type="submit" class="w-full justify-center">{{ __('Email Password Reset Link') }}</x-button>
    </form>
</x-guest-layout>
