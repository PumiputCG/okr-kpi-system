<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_users') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `app_users` MODIFY COLUMN `dept_abbr_hr` VARCHAR(100) NULL AFTER `department`');
        DB::statement('ALTER TABLE `app_users` MODIFY COLUMN `dept_abbr_qms` VARCHAR(100) NULL AFTER `dept_abbr_hr`');
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_users') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `app_users` MODIFY COLUMN `dept_abbr_qms` VARCHAR(100) NULL AFTER `department`');
        DB::statement('ALTER TABLE `app_users` MODIFY COLUMN `dept_abbr_hr` VARCHAR(100) NULL AFTER `dept_abbr_qms`');
    }
};
