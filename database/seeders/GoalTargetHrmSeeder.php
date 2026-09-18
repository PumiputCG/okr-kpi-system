<?php

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds Level 2 Key Results for HRM and HRD departments (separately).
 * Requires GoalTargetSeeder (L1) to have been run first.
 */
class GoalTargetHrmSeeder extends Seeder
{
    private const DEPTS = ['HRM', 'HRD'];
    private const KPI_HRM_SOURCE = 'C:/Users/MS/OneDrive/เดสก์ท็อป/Supavut_work/OKR-KPI System/Document/KPI-HRM.xlsx';
    private const KPI_HRM_NAME   = 'KPI-HRM.xlsx';

    /**
     * L2 key results grouped by L1 sort_no.
     *
     * @var array<int, array<int, array{sort_no: int, title: string, detail: string}>>
     */
    private const KEY_RESULTS = [
        // ── L1 #1: Award Company ──
        1 => [
            [
                'sort_no' => 1,
                'title'   => 'OJT Completion Rate (100%)',
                'detail'  => 'พนักงานใหม่และย้ายไลน์ทุกคนต้องสอบผ่าน OJT ก่อนลงปฏิบัติงานจริง',
            ],
            [
                'sort_no' => 2,
                'title'   => 'Skills Matrix Accuracy (100%)',
                'detail'  => 'อัปเดตข้อมูลทักษะให้ตรงหน้างานจริง การันตี 0 NC จากการ Audit ของ Ford และ AAT',
            ],
            [
                'sort_no' => 3,
                'title'   => 'Training Defect Correlation',
                'detail'  => 'ลดสถิติของเสีย (Defect) ที่เกิดจาก Human Error (ติดตามผลรวมกับ QC&QA)',
            ],
            [
                'sort_no' => 4,
                'title'   => 'Absenteeism & Turnover Rate (<2.5%)',
                'detail'  => 'ควบคุมอัตราการขาดงานและลาออก (โดยเฉพาะกลุ่มมีทักษะ/ผ่านโปรแล้ว) ให้อยู่ในเกณฑ์ที่กำหนด',
            ],
        ],
        // ── L1 #2: Sales & Profit ──
        2 => [
            [
                'sort_no' => 1,
                'title'   => 'Labor Cost Optimization (การปรับต้นทุนแรงงานให้เหมาะสม)',
                'detail'  => 'ควบคุมงบประมาณด้านบุคคล (ชั่วโมง OT, อัตรากำลังคน, ค่าใช้จ่ายแฝง) ให้คุ้มค่าที่สุด เพื่อสนับสนุนเป้าหมายการเพิ่มกำไรสุทธิ',
            ],
            [
                'sort_no' => 2,
                'title'   => 'Productivity Enhancement (การเพิ่มผลิตภาพเพื่อดันยอดขาย)',
                'detail'  => 'พัฒนาทักษะบุคลากรให้มีความยืดหยุ่นและพร้อมตอบสนองแผนการผลิต เพื่อให้บริษัทผลิตและส่งมอบสินค้าได้ทันตามเป้ายอดขาย (100% OTD)',
            ],
        ],
        // ── L1 #3: Standard System & Cyber Security ──
        3 => [
            [
                'sort_no' => 1,
                'title'   => 'Legal & Standard Compliance (TLS 8001 & IATF 16949)',
                'detail'  => 'พัฒนาระบบบริหารทรัพยากรบุคคลให้สอดคล้องกับข้อกำหนดทางกฎหมายแรงงานและมาตรฐานสากล (TLS 8001 & IATF 16949) แบบ 100% เพื่อสร้างความเชื่อมั่นให้กับลูกค้าและคู่ค้า',
            ],
            [
                'sort_no' => 2,
                'title'   => 'Secure HR Information System',
                'detail'  => 'ยกระดับการจัดเก็บและเข้าถึงข้อมูลพนักงานให้มีความปลอดภัยสูง (Data Security) ตามมาตรฐาน TISAX เพื่อป้องกันการรั่วไหลของข้อมูลความลับองค์กรและข้อมูลส่วนบุคคล',
            ],
        ],
        // ── L1 #4: Innovation Organization ──
        4 => [
            [
                'sort_no' => 1,
                'title'   => 'ด้าน HR Process',
                'detail'  => 'ลดเวลาและขั้นตอนการทำงานซ้ำซ้อนในแผนก HR เอง (Zero Paper / Automation) เพื่อลดต้นทุนการบริหารจัดการ',
            ],
        ],
    ];

    public function run(): void
    {
        $cycle = Cycle::query()->where('is_active', true)->orderByDesc('id')->first()
            ?? Cycle::query()->orderByDesc('id')->first();

        if (! $cycle) {
            $this->command->warn('GoalTargetHrmSeeder: no cycle found — skipping.');
            return;
        }

        $this->command->info('GoalTargetHrmSeeder (L2): cycle #' . $cycle->id . ' — departments: ' . implode(', ', self::DEPTS));

        $objBySortNo = OkrObjective::query()
            ->where('cycle_id', (int) $cycle->id)
            ->get()
            ->keyBy('sort_no');

        if ($objBySortNo->isEmpty()) {
            $this->command->warn('  No L1 objectives found — run GoalTargetSeeder first.');
            return;
        }

        DB::transaction(function () use ($objBySortNo): void {
            foreach (self::DEPTS as $dept) {
                $this->command->line('');
                $this->command->line('  ── ' . $dept . ' ──');

                foreach (self::KEY_RESULTS as $l1SortNo => $keyResults) {
                    $obj = $objBySortNo->get($l1SortNo);
                    if (! $obj) {
                        $this->command->warn('  L1 sort_no ' . $l1SortNo . ' not found — skipping.');
                        continue;
                    }

                    foreach ($keyResults as $krData) {
                        $kr = OkrKeyResult::query()->updateOrCreate(
                            [
                                'okr_objective_id' => (int) $obj->id,
                                'dept_abbr_hr'     => $dept,
                                'sort_no'          => $krData['sort_no'],
                            ],
                            [
                                'title'  => $krData['title'],
                                'detail' => $krData['detail'] !== '' ? $krData['detail'] : null,
                            ]
                        );
                        $this->command->line(
                            '  L2 (L1 #' . $l1SortNo . ') #' . $krData['sort_no'] . ': ' . $krData['title']
                        );
                        $this->copyFile($kr);
                    }
                }
            }
        });

        $this->command->info('GoalTargetHrmSeeder (L2): done.');
    }

    private function copyFile(OkrKeyResult $kr): void
    {
        $source = self::KPI_HRM_SOURCE;
        if ($source === '' || ! file_exists($source)) {
            return;
        }

        $rel = 'goal-targets/key-results/' . $kr->id . '/' . self::KPI_HRM_NAME;
        $abs = storage_path('app/public/' . $rel);
        $dir = dirname($abs);

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (! copy($source, $abs)) {
            $this->command->warn('  Could not copy KPI-HRM.xlsx for L2 #' . $kr->id);
            return;
        }

        $kr->update(['file_path' => $rel, 'file_original_name' => self::KPI_HRM_NAME]);
        $this->command->line('    Attached KPI-HRM.xlsx → ' . $rel);
    }
}
