<?php

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketActivityLogger;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\SlaPolicySeeder;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([BusinessHoursSeeder::class, SlaPolicySeeder::class]);
});

it('logs a ticket activity with event, causer and properties', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create(['created_by' => $user->id]);

    app(TicketActivityLogger::class)->log(
        $ticket,
        'priority_changed',
        'Priority changed',
        $user,
        ['old' => ['priority' => 'high'], 'new' => ['priority' => 'low']],
    );

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->log_name)->toBe('ticket')
        ->and($activity->event)->toBe('priority_changed')
        ->and($activity->description)->toBe('Priority changed')
        ->and($activity->subject_id)->toBe($ticket->id)
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->properties->get('old'))->toBe(['priority' => 'high'])
        ->and($activity->properties->get('new'))->toBe(['priority' => 'low']);
});

it('uses a null causer for system activity', function () {
    $ticket = Ticket::factory()->create();

    app(TicketActivityLogger::class)->log($ticket, 'sla_warning', 'SLA warning sent');

    $activity = Activity::query()->latest('id')->first();

    expect($activity->causer_id)->toBeNull()
        ->and($activity->event)->toBe('sla_warning');
});
