<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-content">{{ __('Ticket imports') }}</h2></x-slot>
    <div class="py-12">
        <div class="page-shell space-y-4">
            <div class="flex justify-between">
                <h3 class="text-lg font-medium text-content">{{ __('Import batches') }}</h3>
                <x-button href="{{ route('imports.create') }}" size="sm">{{ __('New import') }}</x-button>
            </div>
            <x-responsive-table class="rounded-lg bg-surface shadow-card">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-surface-muted"><tr>
                        <th class="px-4 py-3 text-left text-xs uppercase text-content-muted">{{ __('File') }}</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-content-muted">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-content-muted">{{ __('Progress') }}</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-content-muted">{{ __('Started') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($batches as $batch)
                            <tr>
                                <td class="px-4 py-3"><a class="text-primary" href="{{ route('imports.show', $batch) }}">{{ $batch->original_name }}</a></td>
                                <td class="px-4 py-3">{{ $batch->status->label() }}</td>
                                <td class="px-4 py-3">{{ $batch->processed_rows }} / {{ $batch->total_rows }}</td>
                                <td class="px-4 py-3">{{ $batch->started_at?->setTimezone(config('sla.timezone'))->format('d M Y, h:i A') ?? __('Not started') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-content-muted">{{ __('No imports found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-responsive-table>
            {{ $batches->links() }}
        </div>
    </div>
</x-app-layout>
