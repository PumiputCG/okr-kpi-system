<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_home_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->unsignedInteger('level_no');
            $table->text('title')->nullable();
            $table->longText('detail')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['admin_user_id', 'level_no'], 'admin_home_announcements_admin_level_idx');
            $table->index(['admin_user_id', 'posted_at'], 'admin_home_announcements_admin_posted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_home_announcements');
    }
};

