<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_months', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained('cycles')->cascadeOnDelete();
            $table->unsignedTinyInteger('month_no');
            $table->dateTime('open_at')->nullable();
            $table->timestamps();

            $table->unique(['cycle_id', 'month_no']);
            $table->index('month_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_months');
    }
};

