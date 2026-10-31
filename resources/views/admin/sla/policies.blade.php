<x-app-layout>
    <x-slot name="header"><x-admin.partials.header title="{{ __('SLA policies') }}" /></x-slot>
    <div class="py-8"><div class="page-shell"><form method="POST" action="{{ route('admin.sla.policies.update') }}" class="space-y-5 rounded-lg bg-surface p-6 shadow-card">@csrf @method('PUT')
        <p class="text-sm text-content-muted">{{ __('Changes apply to tickets created or re-prioritised after saving; existing tickets keep their stored SLA.') }}</p>
        @foreach (\App\Enums\TicketPriority::cases() as $priority) @php($policy = $policies[$priority->value] ?? null)
            <div class="grid gap-4 border-b border-line pb-4 md:grid-cols-3"><div class="font-semibold">{{ $priority->label() }}</div>
                <x-form.field name="policies.{{ $priority->value }}.resolution_minutes" label="{{ __('Resolution minutes') }}" :required="true"><x-form.input name="policies[{{ $priority->value }}][resolution_minutes]" type="number" :value="$policy?->resolution_minutes" min="1" max="525600" required /></x-form.field>
                <x-form.field name="policies.{{ $priority->value }}.warning_threshold_percent" label="{{ __('Warning %') }}" :required="true"><x-form.input name="policies[{{ $priority->value }}][warning_threshold_percent]" type="number" :value="$policy?->warning_threshold_percent ?? 80" min="1" max="99" required /></x-form.field>
            </div>
        @endforeach
        <x-button type="submit">{{ __('Save policies') }}</x-button>
    </form></div></div>
</x-app-layout>
