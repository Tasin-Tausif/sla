<x-guest-layout>
    <x-slot name="title">{{ __('Verify your email') }}</x-slot>
    <x-slot name="subtitle">{{ __('One more step before you get started.') }}</x-slot>

    <p class="text-sm leading-6 text-content-muted">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-5 rounded-md bg-success-soft px-4 py-3 text-sm text-success" role="status">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
        <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
            @csrf
            <x-button type="submit" class="w-full justify-center">{{ __('Resend Verification Email') }}</x-button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="flex-1">
            @csrf
            <x-button type="submit" variant="secondary" class="w-full justify-center normal-case tracking-normal">{{ __('Log Out') }}</x-button>
        </form>
    </div>
</x-guest-layout>
