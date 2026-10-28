<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $year = now()->year;

        $holidays = [
            ['holiday_date' => "{$year}-01-01", 'name' => "New Year's Day"],
            ['holiday_date' => "{$year}-05-01", 'name' => 'Labour Day'],
        ];

        foreach ($holidays as $holiday) {
            DB::table('holidays')->updateOrInsert(
                ['holiday_date' => $holiday['holiday_date']],
                [
                    'name' => $holiday['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
