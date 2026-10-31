<x-guest-layout>
    <x-slot name="title">{{ __('Reset password') }}</x-slot>
    <x-slot name="subtitle">{{ __('Choose a new password for your account.') }}</x-slot>
    <x-slot name="footer">
        <a href="{{ route('login') }}" class="font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:text-primary-hover focus:outline-none focus:ring-2 focus:ring-focus-ring">{{ __('Back to sign in') }}</a>
    </x-slot>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-form.field name="email" label="{{ __('Email') }}" :required="true">
            <x-form.input name="email" type="email" :value="$request->email" autocomplete="username" autofocus required />
        </x-form.field>
        <x-form.field name="password" label="{{ __('Password') }}" :required="true">
            <x-form.input name="password" type="password" autocomplete="new-password" required />
        </x-form.field>
        <x-form.field name="password_confirmation" label="{{ __('Confirm Password') }}" :required="true">
            <x-form.input name="password_confirmation" type="password" autocomplete="new-password" required />
        </x-form.field>
        <x-button type="submit" class="w-full justify-center">{{ __('Reset Password') }}</x-button>
    </form>
</x-guest-layout>
