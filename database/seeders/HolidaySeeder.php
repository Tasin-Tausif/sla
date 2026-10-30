<?php

namespace Database\Seeders;

use App\Services\Sla\SlaConfigLoader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            '01-01' => "New Year's Day",
            '02-21' => "Language Martyrs' Day",
            '03-26' => 'Independence Day',
            '05-01' => 'Labour Day',
            '12-16' => 'Victory Day',
            '12-25' => 'Christmas Day',
        ];

        foreach ([now()->year, now()->year + 1] as $year) {
            foreach ($holidays as $date => $name) {
                DB::table('holidays')->updateOrInsert(
                    ['holiday_date' => "{$year}-{$date}"],
                    [
                        'name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        SlaConfigLoader::forget();
    }
}
