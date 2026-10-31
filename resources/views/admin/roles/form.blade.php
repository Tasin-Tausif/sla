<x-app-layout>
    <x-slot name="header"><x-admin.partials.header title="{{ __('Edit role') }}" /></x-slot>
    <div class="py-8"><div class="page-shell max-w-3xl"><form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="space-y-5 rounded-lg bg-surface p-6 shadow-card">@csrf @if($role->exists) @method('PUT') @endif
        @if(!$role->exists)<x-form.field name="name" label="{{ __('Name') }}" :required="true"><x-form.input name="name" required /></x-form.field>@else<div class="text-lg font-semibold">{{ $role->name }}</div>@endif
        @foreach($groups as $group => $permissions)<fieldset class="border-b border-line pb-4"><legend class="mb-2 font-semibold capitalize">{{ $group }}</legend><div class="grid gap-3 sm:grid-cols-2">@foreach($permissions as $permission => $description)<x-form.checkbox name="permissions[]" :value="$permission" :label="$description" :checked="$role->hasPermissionTo($permission)" :disabled="$role->name === 'admin'" />@endforeach</div></fieldset>@endforeach
        @if($role->name !== 'admin')<x-button type="submit">{{ __('Save role') }}</x-button>@endif
    </form></div></div>
</x-app-layout>
