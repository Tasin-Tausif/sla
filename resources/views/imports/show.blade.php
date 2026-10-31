@if (! $batch->status->isFinished())
    <meta http-equiv="refresh" content="3">
@endif
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-content">{{ $batch->original_name }}</h2></x-slot>
    <div class="py-12">
        <div class="page-shell space-y-6">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                @foreach (['total_rows' => 'Total', 'processed_rows' => 'Processed', 'imported_rows' => 'Imported', 'duplicate_rows' => 'Duplicates', 'failed_rows' => 'Failed'] as $field => $label)
                    <div class="rounded-lg bg-surface p-4 shadow-sm"><div class="text-sm text-content-muted">{{ __($label) }}</div><div class="text-2xl font-semibold">{{ $batch->{$field} }}</div></div>
                @endforeach
            </div>
            <div class="rounded-lg bg-surface p-6 shadow-sm">
                <div class="flex justify-between"><span>{{ $batch->status->label() }}</span><span>{{ $batch->total_rows ? round($batch->processed_rows / $batch->total_rows * 100) : 0 }}%</span></div>
                <div class="mt-2 h-3 rounded bg-bg"><div class="h-3 rounded bg-primary" style="width: {{ $batch->total_rows ? ($batch->processed_rows / $batch->total_rows) * 100 : 0 }}%"></div></div>
                @if ($batch->failed_rows > 0)
                    <x-button href="{{ route('imports.errors', $batch) }}" variant="secondary" size="sm" class="mt-4">{{ __('Download errors CSV') }}</x-button>
                @endif
            </div>
            <x-responsive-table class="rounded-lg bg-surface shadow-card">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-surface-muted"><tr><th class="px-4 py-3 text-left">{{ __('Row') }}</th><th class="px-4 py-3 text-left">{{ __('Message') }}</th><th class="px-4 py-3 text-left">{{ __('Raw data') }}</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($errors as $error)
                            <tr><td class="px-4 py-3">{{ $error->row_number }}</td><td class="px-4 py-3">{{ $error->error_message }}</td><td class="px-4 py-3 text-sm">{{ json_encode($error->raw_data) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-content-muted">{{ __('No errors.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-responsive-table>
            {{ $errors->links() }}
        </div>
    </div>
</x-app-layout>
