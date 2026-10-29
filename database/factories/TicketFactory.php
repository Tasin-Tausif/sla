<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Sla\SlaCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = now();
        $priority = TicketPriority::Medium;
        $sla = app(SlaCalculator::class)->forPriority($priority, $createdAt);

        return [
            'ticket_number' => sprintf(
                'TKT-%d-%06d',
                $createdAt->year,
                fake()->unique()->numberBetween(1, 999999),
            ),
            'subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => $priority->value,
            'status' => TicketStatus::Open->value,
            'category_id' => Category::firstOrCreate(
                ['slug' => 'general-inquiry'],
                ['name' => 'General Inquiry', 'is_active' => true],
            )->id,
            'created_by' => User::factory(),
            'sla_policy_id' => $sla['policy_id'],
            'sla_minutes' => $sla['minutes'],
            'sla_due_at' => $sla['due_at'],
            'sla_warning_at' => $sla['warning_at'],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
