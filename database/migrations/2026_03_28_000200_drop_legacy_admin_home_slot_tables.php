<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('admin_home_slot_files');
        Schema::dropIfExists('admin_home_slot_positions');
        Schema::dropIfExists('admin_home_slots');
    }

    public function down(): void
    {
        // Legacy slot tables were removed permanently.
    }
};

