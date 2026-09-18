<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_units')) {
            return;
        }

        $now = now();
        $units = [
            ['code' => 'บาท', 'name_th' => 'บาท', 'name_en' => 'Baht', 'sort_order' => 1, 'is_active' => true],
            ['code' => '%', 'name_th' => '%', 'name_en' => '%', 'sort_order' => 2, 'is_active' => true],
            ['code' => 'คน', 'name_th' => 'คน', 'name_en' => 'Person', 'sort_order' => 3, 'is_active' => true],
            ['code' => 'ราย', 'name_th' => 'ราย', 'name_en' => 'Account', 'sort_order' => 4, 'is_active' => true],
            ['code' => 'ครั้ง', 'name_th' => 'ครั้ง', 'name_en' => 'Time', 'sort_order' => 5, 'is_active' => true],
            ['code' => 'ชิ้น', 'name_th' => 'ชิ้น', 'name_en' => 'Piece', 'sort_order' => 6, 'is_active' => true],
            ['code' => 'งาน', 'name_th' => 'งาน', 'name_en' => 'Task', 'sort_order' => 7, 'is_active' => true],
            ['code' => 'โครงการ', 'name_th' => 'โครงการ', 'name_en' => 'Project', 'sort_order' => 8, 'is_active' => true],
            ['code' => 'เอกสาร', 'name_th' => 'เอกสาร', 'name_en' => 'Document', 'sort_order' => 9, 'is_active' => true],
            ['code' => 'ชั่วโมง', 'name_th' => 'ชั่วโมง', 'name_en' => 'Hour', 'sort_order' => 10, 'is_active' => true],
            ['code' => 'วัน', 'name_th' => 'วัน', 'name_en' => 'Day', 'sort_order' => 11, 'is_active' => true],
            ['code' => 'เดือน', 'name_th' => 'เดือน', 'name_en' => 'Month', 'sort_order' => 12, 'is_active' => true],
            ['code' => 'ไตรมาส', 'name_th' => 'ไตรมาส', 'name_en' => 'Quarter', 'sort_order' => 13, 'is_active' => true],
            ['code' => 'ปี', 'name_th' => 'ปี', 'name_en' => 'Year', 'sort_order' => 14, 'is_active' => true],
            ['code' => 'คะแนน', 'name_th' => 'คะแนน', 'name_en' => 'Point', 'sort_order' => 15, 'is_active' => true],
        ];

        $upsertPayload = array_map(function (array $unit) use ($now): array {
            return [
                'code' => $unit['code'],
                'name_th' => $unit['name_th'],
                'name_en' => $unit['name_en'],
                'sort_order' => $unit['sort_order'],
                'is_active' => $unit['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $units);

        DB::table('kpi_units')->upsert(
            $upsertPayload,
            ['code'],
            ['name_th', 'name_en', 'sort_order', 'is_active', 'updated_at']
        );

        $allowedCodes = array_map(static fn (array $unit): string => $unit['code'], $units);
        DB::table('kpi_units')
            ->whereNotIn('code', $allowedCodes)
            ->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_units')) {
            return;
        }

        $now = now();
        DB::table('kpi_units')->upsert(
            [
                ['code' => 'คน/เดือน', 'name_th' => 'คน/เดือน', 'name_en' => 'Person/Month', 'sort_order' => 16, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'ชิ้น/วัน', 'name_th' => 'ชิ้น/วัน', 'name_en' => 'Piece/Day', 'sort_order' => 17, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'บาท/คน', 'name_th' => 'บาท/คน', 'name_en' => 'Baht/Person', 'sort_order' => 18, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'บาท/ชิ้น', 'name_th' => 'บาท/ชิ้น', 'name_en' => 'Baht/Piece', 'sort_order' => 19, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'ชั่วโมง/งาน', 'name_th' => 'ชั่วโมง/งาน', 'name_en' => 'Hour/Task', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ],
            ['code'],
            ['name_th', 'name_en', 'sort_order', 'is_active', 'updated_at']
        );

        DB::table('kpi_units')
            ->whereIn('code', ['คน/เดือน', 'ชิ้น/วัน', 'บาท/คน', 'บาท/ชิ้น', 'ชั่วโมง/งาน'])
            ->update([
                'is_active' => true,
                'updated_at' => $now,
            ]);
    }
};
