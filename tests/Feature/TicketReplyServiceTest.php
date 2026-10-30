<?php

use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketReplyService;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([BusinessHoursSeeder::class, CategorySeeder::class, SlaPolicySeeder::class]);
    Storage::fake('local');
});

function replyUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function replyService(): TicketReplyService
{
    return app(TicketReplyService::class);
}

it('sets first_response_at on the first public reply from staff', function () {
    $customer = replyUser('customer');
    $agent = replyUser('agent');
    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    replyService()->add($ticket, $agent, 'We are looking into it now.', false);

    expect($ticket->fresh()->first_response_at)->not->toBeNull()
        ->and($ticket->activities()->where('event', 'replied')->exists())->toBeTrue();
});

it('does not set first_response_at for an internal note', function () {
    $customer = replyUser('customer');
    $agent = replyUser('agent');
    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    replyService()->add($ticket, $agent, 'Customer history checked internally.', true);

    expect($ticket->fresh()->first_response_at)->toBeNull()
        ->and($ticket->activities()->where('event', 'note_added')->exists())->toBeTrue();
});

it('does not set first_response_at when the customer replies publicly', function () {
    $customer = replyUser('customer');
    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    replyService()->add($ticket, $customer, 'Any update on this?', false);

    expect($ticket->fresh()->first_response_at)->toBeNull();
});

it('moves a waiting ticket back to in progress when the customer replies', function () {
    $customer = replyUser('customer');
    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::WaitingForCustomer,
    ]);

    replyService()->add($ticket, $customer, 'It works again.', false);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

it('reopens a resolved ticket when the customer replies', function () {
    $customer = replyUser('customer');
    $ticket = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Resolved,
        'resolved_at' => now()->subDay(),
    ]);

    replyService()->add($ticket, $customer, 'It broke again after the fix.', false);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open)
        ->and($ticket->fresh()->resolved_at)->toBeNull()
        ->and($ticket->activities()->where('event', 'reopened')->exists())->toBeTrue();
});

it('stores uploaded files under a random name on the private disk', function () {
    $customer = replyUser('customer');
    $agent = replyUser('agent');
    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $file = UploadedFile::fake()->create('quarterly-report.pdf', 120, 'application/pdf');

    $reply = replyService()->add($ticket, $agent, 'The report is attached.', false, [$file]);

    $attachment = Attachment::query()->where('reply_id', $reply->id)->firstOrFail();

    expect($attachment->original_name)->toBe('quarterly-report.pdf')
        ->and(basename($attachment->stored_path))->not->toBe('quarterly-report.pdf')
        ->and($attachment->disk)->toBe('local')
        ->and($attachment->ticket_id)->toBe($ticket->id)
        ->and($attachment->uploaded_by)->toBe($agent->id)
        ->and($attachment->mime_type)->toBe('application/pdf')
        ->and($attachment->size)->toBe(120 * 1024);

    Storage::disk('local')->assertExists($attachment->stored_path);
});

it('never stores the reply body in activity rows', function () {
    $customer = replyUser('customer');
    $agent = replyUser('agent');
    $ticket = Ticket::factory()->create(['created_by' => $customer->id]);

    $publicBody = 'Unique public marker zebra-9137';
    $internalBody = 'Unique internal marker kiwi-4412';

    replyService()->add($ticket, $agent, $publicBody, false);
    replyService()->add($ticket, $agent, $internalBody, true);

    $haystack = Activity::query()->get()
        ->map(fn (Activity $activity): string => implode(' ', [
            (string) $activity->event,
            $activity->description,
            json_encode($activity->properties->all()),
        ]))
        ->implode(' ');

    expect($haystack)->toContain('replied')
        ->and($haystack)->toContain('note_added')
        ->not->toContain($publicBody)
        ->not->toContain($internalBody);
});
