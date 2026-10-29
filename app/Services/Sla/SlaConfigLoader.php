<?php

namespace App\Services\Sla;

use App\Models\BusinessHour;
use App\Models\Holiday;
use App\Models\SlaPolicy;
use Illuminate\Support\Facades\Cache;

class SlaConfigLoader
{
    public static function load(): SlaConfig
    {
        $data = Cache::remember(
            'sla.config',
            (int) config('sla.config_cache_ttl', 3600),
            function (): array {
                $config = self::build();

                return [
                    'workingDays' => $config->workingDays,
                    'holidays' => $config->holidays,
                    'policies' => $config->policies,
                ];
            },
        );

        return new SlaConfig(
            $data['workingDays'],
            $data['holidays'],
            $data['policies'],
        );
    }

    public static function forget(): void
    {
        Cache::forget('sla.config');
    }

    private static function build(): SlaConfig
    {
        $workingDays = array_fill(0, 7, null);

        BusinessHour::query()->get()->each(function (BusinessHour $hour) use (&$workingDays): void {
            if (! $hour->is_working_day || $hour->start_time === null || $hour->end_time === null) {
                $workingDays[$hour->day_of_week] = null;

                return;
            }

            $workingDays[$hour->day_of_week] = [
                'start' => substr((string) $hour->start_time, 0, 5),
                'end' => substr((string) $hour->end_time, 0, 5),
            ];
        });

        $holidays = Holiday::query()
            ->pluck('holiday_date')
            ->map(fn ($date): string => $date->format('Y-m-d'))
            ->all();

        $policies = SlaPolicy::query()
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(fn (SlaPolicy $policy): array => [
                $policy->priority->value => [
                    'id' => $policy->id,
                    'minutes' => $policy->resolution_minutes,
                    'threshold_percent' => $policy->warning_threshold_percent,
                ],
            ])
            ->all();

        return new SlaConfig($workingDays, $holidays, $policies);
    }
}
