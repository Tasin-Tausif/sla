<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
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
        $slaMinutes = 480;
        $slaDueAt = $createdAt->copy()->addMinutes($slaMinutes);

        // TODO replace with SlaCalculator
        $slaWarningAt = $createdAt->copy()->addMinutes(
            (int) floor($slaMinutes * 80 / 100),
        );

        return [
            'ticket_number' => sprintf(
                'TKT-%d-%06d',
                $createdAt->year,
                fake()->unique()->numberBetween(1, 999999),
            ),
            'subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => TicketPriority::Medium->value,
            'status' => TicketStatus::Open->value,
            'category_id' => Category::firstOrCreate(
                ['slug' => 'general-inquiry'],
                ['name' => 'General Inquiry', 'is_active' => true],
            )->id,
            'created_by' => User::factory(),
            'sla_minutes' => $slaMinutes,
            'sla_due_at' => $slaDueAt,
            'sla_warning_at' => $slaWarningAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
