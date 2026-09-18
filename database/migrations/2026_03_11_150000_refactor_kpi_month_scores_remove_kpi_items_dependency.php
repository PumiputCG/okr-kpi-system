<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasKpiItemId = Schema::hasColumn('kpi_month_scores', 'kpi_item_id');
        $hasKpiMetaId = Schema::hasColumn('kpi_month_scores', 'kpi_meta_id');

        Schema::table('kpi_month_scores', function (Blueprint $table) use ($hasKpiItemId, $hasKpiMetaId) {
            if ($hasKpiItemId) {
                try {
                    $table->dropForeign(['kpi_item_id']);
                } catch (\Throwable) {
                    // ignore if foreign key does not exist in current database
                }

                try {
                    $table->dropIndex(['kpi_item_id', 'month_no']);
                } catch (\Throwable) {
                    // ignore if composite index does not exist in current database
                }

                $table->dropColumn('kpi_item_id');
            }

            if (! $hasKpiMetaId) {
                $table->unsignedBigInteger('kpi_meta_id')->nullable()->after('cycle_id');
                $table->index(['kpi_meta_id', 'month_no'], 'kpi_month_scores_meta_month_idx');
                $table->index(['app_user_id', 'cycle_id', 'kpi_meta_id'], 'kpi_month_scores_meta_owner_idx');
            }
        });

        if (Schema::hasTable('kpi_items')) {
            Schema::drop('kpi_items');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_items')) {
            Schema::create('kpi_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
                $table->foreignId('cycle_id')->constrained('cycles')->cascadeOnDelete();
                $table->text('objective');
                $table->text('detail');
                $table->decimal('target_value', 12, 2)->nullable();
                $table->string('criteria_operator', 5)->nullable();
                $table->timestamp('step_one_saved_at')->nullable();
                $table->timestamp('step_two_saved_at')->nullable();
                $table->timestamps();
                $table->index(['app_user_id', 'cycle_id']);
            });
        }

        $hasKpiItemId = Schema::hasColumn('kpi_month_scores', 'kpi_item_id');
        $hasKpiMetaId = Schema::hasColumn('kpi_month_scores', 'kpi_meta_id');

        Schema::table('kpi_month_scores', function (Blueprint $table) use ($hasKpiItemId, $hasKpiMetaId) {
            if ($hasKpiMetaId) {
                try {
                    $table->dropIndex('kpi_month_scores_meta_month_idx');
                } catch (\Throwable) {
                    // ignore
                }

                try {
                    $table->dropIndex('kpi_month_scores_meta_owner_idx');
                } catch (\Throwable) {
                    // ignore
                }

                $table->dropColumn('kpi_meta_id');
            }

            if (! $hasKpiItemId) {
                $table->foreignId('kpi_item_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('kpi_items')
                    ->cascadeOnDelete();
                $table->index(['kpi_item_id', 'month_no']);
            }
        });
    }
};
