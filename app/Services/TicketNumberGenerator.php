<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TicketNumberGenerator
{
    /**
     * @return array<int, string>
     */
    public function reserveBlock(int $year, int $count): array
    {
        if ($count < 1) {
            return [];
        }

        return DB::transaction(function () use ($year, $count): array {
            DB::table('ticket_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
            ]);

            $row = DB::table('ticket_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $first = $row->last_number + 1;

            DB::table('ticket_sequences')
                ->where('year', $year)
                ->update(['last_number' => $row->last_number + $count]);

            return collect(range($first, $first + $count - 1))
                ->map(fn (int $number): string => sprintf('TKT-%d-%06d', $year, $number))
                ->all();
        });
    }

    /**
     * insertOrIgnore is required because lockForUpdate() cannot lock a row
     * that does not exist yet. The unique index on tickets.ticket_number is
     * the final safety net if a number is ever generated twice.
     */
    public function generate(): string
    {
        $year = (int) now()->year;

        return DB::transaction(function () use ($year): string {
            DB::table('ticket_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
            ]);

            $row = DB::table('ticket_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            $next = $row->last_number + 1;

            DB::table('ticket_sequences')
                ->where('year', $year)
                ->update(['last_number' => $next]);

            return sprintf('TKT-%d-%06d', $year, $next);
        });
    }
}
