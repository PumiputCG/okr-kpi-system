<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_department_assignments')) {
            return;
        }

        Schema::table('admin_department_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_department_assignments', 'target_user_ids_json')) {
                $table->json('target_user_ids_json')->nullable()->after('target_user_id');
            }
            if (! Schema::hasColumn('admin_department_assignments', 'reviewer_user_ids_json')) {
                $table->json('reviewer_user_ids_json')->nullable()->after('reviewer_user_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_department_assignments')) {
            return;
        }

        Schema::table('admin_department_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('admin_department_assignments', 'target_user_ids_json')) {
                $table->dropColumn('target_user_ids_json');
            }
            if (Schema::hasColumn('admin_department_assignments', 'reviewer_user_ids_json')) {
                $table->dropColumn('reviewer_user_ids_json');
            }
        });
    }
};

