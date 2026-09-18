<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_month_scores', 'target_departments')) {
            Schema::table('kpi_month_scores', function (Blueprint $table): void {
                $table->json('target_departments')
                    ->nullable()
                    ->after('detail');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kpi_month_scores', 'target_departments')) {
            Schema::table('kpi_month_scores', function (Blueprint $table): void {
                $table->dropColumn('target_departments');
            });
        }
    }
};

