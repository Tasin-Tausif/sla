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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'tickets.viewAny',
            'tickets.view',
            'tickets.create',
            'tickets.update',
            'tickets.assign',
            'tickets.accept',
            'tickets.resolve',
            'tickets.close',
            'tickets.reopen',
            'tickets.delete',
            'tickets.reply',
            'tickets.internal-note',
            'tickets.import',
            'categories.manage',
            'sla.manage',
            'users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $administrator = Role::findOrCreate('admin', 'web');
        $supportAgent = Role::findOrCreate('agent', 'web');
        $customer = Role::findOrCreate('customer', 'web');

        $administrator->syncPermissions($permissions);

        $supportAgent->syncPermissions([
            'dashboard.view',
            'tickets.viewAny',
            'tickets.view',
            'tickets.update',
            'tickets.assign',
            'tickets.accept',
            'tickets.resolve',
            'tickets.close',
            'tickets.reopen',
            'tickets.reply',
            'tickets.internal-note',
            'tickets.import',
        ]);

        $customer->syncPermissions([
            'tickets.viewAny',
            'tickets.view',
            'tickets.create',
            'tickets.reply',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
