<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaEscalationService;
use App\Services\TicketReplyService;
use App\Services\TicketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class DemoDataSeeder extends Seeder
{
    public function run(TicketService $tickets, TicketReplyService $replies, SlaEscalationService $escalation): void
    {
        if (Ticket::query()->exists()) {
            return;
        }

        Notification::fake();

        $customers = User::query()->role('customer')->get()->values();
        for ($i = $customers->count(); $i < 5; $i++) {
            $customer = User::factory()->create([
                'name' => 'Demo Customer '.($i + 1),
                'email' => 'demo.customer.'.($i + 1).'@example.com',
            ]);
            $customer->assignRole('customer');
            $customers->push($customer);
        }

        $agents = User::query()->role('agent')->get()->values();
        $categories = Category::query()->where('is_active', true)->get();
        $admin = User::query()->role('admin')->firstOrFail();
        $priorities = TicketPriority::cases();
        $statusPlan = [
            TicketStatus::Open,
            TicketStatus::InProgress,
            TicketStatus::WaitingForCustomer,
            TicketStatus::Resolved,
            TicketStatus::Closed,
        ];
        $now = Carbon::now();

        try {
            foreach (range(0, 39) as $index) {
                $createdAt = $now->copy()->subDays(1 + ($index % 12))->subHours($index % 8);
                Carbon::setTestNow($createdAt);

                $ticket = $tickets->create($customers[$index % $customers->count()], [
                    'subject' => 'Demo support request '.($index + 1),
                    'description' => 'Demonstration ticket for the support dashboard.',
                    'priority' => $priorities[$index % count($priorities)],
                    'category_id' => $categories[$index % $categories->count()]->id,
                    'assigned_agent_id' => $index % 5 === 0 ? null : $agents[$index % $agents->count()]->id,
                    'assigned_at' => $index % 5 === 0 ? null : $createdAt,
                ]);

                $state = $index % 5;
                if ($state === 0) {
                    $ticket->forceFill([
                        'sla_warning_at' => $now->copy()->addHours(2),
                        'sla_due_at' => $now->copy()->addHours(4),
                    ])->save();
                } elseif ($state === 1) {
                    $ticket->forceFill([
                        'sla_warning_at' => $now->copy()->subHour(),
                        'sla_due_at' => $now->copy()->addHours(2),
                    ])->save();
                } elseif ($state === 2) {
                    $ticket->forceFill([
                        'sla_warning_at' => $now->copy()->subHours(4),
                        'sla_due_at' => $now->copy()->subHours(2),
                    ])->save();
                } else {
                    $tickets->changeStatus($ticket, $statusPlan[$state], $admin);
                }

                if ($index < 3) {
                    $replies->add($ticket, $customers[$index % $customers->count()], 'A public demo reply.', false);
                }
                if ($index < 3) {
                    $replies->add($ticket, $admin, 'Internal demo note.', true);
                }
            }
        } finally {
            Carbon::setTestNow();
        }

        $escalation->run();
    }
}
