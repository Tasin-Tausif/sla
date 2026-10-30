<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $this->seed(RolePermissionSeeder::class);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('newly registered users get the customer role and can open the ticket screens', function () {
    $this->seed(RolePermissionSeeder::class);

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->hasRole('customer'))->toBeTrue();

    $this->get(route('tickets.create'))->assertOk();

    $this->get(route('tickets.index'))
        ->assertOk()
        ->assertSee('No tickets found.');
});
