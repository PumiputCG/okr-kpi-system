<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('okr_result')) {
            return;
        }

        if (! Schema::hasColumn('okr_result', 'cycle_id')) {
            Schema::table('okr_result', function (Blueprint $table) {
                $table->foreignId('cycle_id')
                    ->nullable()
                    ->after('dept_abbr_hr')
                    ->constrained('cycles')
                    ->cascadeOnDelete();
            });
        }

        $fallbackCycleId = DB::table('cycles')->where('is_active', true)->value('id');
        if (! $fallbackCycleId) {
            $fallbackCycleId = DB::table('cycles')->max('id');
        }

        if ($fallbackCycleId) {
            DB::table('okr_result')
                ->whereNull('cycle_id')
                ->update(['cycle_id' => (int) $fallbackCycleId]);
        }

        try {
            Schema::table('okr_result', function (Blueprint $table) {
                $table->dropUnique('okr_result_dept_abbr_hr_unique');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('okr_result', function (Blueprint $table) {
                $table->unique(['dept_abbr_hr', 'cycle_id'], 'okr_result_dept_cycle_unique');
            });
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('okr_result')) {
            return;
        }

        try {
            Schema::table('okr_result', function (Blueprint $table) {
                $table->dropUnique('okr_result_dept_cycle_unique');
            });
        } catch (\Throwable) {
        }

        if (Schema::hasColumn('okr_result', 'cycle_id')) {
            Schema::table('okr_result', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cycle_id');
            });
        }

        try {
            Schema::table('okr_result', function (Blueprint $table) {
                $table->unique('dept_abbr_hr');
            });
        } catch (\Throwable) {
        }
    }
};
