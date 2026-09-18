<?php

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\CycleMonth;
use Illuminate\Database\Seeder;

class CycleSeeder extends Seeder
{
    public function run(): void
    {
        $cycle = Cycle::create([
            'name'       => 'รอบประจำปี 2569',
            'code'       => 'FY2569',
            'start_date' => '2026-01-01',
            'end_date'   => '2026-12-31',
            'status'     => Cycle::STATUS_OPEN,
            'is_active'  => true,
            'opened_at'  => now(),
            'note'       => 'รอบการประเมิน OKR/KPI ประจำปีงบประมาณ พ.ศ. 2569',
        ]);

        $months = [
            1  => '2026-01-01',
            2  => '2026-02-01',
            3  => '2026-03-01',
            4  => '2026-04-01',
            5  => '2026-05-01',
            6  => '2026-06-01',
            7  => '2026-07-01',
            8  => '2026-08-01',
            9  => '2026-09-01',
            10 => '2026-10-01',
            11 => '2026-11-01',
            12 => '2026-12-01',
        ];

        foreach ($months as $monthNo => $openAt) {
            CycleMonth::create([
                'cycle_id'  => $cycle->id,
                'month_no'  => $monthNo,
                'open_at'   => $openAt . ' 00:00:00',
                'is_active' => true,
            ]);
        }
    }
}
