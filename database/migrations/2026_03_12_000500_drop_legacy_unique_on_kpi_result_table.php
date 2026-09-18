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

        $exists = DB::table('information_schema.statistics')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'kpi_result')
            ->where('INDEX_NAME', 'kpi_result_app_user_id_unique')
            ->exists();

        if ($exists) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->dropUnique('kpi_result_app_user_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_result')) {
            return;
        }

        $exists = DB::table('information_schema.statistics')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'kpi_result')
            ->where('INDEX_NAME', 'kpi_result_app_user_id_unique')
            ->exists();

        if (! $exists) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->unique('app_user_id');
            });
        }
    }
};
