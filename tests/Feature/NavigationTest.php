<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function navigationUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('shows all permitted configuration links in the profile menu for administrators', function () {
    $response = $this->actingAs(navigationUser('admin'))->get(route('admin.categories.index'));

    $response->assertOk()
        ->assertSee('Configuration')
        ->assertSee(route('admin.users.index'))
        ->assertSee(route('admin.roles.index'))
        ->assertSee(route('admin.permissions.index'))
        ->assertSee(route('admin.categories.index'))
        ->assertSee(route('admin.sla.policies.index'))
        ->assertSee(route('admin.sla.business-hours.index'))
        ->assertSee(route('admin.holidays.index'))
        ->assertSee(route('admin.audit.index'));
});

it('shows only permitted configuration links for a category manager', function () {
    $role = Role::create(['name' => 'category-manager', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::findByName('categories.manage', 'web'));
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)->get(route('admin.categories.index'));

    $response->assertOk()
        ->assertSee('Configuration')
        ->assertSee(route('admin.categories.index'))
        ->assertDontSee(route('admin.users.index'))
        ->assertDontSee(route('admin.roles.index'))
        ->assertDontSee(route('admin.permissions.index'))
        ->assertDontSee(route('admin.sla.policies.index'))
        ->assertDontSee(route('admin.audit.index'));
});

it('hides configuration links for agents and customers', function () {
    foreach (['agent', 'customer'] as $role) {
        $response = $this->actingAs(navigationUser($role))->get(route('tickets.index'));

        $response->assertOk()
            ->assertDontSee('Configuration')
            ->assertDontSee('/admin/');
    }
});
