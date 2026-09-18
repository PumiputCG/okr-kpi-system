<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admin_home_announcements', 'dept_abbr_hr')) {
            Schema::table('admin_home_announcements', function (Blueprint $table) {
                $table->string('dept_abbr_hr', 100)
                    ->nullable()
                    ->after('level_no');
                $table->index('dept_abbr_hr', 'admin_home_announcements_dept_hr_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admin_home_announcements', 'dept_abbr_hr')) {
            Schema::table('admin_home_announcements', function (Blueprint $table) {
                $table->dropIndex('admin_home_announcements_dept_hr_idx');
                $table->dropColumn('dept_abbr_hr');
            });
        }
    }
};
