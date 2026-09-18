<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admin_home_announcements', 'parent_announcement_id')) {
            Schema::table('admin_home_announcements', function (Blueprint $table) {
                $table->foreignId('parent_announcement_id')
                    ->nullable()
                    ->after('level_no')
                    ->constrained('admin_home_announcements')
                    ->restrictOnDelete();
                $table->index(
                    ['admin_user_id', 'parent_announcement_id'],
                    'admin_home_announcements_admin_parent_idx'
                );
            });
        }

        // Backfill legacy level-2 rows to the oldest level-1 row of the same admin when possible.
        $adminIds = DB::table('admin_home_announcements')
            ->select('admin_user_id')
            ->distinct()
            ->pluck('admin_user_id')
            ->map(static fn ($value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->values()
            ->all();

        foreach ($adminIds as $adminId) {
            $parentId = DB::table('admin_home_announcements')
                ->where('admin_user_id', $adminId)
                ->where('level_no', 1)
                ->orderBy('id')
                ->value('id');

            if (! $parentId) {
                continue;
            }

            DB::table('admin_home_announcements')
                ->where('admin_user_id', $adminId)
                ->where('level_no', 2)
                ->whereNull('parent_announcement_id')
                ->update([
                    'parent_announcement_id' => (int) $parentId,
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admin_home_announcements', 'parent_announcement_id')) {
            Schema::table('admin_home_announcements', function (Blueprint $table) {
                $table->dropIndex('admin_home_announcements_admin_parent_idx');
                $table->dropConstrainedForeignId('parent_announcement_id');
            });
        }
    }
};

