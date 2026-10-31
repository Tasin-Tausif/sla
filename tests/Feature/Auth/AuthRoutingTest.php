<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('guests visiting home are sent to login', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('staff visiting home are sent to the dashboard', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)->get(route('home'))->assertRedirect(route('dashboard'));
});

test('customers visiting home are sent to tickets', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('home'))->assertRedirect(route('tickets.index'));
});

test('authenticated users are redirected from guest auth pages', function () {
    $this->seed(RolePermissionSeeder::class);

    $staff = User::factory()->create();
    $staff->assignRole('agent');
    $this->actingAs($staff)->get(route('login'))->assertRedirect(route('dashboard'));
    $this->actingAs($staff)->get(route('register'))->assertRedirect(route('dashboard'));

    $customer = User::factory()->create();
    $this->actingAs($customer)->get(route('login'))->assertRedirect(route('tickets.index'));
    $this->actingAs($customer)->get(route('register'))->assertRedirect(route('tickets.index'));
});

test('registration screen includes the sign in link and app name', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee(route('login'));
});
