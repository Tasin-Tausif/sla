<?php

use App\Models\Category;
use App\Models\User;

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('protects the administration area by permission', function () {
    $customer = User::factory()->create();
    $this->actingAs($customer)->get(route('admin.categories.index'))->assertForbidden();
    auth()->logout();
    $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
});

it('allows administrators to manage categories', function () {
    $admin = adminUser();
    $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();

    $this->post(route('admin.categories.store'), [
        'name' => 'Billing',
        'slug' => '',
        'is_active' => '1',
    ])->assertRedirect(route('admin.categories.index'));

    expect(Category::query()->where('slug', 'billing')->exists())->toBeTrue();
});

it('does not delete categories that have tickets', function () {
    $category = Category::create(['name' => 'Existing', 'slug' => 'existing', 'is_active' => true]);
    $admin = adminUser();
    $category->tickets()->create([
        'ticket_number' => 'TKT-2026-999999',
        'subject' => 'Existing ticket',
        'description' => 'Existing ticket',
        'priority' => 'low',
        'status' => 'open',
        'created_by' => $admin->id,
        'sla_minutes' => 60,
        'sla_due_at' => now()->addHour(),
        'sla_warning_at' => now()->addMinutes(48),
    ]);

    $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))
        ->assertSessionHas('error', 'This category has tickets. Deactivate it instead.');
});
