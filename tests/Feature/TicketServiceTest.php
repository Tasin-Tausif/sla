<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Models\Category;
use App\Models\User;
use App\Services\Sla\SlaCalculator;
use App\Services\TicketService;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed([BusinessHoursSeeder::class, CategorySeeder::class, SlaPolicySeeder::class]);
    Carbon::setTestNow(Carbon::parse('2026-01-04 09:00:00', 'Asia/Dhaka'));
});

afterEach(function () {
    Carbon::setTestNow();
});

function ticketData(TicketPriority $priority = TicketPriority::High): array
{
    return [
        'subject' => 'Invoice issue',
        'description' => 'Please help with this invoice.',
        'category_id' => Category::query()->where('slug', 'billing')->firstOrFail()->id,
        'priority' => $priority,
    ];
}

it('creates a ticket with a unique number, SLA fields and a created activity', function () {
    Event::fake([TicketCreated::class]);

    $creator = User::factory()->create();
    $service = app(TicketService::class);

    $first = $service->create($creator, ticketData(TicketPriority::High));
    $second = $service->create($creator, ticketData(TicketPriority::High));

    $expected = app(SlaCalculator::class)->forPriority(TicketPriority::High, $first->created_at);

    expect($first->ticket_number)->toBe('TKT-2026-000001')
        ->and($second->ticket_number)->toBe('TKT-2026-000002')
        ->and($first->status)->toBe(TicketStatus::Open)
        ->and($first->priority)->toBe(TicketPriority::High)
        ->and($first->created_by)->toBe($creator->id)
        ->and($first->sla_policy_id)->toBe($expected['policy_id'])
        ->and($first->sla_minutes)->toBe(480)
        ->and($first->sla_due_at->equalTo($expected['due_at']))->toBeTrue()
        ->and($first->sla_warning_at->equalTo($expected['warning_at']))->toBeTrue()
        ->and($first->activities()->where('event', 'created')->exists())->toBeTrue();

    Event::assertDispatched(
        TicketCreated::class,
        fn (TicketCreated $event): bool => $event->ticket->is($first),
    );
});

it('recomputes SLA and resets tracking when the new due time is in the future', function () {
    $actor = User::factory()->create();
    $service = app(TicketService::class);
    $ticket = $service->create($actor, ticketData(TicketPriority::Critical));

    $ticket->forceFill([
        'sla_warning_sent_at' => now(),
        'sla_breached_at' => now(),
        'escalated_at' => now(),
        'escalation_level' => 2,
    ])->save();

    Carbon::setTestNow(Carbon::parse('2026-01-04 10:00:00', 'Asia/Dhaka'));

    $service->changePriority($ticket, TicketPriority::Low, $actor);

    $expected = app(SlaCalculator::class)->forPriority(TicketPriority::Low, $ticket->created_at);

    expect($ticket->priority)->toBe(TicketPriority::Low)
        ->and($ticket->sla_minutes)->toBe(2880)
        ->and($ticket->sla_policy_id)->toBe($expected['policy_id'])
        ->and($ticket->sla_due_at->equalTo($expected['due_at']))->toBeTrue()
        ->and($ticket->sla_warning_at->equalTo($expected['warning_at']))->toBeTrue()
        ->and($ticket->sla_warning_sent_at)->toBeNull()
        ->and($ticket->sla_breached_at)->toBeNull()
        ->and($ticket->escalated_at)->toBeNull()
        ->and($ticket->escalation_level)->toBe(0)
        ->and($ticket->activities()->where('event', 'priority_changed')->exists())->toBeTrue();
});

it('does not reset tracking when the new due time is already in the past', function () {
    $actor = User::factory()->create();
    $service = app(TicketService::class);
    $ticket = $service->create($actor, ticketData(TicketPriority::Critical));

    $ticket->forceFill([
        'sla_warning_sent_at' => now(),
        'sla_breached_at' => now(),
        'escalated_at' => now(),
        'escalation_level' => 2,
    ])->save();

    Carbon::setTestNow(Carbon::parse('2026-01-20 09:00:00', 'Asia/Dhaka'));

    $service->changePriority($ticket, TicketPriority::Low, $actor);

    expect($ticket->sla_warning_sent_at)->not->toBeNull()
        ->and($ticket->sla_breached_at)->not->toBeNull()
        ->and($ticket->escalated_at)->not->toBeNull()
        ->and($ticket->escalation_level)->toBe(2);
});

it('sets resolved_at and logs when a ticket is resolved', function () {
    $actor = User::factory()->create();
    $service = app(TicketService::class);
    $ticket = $service->create($actor, ticketData());

    Carbon::setTestNow(Carbon::parse('2026-01-04 10:00:00', 'Asia/Dhaka'));

    $service->changeStatus($ticket, TicketStatus::Resolved, $actor);

    expect($ticket->status)->toBe(TicketStatus::Resolved)
        ->and($ticket->resolved_at)->not->toBeNull()
        ->and($ticket->resolved_by)->toBe($actor->id)
        ->and($ticket->activities()->where('event', 'resolved')->exists())->toBeTrue();

    $service->changeStatus($ticket, TicketStatus::InProgress, $actor);

    expect($ticket->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->resolved_at)->toBeNull()
        ->and($ticket->resolved_by)->toBeNull()
        ->and($ticket->activities()->where('event', 'reopened')->exists())->toBeTrue();
});
