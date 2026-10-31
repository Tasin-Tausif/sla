<x-app-layout>
    <x-slot name="header"><x-admin.partials.header title="{{ __('Categories') }}" /></x-slot>
    <div class="py-8"><div class="page-shell space-y-4">
        @if (session('success')) <x-auth-session-status :status="session('success')" /> @endif
        @if (session('error')) <div class="rounded-md bg-danger-soft p-4 text-sm text-danger">{{ session('error') }}</div> @endif
        <div class="flex justify-end"><x-button href="{{ route('admin.categories.create') }}">{{ __('New category') }}</x-button></div>
        <div class="overflow-x-auto rounded-lg bg-surface shadow-card"><x-responsive-table><table class="min-w-full divide-y divide-line"><thead><tr><th class="px-4 py-3 text-left text-xs text-content-muted">{{ __('Name') }}</th><th class="px-4 py-3 text-left text-xs text-content-muted">{{ __('Slug') }}</th><th class="px-4 py-3 text-left text-xs text-content-muted">{{ __('Tickets') }}</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-line">
        @foreach ($categories as $category)<tr><td class="px-4 py-3">{{ $category->name }}</td><td class="px-4 py-3 text-content-muted">{{ $category->slug }}</td><td class="px-4 py-3">{{ $category->tickets_count }}</td><td class="px-4 py-3 text-right"><x-button href="{{ route('admin.categories.edit', $category) }}" variant="secondary" size="sm">{{ __('Edit') }}</x-button><form class="inline" method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('{{ __('Delete this category?') }}')">@csrf @method('DELETE')<x-button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-button></form></td></tr>@endforeach
        </tbody></table></x-responsive-table></div>{{ $categories->links() }}
    </div></div>
</x-app-layout>
