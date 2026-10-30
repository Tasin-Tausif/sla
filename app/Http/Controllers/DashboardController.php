<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->can('dashboard.view')) {
            return to_route('tickets.index');
        }

        return view('dashboard', $dashboard->forUser($user));
    }
}
