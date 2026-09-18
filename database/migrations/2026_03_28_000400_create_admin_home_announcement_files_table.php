<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_home_announcement_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('admin_home_announcements')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('original_name', 255);
            $table->string('storage_path', 500);
            $table->string('mime_type', 255)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->index('announcement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_home_announcement_files');
    }
};

