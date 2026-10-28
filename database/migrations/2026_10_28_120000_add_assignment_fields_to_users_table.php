<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('password');
            $table->unsignedSmallInteger('max_open_tickets')->default(10)->after('is_available');
            $table->timestamp('last_assigned_at')->nullable()->after('max_open_tickets');

            $table->index(['is_available', 'last_assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_available', 'last_assigned_at']);
            $table->dropColumn(['is_available', 'max_open_tickets', 'last_assigned_at']);
        });
    }
};
