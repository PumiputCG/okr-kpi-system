<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cycle_months', 'is_active')) {
            Schema::table('cycle_months', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cycle_months', 'is_active')) {
            Schema::table('cycle_months', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
