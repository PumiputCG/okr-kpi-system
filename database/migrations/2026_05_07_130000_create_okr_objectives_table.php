<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_objectives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id')->nullable()->index();
            $table->unsignedSmallInteger('sort_no')->default(1);
            $table->string('title', 500);
            $table->text('detail')->nullable();
            $table->string('file_path', 1000)->nullable();
            $table->string('file_original_name', 500)->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_objectives');
    }
};
