<?php

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\Sla\SlaConfigLoader;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SlaPolicySeeder;

beforeEach(function () {
    $this->seed([
        BusinessHoursSeeder::class,
        CategorySeeder::class,
        SlaPolicySeeder::class,
    ]);
    SlaConfigLoader::forget();
});

function visibleRoleUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('shows all tickets to administrators', function () {
    $admin = visibleRoleUser('admin');
    $agent = visibleRoleUser('agent');
    $customer = visibleRoleUser('customer');

    $ticketA = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);
    $ticketB = Ticket::factory()->create(['assigned_agent_id' => null]);
    $ticketC = Ticket::factory()->create(['created_by' => $customer->id]);

    expect(Ticket::query()->visibleTo($admin)->pluck('id')->all())
        ->toEqualCanonicalizing([$ticketA->id, $ticketB->id, $ticketC->id]);
});

it('shows unassigned and own tickets to agents', function () {
    $agent = visibleRoleUser('agent');
    $otherAgent = visibleRoleUser('agent');

    $own = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);
    $unassigned = Ticket::factory()->create(['assigned_agent_id' => null]);
    $other = Ticket::factory()->create(['assigned_agent_id' => $otherAgent->id]);

    expect(Ticket::query()->visibleTo($agent)->pluck('id')->all())
        ->toEqualCanonicalizing([$own->id, $unassigned->id])
        ->not->toContain($other->id);
});

it('shows only their own tickets to customers', function () {
    $customer = visibleRoleUser('customer');
    $otherCustomer = visibleRoleUser('customer');

    $own = Ticket::factory()->create(['created_by' => $customer->id]);
    $other = Ticket::factory()->create(['created_by' => $otherCustomer->id]);

    expect(Ticket::query()->visibleTo($customer)->pluck('id')->all())
        ->toBe([$own->id])
        ->not->toContain($other->id);
});

it('hides internal replies from customers', function () {
    $customer = visibleRoleUser('customer');
    $agent = visibleRoleUser('agent');
    $admin = visibleRoleUser('admin');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'body' => 'Public reply',
        'is_internal' => false,
    ]);

    TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'body' => 'Internal note',
        'is_internal' => true,
    ]);

    expect($ticket->visibleReplies($customer)->pluck('body')->all())
        ->toBe(['Public reply'])
        ->and($ticket->visibleReplies($agent)->count())->toBe(2)
        ->and($ticket->visibleReplies($admin)->count())->toBe(2);
});

it('uses one internal-note visibility rule for visibleReplies and TicketReply::visibleTo', function () {
    $customer = visibleRoleUser('customer');
    $agent = visibleRoleUser('agent');
    $admin = visibleRoleUser('admin');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'body' => 'Public reply',
        'is_internal' => false,
    ]);

    TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'body' => 'Internal note',
        'is_internal' => true,
    ]);

    expect($ticket->visibleReplies($customer)->pluck('body')->all())
        ->toEqualCanonicalizing(['Public reply'])
        ->and($ticket->visibleReplies($agent)->pluck('body')->all())
        ->toEqualCanonicalizing(['Public reply', 'Internal note'])
        ->and($ticket->visibleReplies($admin)->pluck('body')->all())
        ->toEqualCanonicalizing(['Public reply', 'Internal note'])
        ->and(TicketReply::query()->visibleTo($customer)->pluck('body')->all())
        ->toEqualCanonicalizing(['Public reply'])
        ->and(TicketReply::query()->visibleTo($agent)->pluck('body')->all())
        ->toEqualCanonicalizing(['Public reply', 'Internal note'])
        ->and(TicketReply::query()->visibleTo($admin)->pluck('body')->all())
        ->toEqualCanonicalizing(['Public reply', 'Internal note']);
});
