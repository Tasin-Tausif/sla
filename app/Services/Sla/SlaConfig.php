<?php

namespace App\Services\Sla;

final readonly class SlaConfig
{
    /**
     * @param  array<int, array{start: string, end: string}|null>  $workingDays
     * @param  array<int, string>  $holidays
     * @param  array<string, array{id: int, minutes: int, threshold_percent: int}>  $policies
     */
    public function __construct(
        public array $workingDays,
        public array $holidays,
        public array $policies,
    ) {}
}
