<?php

use App\Services\Sla\SlaConfig;
use App\Services\Sla\SlaConfigLoader;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\HolidaySeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::store('database')->flush();
    $this->seed([BusinessHoursSeeder::class, SlaPolicySeeder::class, HolidaySeeder::class]);
    SlaConfigLoader::forget();
});

it('reloads the SLA config from a serializing cache store', function () {
    Cache::setDefaultDriver('database');

    $first = SlaConfigLoader::load();
    $second = SlaConfigLoader::load();

    expect($first)->toBeInstanceOf(SlaConfig::class)
        ->and($second)->toBeInstanceOf(SlaConfig::class)
        ->and($second->workingDays[0])->toBe(['start' => '09:00', 'end' => '18:00'])
        ->and($second->workingDays[5])->toBeNull()
        ->and($second->workingDays[6])->toBeNull()
        ->and($second->policies)->toHaveKey('critical')
        ->and($second->holidays)->toHaveCount(12);
});

it('loads working hours from the seeded database in the calculator format', function () {
    $config = SlaConfigLoader::load();

    expect($config->workingDays[0])->toBe(['start' => '09:00', 'end' => '18:00'])
        ->and($config->workingDays[5])->toBeNull()
        ->and($config->workingDays[6])->toBeNull();
});
