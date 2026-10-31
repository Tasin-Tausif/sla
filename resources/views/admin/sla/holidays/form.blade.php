<x-app-layout>
    <x-slot name="header"><x-admin.partials.header title="{{ $holiday->exists ? __('Edit holiday') : __('New holiday') }}" /></x-slot>
    <div class="py-8"><div class="page-shell max-w-2xl"><form method="POST" action="{{ $holiday->exists ? route('admin.holidays.update', $holiday) : route('admin.holidays.store') }}" class="space-y-5 rounded-lg bg-surface p-6 shadow-card">@csrf @if($holiday->exists) @method('PUT') @endif
        <x-form.field name="holiday_date" label="{{ __('Date') }}" :required="true"><x-form.input name="holiday_date" type="date" :value="$holiday->holiday_date?->format('Y-m-d')" required /></x-form.field>
        <x-form.field name="name" label="{{ __('Name') }}" :required="true"><x-form.input name="name" :value="$holiday->name" required /></x-form.field>
        <x-button type="submit">{{ __('Save holiday') }}</x-button>
    </form></div></div>
</x-app-layout>
