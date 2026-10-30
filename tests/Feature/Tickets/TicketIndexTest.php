<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

function indexUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('redirects guests to the login screen', function () {
    $this->get(route('tickets.index'))->assertRedirect(route('login'));
    $this->get(route('tickets.create'))->assertRedirect(route('login'));
});

it('shows a customer only their own tickets', function () {
    $customer = indexUser('customer');
    $other = indexUser('customer');

    $mine = Ticket::factory()->create(['created_by' => $customer->id]);
    $theirs = Ticket::factory()->create(['created_by' => $other->id]);

    $this->actingAs($customer)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee($mine->ticket_number)
        ->assertDontSee($theirs->ticket_number);
});

it('shows an agent unassigned tickets and their own, but not other agents tickets', function () {
    $agent = indexUser('agent');
    $colleague = indexUser('agent');

    $unassigned = Ticket::factory()->create(['assigned_agent_id' => null]);
    $mine = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);
    $colleagues = Ticket::factory()->create(['assigned_agent_id' => $colleague->id]);

    $this->actingAs($agent)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee($unassigned->ticket_number)
        ->assertSee($mine->ticket_number)
        ->assertDontSee($colleagues->ticket_number);
});

it('shows an admin every ticket', function () {
    $admin = indexUser('admin');
    $agent = indexUser('agent');
    $customer = indexUser('customer');

    $first = Ticket::factory()->create(['assigned_agent_id' => $agent->id, 'created_by' => $customer->id]);
    $second = Ticket::factory()->create(['assigned_agent_id' => null]);

    $this->actingAs($admin)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee($first->ticket_number)
        ->assertSee($second->ticket_number);
});

it('separates the active and closed tabs', function () {
    $customer = indexUser('customer');

    $open = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Open,
    ]);
    $waiting = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::WaitingForCustomer,
    ]);
    $resolved = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Resolved,
    ]);
    $closed = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Closed,
    ]);

    $this->actingAs($customer)
        ->get(route('tickets.index', ['view' => 'active']))
        ->assertOk()
        ->assertSee($open->ticket_number)
        ->assertSee($waiting->ticket_number)
        ->assertDontSee($resolved->ticket_number)
        ->assertDontSee($closed->ticket_number);

    $this->actingAs($customer)
        ->get(route('tickets.index', ['view' => 'closed']))
        ->assertOk()
        ->assertSee($resolved->ticket_number)
        ->assertSee($closed->ticket_number)
        ->assertDontSee($open->ticket_number)
        ->assertDontSee($waiting->ticket_number);
});

it('lists active tickets by soonest SLA deadline first', function () {
    $customer = indexUser('customer');

    $later = Ticket::factory()->create([
        'created_by' => $customer->id,
        'sla_due_at' => now()->addDay(),
    ]);
    $sooner = Ticket::factory()->create([
        'created_by' => $customer->id,
        'sla_due_at' => now()->addHour(),
    ]);

    $html = $this->actingAs($customer)
        ->get(route('tickets.index'))
        ->assertOk()
        ->getContent();

    expect(strpos($html, $sooner->ticket_number))
        ->toBeLessThan(strpos($html, $later->ticket_number));
});

it('shows the empty state for a customer without tickets', function () {
    $customer = indexUser('customer');

    $this->actingAs($customer)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee('No active tickets found.');
});
