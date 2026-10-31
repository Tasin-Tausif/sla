<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TicketFilter
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters, User $user): Builder
    {
        $staff = $user->can('tickets.internal-note');

        if (($q = $filters['q'] ?? null) !== null && $q !== '') {
            if (str_starts_with(strtolower($q), 'tkt-')) {
                $query->where('ticket_number', 'like', $q.'%');
            } else {
                $query->searchKeyword($q);
            }
        }

        foreach (['status', 'priority', 'category_id'] as $field) {
            if (($value = $filters[$field] ?? null) !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        if ($staff && array_key_exists('assigned_agent_id', $filters)) {
            $assigned = $filters['assigned_agent_id'];
            if ($assigned === 'unassigned') {
                $query->whereNull('assigned_agent_id');
            } elseif ($assigned !== null && $assigned !== '') {
                $query->where('assigned_agent_id', $assigned);
            }
        }

        if ($staff && ($customer = $filters['customer'] ?? null) !== null && $customer !== '') {
            $escaped = $this->escapeLike($customer);
            $query->whereIn('created_by', function ($subquery) use ($escaped): void {
                $subquery->from('users')
                    ->select('id')
                    ->where(function ($subquery) use ($escaped): void {
                        $subquery->where('name', 'like', "%{$escaped}%")
                            ->orWhere('email', 'like', "%{$escaped}%");
                    });
            });
        }

        $timezone = config('sla.timezone', 'Asia/Dhaka');
        if (($dateFrom = $filters['date_from'] ?? null) !== null && $dateFrom !== '') {
            $query->where('created_at', '>=', Carbon::parse($dateFrom, $timezone)->startOfDay()->utc());
        }
        if (($dateTo = $filters['date_to'] ?? null) !== null && $dateTo !== '') {
            $query->where('created_at', '<=', Carbon::parse($dateTo, $timezone)->endOfDay()->utc());
        }

        match ($filters['sla_status'] ?? null) {
            'within' => $query->withinSla(),
            'approaching' => $query->approachingSla(),
            'breached' => $query->breachedSla(),
            default => null,
        };

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';

        if ($sort === 'priority') {
            $query->orderByRaw(
                'CASE priority WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 WHEN ? THEN 4 ELSE 5 END '.$direction,
                [
                    TicketPriority::Critical->value,
                    TicketPriority::High->value,
                    TicketPriority::Medium->value,
                    TicketPriority::Low->value,
                ],
            );
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query->orderBy('id');
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
