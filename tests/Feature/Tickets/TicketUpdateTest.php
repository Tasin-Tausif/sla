<?php

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;

function updateScreenUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('lets the creator edit their own open ticket', function () {
    $customer = updateScreenUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'subject' => 'Old subject',
        'description' => 'Old description',
        'status' => TicketStatus::Open,
    ]);

    $category = Category::query()->where('slug', 'billing')->firstOrFail();

    $response = $this->actingAs($customer)->put(route('tickets.update', $ticket), [
        'subject' => 'New subject',
        'description' => 'New description',
        'category_id' => $category->id,
    ]);

    $response->assertRedirect(route('tickets.show', $ticket))
        ->assertSessionHas('status');

    $ticket->refresh();

    expect($ticket->subject)->toBe('New subject')
        ->and($ticket->description)->toBe('New description')
        ->and($ticket->category_id)->toBe($category->id)
        ->and($ticket->activities()->where('event', 'updated')->count())->toBe(1);
});

it('records only the changed fields in the activity log', function () {
    $customer = updateScreenUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'subject' => 'Same subject',
        'description' => 'Same description',
        'status' => TicketStatus::Open,
    ]);

    $this->actingAs($customer)->put(route('tickets.update', $ticket), [
        'subject' => 'Same subject',
        'description' => 'Same description',
        'category_id' => $ticket->category_id,
    ]);

    expect($ticket->activities()->where('event', 'updated')->count())->toBe(0);

    $this->actingAs($customer)->put(route('tickets.update', $ticket), [
        'subject' => 'Renamed subject',
        'description' => 'Same description',
        'category_id' => $ticket->category_id,
    ]);

    $activity = $ticket->activities()->where('event', 'updated')->firstOrFail();

    expect($activity->properties->get('old'))->toBe(['subject' => 'Same subject'])
        ->and($activity->properties->get('new'))->toBe(['subject' => 'Renamed subject']);
});

it('blocks a customer from editing somebody elses ticket', function () {
    $customer = updateScreenUser('customer');
    $other = updateScreenUser('customer');

    $ticket = Ticket::factory()->create(['created_by' => $other->id]);

    $this->actingAs($customer)
        ->put(route('tickets.update', $ticket), [
            'subject' => 'Hijacked',
            'description' => 'Nope',
            'category_id' => $ticket->category_id,
        ])
        ->assertForbidden();

    expect($ticket->refresh()->subject)->not->toBe('Hijacked');
});

it('blocks a customer from editing a ticket that is no longer open', function () {
    $customer = updateScreenUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::WaitingForCustomer,
    ]);

    $this->actingAs($customer)
        ->put(route('tickets.update', $ticket), [
            'subject' => 'Changed while waiting',
            'description' => 'Changed',
            'category_id' => $ticket->category_id,
        ])
        ->assertForbidden();

    expect($ticket->refresh()->subject)->not->toBe('Changed while waiting');
});

it('validates the update payload', function () {
    $customer = updateScreenUser('customer');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $this->actingAs($customer)
        ->from(route('tickets.edit', $ticket))
        ->put(route('tickets.update', $ticket), [
            'subject' => '',
            'description' => '',
            'category_id' => 999999,
        ])
        ->assertSessionHasErrors(['subject', 'description', 'category_id'])
        ->assertRedirect(route('tickets.edit', $ticket));

    expect($ticket->refresh()->subject)->not->toBe('');
});

it('lets an admin edit any ticket', function () {
    $admin = updateScreenUser('admin');
    $customer = updateScreenUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Resolved,
        'subject' => 'Needs a tweak',
    ]);

    $this->actingAs($admin)
        ->put(route('tickets.update', $ticket), [
            'subject' => 'Corrected subject',
            'description' => $ticket->description,
            'category_id' => $ticket->category_id,
        ])
        ->assertRedirect(route('tickets.show', $ticket));

    expect($ticket->refresh()->subject)->toBe('Corrected subject');
});

it('shows the edit screen to the creator and hides it from others', function () {
    $customer = updateScreenUser('customer');
    $other = updateScreenUser('customer');

    $mine = Ticket::factory()->create(['created_by' => $customer->id, 'status' => TicketStatus::Open]);
    $theirs = Ticket::factory()->create(['created_by' => $other->id, 'status' => TicketStatus::Open]);

    $this->actingAs($customer)->get(route('tickets.edit', $mine))->assertOk();
    $this->actingAs($customer)->get(route('tickets.edit', $theirs))->assertForbidden();
});
