<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('okr_all_result')) {
            Schema::create('okr_all_result', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cycle_id')->unique()->constrained('cycles')->cascadeOnDelete();
                $table->decimal('result', 5, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_all_result');
    }
};
