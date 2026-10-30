<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\Sla\SlaConfigLoader;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->seed([CategorySeeder::class, SlaPolicySeeder::class, BusinessHoursSeeder::class]);
    SlaConfigLoader::forget();
});

function dashboardUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('redirects customers from the dashboard and lands them on tickets', function () {
    $customer = dashboardUser('customer');

    $this->actingAs($customer)->get(route('dashboard'))
        ->assertRedirect(route('tickets.index'));

    expect($customer->homeRoute())->toBe('tickets.index');
});

it('returns scoped dashboard counts and admin agent breakdown', function () {
    $admin = dashboardUser('admin');
    $agent = dashboardUser('agent');
    $customer = dashboardUser('customer');
    $now = Carbon::parse('2026-10-30 12:00:00', 'Asia/Dhaka');
    Carbon::setTestNow($now);

    $make = function (array $attributes) use ($customer): Ticket {
        return Ticket::factory()->create(array_merge(['created_by' => $customer->id], $attributes));
    };

    $make(['status' => TicketStatus::Open, 'priority' => TicketPriority::Critical, 'assigned_agent_id' => null, 'sla_warning_at' => now()->addHour(), 'sla_due_at' => now()->addHours(2)]);
    $make(['status' => TicketStatus::InProgress, 'assigned_agent_id' => $agent->id, 'sla_warning_at' => now()->subHour(), 'sla_due_at' => now()->addHour()]);
    $make(['status' => TicketStatus::WaitingForCustomer, 'assigned_agent_id' => $agent->id, 'sla_warning_at' => now()->subHours(2), 'sla_due_at' => now()->subHour()]);
    $make(['status' => TicketStatus::Resolved]);
    $make(['status' => TicketStatus::Closed]);

    $data = app(DashboardService::class)->forUser($admin);

    expect($data['stats'])->toMatchArray([
        'open' => 3,
        'unassigned' => 1,
        'critical' => 1,
        'breached' => 1,
        'approaching' => 1,
        'within' => 1,
        'resolved' => 2,
    ])->and($data['byAgent'])->toHaveKeys([$agent->name, 'Unassigned']);

    Carbon::setTestNow();
});

it('limits agent dashboard counts to visible tickets and excludes agent breakdown', function () {
    $agent = dashboardUser('agent');
    $mine = dashboardUser('customer');
    $other = dashboardUser('customer');

    Ticket::factory()->create(['created_by' => $mine->id, 'assigned_agent_id' => $agent->id]);
    Ticket::factory()->create(['created_by' => $other->id, 'assigned_agent_id' => null]);

    $data = app(DashboardService::class)->forUser($agent);

    expect($data['stats']['open'])->toBe(2)
        ->and($data['byAgent'])->toBe([]);
});

it('keeps dashboard queries bounded', function () {
    $admin = dashboardUser('admin');
    Ticket::factory()->count(25)->create();

    DB::enableQueryLog();
    app(DashboardService::class)->forUser($admin);
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(8);
});
