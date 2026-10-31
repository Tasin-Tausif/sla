<x-guest-layout>
    <x-slot name="title">{{ __('Confirm your password') }}</x-slot>
    <x-slot name="subtitle">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</x-slot>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf
        <x-form.field name="password" label="{{ __('Password') }}" :required="true">
            <x-form.input name="password" type="password" autocomplete="current-password" autofocus required />
        </x-form.field>
        <x-button type="submit" class="w-full justify-center">{{ __('Confirm') }}</x-button>
    </form>
</x-guest-layout>
