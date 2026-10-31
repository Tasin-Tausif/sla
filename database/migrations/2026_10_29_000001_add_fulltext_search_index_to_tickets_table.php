<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->supportsFullTextIndex()) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->fullText(['subject', 'description']);
        });
    }

    public function down(): void
    {
        if (! $this->supportsFullTextIndex()) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropFullText(['subject', 'description']);
        });
    }

    private function supportsFullTextIndex(): bool
    {
        if (
            ! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
            || ! config('tickets.search.fulltext', true)
        ) {
            return false;
        }

        try {
            $version = (string) DB::selectOne('select version() as version')->version;
        } catch (Throwable) {
            $version = '';
        }

        return ! str_contains(strtolower($version), 'tidb');
    }
};
