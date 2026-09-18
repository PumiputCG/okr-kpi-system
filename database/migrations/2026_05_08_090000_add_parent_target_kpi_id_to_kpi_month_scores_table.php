<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_month_scores', 'parent_target_kpi_id')) {
            Schema::table('kpi_month_scores', function (Blueprint $table): void {
                $table->unsignedBigInteger('parent_target_kpi_id')
                    ->nullable()
                    ->after('okr_key_result_id');
                $table->index('parent_target_kpi_id', 'kpi_month_scores_parent_target_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kpi_month_scores', 'parent_target_kpi_id')) {
            Schema::table('kpi_month_scores', function (Blueprint $table): void {
                $table->dropIndex('kpi_month_scores_parent_target_idx');
                $table->dropColumn('parent_target_kpi_id');
            });
        }
    }
};
