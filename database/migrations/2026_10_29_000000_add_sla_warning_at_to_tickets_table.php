<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('sla_warning_at')->nullable()->after('sla_due_at');
        });

        DB::table('tickets')->update([
            'sla_warning_at' => DB::raw('sla_due_at'),
        ]);

        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('sla_warning_at')->nullable(false)->change();
            $table->index(['status', 'sla_warning_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status', 'sla_warning_at']);
            $table->dropColumn('sla_warning_at');
        });
    }
};
