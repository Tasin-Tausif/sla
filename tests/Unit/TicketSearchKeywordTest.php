<?php

use App\Models\Ticket;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

uses(TestCase::class, DatabaseMigrations::class);

beforeEach(function () {
    $this->seed([BusinessHoursSeeder::class, SlaPolicySeeder::class]);
});

it('returns tickets matching the keyword in the subject', function () {
    $matching = Ticket::factory()->create([
        'subject' => 'Printer not working',
        'description' => 'The office device is offline.',
    ]);
    Ticket::factory()->create([
        'subject' => 'Billing issue',
        'description' => 'Invoice total is wrong.',
    ]);

    expect(Ticket::searchKeyword('printer')->pluck('id')->all())
        ->toBe([$matching->id]);
});

it('returns tickets matching the keyword in the description', function () {
    $matching = Ticket::factory()->create([
        'subject' => 'Account access',
        'description' => 'The invoice total cannot be loaded.',
    ]);
    Ticket::factory()->create([
        'subject' => 'Printer not working',
        'description' => 'The office device is offline.',
    ]);

    expect(Ticket::searchKeyword('invoice')->pluck('id')->all())
        ->toBe([$matching->id]);
});

it('excludes tickets that do not match the keyword', function () {
    $matching = Ticket::factory()->create([
        'subject' => 'Printer not working',
        'description' => 'The office device is offline.',
    ]);
    Ticket::factory()->create([
        'subject' => 'Billing issue',
        'description' => 'Invoice total is wrong.',
    ]);
    Ticket::factory()->create([
        'subject' => 'Account access',
        'description' => 'Cannot log in.',
    ]);

    $results = Ticket::searchKeyword('printer')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($matching->id);
});
