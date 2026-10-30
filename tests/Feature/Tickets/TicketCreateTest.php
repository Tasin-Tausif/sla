<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createScreenUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('customer');

    return $user;
}

/**
 * @return array<string, mixed>
 */
function ticketPayload(array $overrides = []): array
{
    return [
        'subject' => 'Printer is offline',
        'description' => 'The office printer stopped responding this morning.',
        'priority' => TicketPriority::High->value,
        'category_id' => Category::query()->where('slug', 'technical-support')->firstOrFail()->id,
        ...$overrides,
    ];
}

it('lets a customer open a new ticket', function () {
    $customer = createScreenUser();

    $response = $this->actingAs($customer)->post(route('tickets.store'), ticketPayload());

    $ticket = Ticket::query()->firstOrFail();

    $response->assertRedirect(route('tickets.show', $ticket))
        ->assertSessionHas('status');

    expect($ticket->created_by)->toBe($customer->id)
        ->and($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->priority)->toBe(TicketPriority::High)
        ->and($ticket->subject)->toBe('Printer is offline')
        ->and($ticket->ticket_number)->toStartWith('TKT-')
        ->and($ticket->first_response_at)->toBeNull()
        ->and($ticket->assigned_agent_id)->toBeNull()
        ->and($ticket->sla_due_at)->not->toBeNull();
});

it('rejects a ticket payload that fails validation', function (array $overrides, string $field) {
    $customer = createScreenUser();

    $response = $this->actingAs($customer)
        ->post(route('tickets.store'), ticketPayload($overrides));

    $response->assertSessionHasErrors($field);

    expect(Ticket::query()->count())->toBe(0);
})->with([
    'missing subject' => [['subject' => ''], 'subject'],
    'missing description' => [['description' => ''], 'description'],
    'unknown priority' => [['priority' => 'urgent'], 'priority'],
    'unknown category' => [['category_id' => 999999], 'category_id'],
]);

it('refuses an inactive category', function () {
    $customer = createScreenUser();

    $category = Category::query()->where('slug', 'feature-request')->firstOrFail();
    $category->update(['is_active' => false]);

    $this->actingAs($customer)
        ->post(route('tickets.store'), ticketPayload(['category_id' => $category->id]))
        ->assertSessionHasErrors('category_id');

    expect(Ticket::query()->count())->toBe(0);
});

it('stores the attachments of a new ticket at ticket level', function () {
    Storage::fake('local');

    $customer = createScreenUser();

    $response = $this->actingAs($customer)->post(route('tickets.store'), ticketPayload([
        'attachments' => [
            UploadedFile::fake()->create('log.txt', 12, 'text/plain'),
            UploadedFile::fake()->create('shot.png', 30, 'image/png'),
        ],
    ]), ['attachments' => 'files']);

    $ticket = Ticket::query()->firstOrFail();

    $response->assertRedirect(route('tickets.show', $ticket));

    $attachments = Attachment::query()->where('ticket_id', $ticket->id)->get();

    expect($attachments)->toHaveCount(2)
        ->and($attachments->pluck('reply_id')->unique()->all())->toBe([null])
        ->and($attachments->pluck('original_name')->sort()->values()->all())
        ->toBe(['log.txt', 'shot.png']);

    foreach ($attachments as $attachment) {
        Storage::disk('local')->assertExists($attachment->stored_path);
        expect($attachment->stored_path)->toStartWith("tickets/{$ticket->id}/")
            ->and($attachment->uploaded_by)->toBe($customer->id);
    }
});

it('blocks a user without a role from creating tickets', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('tickets.create'))->assertForbidden();
    $this->actingAs($user)->post(route('tickets.store'), ticketPayload())->assertForbidden();

    expect(Ticket::query()->count())->toBe(0);
});

it('rejects an attachment with a disallowed extension', function () {
    Storage::fake('local');

    $customer = createScreenUser();

    $this->actingAs($customer)
        ->post(route('tickets.store'), ticketPayload([
            'attachments' => [UploadedFile::fake()->create('payload.php', 5, 'text/x-php')],
        ]), ['attachments' => 'files'])
        ->assertSessionHasErrors('attachments.0');

    expect(Ticket::query()->count())->toBe(0)
        ->and(Attachment::query()->count())->toBe(0);
});
