<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_key_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('okr_objective_id');
            $table->string('dept_abbr_hr', 100)->nullable();
            $table->unsignedSmallInteger('sort_no')->default(1);
            $table->string('title', 500);
            $table->text('detail')->nullable();
            $table->string('file_path', 1000)->nullable();
            $table->string('file_original_name', 500)->nullable();
            $table->timestamps();

            $table->index('okr_objective_id');
            $table->foreign('okr_objective_id')
                ->references('id')
                ->on('okr_objectives')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_key_results');
    }
};
