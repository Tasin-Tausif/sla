<?php

namespace App\Services\Sla;

use App\Enums\TicketPriority;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use LogicException;

class SlaCalculator
{
    public function __construct(private readonly SlaConfig $config) {}

    public function addBusinessMinutes(CarbonInterface $start, int $minutes): CarbonImmutable
    {
        $start = CarbonImmutable::instance($start);

        if ($minutes === 0) {
            return $start->utc();
        }

        if (array_filter($this->config->workingDays) === []) {
            throw new LogicException('No working days are configured for SLA calculation.');
        }

        $timezone = config('sla.timezone', 'Asia/Dhaka');
        $cursor = $start->setTimezone($timezone);
        $remaining = $minutes;
        $iterations = 0;

        while (true) {
            if (++$iterations > 800) {
                throw new LogicException('SLA calculation exceeded 800 iterations.');
            }

            $window = $this->config->workingDays[$cursor->dayOfWeek] ?? null;

            if ($window === null || in_array($cursor->format('Y-m-d'), $this->config->holidays, true)) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }

            [$startHour, $startMinute] = array_map('intval', explode(':', $window['start']));
            [$endHour, $endMinute] = array_map('intval', explode(':', $window['end']));

            $windowStart = $cursor->setTime($startHour, $startMinute);
            $windowEnd = $cursor->setTime($endHour, $endMinute);

            if ($cursor->lt($windowStart)) {
                $cursor = $windowStart;
            }

            if ($cursor->gte($windowEnd)) {
                $cursor = $cursor->addDay()->startOfDay();

                continue;
            }

            $available = $cursor->diffInMinutes($windowEnd);

            if ($remaining <= $available) {
                return $cursor->addMinutes($remaining)->utc();
            }

            $remaining -= $available;
            $cursor = $cursor->addDay()->startOfDay();
        }
    }

    /**
     * @return array{policy_id: int, minutes: int, due_at: CarbonImmutable, warning_at: CarbonImmutable}
     */
    public function forPriority(TicketPriority|string $priority, CarbonInterface $start): array
    {
        $key = $priority instanceof TicketPriority ? $priority->value : $priority;
        $policy = $this->config->policies[$key] ?? null;

        if ($policy === null) {
            throw new InvalidArgumentException("No active SLA policy exists for priority [{$key}].");
        }

        $warningMinutes = intdiv($policy['minutes'] * $policy['threshold_percent'], 100);

        return [
            'policy_id' => $policy['id'],
            'minutes' => $policy['minutes'],
            'due_at' => $this->addBusinessMinutes($start, $policy['minutes']),
            'warning_at' => $this->addBusinessMinutes($start, $warningMinutes),
        ];
    }
}
