<?php

use App\Models\Ticket;
use App\Models\User;
use App\Services\Sla\SlaConfigLoader;
use App\Support\TicketRecipients;
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

function recipientUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('returns only admins', function () {
    $admin = recipientUser('admin');
    $agent = recipientUser('agent');
    $customer = recipientUser('customer');

    $admins = TicketRecipients::admins();

    expect($admins->pluck('id')->all())->toBe([$admin->id])
        ->and($admins->every(fn (User $user): bool => $user->hasRole('admin')))->toBeTrue()
        ->and($admins->pluck('id')->contains($agent->id))->toBeFalse()
        ->and($admins->pluck('id')->contains($customer->id))->toBeFalse();
});

it('escalates an assigned ticket to the agent and every admin', function () {
    $admin = recipientUser('admin');
    $otherAdmin = recipientUser('admin');
    $agent = recipientUser('agent');
    $unrelated = recipientUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);

    $recipients = TicketRecipients::forEscalation($ticket);

    expect($recipients->first()->id)->toBe($agent->id)
        ->and($recipients->pluck('id')->sort()->values()->all())
        ->toBe(collect([$agent->id, $admin->id, $otherAdmin->id])->sort()->values()->all())
        ->and($recipients->pluck('id')->contains($unrelated->id))->toBeFalse();
});

it('escalates an unassigned ticket to the admins only', function () {
    $admin = recipientUser('admin');
    $agent = recipientUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    $recipients = TicketRecipients::forEscalation($ticket);

    expect($recipients->pluck('id')->all())->toBe([$admin->id])
        ->and($recipients->pluck('id')->contains($agent->id))->toBeFalse();
});

it('never repeats a recipient when the assigned agent is also an admin', function () {
    $admin = recipientUser('admin');
    $otherAdmin = recipientUser('admin');
    $agent = recipientUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => $admin->id]);

    $recipients = TicketRecipients::forEscalation($ticket);

    expect($recipients->pluck('id')->sort()->values()->all())
        ->toBe(collect([$admin->id, $otherAdmin->id])->sort()->values()->all());

    expect($agent->id)->not->toBeNull();
});

it('sends a customer reply to the assigned agent only', function () {
    $admin = recipientUser('admin');
    $agent = recipientUser('agent');
    $colleague = recipientUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => $agent->id]);

    $recipients = TicketRecipients::forCustomerReply($ticket);

    expect($recipients->pluck('id')->all())->toBe([$agent->id])
        ->and($recipients->pluck('id')->contains($admin->id))->toBeFalse()
        ->and($recipients->pluck('id')->contains($colleague->id))->toBeFalse();
});

it('falls back to the admins for a reply on an unassigned ticket', function () {
    $admin = recipientUser('admin');
    $otherAdmin = recipientUser('admin');
    $agent = recipientUser('agent');

    $ticket = Ticket::factory()->create(['assigned_agent_id' => null]);

    $recipients = TicketRecipients::forCustomerReply($ticket);

    expect($recipients->pluck('id')->sort()->values()->all())
        ->toBe(collect([$admin->id, $otherAdmin->id])->sort()->values()->all())
        ->and($recipients->pluck('id')->contains($agent->id))->toBeFalse();
});
