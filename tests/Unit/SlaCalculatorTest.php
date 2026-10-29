<?php

use App\Enums\TicketPriority;
use App\Services\Sla\SlaCalculator;
use App\Services\Sla\SlaConfig;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

function makeSlaConfig(array $holidays = []): SlaConfig
{
    $workingDay = ['start' => '09:00', 'end' => '18:00'];
    $workingDays = [
        0 => $workingDay,
        1 => $workingDay,
        2 => $workingDay,
        3 => $workingDay,
        4 => $workingDay,
        5 => null,
        6 => null,
    ];

    return new SlaConfig(
        workingDays: $workingDays,
        holidays: $holidays,
        policies: [
            'low' => ['id' => 1, 'minutes' => 2880, 'threshold_percent' => 80],
            'medium' => ['id' => 2, 'minutes' => 1440, 'threshold_percent' => 80],
            'high' => ['id' => 3, 'minutes' => 480, 'threshold_percent' => 80],
            'critical' => ['id' => 4, 'minutes' => 120, 'threshold_percent' => 80],
        ],
    );
}

function dhakaTime(string $value): CarbonImmutable
{
    return CarbonImmutable::parse($value, 'Asia/Dhaka');
}

function dueInDhaka(CarbonImmutable $due): string
{
    return $due->setTimezone('Asia/Dhaka')->format('Y-m-d H:i');
}

it('moves Thursday 120 minutes across the weekend to Sunday 10:00', function () {
    $calculator = new SlaCalculator(makeSlaConfig());

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-01 17:00:00'), 120);

    expect(dueInDhaka($due))->toBe('2026-01-04 10:00');
});

it('starts counting Monday when created on Friday', function () {
    $calculator = new SlaCalculator(makeSlaConfig());

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-02 10:00:00'), 120);

    expect(dueInDhaka($due))->toBe('2026-01-04 11:00');
});

it('waits for the window to open when created before business hours', function () {
    $calculator = new SlaCalculator(makeSlaConfig());

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-04 07:00:00'), 120);

    expect(dueInDhaka($due))->toBe('2026-01-04 11:00');
});

it('moves to the next day when created after business hours', function () {
    $calculator = new SlaCalculator(makeSlaConfig());

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-04 19:00:00'), 120);

    expect(dueInDhaka($due))->toBe('2026-01-05 11:00');
});

it('adds minutes inside a single business day', function () {
    $calculator = new SlaCalculator(makeSlaConfig());

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-04 10:00:00'), 120);

    expect(dueInDhaka($due))->toBe('2026-01-04 12:00');
});

it('handles 2880 business minutes over multiple days', function () {
    // 2880 / 540 = 5 full 9-hour days (2700) plus 180 minutes.
    // Sun-Thu are full days, Fri/Sat are skipped, then the next Sunday
    // consumes the remaining 180 minutes: 09:00 + 03:00 = 12:00.
    $calculator = new SlaCalculator(makeSlaConfig());

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-04 09:00:00'), 2880);

    expect(dueInDhaka($due))->toBe('2026-01-11 12:00');
});

it('skips holidays', function () {
    $calculator = new SlaCalculator(makeSlaConfig(holidays: ['2026-01-05']));

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-04 09:00:00'), 600);

    expect(dueInDhaka($due))->toBe('2026-01-06 10:00');
});

it('returns the result in UTC', function () {
    $calculator = new SlaCalculator(makeSlaConfig());

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-04 10:00:00'), 120);

    expect($due->timezoneName)->toBe('UTC')
        ->and($due->format('Y-m-d H:i'))->toBe('2026-01-04 06:00');
});

it('computes warning_at at 80 percent of the minutes before due_at', function () {
    $calculator = new SlaCalculator(makeSlaConfig());
    $start = dhakaTime('2026-01-04 09:00:00');

    $sla = $calculator->forPriority(TicketPriority::Critical, $start);

    expect($sla['minutes'])->toBe(120)
        ->and($sla['policy_id'])->toBe(4)
        ->and($sla['warning_at']->lt($sla['due_at']))->toBeTrue()
        ->and($sla['due_at']->equalTo($calculator->addBusinessMinutes($start, 120)))->toBeTrue()
        ->and($sla['warning_at']->equalTo($calculator->addBusinessMinutes($start, 96)))->toBeTrue()
        ->and(dueInDhaka($sla['warning_at']))->toBe('2026-01-04 10:36');
});

it('lands exactly on the window end at 18:00', function () {
    $calculator = new SlaCalculator(makeSlaConfig());

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-04 16:00:00'), 120);

    expect(dueInDhaka($due))->toBe('2026-01-04 18:00');
});

it('preserves sub-minute start precision across days', function () {
    $calculator = new SlaCalculator(makeSlaConfig());

    $due = $calculator->addBusinessMinutes(dhakaTime('2026-01-04 09:00:30'), 2880);

    expect($due->setTimezone('Asia/Dhaka')->format('Y-m-d H:i:s'))->toBe('2026-01-11 12:00:30');
});
