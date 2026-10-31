<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $logs = Activity::query()->where('log_name', 'admin')->with('causer')->when($request->filled('event'), fn ($q) => $q->where('event', $request->event))->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))->latest()->paginate(25)->withQueryString();

        return view('admin.audit.index', compact('logs'));
    }
}
