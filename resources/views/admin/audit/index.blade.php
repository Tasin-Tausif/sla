<x-app-layout>
    <x-slot name="header"><x-admin.partials.header title="{{ __('Configuration history') }}" /></x-slot>
    <div class="py-8"><div class="page-shell space-y-4">
        <form method="GET" class="grid gap-3 rounded-lg bg-surface p-4 shadow-card sm:grid-cols-4"><x-form.input name="event" placeholder="{{ __('Event') }}" :value="request('event')" /><x-form.input name="from" type="date" :value="request('from')" /><x-form.input name="to" type="date" :value="request('to')" /><x-button type="submit">{{ __('Filter') }}</x-button></form>
        <div class="space-y-3">@foreach($logs as $log)<details class="rounded-lg bg-surface p-4 shadow-card"><summary class="cursor-pointer"><span class="font-medium">{{ $log->event }}</span> <span class="text-content-muted">{{ $log->description }} — {{ $log->causer?->name ?? __('System') }}</span></summary><pre class="mt-3 overflow-x-auto rounded bg-surface-muted p-3 text-xs">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>@endforeach</div>{{ $logs->links() }}
    </div></div>
</x-app-layout>
