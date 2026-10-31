<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Models\SlaPolicy;
use App\Services\Admin\ConfigAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SlaPolicyController extends Controller
{
    public function index(): View
    {
        return view('admin.sla.policies', ['policies' => SlaPolicy::query()->get()->keyBy(fn (SlaPolicy $policy) => $policy->priority->value)]);
    }

    public function update(Request $request, ConfigAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'policies' => ['required', 'array'],
            'policies.*.resolution_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'policies.*.warning_threshold_percent' => ['required', 'integer', 'min:1', 'max:99'],
        ]);
        DB::transaction(function () use ($data, $audit): void {
            foreach (TicketPriority::cases() as $priority) {
                $values = $data['policies'][$priority->value];
                $policy = SlaPolicy::firstOrNew(['priority' => $priority->value]);
                $old = $policy->exists ? $policy->only(['resolution_minutes', 'warning_threshold_percent', 'is_active']) : [];
                $policy->fill([...$values, 'is_active' => true])->save();
                $audit->log('updated', 'SLA policy updated', $policy, $old, $policy->only(['resolution_minutes', 'warning_threshold_percent', 'is_active']));
            }
        });

        return back()->with('success', 'SLA policies saved.');
    }
}
