<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessHour;
use App\Models\Holiday;
use App\Services\Admin\ConfigAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function businessHours(): View
    {
        return view('admin.sla.business-hours', ['hours' => BusinessHour::query()->orderBy('day_of_week')->get()->keyBy('day_of_week')]);
    }

    public function updateBusinessHours(Request $request, ConfigAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'hours' => ['required', 'array'],
            'hours.*.start_time' => ['nullable', 'date_format:H:i'],
            'hours.*.end_time' => ['nullable', 'date_format:H:i'],
            'hours.*.is_working_day' => ['nullable', 'boolean'],
        ]);
        if (! collect($data['hours'])->contains(fn (array $hour): bool => (bool) ($hour['is_working_day'] ?? false))) {
            return back()->withErrors(['hours' => 'At least one working day is required.']);
        }
        DB::transaction(function () use ($data, $audit): void {
            foreach (range(0, 6) as $day) {
                $values = $data['hours'][$day] ?? [];
                $working = (bool) ($values['is_working_day'] ?? false);
                if ($working && (($values['end_time'] ?? '') <= ($values['start_time'] ?? ''))) {
                    throw ValidationException::withMessages(["hours.{$day}.end_time" => 'The end time must be after the start time.']);
                }
                $hour = BusinessHour::firstOrNew(['day_of_week' => $day]);
                $old = $hour->exists ? $hour->only(['is_working_day', 'start_time', 'end_time']) : [];
                $hour->fill([
                    'is_working_day' => $working,
                    'start_time' => $working ? ($values['start_time'] ?? null) : null,
                    'end_time' => $working ? ($values['end_time'] ?? null) : null,
                ])->save();
                $audit->log('updated', 'Business hours updated', $hour, $old, $hour->only(['is_working_day', 'start_time', 'end_time']));
            }
        });

        return back()->with('success', 'Business hours saved.');
    }

    public function holidays(Request $request): View
    {
        $year = (int) $request->query('year', now()->year);

        return view('admin.sla.holidays.index', ['holidays' => Holiday::forYear($year)->latest('holiday_date')->paginate(15)->withQueryString(), 'year' => $year]);
    }

    public function createHoliday(): View
    {
        return view('admin.sla.holidays.form', ['holiday' => new Holiday]);
    }

    public function storeHoliday(Request $request, ConfigAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['holiday_date' => ['required', 'date', 'unique:holidays,holiday_date'], 'name' => ['required', 'string', 'max:100']]);
        $holiday = Holiday::create($data);
        $audit->log('created', 'Holiday created', $holiday, [], $data);

        return redirect()->route('admin.holidays.index')->with('success', 'Holiday created.');
    }

    public function editHoliday(Holiday $holiday): View
    {
        return view('admin.sla.holidays.form', compact('holiday'));
    }

    public function updateHoliday(Request $request, Holiday $holiday, ConfigAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['holiday_date' => ['required', 'date', Rule::unique('holidays', 'holiday_date')->ignore($holiday->id)], 'name' => ['required', 'string', 'max:100']]);
        $old = $holiday->only(['holiday_date', 'name']);
        $holiday->update($data);
        $audit->log('updated', 'Holiday updated', $holiday, $old, $data);

        return redirect()->route('admin.holidays.index')->with('success', 'Holiday updated.');
    }

    public function destroyHoliday(Holiday $holiday, ConfigAuditLogger $audit): RedirectResponse
    {
        $old = $holiday->only(['holiday_date', 'name']);
        $holiday->delete();
        $audit->log('deleted', 'Holiday deleted', $holiday, $old);

        return back()->with('success', 'Holiday deleted.');
    }
}
