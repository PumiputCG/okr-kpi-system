<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_home_announcements', function (Blueprint $table) {
            $table->foreignId('posted_by_user_id')
                ->nullable()
                ->after('admin_user_id')
                ->constrained('app_users')
                ->nullOnDelete();
            $table->index('posted_by_user_id', 'admin_home_announcements_posted_by_idx');
        });

        DB::table('admin_home_announcements')
            ->whereNull('posted_by_user_id')
            ->update([
                'posted_by_user_id' => DB::raw('admin_user_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('admin_home_announcements', function (Blueprint $table) {
            $table->dropIndex('admin_home_announcements_posted_by_idx');
            $table->dropForeign(['posted_by_user_id']);
            $table->dropColumn('posted_by_user_id');
        });
    }
};
