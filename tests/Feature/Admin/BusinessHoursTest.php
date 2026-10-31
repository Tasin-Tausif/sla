<?php

use App\Models\BusinessHour;
use App\Models\User;
use App\Services\Sla\SlaConfigLoader;
use App\Support\FormFieldName;

beforeEach(function () {
    foreach (range(0, 6) as $day) {
        BusinessHour::create([
            'day_of_week' => $day,
            'is_working_day' => $day < 5,
            'start_time' => $day < 5 ? '09:00:00' : null,
            'end_time' => $day < 5 ? '18:00:00' : null,
        ]);
    }
});

function businessHoursAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function businessHoursPayload(array $overrides = []): array
{
    $hours = [];

    foreach (range(0, 6) as $day) {
        $hours[$day] = [
            'is_working_day' => $day < 5 ? '1' : '0',
            'start_time' => $day < 5 ? '09:00' : '',
            'end_time' => $day < 5 ? '18:00' : '',
        ];
    }

    return array_replace_recursive($hours, $overrides);
}

it('saves changed business hours and renders the saved values', function () {
    $admin = businessHoursAdmin();
    $this->actingAs($admin)->put(route('admin.sla.business-hours.update'), ['hours' => businessHoursPayload([
        0 => ['start_time' => '08:00', 'end_time' => '17:00'],
    ])])->assertRedirect()->assertSessionHas('success', 'Business hours saved.');

    expect(BusinessHour::query()->where('day_of_week', 0)->value('start_time'))->toBe('08:00:00');

    $this->actingAs($admin)->get(route('admin.sla.business-hours.index'))
        ->assertSee('value="08:00"', false)
        ->assertSee('value="17:00"', false);
});

it('accepts seconds in submitted business-hour values and clears the config cache', function () {
    SlaConfigLoader::load();

    $this->actingAs(businessHoursAdmin())
        ->put(route('admin.sla.business-hours.update'), ['hours' => businessHoursPayload([
            0 => ['start_time' => '09:00:00', 'end_time' => '17:00:00'],
        ])])
        ->assertRedirect();

    expect(SlaConfigLoader::load()->workingDays[0])->toBe(['start' => '09:00', 'end' => '17:00']);
});

it('preserves submitted bracket-name values and reports row-specific errors', function () {
    $admin = businessHoursAdmin();
    $before = BusinessHour::query()->where('day_of_week', 0)->firstOrFail()->start_time;
    $payload = businessHoursPayload([
        0 => ['start_time' => '18:00', 'end_time' => '09:00'],
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.sla.business-hours.index'))
        ->put(route('admin.sla.business-hours.update'), ['hours' => $payload])
        ->assertRedirect(route('admin.sla.business-hours.index'))
        ->assertSessionHasErrors('hours.0.end_time');

    $response->assertSessionHasInput('hours.0.start_time', '18:00')
        ->assertSessionHasInput('hours.0.end_time', '09:00');

    $this->actingAs($admin)->get(route('admin.sla.business-hours.index'))
        ->assertSee('value="18:00"', false)
        ->assertSee('value="09:00"', false);

    expect(BusinessHour::query()->where('day_of_week', 0)->value('start_time'))->toBe($before);
});

it('rejects missing times and submissions without a working day', function () {
    $admin = businessHoursAdmin();
    $before = BusinessHour::query()->where('day_of_week', 0)->value('start_time');

    $missingTime = businessHoursPayload([
        0 => ['start_time' => '', 'end_time' => '18:00'],
    ]);

    $this->actingAs($admin)
        ->put(route('admin.sla.business-hours.update'), ['hours' => $missingTime])
        ->assertSessionHasErrors('hours.0.start_time');

    $noWorkingDay = businessHoursPayload();
    foreach ($noWorkingDay as $day => $values) {
        $noWorkingDay[$day] = ['is_working_day' => '0', 'start_time' => '', 'end_time' => ''];
    }

    $this->actingAs($admin)
        ->put(route('admin.sla.business-hours.update'), ['hours' => $noWorkingDay])
        ->assertSessionHasErrors('hours');

    expect(BusinessHour::query()->where('day_of_week', 0)->value('start_time'))->toBe($before);
});

it('normalizes bracket field names for old input and validation lookups', function () {
    expect(FormFieldName::toDotNotation('hours[0][start_time]'))->toBe('hours.0.start_time')
        ->and(FormFieldName::toDotNotation('roles[]'))->toBe('roles');
});
