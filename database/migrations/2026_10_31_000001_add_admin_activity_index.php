<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table): void {
            $table->index(['log_name', 'created_at'], 'activity_log_log_name_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table): void {
            $table->dropIndex('activity_log_log_name_created_at_index');
        });
    }
};
