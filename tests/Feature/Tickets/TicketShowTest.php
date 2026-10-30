<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\TicketReplyService;
use Illuminate\Support\Facades\Storage;

function showUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('redirects a guest away from the ticket page', function () {
    $ticket = Ticket::factory()->create();

    $this->get(route('tickets.show', $ticket))->assertRedirect(route('login'));
});

it('lets the creator read their own ticket', function () {
    $customer = showUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'subject' => 'Invoice question',
        'description' => 'Why was I charged twice in March?',
    ]);

    $this->actingAs($customer)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee($ticket->ticket_number)
        ->assertSee('Invoice question')
        ->assertSee('Why was I charged twice in March?')
        ->assertSee('Within SLA');
});

it('blocks a customer from opening another customers ticket', function () {
    $customer = showUser('customer');
    $other = showUser('customer');

    $ticket = Ticket::factory()->create(['created_by' => $other->id]);

    $this->actingAs($customer)
        ->get(route('tickets.show', $ticket))
        ->assertForbidden();
});

it('lets an agent open an unassigned ticket but not one assigned to someone else', function () {
    $agent = showUser('agent');
    $colleague = showUser('agent');

    $unassigned = Ticket::factory()->create(['assigned_agent_id' => null]);
    $taken = Ticket::factory()->create(['assigned_agent_id' => $colleague->id]);

    $this->actingAs($agent)->get(route('tickets.show', $unassigned))->assertOk();
    $this->actingAs($agent)->get(route('tickets.show', $taken))->assertForbidden();
});

it('hides internal notes from customers and shows them to staff', function () {
    $customer = showUser('customer');
    $agent = showUser('agent');
    $admin = showUser('admin');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $replyService = app(TicketReplyService::class);
    $replyService->add($ticket, $customer, 'My printer is offline.', false);
    $replyService->add($ticket, $agent, 'Customer replaced the cable.', true);

    $this->actingAs($customer)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('My printer is offline.')
        ->assertDontSee('Customer replaced the cable.')
        ->assertDontSee('Internal note');

    $this->actingAs($agent)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Customer replaced the cable.')
        ->assertSee('Internal note');

    $this->actingAs($admin)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Customer replaced the cable.');
});

it('keeps the note_added activity out of the customer timeline', function () {
    $customer = showUser('customer');
    $agent = showUser('agent');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $replyService = app(TicketReplyService::class);
    $replyService->add($ticket, $agent, 'Internal remark about the customer.', true);
    $replyService->add($ticket, $agent, 'A public answer for the customer.', false);

    $this->actingAs($customer)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('A public answer for the customer.')
        ->assertSee('Reply added')
        ->assertDontSee('Internal note added')
        ->assertDontSee('Internal remark about the customer.');

    $this->actingAs($admin = showUser('admin'))
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Internal note added');
});

it('does not offer a reply form on a closed ticket', function () {
    $customer = showUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Closed,
    ]);

    $this->actingAs($customer)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertDontSee('Your reply');
});

it('offers a reply form on an open ticket', function () {
    $customer = showUser('customer');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $this->actingAs($customer)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Your reply')
        ->assertSee(route('tickets.replies.store', $ticket), false);
});

it('serves a ticket level attachment to the creator and denies internal ones', function () {
    Storage::fake('local');

    $customer = showUser('customer');
    $agent = showUser('agent');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    Storage::disk('local')->put("tickets/{$ticket->id}/public.txt", 'public file');

    $public = Attachment::query()->create([
        'ticket_id' => $ticket->id,
        'reply_id' => null,
        'uploaded_by' => $customer->id,
        'original_name' => 'public.txt',
        'stored_path' => "tickets/{$ticket->id}/public.txt",
        'disk' => 'local',
        'mime_type' => 'text/plain',
        'size' => 12,
    ]);

    $note = TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'body' => 'Internal file attached.',
        'is_internal' => true,
    ]);

    Storage::disk('local')->put("tickets/{$ticket->id}/internal.txt", 'internal file');

    $internal = Attachment::query()->create([
        'ticket_id' => $ticket->id,
        'reply_id' => $note->id,
        'uploaded_by' => $agent->id,
        'original_name' => 'internal.txt',
        'stored_path' => "tickets/{$ticket->id}/internal.txt",
        'disk' => 'local',
        'mime_type' => 'text/plain',
        'size' => 15,
    ]);

    $this->actingAs($customer)
        ->get(route('attachments.download', $public))
        ->assertOk()
        ->assertDownload('public.txt');

    $this->actingAs($customer)
        ->get(route('attachments.download', $internal))
        ->assertForbidden();

    $this->actingAs($agent)
        ->get(route('attachments.download', $internal))
        ->assertOk()
        ->assertDownload('internal.txt');
});

it('hides the download link of an internal file from the customer', function () {
    Storage::fake('local');

    $customer = showUser('customer');
    $agent = showUser('agent');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $note = TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $agent->id,
        'body' => 'See the attached audit trail.',
        'is_internal' => true,
    ]);

    Storage::disk('local')->put("tickets/{$ticket->id}/audit.txt", 'audit');

    Attachment::query()->create([
        'ticket_id' => $ticket->id,
        'reply_id' => $note->id,
        'uploaded_by' => $agent->id,
        'original_name' => 'audit.txt',
        'stored_path' => "tickets/{$ticket->id}/audit.txt",
        'disk' => 'local',
        'mime_type' => 'text/plain',
        'size' => 5,
    ]);

    $this->actingAs($agent)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('audit.txt')
        ->assertSee(route('attachments.download', Attachment::query()->firstOrFail()), false);

    $this->actingAs($customer)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertDontSee('audit.txt');
});

it('shows the assignment controls to an admin', function () {
    $admin = showUser('admin');
    $agent = showUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    $this->actingAs($admin)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee(route('tickets.assign', $ticket), false)
        ->assertSee($agent->name)
        ->assertDontSee(route('tickets.claim', $ticket), false);
});

it('does not show assignment controls to the creator', function () {
    $customer = showUser('customer');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id, 'assigned_agent_id' => null]);

    $this->actingAs($customer)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertDontSee(route('tickets.assign', $ticket), false)
        ->assertDontSee(route('tickets.claim', $ticket), false);
});

it('shows badges for status priority and sla', function () {
    $admin = showUser('admin');

    $ticket = Ticket::factory()->create([
        'priority' => TicketPriority::Critical,
        'status' => TicketStatus::InProgress,
        'sla_due_at' => now()->subHour(),
        'sla_warning_at' => now()->subHours(2),
    ]);

    $this->actingAs($admin)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('In Progress')
        ->assertSee('Critical')
        ->assertSee('SLA breached');
});
