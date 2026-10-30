<?php

namespace Database\Seeders;

use App\Services\Sla\SlaConfigLoader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SlaPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            ['priority' => 'low', 'resolution_minutes' => 48 * 60],
            ['priority' => 'medium', 'resolution_minutes' => 24 * 60],
            ['priority' => 'high', 'resolution_minutes' => 8 * 60],
            ['priority' => 'critical', 'resolution_minutes' => 2 * 60],
        ];

        foreach ($policies as $policy) {
            DB::table('sla_policies')->updateOrInsert(
                ['priority' => $policy['priority']],
                [
                    'resolution_minutes' => $policy['resolution_minutes'],
                    'warning_threshold_percent' => 80,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        SlaConfigLoader::forget();
    }
}
