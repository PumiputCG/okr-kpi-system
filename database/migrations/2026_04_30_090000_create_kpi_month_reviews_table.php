<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kpi_month_reviews')) {
            return;
        }

        Schema::create('kpi_month_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_month_score_id')
                ->unique()
                ->constrained('kpi_month_scores')
                ->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('app_users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reject_detail')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('reviewed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_month_reviews');
    }
};

