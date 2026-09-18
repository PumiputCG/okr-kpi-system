<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('kpi_items');
    }
};
