<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_result')) {
            return;
        }

        if (! Schema::hasColumn('kpi_result', 'cycle_id')) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->foreignId('cycle_id')
                    ->nullable()
                    ->after('app_user_id')
                    ->constrained('cycles')
                    ->cascadeOnDelete();
            });
        }

        $fallbackCycleId = DB::table('cycles')->where('is_active', true)->value('id');
        if (! $fallbackCycleId) {
            $fallbackCycleId = DB::table('cycles')->max('id');
        }

        if ($fallbackCycleId) {
            DB::table('kpi_result')
                ->whereNull('cycle_id')
                ->update(['cycle_id' => (int) $fallbackCycleId]);
        }

        try {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->dropUnique('kpi_result_app_user_id_unique');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->unique(['app_user_id', 'cycle_id'], 'kpi_result_app_user_cycle_unique');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_result')) {
            return;
        }

        try {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->dropUnique('kpi_result_app_user_cycle_unique');
            });
        } catch (\Throwable) {
        }

        if (Schema::hasColumn('kpi_result', 'cycle_id')) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cycle_id');
            });
        }

        try {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->unique('app_user_id');
            });
        } catch (\Throwable) {
        }
    }
};
