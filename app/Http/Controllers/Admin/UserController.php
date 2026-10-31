<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()->with('roles')->withCount(['assignedTickets as open_assigned_tickets' => fn ($q) => $q->whereIn('status', TicketStatus::activeValues())])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%')))
            ->when($request->filled('role'), fn ($q) => $q->role($request->role))->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::query()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User, 'roles' => Role::query()->orderBy('name')->get()]);
    }

    public function store(Request $request, UserAdminService $service): RedirectResponse
    {
        $service->create($this->validated($request, true));

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user->load('roles'), 'roles' => Role::query()->orderBy('name')->get()]);
    }

    public function update(Request $request, User $user, UserAdminService $service): RedirectResponse
    {
        $service->update($user, $this->validated($request));

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user, UserAdminService $service): RedirectResponse
    {
        $service->delete($user, $request = auth()->user());

        return back()->with('success', 'User deleted.');
    }

    public function makeAgent(User $user, UserAdminService $service): RedirectResponse
    {
        $service->makeAgent($user, auth()->user());

        return back()->with('success', 'Agent role assigned.');
    }

    public function removeAgent(User $user, UserAdminService $service): RedirectResponse
    {
        $service->removeAgent($user, auth()->user());

        return back()->with('success', 'Agent role removed.');
    }

    private function validated(Request $request, bool $creating = false): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->route('user'))],
            'password' => [$creating ? 'required' : 'nullable', 'confirmed', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],
            'is_available' => ['boolean'],
            'max_open_tickets' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }
}
