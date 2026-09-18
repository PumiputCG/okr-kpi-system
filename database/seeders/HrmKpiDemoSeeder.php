<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * HRM&HRD KPI Demo Seeder — สำหรับ Present
 * ─────────────────────────────────────────
 * รัน seeder ทั้งหมดที่เกี่ยวข้องกับแผนก HRM&HRD ตามลำดับ:
 *
 *  1. GoalTargetSeeder            — L1 OKR Objectives (company-wide)
 *  2. GoalTargetAllDeptSeeder     — L2 Key Results สำหรับทุกแผนก รวม HRM และ HRD
 *  3. AdminDepartmentAssignmentSeeder — กำหนดพนักงานตามแผนก (admin page)
 *  4. KpiReportHrmWelfareSeeder   — รายงาน KPI ทั้ง 5 กลุ่มงานของ HRM&HRD
 *                                   (Payroll / Time Attendance / Admin & Migrant /
 *                                    Welfare & CSR / Secretary of CEO)
 *
 * วิธีรัน:
 *   php artisan db:seed --class=HrmKpiDemoSeeder
 */
class HrmKpiDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GoalTargetSeeder::class,
            GoalTargetAllDeptSeeder::class,
            AdminDepartmentAssignmentSeeder::class,
            KpiReportHrmWelfareSeeder::class,
        ]);
    }
}
