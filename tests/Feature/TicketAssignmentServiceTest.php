<?php

use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Sla\SlaConfigLoader;
use App\Services\TicketAssignmentService;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->seed([
        BusinessHoursSeeder::class,
        CategorySeeder::class,
        SlaPolicySeeder::class,
    ]);
    SlaConfigLoader::forget();
});

function assignmentUser(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

function assignmentService(): TicketAssignmentService
{
    return app(TicketAssignmentService::class);
}

it('claims an unassigned ticket', function () {
    Event::fake([TicketAssigned::class]);

    $agent = assignmentUser('agent', ['is_available' => true, 'max_open_tickets' => 10]);
    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    $claimed = assignmentService()->claim($ticket, $agent);

    $ticket->refresh();

    expect($claimed)->toBeTrue()
        ->and($ticket->assigned_agent_id)->toBe($agent->id)
        ->and($ticket->assigned_at)->not->toBeNull()
        ->and($ticket->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->activities()->where('event', 'assigned')->exists())->toBeTrue()
        ->and($agent->fresh()->last_assigned_at)->not->toBeNull();

    Event::assertDispatched(
        TicketAssigned::class,
        fn (TicketAssigned $event): bool => $event->ticket->is($ticket)
            && $event->agent?->is($agent),
    );
});

it('returns false when a second agent tries to claim the same ticket', function () {
    $first = assignmentUser('agent');
    $second = assignmentUser('agent');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    expect(assignmentService()->claim($ticket, $first))->toBeTrue()
        ->and(assignmentService()->claim($ticket, $second))->toBeFalse()
        ->and($ticket->fresh()->assigned_agent_id)->toBe($first->id);
});

it('returns false when the ticket was assigned after the model was loaded', function () {
    $agent = assignmentUser('agent');
    $otherAgent = assignmentUser('agent');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    DB::table('tickets')
        ->where('id', $ticket->id)
        ->update(['assigned_agent_id' => $otherAgent->id]);

    expect(assignmentService()->claim($ticket, $agent))->toBeFalse()
        ->and($ticket->fresh()->assigned_agent_id)->toBe($otherAgent->id);
});

it('only moves open tickets to in progress when claiming', function () {
    $agent = assignmentUser('agent');
    $ticket = Ticket::factory()->create([
        'assigned_agent_id' => null,
        'status' => TicketStatus::WaitingForCustomer,
    ]);

    expect(assignmentService()->claim($ticket, $agent))->toBeTrue()
        ->and($ticket->fresh()->status)->toBe(TicketStatus::WaitingForCustomer);
});

it('auto assigns to the agent with the fewest open tickets', function () {
    $busy = assignmentUser('agent');
    $lessBusy = assignmentUser('agent');
    $free = assignmentUser('agent');

    Ticket::factory()->count(2)->create(['assigned_agent_id' => $busy->id]);
    Ticket::factory()->create(['assigned_agent_id' => $lessBusy->id]);

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    expect(assignmentService()->autoAssign($ticket))->toBeTrue()
        ->and($ticket->fresh()->assigned_agent_id)->toBe($free->id);
});

it('breaks auto assignment ties by the least recently assigned agent', function () {
    $recent = assignmentUser('agent', ['last_assigned_at' => now()->subHour()]);
    $older = assignmentUser('agent', ['last_assigned_at' => now()->subDay()]);

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    expect(assignmentService()->autoAssign($ticket))->toBeTrue()
        ->and($ticket->fresh()->assigned_agent_id)->toBe($older->id)
        ->and($recent->id)->not->toBe($ticket->fresh()->assigned_agent_id);
});

it('prefers agents who have never been assigned when tie breaking', function () {
    $assignedBefore = assignmentUser('agent', ['last_assigned_at' => now()->subDay()]);
    $neverAssigned = assignmentUser('agent', ['last_assigned_at' => null]);

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    expect(assignmentService()->autoAssign($ticket))->toBeTrue()
        ->and($ticket->fresh()->assigned_agent_id)->toBe($neverAssigned->id)
        ->and($assignedBefore->id)->not->toBe($ticket->fresh()->assigned_agent_id);
});

it('skips unavailable agents and agents at their maximum open tickets', function () {
    $unavailable = assignmentUser('agent', ['is_available' => false]);
    $atMaximum = assignmentUser('agent', ['max_open_tickets' => 1]);

    Ticket::factory()->create(['assigned_agent_id' => $atMaximum->id]);

    $eligible = assignmentUser('agent', ['max_open_tickets' => 2]);
    Ticket::factory()->count(1)->create(['assigned_agent_id' => $eligible->id]);

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    expect(assignmentService()->autoAssign($ticket))->toBeTrue()
        ->and($ticket->fresh()->assigned_agent_id)->toBe($eligible->id)
        ->and($ticket->fresh()->assigned_agent_id)->not->toBe($unavailable->id)
        ->and($ticket->fresh()->assigned_agent_id)->not->toBe($atMaximum->id);
});

it('leaves the ticket unassigned and logs a warning when no agent is eligible', function () {
    Log::spy();

    assignmentUser('agent', ['is_available' => false]);

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    expect(assignmentService()->autoAssign($ticket))->toBeFalse()
        ->and($ticket->fresh()->assigned_agent_id)->toBeNull();

    Log::shouldHaveReceived('warning');
});

it('allows an administrator to reassign an already assigned ticket', function () {
    $admin = assignmentUser('admin');
    $firstAgent = assignmentUser('agent');
    $secondAgent = assignmentUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => $firstAgent->id]);

    assignmentService()->assignManually($ticket, $secondAgent, $admin);

    expect($ticket->fresh()->assigned_agent_id)->toBe($secondAgent->id)
        ->and($ticket->activities()->where('event', 'unassigned')->exists())->toBeTrue()
        ->and($ticket->activities()->where('event', 'assigned')->exists())->toBeTrue();
});

it('does not allow an agent to reassign a ticket manually', function () {
    $actor = assignmentUser('agent');
    $target = assignmentUser('agent');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => $actor->id]);

    expect(fn () => assignmentService()->assignManually($ticket, $target, $actor))
        ->toThrow(AuthorizationException::class);

    expect($ticket->fresh()->assigned_agent_id)->toBe($actor->id);
});

it('requires the manual assignment target to have the agent role', function () {
    $admin = assignmentUser('admin');
    $customer = assignmentUser('customer');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    expect(fn () => assignmentService()->assignManually($ticket, $customer, $admin))
        ->toThrow(InvalidArgumentException::class);

    expect($ticket->fresh()->assigned_agent_id)->toBeNull();
});
