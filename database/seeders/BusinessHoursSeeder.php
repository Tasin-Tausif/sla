<?php

namespace Database\Seeders;

use App\Services\Sla\SlaConfigLoader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessHoursSeeder extends Seeder
{
    public function run(): void
    {
        $workingDays = [0, 1, 2, 3, 4];

        foreach (range(0, 6) as $dayOfWeek) {
            $isWorkingDay = in_array($dayOfWeek, $workingDays, true);

            DB::table('business_hours')->updateOrInsert(
                ['day_of_week' => $dayOfWeek],
                [
                    'is_working_day' => $isWorkingDay,
                    'start_time' => $isWorkingDay ? '09:00:00' : null,
                    'end_time' => $isWorkingDay ? '18:00:00' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        SlaConfigLoader::forget();
    }
}
