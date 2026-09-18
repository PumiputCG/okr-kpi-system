<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_month_scores', 'kpi_item_id')) {
            Schema::table('kpi_month_scores', function (Blueprint $table) {
                $table->foreignId('kpi_item_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('kpi_items')
                    ->cascadeOnDelete();
                $table->index(['kpi_item_id', 'month_no']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kpi_month_scores', 'kpi_item_id')) {
            Schema::table('kpi_month_scores', function (Blueprint $table) {
                $table->dropForeign(['kpi_item_id']);
                $table->dropColumn('kpi_item_id');
            });
        }
    }
};
