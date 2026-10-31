<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ConfigAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', ['roles' => Role::withCount(['users', 'permissions'])->with('permissions')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role, 'groups' => config('access.permissions')]);
    }

    public function store(Request $request, ConfigAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:roles,name']]);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $this->sync($request, $role);
        $audit->log('created', 'Role created', $role, [], ['name' => $role->name]);

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.form', ['role' => $role->load('permissions'), 'groups' => config('access.permissions')]);
    }

    public function update(Request $request, Role $role, ConfigAuditLogger $audit): RedirectResponse
    {
        if ($role->name === 'admin') {
            $role->syncPermissions(array_keys(collect(config('access.permissions'))->flatMap(fn ($g) => array_keys($g))->all()));

            return back()->with('success', 'Administrator permissions are fixed to the catalog.');
        }
        $old = $role->permissions->pluck('name')->all();
        if (! in_array($role->name, ['agent', 'customer'], true)) {
            $name = $request->validate(['name' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('roles', 'name')->ignore($role->id)]])['name'];
            $role->name = $name;
            $role->save();
        }
        $this->sync($request, $role);
        $audit->log('updated', 'Role updated', $role, ['permissions' => $old], ['permissions' => $role->permissions()->pluck('name')->all()]);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role, ConfigAuditLogger $audit): RedirectResponse
    {
        if (in_array($role->name, ['admin', 'agent', 'customer'], true)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }
        if ($role->users()->exists()) {
            return back()->with('error', 'Roles with users cannot be deleted.');
        }
        $role->delete();
        $audit->log('deleted', 'Role deleted', null, ['name' => $role->name]);

        return back()->with('success', 'Role deleted.');
    }

    public function reset(Role $role): RedirectResponse
    {
        abort_if(! in_array($role->name, ['agent', 'customer'], true), 403);
        $role->syncPermissions(config('access.system_roles.'.$role->name, []));

        return back()->with('success', 'Role reset to defaults.');
    }

    private function sync(Request $request, Role $role): void
    {
        $allowed = collect(config('access.permissions'))->flatMap(fn ($group) => array_keys($group))->all();
        $role->syncPermissions(array_values(array_intersect($request->input('permissions', []), $allowed)));
    }
}
