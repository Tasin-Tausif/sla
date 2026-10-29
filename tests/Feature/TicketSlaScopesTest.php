<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-01-15 12:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('returns only tickets within SLA', function () {
    $within = Ticket::factory()->create([
        'status' => TicketStatus::Open,
        'sla_warning_at' => now()->addHour(),
        'sla_due_at' => now()->addHours(5),
    ]);
    $approaching = Ticket::factory()->create([
        'status' => TicketStatus::Open,
        'sla_warning_at' => now()->subHour(),
        'sla_due_at' => now()->addHour(),
    ]);
    $breached = Ticket::factory()->create([
        'status' => TicketStatus::InProgress,
        'sla_warning_at' => now()->subHours(3),
        'sla_due_at' => now()->subHour(),
    ]);

    expect(Ticket::withinSla()->pluck('id')->all())->toBe([$within->id])
        ->and(Ticket::withinSla()->pluck('id')->all())
        ->not->toContain($approaching->id)
        ->not->toContain($breached->id);
});

it('returns only approaching tickets', function () {
    $approaching = Ticket::factory()->create([
        'status' => TicketStatus::InProgress,
        'sla_warning_at' => now()->subHour(),
        'sla_due_at' => now()->addHour(),
    ]);
    Ticket::factory()->create([
        'status' => TicketStatus::Open,
        'sla_warning_at' => now()->addHour(),
        'sla_due_at' => now()->addHours(5),
    ]);
    Ticket::factory()->create([
        'status' => TicketStatus::Open,
        'sla_warning_at' => now()->subHours(3),
        'sla_due_at' => now()->subHour(),
    ]);

    expect(Ticket::approachingSla()->pluck('id')->all())->toBe([$approaching->id]);
});

it('returns only breached tickets', function () {
    $breached = Ticket::factory()->create([
        'status' => TicketStatus::WaitingForCustomer,
        'sla_warning_at' => now()->subHours(3),
        'sla_due_at' => now()->subHour(),
    ]);
    Ticket::factory()->create([
        'status' => TicketStatus::Open,
        'sla_warning_at' => now()->addHour(),
        'sla_due_at' => now()->addHours(5),
    ]);
    Ticket::factory()->create([
        'status' => TicketStatus::Open,
        'sla_warning_at' => now()->subHour(),
        'sla_due_at' => now()->addHour(),
    ]);

    expect(Ticket::breachedSla()->pluck('id')->all())->toBe([$breached->id]);
});

it('never returns resolved or closed tickets from any SLA scope', function () {
    $resolvedApproaching = Ticket::factory()->create([
        'status' => TicketStatus::Resolved,
        'sla_warning_at' => now()->subHour(),
        'sla_due_at' => now()->addHour(),
    ]);
    $resolvedWithin = Ticket::factory()->create([
        'status' => TicketStatus::Resolved,
        'sla_warning_at' => now()->addHour(),
        'sla_due_at' => now()->addHours(5),
    ]);
    $closedBreached = Ticket::factory()->create([
        'status' => TicketStatus::Closed,
        'sla_warning_at' => now()->subHours(3),
        'sla_due_at' => now()->subHour(),
    ]);

    expect(Ticket::openForSla()->pluck('id')->all())->toBeEmpty()
        ->and(Ticket::withinSla()->pluck('id')->all())
        ->not->toContain($resolvedWithin->id)
        ->and(Ticket::approachingSla()->pluck('id')->all())
        ->not->toContain($resolvedApproaching->id)
        ->and(Ticket::breachedSla()->pluck('id')->all())
        ->not->toContain($closedBreached->id);
});

it('combines open, approaching, breached and within SLA tickets correctly', function () {
    $within = Ticket::factory()->create([
        'status' => TicketStatus::Open,
        'sla_warning_at' => now()->addHour(),
        'sla_due_at' => now()->addHours(5),
    ]);
    $approaching = Ticket::factory()->create([
        'status' => TicketStatus::InProgress,
        'sla_warning_at' => now()->subHour(),
        'sla_due_at' => now()->addHour(),
    ]);
    $breached = Ticket::factory()->create([
        'status' => TicketStatus::WaitingForCustomer,
        'sla_warning_at' => now()->subHours(3),
        'sla_due_at' => now()->subHour(),
    ]);
    $resolved = Ticket::factory()->create([
        'status' => TicketStatus::Resolved,
        'sla_warning_at' => now()->addHour(),
        'sla_due_at' => now()->addHours(5),
    ]);
    $closed = Ticket::factory()->create([
        'status' => TicketStatus::Closed,
        'sla_warning_at' => now()->subHours(3),
        'sla_due_at' => now()->subHour(),
    ]);

    expect(Ticket::openForSla()->orderBy('id')->pluck('id')->all())
        ->toBe([$within->id, $approaching->id, $breached->id])
        ->and(Ticket::openForSla()->count())->toBe(3)
        ->and(Ticket::withinSla()->count())->toBe(1)
        ->and(Ticket::approachingSla()->count())->toBe(1)
        ->and(Ticket::breachedSla()->count())->toBe(1)
        ->and(Ticket::openForSla()->pluck('id')->all())
        ->not->toContain($resolved->id)
        ->not->toContain($closed->id);
});
