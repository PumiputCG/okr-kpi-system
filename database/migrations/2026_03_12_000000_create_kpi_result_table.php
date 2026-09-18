<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_result')) {
            Schema::create('kpi_result', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_user_id')->unique()->constrained('app_users')->cascadeOnDelete();
                $table->decimal('result', 5, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_result');
    }
};
