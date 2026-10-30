<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->seed([BusinessHoursSeeder::class, SlaPolicySeeder::class]);
    Carbon::setTestNow(Carbon::parse('2026-01-15 12:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('mirrors the SLA scopes in slaState', function () {
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
        'status' => TicketStatus::Open,
        'sla_warning_at' => now()->subHours(3),
        'sla_due_at' => now()->subHour(),
    ]);
    $resolved = Ticket::factory()->create([
        'status' => TicketStatus::Resolved,
        'sla_warning_at' => now()->subHours(3),
        'sla_due_at' => now()->subHour(),
    ]);

    expect($within->slaState())->toBe('within')
        ->and($approaching->slaState())->toBe('approaching')
        ->and($breached->slaState())->toBe('breached')
        ->and($resolved->slaState())->toBe('resolved')
        ->and(Ticket::withinSla()->pluck('id')->all())->toBe([$within->id])
        ->and(Ticket::approachingSla()->pluck('id')->all())->toBe([$approaching->id])
        ->and(Ticket::breachedSla()->pluck('id')->all())->toBe([$breached->id]);
});

it('renders the ticket badge components', function () {
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::Critical,
        'sla_warning_at' => now()->addHour(),
        'sla_due_at' => now()->addHours(5),
    ]);

    $html = Blade::render(
        '<x-ticket.status-badge :ticket="$ticket" /><x-ticket.priority-badge :ticket="$ticket" /><x-ticket.sla-badge :ticket="$ticket" />',
        ['ticket' => $ticket],
    );

    expect($html)->toContain('Open')
        ->toContain('Critical')
        ->toContain('Within SLA');
});
