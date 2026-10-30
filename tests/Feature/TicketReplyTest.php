<?php

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\Sla\SlaConfigLoader;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed([BusinessHoursSeeder::class, SlaPolicySeeder::class]);
    SlaConfigLoader::forget();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('updates the parent ticket timestamp when a reply is created', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-01 09:00:00', 'UTC'));

    $ticket = Ticket::factory()->create();
    $user = User::factory()->create();

    Carbon::setTestNow(now()->addHour());

    TicketReply::create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'body' => 'A reply that touches the ticket',
        'is_internal' => false,
    ]);

    expect($ticket->refresh()->updated_at->equalTo(Carbon::now()))->toBeTrue();
});
