<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ConfigAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): View
    {
        return view('admin.permissions.index', ['permissions' => Permission::with('roles')->orderBy('name')->get(), 'catalog' => collect(config('access.permissions'))->flatMap(fn ($group) => $group)->all()]);
    }

    public function store(Request $request, ConfigAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9.-]+$/', Rule::unique('permissions', 'name')]]);
        $permission = Permission::create(['name' => $data['name'], 'guard_name' => 'web']);
        $audit->log('created', 'Custom permission created', $permission, [], $data);

        return back()->with('success', 'Permission created.');
    }

    public function destroy(Permission $permission, ConfigAuditLogger $audit): RedirectResponse
    {
        $catalog = collect(config('access.permissions'))->flatMap(fn ($group) => array_keys($group))->all();
        abort_if(in_array($permission->name, $catalog, true), 403);
        $name = $permission->name;
        $permission->delete();
        $audit->log('deleted', 'Custom permission deleted', null, ['name' => $name]);

        return back()->with('success', 'Permission deleted.');
    }
}
