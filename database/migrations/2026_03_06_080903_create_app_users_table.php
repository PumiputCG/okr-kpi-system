<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();

            $table->string('employee_code', 100)->unique();
            $table->string('password')->nullable();

            $table->string('full_name_th', 255)->nullable();
            $table->string('full_name_en', 255)->nullable();
            $table->string('employee_type', 100)->nullable();
            $table->string('position', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->string('dept_abbr_hr', 100)->nullable();
            $table->string('dept_abbr_qms', 100)->nullable();

            $table->string('email', 255)->nullable()->unique();

            $table->string('id_thai_hash')->nullable();
            $table->string('role', 50)->default('user');

            $table->string('profile_picture')->nullable();

            $table->string('reset_token', 100)->nullable();
            $table->timestamp('reset_token_expiry')->nullable();

            $table->string('session_id')->nullable();

            $table->boolean('is_registered')->default(false)->index();
            $table->timestamp('registered_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed default admin account for fresh databases.
        DB::table('app_users')->insert([
            'employee_code' => 'admin',
            'password' => Hash::make('00000000'),
            'full_name_th' => 'Administrator',
            'full_name_en' => 'Administrator',
            'employee_type' => null,
            'position' => null,
            'department' => null,
            'dept_abbr_hr' => null,
            'dept_abbr_qms' => null,
            'email' => null,
            'id_thai_hash' => '00000000',
            'role' => 'admin',
            'is_registered' => true,
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_users');
    }
};
