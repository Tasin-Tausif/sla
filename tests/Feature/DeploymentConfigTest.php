<?php

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

it('uses LIKE search when full text search is disabled', function () {
    config(['tickets.search.fulltext' => false]);

    expect(Ticket::query()->searchKeyword('support')->toSql())
        ->toContain('like')
        ->not->toContain('match');
});

it('forces HTTPS URLs when configured', function () {
    config(['app.force_https' => true]);
    URL::forceScheme('https');

    expect(route('home'))->toStartWith('https://');
});

it('uses the configured seed password', function () {
    config(['app.seed_password' => 'S3cret-Test-Pass']);

    $this->seed(RolePermissionSeeder::class);
    $this->seed(UserSeeder::class);

    expect(Hash::check('S3cret-Test-Pass', User::where('email', 'admin@example.com')->first()->password))->toBeTrue();
});
