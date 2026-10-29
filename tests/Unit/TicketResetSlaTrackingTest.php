<?php

use App\Models\Ticket;
use Tests\TestCase;

uses(TestCase::class);

it('resets SLA tracking fields without saving', function () {
    $ticket = new Ticket;
    $ticket->exists = true;
    $ticket->sla_warning_sent_at = '2026-01-01 10:00:00';
    $ticket->sla_breached_at = '2026-01-01 11:00:00';
    $ticket->escalated_at = '2026-01-01 12:00:00';
    $ticket->escalation_level = 3;
    $ticket->syncOriginal();

    $ticket->resetSlaTracking();

    expect($ticket->sla_warning_sent_at)->toBeNull()
        ->and($ticket->sla_breached_at)->toBeNull()
        ->and($ticket->escalated_at)->toBeNull()
        ->and($ticket->escalation_level)->toBe(0)
        ->and($ticket->isDirty())->toBeTrue();
});
