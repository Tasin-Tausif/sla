<x-app-layout>
    <x-slot name="header"><x-admin.partials.header title="{{ $category->exists ? __('Edit category') : __('New category') }}" /></x-slot>
    <div class="py-8"><div class="page-shell max-w-2xl"><form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="space-y-5 rounded-lg bg-surface p-6 shadow-card">@csrf @if($category->exists) @method('PUT') @endif
        <x-form.field name="name" label="{{ __('Name') }}" :required="true"><x-form.input name="name" :value="$category->name" required /></x-form.field>
        <x-form.field name="slug" label="{{ __('Slug') }}" hint="{{ __('CSV imports use this slug.') }}"><x-form.input name="slug" :value="$category->slug" /></x-form.field>
        <x-form.checkbox name="is_active" label="{{ __('Active') }}" :checked="$category->exists ? $category->is_active : true" />
        <x-button type="submit">{{ $category->exists ? __('Save changes') : __('Create category') }}</x-button>
    </form></div></div>
</x-app-layout>
