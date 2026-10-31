<x-app-layout>
    <x-slot name="header"><x-admin.partials.header title="{{ __('Business hours') }}" /></x-slot>
    <div class="py-8"><div class="page-shell"><form method="POST" action="{{ route('admin.sla.business-hours.update') }}" class="space-y-5 rounded-lg bg-surface p-6 shadow-card">@csrf @method('PUT')
        <p class="text-sm text-content-muted">{{ __('Changes apply to tickets created or re-prioritised after saving; existing tickets keep their stored SLA.') }}</p>
        @foreach (range(0, 6) as $day) @php($hour = $hours[$day] ?? new \App\Models\BusinessHour(['day_of_week' => $day]))
            <div class="grid items-end gap-4 border-b border-line pb-4 sm:grid-cols-3"><div class="font-semibold">{{ $hour->dayName() }}</div><x-form.checkbox name="hours[{{ $day }}][is_working_day]" label="{{ __('Working day') }}" :checked="$hour->is_working_day" /><div class="grid grid-cols-2 gap-3"><x-form.input name="hours[{{ $day }}][start_time]" type="time" :value="$hour->start_time" /><x-form.input name="hours[{{ $day }}][end_time]" type="time" :value="$hour->end_time" /></div></div>
        @endforeach
        <x-button type="submit">{{ __('Save business hours') }}</x-button>
    </form></div></div>
</x-app-layout>
