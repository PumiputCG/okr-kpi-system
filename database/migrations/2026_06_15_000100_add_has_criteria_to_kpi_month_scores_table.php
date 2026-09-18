<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_month_scores', 'has_criteria')) {
            Schema::table('kpi_month_scores', function (Blueprint $table): void {
                $table->boolean('has_criteria')
                    ->nullable()
                    ->after('criteria_operator');
            });
        }

        DB::table('kpi_month_scores')
            ->whereNotNull('kpi_unit_id')
            ->whereNotNull('criteria_operator')
            ->where('criteria_operator', '<>', '')
            ->update([
                'has_criteria' => true,
            ]);

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `kpi_month_scores` MODIFY `target_value` DECIMAL(30,10) NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "kpi_month_scores" ALTER COLUMN "target_value" DROP NOT NULL');

            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE [kpi_month_scores] ALTER COLUMN [target_value] DECIMAL(30,10) NULL');

            return;
        }

        Schema::table('kpi_month_scores', function (Blueprint $table): void {
            $table->decimal('target_value', 30, 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('kpi_month_scores')
            ->whereNull('target_value')
            ->update([
                'target_value' => 0,
            ]);

        if (Schema::hasColumn('kpi_month_scores', 'has_criteria')) {
            Schema::table('kpi_month_scores', function (Blueprint $table): void {
                $table->dropColumn('has_criteria');
            });
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `kpi_month_scores` MODIFY `target_value` DECIMAL(30,10) NOT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE "kpi_month_scores" ALTER COLUMN "target_value" SET NOT NULL');

            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE [kpi_month_scores] ALTER COLUMN [target_value] DECIMAL(30,10) NOT NULL');

            return;
        }

        Schema::table('kpi_month_scores', function (Blueprint $table): void {
            $table->decimal('target_value', 30, 10)->nullable(false)->change();
        });
    }
};
