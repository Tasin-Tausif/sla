<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

function actionScreenUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('lets an agent claim an unassigned ticket', function () {
    $agent = actionScreenUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    $this->actingAs($agent)
        ->post(route('tickets.claim', $ticket))
        ->assertRedirect()
        ->assertSessionHas('status');

    $ticket->refresh();

    expect($ticket->assigned_agent_id)->toBe($agent->id)
        ->and($ticket->assigned_at)->not->toBeNull()
        ->and($ticket->activities()->where('event', 'assigned')->count())->toBe(1);
});

it('refuses a claim on a ticket that is already taken', function () {
    $agent = actionScreenUser('agent');
    $colleague = actionScreenUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => $colleague->id]);

    $this->actingAs($agent)
        ->post(route('tickets.claim', $ticket))
        ->assertForbidden();

    expect($ticket->refresh()->assigned_agent_id)->toBe($colleague->id);
});

it('refuses a claim from a customer', function () {
    $customer = actionScreenUser('customer');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    $this->actingAs($customer)
        ->post(route('tickets.claim', $ticket))
        ->assertForbidden();

    expect($ticket->refresh()->assigned_agent_id)->toBeNull();
});

it('lets an admin hand a ticket to an agent', function () {
    $admin = actionScreenUser('admin');
    $agent = actionScreenUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    $this->actingAs($admin)
        ->post(route('tickets.assign', $ticket), ['agent_id' => $agent->id])
        ->assertRedirect()
        ->assertSessionHas('status');

    $ticket->refresh();

    expect($ticket->assigned_agent_id)->toBe($agent->id)
        ->and($ticket->assigned_at)->not->toBeNull();
});

it('refuses manual assignment from an agent', function () {
    $agent = actionScreenUser('agent');
    $colleague = actionScreenUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    $this->actingAs($agent)
        ->post(route('tickets.assign', $ticket), ['agent_id' => $colleague->id])
        ->assertForbidden();

    expect($ticket->refresh()->assigned_agent_id)->toBeNull();
});

it('rejects an assignee that is not a user', function () {
    $admin = actionScreenUser('admin');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    $this->actingAs($admin)
        ->post(route('tickets.assign', $ticket), ['agent_id' => 999999])
        ->assertSessionHasErrors('agent_id');

    expect($ticket->refresh()->assigned_agent_id)->toBeNull();
});

it('lets the staff owner change the priority and recompute the sla', function () {
    $agent = actionScreenUser('agent');

    $ticket = Ticket::factory()->create([
        'assigned_agent_id' => $agent->id,
        'priority' => TicketPriority::Low,
    ]);

    $oldDueAt = $ticket->sla_due_at;

    $this->actingAs($agent)
        ->patch(route('tickets.priority', $ticket), ['priority' => TicketPriority::Critical->value])
        ->assertRedirect()
        ->assertSessionHas('status');

    $ticket->refresh();

    expect($ticket->priority)->toBe(TicketPriority::Critical)
        ->and($ticket->sla_minutes)->toBe(2 * 60)
        ->and($ticket->sla_due_at->lessThan($oldDueAt))->toBeTrue()
        ->and($ticket->activities()->where('event', 'priority_changed')->count())->toBe(1);
});

it('refuses a priority change from a customer', function () {
    $customer = actionScreenUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'priority' => TicketPriority::Low,
    ]);

    $this->actingAs($customer)
        ->patch(route('tickets.priority', $ticket), ['priority' => TicketPriority::Critical->value])
        ->assertForbidden();

    expect($ticket->refresh()->priority)->toBe(TicketPriority::Low);
});

it('rejects an unknown priority value', function () {
    $agent = actionScreenUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);

    $this->actingAs($agent)
        ->patch(route('tickets.priority', $ticket), ['priority' => 'sky-high'])
        ->assertSessionHasErrors('priority');
});

it('lets the staff owner resolve and reopen a ticket', function () {
    $agent = actionScreenUser('agent');

    $ticket = Ticket::factory()->create([
        'assigned_agent_id' => $agent->id,
        'status' => TicketStatus::InProgress,
    ]);

    $this->actingAs($agent)
        ->patch(route('tickets.status', $ticket), ['status' => TicketStatus::Resolved->value])
        ->assertSessionHas('status');

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Resolved)
        ->and($ticket->resolved_at)->not->toBeNull()
        ->and($ticket->resolved_by)->toBe($agent->id);

    $this->actingAs($agent)
        ->patch(route('tickets.status', $ticket), ['status' => TicketStatus::Open->value])
        ->assertSessionHas('status');

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->resolved_at)->toBeNull()
        ->and($ticket->activities()->where('event', 'reopened')->count())->toBe(1);
});

it('lets the creator close their resolved ticket', function () {
    $customer = actionScreenUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Resolved,
        'resolved_at' => now(),
    ]);

    $this->actingAs($customer)
        ->patch(route('tickets.status', $ticket), ['status' => TicketStatus::Closed->value])
        ->assertSessionHas('status');

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Closed)
        ->and($ticket->closed_at)->not->toBeNull()
        ->and($ticket->closed_by)->toBe($customer->id);
});

it('refuses a status change from a customer on an open ticket', function () {
    $customer = actionScreenUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Open,
    ]);

    $this->actingAs($customer)
        ->patch(route('tickets.status', $ticket), ['status' => TicketStatus::Resolved->value])
        ->assertForbidden();

    expect($ticket->refresh()->status)->toBe(TicketStatus::Open);
});

it('rejects an unknown status value', function () {
    $agent = actionScreenUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);

    $this->actingAs($agent)
        ->patch(route('tickets.status', $ticket), ['status' => 'on-fire'])
        ->assertSessionHasErrors('status');
});

it('records a status change in the activity log', function () {
    $agent = actionScreenUser('agent');

    $ticket = Ticket::factory()->create([
        'assigned_agent_id' => $agent->id,
        'status' => TicketStatus::Open,
    ]);

    $this->actingAs($agent)
        ->patch(route('tickets.status', $ticket), ['status' => TicketStatus::WaitingForCustomer->value]);

    $activity = $ticket->activities()->where('event', 'status_changed')->firstOrFail();

    expect($activity->properties->get('old'))->toBe(['status' => 'open'])
        ->and($activity->properties->get('new'))->toBe(['status' => 'waiting_for_customer'])
        ->and($activity->causer_id)->toBe($agent->id);
});
