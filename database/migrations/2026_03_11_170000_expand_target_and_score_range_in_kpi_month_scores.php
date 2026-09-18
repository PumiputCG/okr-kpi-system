<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `kpi_month_scores` MODIFY `target_value` DECIMAL(30,10) NOT NULL');
            DB::statement('ALTER TABLE `kpi_month_scores` MODIFY `score_value` DECIMAL(30,10) NOT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "kpi_month_scores" ALTER COLUMN "target_value" TYPE NUMERIC(30,10)');
            DB::statement('ALTER TABLE "kpi_month_scores" ALTER COLUMN "score_value" TYPE NUMERIC(30,10)');
            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE [kpi_month_scores] ALTER COLUMN [target_value] DECIMAL(30,10) NOT NULL');
            DB::statement('ALTER TABLE [kpi_month_scores] ALTER COLUMN [score_value] DECIMAL(30,10) NOT NULL');
            return;
        }

        // sqlite and others: no-op (sqlite does not enforce DECIMAL precision the same way)
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `kpi_month_scores` MODIFY `target_value` DECIMAL(12,2) NOT NULL');
            DB::statement('ALTER TABLE `kpi_month_scores` MODIFY `score_value` DECIMAL(12,2) NOT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "kpi_month_scores" ALTER COLUMN "target_value" TYPE NUMERIC(12,2)');
            DB::statement('ALTER TABLE "kpi_month_scores" ALTER COLUMN "score_value" TYPE NUMERIC(12,2)');
            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE [kpi_month_scores] ALTER COLUMN [target_value] DECIMAL(12,2) NOT NULL');
            DB::statement('ALTER TABLE [kpi_month_scores] ALTER COLUMN [score_value] DECIMAL(12,2) NOT NULL');
            return;
        }
    }
};
