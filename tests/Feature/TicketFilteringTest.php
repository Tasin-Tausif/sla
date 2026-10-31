<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Sla\SlaConfigLoader;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->seed([CategorySeeder::class, SlaPolicySeeder::class, BusinessHoursSeeder::class]);
    SlaConfigLoader::forget();
});

function filterUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('filters by ticket number prefix and two character keyword', function () {
    $customer = filterUser('customer');
    $match = Ticket::factory()->create([
        'created_by' => $customer->id,
        'ticket_number' => 'TKT-2026-123456',
        'subject' => 'AB outage',
    ]);
    Ticket::factory()->create(['created_by' => $customer->id, 'subject' => 'Other issue']);

    $this->actingAs($customer)
        ->get(route('tickets.index', ['q' => 'TKT-2026-12']))
        ->assertSee($match->ticket_number);

    // Two-character search deliberately exercises LIKE because MySQL FULLTEXT
    // indexes cannot see uncommitted rows inside a transaction.
    $this->actingAs($customer)
        ->get(route('tickets.index', ['q' => 'AB']))
        ->assertSee($match->ticket_number);
});

it('uses the LIKE search fallback when full text search is disabled', function () {
    config()->set('tickets.search.fulltext', false);

    $sql = Ticket::query()->searchKeyword('support')->toSql();

    expect($sql)->toContain('like')
        ->not->toContain('match');
});

it('keeps customer visibility before forged filters', function () {
    $customer = filterUser('customer');
    $other = filterUser('customer');
    $agent = filterUser('agent');
    $mine = Ticket::factory()->create(['created_by' => $customer->id]);
    $theirs = Ticket::factory()->create([
        'created_by' => $other->id,
        'assigned_agent_id' => $agent->id,
    ]);

    $response = $this->actingAs($customer)->get(route('tickets.index', [
        'assigned_agent_id' => $agent->id,
        'customer' => $other->email,
    ]));

    $response->assertOk()->assertSee($mine->ticket_number)->assertDontSee($theirs->ticket_number);
});

it('combines status and priority filters and ignores the tab', function () {
    $customer = filterUser('customer');
    $match = Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Resolved,
        'priority' => TicketPriority::Critical,
    ]);
    Ticket::factory()->create([
        'created_by' => $customer->id,
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::Critical,
    ]);

    $this->actingAs($customer)
        ->get(route('tickets.index', [
            'view' => 'active',
            'status' => TicketStatus::Resolved->value,
            'priority' => TicketPriority::Critical->value,
        ]))
        ->assertOk()
        ->assertSee($match->ticket_number);
});

it('uses Dhaka day boundaries for date filters', function () {
    $customer = filterUser('customer');
    $inside = Carbon::create(2026, 10, 10, 18, 0, 0, 'UTC');
    $outside = Carbon::create(2026, 10, 10, 17, 59, 59, 'UTC');
    $match = Ticket::factory()->create(['created_by' => $customer->id, 'created_at' => $inside]);
    $other = Ticket::factory()->create(['created_by' => $customer->id, 'created_at' => $outside]);

    $this->actingAs($customer)
        ->get(route('tickets.index', ['date_from' => '2026-10-11', 'date_to' => '2026-10-11']))
        ->assertOk()
        ->assertSee($match->ticket_number)
        ->assertDontSee($other->ticket_number);
});

it('rejects invalid sort values', function () {
    $this->actingAs(filterUser('customer'))
        ->get(route('tickets.index', ['sort' => 'updated_at']))
        ->assertSessionHasErrors('sort');
});
