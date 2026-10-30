<?php

use App\Enums\TicketPriority;
use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;
use App\Services\TicketService;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed([BusinessHoursSeeder::class, CategorySeeder::class, SlaPolicySeeder::class]);
});

function eventUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function eventTicketData(): array
{
    return [
        'subject' => 'Something is broken',
        'description' => 'Please take a look at this issue.',
        'priority' => TicketPriority::Medium,
        'category_id' => Category::query()->where('slug', 'billing')->firstOrFail()->id,
    ];
}

it('dispatches TicketCreated exactly once when a ticket is created', function () {
    Event::fake([TicketCreated::class]);

    $ticket = app(TicketService::class)->create(eventUser('customer'), eventTicketData());

    Event::assertDispatchedTimes(TicketCreated::class, 1);
    Event::assertDispatched(
        TicketCreated::class,
        fn (TicketCreated $event): bool => $event->ticket->is($ticket),
    );

    expect(new TicketCreated($ticket))->toBeInstanceOf(ShouldDispatchAfterCommit::class)
        ->and(new TicketAssigned($ticket))->toBeInstanceOf(ShouldDispatchAfterCommit::class);
});

it('dispatches TicketAssigned once with the agent who claims the ticket', function () {
    Event::fake([TicketAssigned::class]);

    $agent = eventUser('agent');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    expect(app(TicketAssignmentService::class)->claim($ticket, $agent))->toBeTrue();

    Event::assertDispatchedTimes(TicketAssigned::class, 1);
    Event::assertDispatched(
        TicketAssigned::class,
        fn (TicketAssigned $event): bool => $event->ticket->is($ticket)
            && $event->agent?->is($agent),
    );
});

it('dispatches TicketAssigned once with the agent chosen by a manual assignment', function () {
    Event::fake([TicketAssigned::class]);

    $admin = eventUser('admin');
    $agent = eventUser('agent');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    app(TicketAssignmentService::class)->assignManually($ticket, $agent, $admin);

    Event::assertDispatchedTimes(TicketAssigned::class, 1);
    Event::assertDispatched(
        TicketAssigned::class,
        fn (TicketAssigned $event): bool => $event->ticket->is($ticket)
            && $event->agent?->is($agent),
    );
});

it('does not dispatch TicketAssigned when a manual assignment changes nothing', function () {
    $admin = eventUser('admin');
    $agent = eventUser('agent');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);

    Event::fake([TicketAssigned::class]);

    app(TicketAssignmentService::class)->assignManually($ticket, $agent, $admin);

    Event::assertNotDispatched(TicketAssigned::class);
});

it('dispatches TicketAssigned exactly once for an auto assignment', function () {
    $agent = eventUser('agent');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    Event::fake([TicketAssigned::class]);

    $result = app(TicketAssignmentService::class)->autoAssign($ticket);

    expect($result)->toBeTrue();

    Event::assertDispatchedTimes(TicketAssigned::class, 1);
    Event::assertDispatched(
        TicketAssigned::class,
        fn (TicketAssigned $event): bool => $event->agent?->is($agent),
    );
});

it('dispatches nothing when the claim race is lost', function () {
    $agent = eventUser('agent');
    $winner = eventUser('agent');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    // Another agent wins the race between loading the model and claiming.
    DB::table('tickets')->where('id', $ticket->id)->update(['assigned_agent_id' => $winner->id]);

    Event::fake([TicketAssigned::class]);

    expect(app(TicketAssignmentService::class)->claim($ticket, $agent))->toBeFalse();

    Event::assertNotDispatched(TicketAssigned::class);
});
