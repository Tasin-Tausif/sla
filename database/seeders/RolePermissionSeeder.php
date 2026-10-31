<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $catalog = config('access.permissions', []);
        $permissions = collect($catalog)->flatMap(fn (array $items): array => array_keys($items))->values()->all();
        $created = [];

        foreach ($permissions as $permission) {
            $model = Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            if ($model->wasRecentlyCreated) {
                $created[] = $permission;
            }
        }

        $registrar->forgetCachedPermissions();

        foreach (config('access.system_roles', []) as $name => $defaults) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $roleDefaults = $defaults === '*' ? $permissions : $defaults;

            if ($name === 'admin') {
                $role->syncPermissions($permissions);
            } elseif ($role->wasRecentlyCreated) {
                $role->syncPermissions($roleDefaults);
            } elseif ($created !== []) {
                $role->givePermissionTo(array_values(array_intersect($created, $roleDefaults)));
            }
        }

        $registrar->forgetCachedPermissions();
    }
}
