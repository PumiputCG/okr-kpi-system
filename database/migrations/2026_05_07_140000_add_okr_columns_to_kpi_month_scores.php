<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_month_scores', function (Blueprint $table) {
            $table->unsignedBigInteger('okr_objective_id')->nullable()->after('cycle_id');
            $table->unsignedBigInteger('okr_key_result_id')->nullable()->after('okr_objective_id');
            $table->index('okr_objective_id');
            $table->index('okr_key_result_id');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_month_scores', function (Blueprint $table) {
            $table->dropIndex(['okr_objective_id']);
            $table->dropIndex(['okr_key_result_id']);
            $table->dropColumn(['okr_objective_id', 'okr_key_result_id']);
        });
    }
};
