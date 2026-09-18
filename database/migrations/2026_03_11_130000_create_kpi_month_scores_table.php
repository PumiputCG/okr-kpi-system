<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_month_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained('cycles')->cascadeOnDelete();
            $table->unsignedTinyInteger('month_no');
            $table->text('objective');
            $table->text('detail');
            $table->decimal('target_value', 12, 2);
            $table->string('criteria_operator', 5);
            $table->decimal('score_value', 12, 2);
            $table->boolean('is_pass')->default(false);
            $table->json('evidence_files')->nullable();
            $table->json('action_plan_files')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['cycle_id', 'month_no']);
            $table->index(['app_user_id', 'cycle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_month_scores');
    }
};
