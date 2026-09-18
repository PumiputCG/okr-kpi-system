<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_month_scores', 'kpi_unit_id')) {
            Schema::table('kpi_month_scores', function (Blueprint $table) {
                $table->foreignId('kpi_unit_id')
                    ->nullable()
                    ->after('target_value')
                    ->constrained('kpi_units')
                    ->nullOnDelete();
                $table->index('kpi_unit_id', 'kpi_month_scores_unit_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('kpi_month_scores', 'kpi_unit_id')) {
            return;
        }

        Schema::table('kpi_month_scores', function (Blueprint $table) {
            try {
                $table->dropForeign(['kpi_unit_id']);
            } catch (\Throwable) {
                // ignore if foreign key name differs by database driver
            }

            try {
                $table->dropIndex('kpi_month_scores_unit_idx');
            } catch (\Throwable) {
                // ignore if index does not exist in current database
            }

            $table->dropColumn('kpi_unit_id');
        });
    }
};
