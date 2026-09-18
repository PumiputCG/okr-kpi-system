<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            KpiTestSeeder::class,
            GoalTargetSeeder::class,
            GoalTargetAllDeptSeeder::class,
            GoalTargetAllDeptL3Seeder::class,
            AdminDepartmentAssignmentSeeder::class,
            KpiReportHrmWelfareSeeder::class,
        ]);
    }
}
