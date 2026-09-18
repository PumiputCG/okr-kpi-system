<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_department_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('dept_abbr_hr', 100)->unique();
            $table->foreignId('target_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->foreignId('assigned_by_admin_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_department_assignments');
    }
};

