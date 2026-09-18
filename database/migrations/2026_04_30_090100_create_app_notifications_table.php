<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_notifications')) {
            return;
        }

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_user_id')
                ->constrained('app_users')
                ->cascadeOnDelete();
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('app_users')
                ->nullOnDelete();
            $table->string('type', 80);
            $table->string('title', 255);
            $table->text('message');
            $table->string('link_url', 1000)->nullable();
            $table->json('payload_json')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_user_id', 'is_read'], 'app_notifications_recipient_read_idx');
            $table->index(['recipient_user_id', 'created_at'], 'app_notifications_recipient_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};

