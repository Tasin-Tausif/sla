<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ __('Ticket imports') }}</h2></x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="flex justify-between">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Import batches') }}</h3>
                <x-button href="{{ route('imports.create') }}" size="sm">{{ __('New import') }}</x-button>
            </div>
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">{{ __('File') }}</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">{{ __('Progress') }}</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">{{ __('Started') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($batches as $batch)
                            <tr>
                                <td class="px-4 py-3"><a class="text-indigo-600" href="{{ route('imports.show', $batch) }}">{{ $batch->original_name }}</a></td>
                                <td class="px-4 py-3">{{ $batch->status->label() }}</td>
                                <td class="px-4 py-3">{{ $batch->processed_rows }} / {{ $batch->total_rows }}</td>
                                <td class="px-4 py-3">{{ $batch->started_at?->setTimezone(config('sla.timezone'))->format('d M Y, h:i A') ?? __('Not started') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-gray-500">{{ __('No imports found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $batches->links() }}
        </div>
    </div>
</x-app-layout>
