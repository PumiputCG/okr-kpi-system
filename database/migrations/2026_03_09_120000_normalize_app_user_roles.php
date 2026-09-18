<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_users')) {
            return;
        }

        DB::table('app_users')
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = 'admin'")
            ->update(['role' => 'admin']);

        DB::table('app_users')
            ->where(function ($query) {
                $query
                    ->whereNull('role')
                    ->orWhereRaw("LOWER(TRIM(COALESCE(role, ''))) <> 'admin'");
            })
            ->update(['role' => 'user']);
    }

    public function down(): void
    {
        // Intentionally no-op: old role values cannot be restored reliably.
    }
};

