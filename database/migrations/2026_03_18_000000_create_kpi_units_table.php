<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name_th', 120);
            $table->string('name_en', 120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('kpi_units')->insert([
            ['code' => 'บาท', 'name_th' => 'บาท', 'name_en' => 'Baht', 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '%', 'name_th' => '%', 'name_en' => '%', 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'คน', 'name_th' => 'คน', 'name_en' => 'Person', 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ราย', 'name_th' => 'ราย', 'name_en' => 'Account', 'sort_order' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ครั้ง', 'name_th' => 'ครั้ง', 'name_en' => 'Time', 'sort_order' => 5, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ชิ้น', 'name_th' => 'ชิ้น', 'name_en' => 'Piece', 'sort_order' => 6, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'งาน', 'name_th' => 'งาน', 'name_en' => 'Task', 'sort_order' => 7, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'โครงการ', 'name_th' => 'โครงการ', 'name_en' => 'Project', 'sort_order' => 8, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'เอกสาร', 'name_th' => 'เอกสาร', 'name_en' => 'Document', 'sort_order' => 9, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ชั่วโมง', 'name_th' => 'ชั่วโมง', 'name_en' => 'Hour', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'วัน', 'name_th' => 'วัน', 'name_en' => 'Day', 'sort_order' => 11, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'เดือน', 'name_th' => 'เดือน', 'name_en' => 'Month', 'sort_order' => 12, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ไตรมาส', 'name_th' => 'ไตรมาส', 'name_en' => 'Quarter', 'sort_order' => 13, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ปี', 'name_th' => 'ปี', 'name_en' => 'Year', 'sort_order' => 14, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'คะแนน', 'name_th' => 'คะแนน', 'name_en' => 'Point', 'sort_order' => 15, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_units');
    }
};
