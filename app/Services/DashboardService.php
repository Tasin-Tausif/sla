<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $active = TicketStatus::activeValues();
        $resolved = [TicketStatus::Resolved->value, TicketStatus::Closed->value];
        $now = now();
        $base = Ticket::query()->visibleTo($user);

        $aggregate = (clone $base)
            ->toBase()
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) AS open,
                 SUM(CASE WHEN status IN (?, ?, ?) AND assigned_agent_id IS NULL THEN 1 ELSE 0 END) AS unassigned,
                 SUM(CASE WHEN status IN (?, ?, ?) AND priority = ? THEN 1 ELSE 0 END) AS critical,
                 SUM(CASE WHEN status IN (?, ?, ?) AND sla_due_at < ? THEN 1 ELSE 0 END) AS breached,
                 SUM(CASE WHEN status IN (?, ?, ?) AND sla_warning_at <= ? AND sla_due_at > ? THEN 1 ELSE 0 END) AS approaching,
                 SUM(CASE WHEN status IN (?, ?, ?) AND sla_warning_at > ? THEN 1 ELSE 0 END) AS within,
                 SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) AS resolved',
                [
                    ...$active,
                    ...$active,
                    ...$active, TicketPriority::Critical->value,
                    ...$active, $now,
                    ...$active, $now, $now,
                    ...$active, $now,
                    ...$resolved,
                ],
            )
            ->first();

        $byPriority = (clone $base)
            ->active()
            ->selectRaw('priority, COUNT(*) AS total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $priority = collect(TicketPriority::cases())
            ->mapWithKeys(fn (TicketPriority $item): array => [$item->value => (int) ($byPriority[$item->value] ?? 0)])
            ->all();

        $byAgent = [];
        if ($user->hasRole('admin')) {
            $byAgent = (clone $base)
                ->active()
                ->leftJoin('users', 'users.id', '=', 'tickets.assigned_agent_id')
                ->selectRaw("COALESCE(users.name, 'Unassigned') AS agent, COUNT(*) AS total")
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total')
                ->pluck('total', 'agent')
                ->map(fn ($total): int => (int) $total)
                ->all();
        }

        $urgent = (clone $base)
            ->active()
            ->with(['category', 'assignedAgent'])
            ->orderBy('sla_due_at')
            ->limit(10)
            ->get();

        return [
            'stats' => collect((array) $aggregate)->map(fn ($value): int => (int) $value)->all(),
            'byPriority' => $priority,
            'byAgent' => $byAgent,
            'urgentTickets' => $urgent,
        ];
    }
}
