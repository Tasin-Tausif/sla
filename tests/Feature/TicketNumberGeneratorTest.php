<?php

use App\Services\TicketNumberGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-05-01 10:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('returns 000001 for the first ticket of a year', function () {
    $number = (new TicketNumberGenerator)->generate();

    expect($number)->toBe('TKT-2026-000001');
});

it('returns 50 unique increasing numbers for sequential calls', function () {
    $generator = new TicketNumberGenerator;

    $numbers = collect(range(1, 50))
        ->map(fn () => $generator->generate());

    $expected = collect(range(1, 50))
        ->map(fn (int $number) => sprintf('TKT-2026-%06d', $number));

    expect($numbers->unique()->count())->toBe(50)
        ->and($numbers->values()->all())->toBe($expected->all());
});

it('restarts at 000001 for a new year', function () {
    $generator = new TicketNumberGenerator;

    expect($generator->generate())->toBe('TKT-2026-000001');

    Carbon::setTestNow(Carbon::parse('2027-01-01 00:00:00', 'UTC'));

    expect($generator->generate())->toBe('TKT-2027-000001');
});

it('keeps working beyond six digits', function () {
    DB::table('ticket_sequences')->insert([
        'year' => 2026,
        'last_number' => 999999,
    ]);

    expect((new TicketNumberGenerator)->generate())->toBe('TKT-2026-1000000');
});

it('reserves consecutive ticket number blocks', function () {
    $generator = new TicketNumberGenerator;

    expect($generator->reserveBlock(2026, 3))->toBe([
        'TKT-2026-000001',
        'TKT-2026-000002',
        'TKT-2026-000003',
    ])->and($generator->generate())->toBe('TKT-2026-000004')
        ->and($generator->reserveBlock(2027, 2))->toBe([
            'TKT-2027-000001',
            'TKT-2027-000002',
        ]);
});
