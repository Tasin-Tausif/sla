<x-app-layout>
    <x-slot name="header"><x-admin.partials.header title="{{ $user->exists ? __('Edit user') : __('New user') }}" /></x-slot>
    <div class="py-8"><div class="page-shell max-w-2xl"><form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-5 rounded-lg bg-surface p-6 shadow-card">@csrf @if($user->exists) @method('PUT') @endif
        <x-form.field name="name" label="{{ __('Name') }}" :required="true"><x-form.input name="name" :value="$user->name" required /></x-form.field>
        <x-form.field name="email" label="{{ __('Email') }}" :required="true"><x-form.input name="email" type="email" :value="$user->email" required /></x-form.field>
        <x-form.field name="password" label="{{ __('Password') }}" :required="!$user->exists"><x-form.input name="password" type="password" :required="!$user->exists" /></x-form.field>
        <x-form.field name="password_confirmation" label="{{ __('Confirm password') }}" :required="!$user->exists"><x-form.input name="password_confirmation" type="password" :required="!$user->exists" /></x-form.field>
        <fieldset><legend class="mb-2 font-medium">{{ __('Roles') }}</legend><div class="grid gap-2 sm:grid-cols-3">@foreach($roles as $role)<x-form.checkbox name="roles[]" :value="$role->name" :label="$role->name" :checked="$user->hasRole($role->name)" />@endforeach</div></fieldset>
        <x-form.checkbox name="is_available" label="{{ __('Available for assignment') }}" :checked="$user->is_available" />
        <x-form.field name="max_open_tickets" label="{{ __('Maximum open tickets') }}"><x-form.input name="max_open_tickets" type="number" :value="$user->max_open_tickets ?: 10" min="1" max="100" /></x-form.field>
        <x-button type="submit">{{ $user->exists ? __('Save user') : __('Create user') }}</x-button>
    </form></div></div>
</x-app-layout>
