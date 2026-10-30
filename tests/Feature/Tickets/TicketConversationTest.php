<?php

use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function conversationUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('lets the creator reply to their open ticket', function () {
    $customer = conversationUser('customer');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $response = $this->actingAs($customer)->post(route('tickets.replies.store', $ticket), [
        'body' => 'Any news on this?',
    ]);

    $response->assertRedirect(route('tickets.show', $ticket))
        ->assertSessionHas('status');

    $reply = $ticket->replies()->firstOrFail();

    expect($reply->body)->toBe('Any news on this?')
        ->and($reply->user_id)->toBe($customer->id)
        ->and($reply->is_internal)->toBeFalse()
        ->and($reply->ticket_id)->toBe($ticket->id)
        ->and($ticket->refresh()->first_response_at)->toBeNull();
});

it('sets the first response timestamp on the first public staff reply', function () {
    $customer = conversationUser('customer');
    $agent = conversationUser('agent');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $this->actingAs($agent)->post(route('tickets.replies.store', $ticket), [
        'body' => 'We are looking into it.',
    ]);

    expect($ticket->refresh()->first_response_at)->not->toBeNull();

    $firstResponseAt = $ticket->first_response_at;

    $this->actingAs($agent)->post(route('tickets.replies.store', $ticket), [
        'body' => 'A second answer.',
    ]);

    expect($ticket->refresh()->first_response_at->equalTo($firstResponseAt))->toBeTrue();
});

it('stores an internal note for staff only', function () {
    $agent = conversationUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);

    $this->actingAs($agent)->post(route('tickets.replies.store', $ticket), [
        'body' => 'Do not show this to the customer.',
        'is_internal' => 1,
    ])->assertRedirect(route('tickets.show', $ticket));

    $reply = $ticket->replies()->firstOrFail();

    expect($reply->is_internal)->toBeTrue()
        ->and($ticket->refresh()->first_response_at)->toBeNull();
});

it('refuses an internal note from a customer', function () {
    $customer = conversationUser('customer');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $this->actingAs($customer)->post(route('tickets.replies.store', $ticket), [
        'body' => 'Pretending this is staff only.',
        'is_internal' => 1,
    ])->assertForbidden();

    expect($ticket->replies()->count())->toBe(0);
});

it('refuses a reply on a closed ticket', function () {
    $customer = conversationUser('customer');

    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Closed,
    ]);

    $this->actingAs($customer)->post(route('tickets.replies.store', $ticket), [
        'body' => 'Reopening this by replying.',
    ])->assertForbidden();

    expect($ticket->replies()->count())->toBe(0);
});

it('requires a body when replying', function () {
    $customer = conversationUser('customer');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $this->actingAs($customer)
        ->post(route('tickets.replies.store', $ticket), ['body' => ''])
        ->assertSessionHasErrors('body');

    expect($ticket->replies()->count())->toBe(0);
});

it('stores the attachments that come with a reply', function () {
    Storage::fake('local');

    $customer = conversationUser('customer');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $this->actingAs($customer)
        ->post(route('tickets.replies.store', $ticket), [
            'body' => 'Attached is the invoice.',
            'attachments' => [UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf')],
        ], ['attachments' => 'files'])
        ->assertRedirect(route('tickets.show', $ticket));

    $reply = $ticket->replies()->firstOrFail();
    $attachment = Attachment::query()->where('reply_id', $reply->id)->firstOrFail();

    expect($attachment->original_name)->toBe('invoice.pdf')
        ->and($attachment->uploaded_by)->toBe($customer->id)
        ->and($attachment->isInternal())->toBeFalse();

    Storage::disk('local')->assertExists($attachment->stored_path);
});

it('never shows an internal note to the customer after a staff reply', function () {
    $customer = conversationUser('customer');
    $agent = conversationUser('agent');

    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $this->actingAs($agent)->post(route('tickets.replies.store', $ticket), [
        'body' => 'Internal checklist completed.',
        'is_internal' => 1,
    ]);

    $this->actingAs($agent)->post(route('tickets.replies.store', $ticket), [
        'body' => 'Your account is active again.',
    ]);

    $this->actingAs($customer)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('Your account is active again.')
        ->assertDontSee('Internal checklist completed.');
});
