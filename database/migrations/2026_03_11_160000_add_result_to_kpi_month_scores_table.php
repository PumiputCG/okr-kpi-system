<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_month_scores', 'result')) {
            Schema::table('kpi_month_scores', function (Blueprint $table) {
                $table->decimal('result', 5, 2)->nullable()->after('is_pass');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kpi_month_scores', 'result')) {
            Schema::table('kpi_month_scores', function (Blueprint $table) {
                $table->dropColumn('result');
            });
        }
    }
};
