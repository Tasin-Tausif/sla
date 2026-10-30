<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(15),
        ]);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return to_route('notifications.index');
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        $data = $record->data;
        $url = (string) ($data['url'] ?? '');
        $parsed = parse_url($url);
        $path = ($parsed['path'] ?? '').(isset($parsed['query']) ? '?'.$parsed['query'] : '');

        if ($path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '//')
            || isset($parsed['scheme']) || isset($parsed['host'])) {
            return to_route('notifications.index');
        }

        $ticket = isset($data['ticket_id']) ? Ticket::find($data['ticket_id']) : null;

        if ($ticket === null || Gate::denies('view', $ticket)) {
            return to_route('notifications.index')
                ->with('status', 'You no longer have access to this ticket.');
        }

        return redirect()->to($path);
    }
}
