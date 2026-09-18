<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('okr_result')) {
            Schema::create('okr_result', function (Blueprint $table) {
                $table->id();
                $table->string('dept_abbr_hr', 100)->unique();
                $table->decimal('result', 5, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_result');
    }
};
