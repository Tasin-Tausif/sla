<?php

use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\Sla\SlaConfigLoader;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed([
        BusinessHoursSeeder::class,
        CategorySeeder::class,
        SlaPolicySeeder::class,
    ]);
    SlaConfigLoader::forget();
});

function policyUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('allows every authenticated role to view the ticket list', function (string $role) {
    $user = policyUser($role);

    expect($user->can('viewAny', Ticket::class))->toBeTrue();
})->with(['admin', 'agent', 'customer']);

it('lets a customer update only their own open ticket', function () {
    $customer = policyUser('customer');
    $other = policyUser('customer');

    $open = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Open,
    ]);
    $inProgress = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::InProgress,
    ]);
    $anotherCustomers = Ticket::factory()->create([
        'created_by' => $other->id,
        'status' => TicketStatus::Open,
    ]);

    expect($customer->can('update', $open))->toBeTrue()
        ->and($customer->can('update', $inProgress))->toBeFalse()
        ->and($customer->can('update', $anotherCustomers))->toBeFalse();
});

it('prevents a customer from viewing another customer ticket', function () {
    $customer = policyUser('customer');
    $other = policyUser('customer');
    $ticket = Ticket::factory()->create(['created_by' => $other->id]);

    expect($customer->can('view', $ticket))->toBeFalse();
});

it('prevents customers from performing staff and administrative actions', function () {
    $customer = policyUser('customer');
    $agent = policyUser('agent');
    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'assigned_agent_id' => $agent->id,
        'status' => TicketStatus::Resolved,
    ]);

    expect($customer->can('addInternalNote', $ticket))->toBeFalse()
        ->and($customer->can('resolve', $ticket))->toBeFalse()
        ->and($customer->can('changeStatus', $ticket))->toBeFalse()
        ->and($customer->can('changePriority', $ticket))->toBeFalse()
        ->and($customer->can('claim', $ticket))->toBeFalse()
        ->and($customer->can('assign', $ticket))->toBeFalse();
});

it('lets a customer close only their own resolved ticket', function () {
    $customer = policyUser('customer');
    $other = policyUser('customer');

    $ownResolved = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Resolved,
    ]);
    $ownOpen = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Open,
    ]);
    $otherResolved = Ticket::factory()->create([
        'created_by' => $other->id,
        'status' => TicketStatus::Resolved,
    ]);

    expect($customer->can('close', $ownResolved))->toBeTrue()
        ->and($customer->can('close', $ownOpen))->toBeFalse()
        ->and($customer->can('close', $otherResolved))->toBeFalse();
});

it('permits agents to claim unassigned open tickets only', function () {
    $agent = policyUser('agent');
    $other = policyUser('agent');

    $unassigned = Ticket::factory()->create([
        'assigned_agent_id' => null,
        'status' => TicketStatus::Open,
    ]);
    $assigned = Ticket::factory()->create([
        'assigned_agent_id' => $other->id,
        'status' => TicketStatus::Open,
    ]);
    $resolved = Ticket::factory()->create([
        'assigned_agent_id' => null,
        'status' => TicketStatus::Resolved,
    ]);

    expect($agent->can('claim', $unassigned))->toBeTrue()
        ->and($agent->can('claim', $assigned))->toBeFalse()
        ->and($agent->can('claim', $resolved))->toBeFalse();
});

it('prevents an agent from viewing or updating a ticket assigned to another agent', function () {
    $agent = policyUser('agent');
    $other = policyUser('agent');
    $ticket = Ticket::factory()->create(['assigned_agent_id' => $other->id]);

    expect($agent->can('view', $ticket))->toBeFalse()
        ->and($agent->can('update', $ticket))->toBeFalse();
});

it('permits agents to view, reply to and claim unassigned tickets', function () {
    $agent = policyUser('agent');
    $ticket = Ticket::factory()->create([
        'assigned_agent_id' => null,
        'status' => TicketStatus::Open,
    ]);

    expect($agent->can('view', $ticket))->toBeTrue()
        ->and($agent->can('claim', $ticket))->toBeTrue()
        ->and($agent->can('reply', $ticket))->toBeTrue();
});

it('restricts agent actions to their assigned tickets and forbids assignment', function () {
    $agent = policyUser('agent');
    $other = policyUser('agent');
    $own = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);
    $otherTicket = Ticket::factory()->create(['assigned_agent_id' => $other->id]);

    expect($agent->can('update', $own))->toBeTrue()
        ->and($agent->can('changePriority', $own))->toBeTrue()
        ->and($agent->can('changeStatus', $own))->toBeTrue()
        ->and($agent->can('resolve', $own))->toBeTrue()
        ->and($agent->can('close', $own))->toBeTrue()
        ->and($agent->can('reopen', $own))->toBeTrue()
        ->and($agent->can('changePriority', $otherTicket))->toBeFalse()
        ->and($agent->can('resolve', $otherTicket))->toBeFalse()
        ->and($agent->can('assign', $own))->toBeFalse();
});

it('allows administrators to assign, resolve, close and reopen any ticket', function () {
    $admin = policyUser('admin');
    $agent = policyUser('agent');
    $ticket = Ticket::factory()->create([
        'assigned_agent_id' => $agent->id,
        'status' => TicketStatus::Open,
    ]);

    expect($admin->can('viewAny', Ticket::class))->toBeTrue()
        ->and($admin->can('view', $ticket))->toBeTrue()
        ->and($admin->can('create', Ticket::class))->toBeTrue()
        ->and($admin->can('update', $ticket))->toBeTrue()
        ->and($admin->can('reply', $ticket))->toBeTrue()
        ->and($admin->can('addInternalNote', $ticket))->toBeTrue()
        ->and($admin->can('assign', $ticket))->toBeTrue()
        ->and($admin->can('changePriority', $ticket))->toBeTrue()
        ->and($admin->can('changeStatus', $ticket))->toBeTrue()
        ->and($admin->can('resolve', $ticket))->toBeTrue()
        ->and($admin->can('close', $ticket))->toBeTrue()
        ->and($admin->can('reopen', $ticket))->toBeTrue();
});

it('does not let anyone reply to a closed ticket', function () {
    $customer = policyUser('customer');
    $agent = policyUser('agent');
    $admin = policyUser('admin');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'assigned_agent_id' => $agent->id,
        'status' => TicketStatus::Closed,
    ]);

    expect($customer->can('reply', $ticket))->toBeFalse()
        ->and($agent->can('reply', $ticket))->toBeFalse()
        ->and($admin->can('reply', $ticket))->toBeFalse();
});

it('only exposes internal attachments to agents and administrators', function () {
    $customer = policyUser('customer');
    $agent = policyUser('agent');
    $admin = policyUser('admin');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'assigned_agent_id' => $agent->id,
    ]);

    $reply = TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'body' => 'Private note',
        'is_internal' => true,
    ]);

    $attachment = Attachment::create([
        'ticket_id' => $ticket->id,
        'reply_id' => $reply->id,
        'uploaded_by' => $agent->id,
        'original_name' => 'private.pdf',
        'stored_path' => 'attachments/private.pdf',
        'disk' => 'local',
        'mime_type' => 'application/pdf',
        'size' => 1024,
    ]);

    expect($customer->can('downloadAttachment', [$ticket, $attachment]))->toBeFalse()
        ->and($agent->can('downloadAttachment', [$ticket, $attachment]))->toBeTrue()
        ->and($admin->can('downloadAttachment', [$ticket, $attachment]))->toBeTrue();
});

it('redirects unauthenticated users away from protected ticket routes', function () {
    $ticket = Ticket::factory()->create();

    Route::middleware(['web', 'auth'])->get(
        '/__test-tickets/{ticket}',
        fn (Ticket $ticket): string => (string) $ticket->id,
    );

    $this->get('/__test-tickets/'.$ticket->id)->assertRedirect('/login');
});
