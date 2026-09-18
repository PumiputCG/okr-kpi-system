<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_month_scores', 'mode_type')) {
            Schema::table('kpi_month_scores', function (Blueprint $table): void {
                $table->string('mode_type', 20)
                    ->nullable()
                    ->after('criteria_operator');
            });
        }

        if (! Schema::hasColumn('kpi_month_scores', 'mode_type')) {
            return;
        }

        DB::table('kpi_month_scores')
            ->whereNull('mode_type')
            ->update([
                'mode_type' => 'report',
            ]);

        $targetRootIds = DB::table('kpi_month_scores as root')
            ->where('root.month_no', 0)
            ->whereNull('root.kpi_meta_id')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('kpi_month_scores as child')
                    ->whereColumn('child.kpi_meta_id', 'root.id')
                    ->whereBetween('child.month_no', [1, 12]);
            })
            ->pluck('root.id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        if ($targetRootIds === []) {
            return;
        }

        DB::table('kpi_month_scores')
            ->whereIn('id', $targetRootIds)
            ->update([
                'mode_type' => 'target',
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('kpi_month_scores', 'mode_type')) {
            return;
        }

        Schema::table('kpi_month_scores', function (Blueprint $table): void {
            $table->dropColumn('mode_type');
        });
    }
};
