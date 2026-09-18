<?php

namespace Database\Seeders;

use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds Level 2 Key Results สำหรับทุกแผนกที่หัวหน้าส่งข้อมูลมา
 * แหล่งข้อมูลต้นทาง: qa-output/ข้อมูลลำดับชั้น2/*.xlsx
 *
 * Pattern ของไฟล์:
 *   - L1 → จัดเก็บใน GoalTargetSeeder.php (ปัจจุบัน sort_no 1-12)
 *   - L2 → จัดเก็บในไฟล์นี้ (ผูกกับ L1 sort_no ของแผนกตัวเอง)
 *
 * Mapping: L2 ของแต่ละแผนกผูกกับ L1 ของแผนกตัวเอง (sort_no ที่กำหนดใน GoalTargetSeeder)
 *   • HRM + HRD → L1 sort_no 1-4 (ใช้ร่วมกัน)
 *   • QMS       → L1 sort_no 5-12
 *
 * Prerequisite:
 *   php artisan db:seed --class=GoalTargetSeeder
 *
 * Run:
 *   php artisan db:seed --class=GoalTargetKeyResultSeeder
 *
 * Re-runnable: ใช้ updateOrCreate (idempotent บน okr_objective_id + dept_abbr_hr + sort_no)
 */
class GoalTargetKeyResultSeeder extends Seeder
{
    /**
     * Plan: dept_abbr_hr => [ L1_sort_no => [ {sort_no, title, detail}, ... ] ]
     *
     * ตัวเลขนำหน้า title (เช่น "1.", "2.") คงไว้ตามต้นฉบับ Excel
     *
     * @var array<string, array<int, array<int, array{sort_no: int, title: string, detail: string}>>>
     */
    private const PLAN = [
        // ============================================================
        // HRM — ผูก L1 sort_no 1-4 (HRM/HRD ใช้ร่วมกัน)
        // ที่มา: OKR HRM.xlsx
        // ============================================================
        'HRM' => [
            1 => [
                ['sort_no' => 1, 'title' => 'OJT Completion Rate (100%)',           'detail' => 'พนักงานใหม่และย้ายไลน์ทุกคนต้องสอบผ่าน OJT ก่อนลงปฏิบัติงานจริง'],
                ['sort_no' => 2, 'title' => 'Skills Matrix Accuracy (100%)',         'detail' => 'อัปเดตข้อมูลทักษะให้ตรงหน้างานจริง การันตี 0 NC จากการ Audit ของ Ford และ AAT'],
                ['sort_no' => 3, 'title' => 'Training Defect Correlation',           'detail' => 'ลดสถิติของเสีย (Defect) ที่เกิดจาก Human Error (ติดตามผลรวมกับ QC&QA)'],
                ['sort_no' => 4, 'title' => 'Absenteeism & Turnover Rate (<2.5%)',   'detail' => 'ควบคุมอัตราการขาดงานและลาออก (โดยเฉพาะกลุ่มมีทักษะ/ผ่านโปรแล้ว) ให้อยู่ในเกณฑ์ที่กำหนด'],
            ],
            2 => [
                ['sort_no' => 1, 'title' => 'Labor Cost Optimization (การปรับต้นทุนแรงงานให้เหมาะสม)',    'detail' => 'ควบคุมงบประมาณด้านบุคคล (ชั่วโมง OT, อัตรากำลังคน, ค่าใช้จ่ายแฝง) ให้คุ้มค่าที่สุด เพื่อสนับสนุนเป้าหมายการเพิ่มกำไรสุทธิ'],
                ['sort_no' => 2, 'title' => 'Productivity Enhancement (การเพิ่มผลิตภาพเพื่อดันยอดขาย)', 'detail' => 'พัฒนาทักษะบุคลากรให้มีความยืดหยุ่นและพร้อมตอบสนองแผนการผลิต เพื่อให้บริษัทผลิตและส่งมอบสินค้าได้ทันตามเป้ายอดขาย (100% OTD)'],
            ],
            3 => [
                ['sort_no' => 1, 'title' => 'Legal & Standard Compliance (TLS 8001 & IATF 16949)', 'detail' => 'พัฒนาระบบบริหารทรัพยากรบุคคลให้สอดคล้องกับข้อกำหนดทางกฎหมายแรงงานและมาตรฐานสากล (TLS 8001 & IATF 16949) แบบ 100% เพื่อสร้างความเชื่อมั่นให้กับลูกค้าและคู่ค้า'],
                ['sort_no' => 2, 'title' => 'Secure HR Information System',                        'detail' => 'ยกระดับการจัดเก็บและเข้าถึงข้อมูลพนักงานให้มีความปลอดภัยสูง (Data Security) ตามมาตรฐาน TISAX เพื่อป้องกันการรั่วไหลของข้อมูลความลับองค์กรและข้อมูลส่วนบุคคล'],
            ],
            4 => [
                ['sort_no' => 1, 'title' => 'ด้าน HR Process', 'detail' => 'ลดเวลาและขั้นตอนการทำงานซ้ำซ้อนในแผนก HR เอง (Zero Paper / Automation) เพื่อลดต้นทุนการบริหารจัดการ'],
            ],
        ],

        // ============================================================
        // HRD — เนื้อหา L2 เหมือนกับ HRM (ใช้ L1 sort_no 1-4 ร่วมกัน)
        // ============================================================
        'HRD' => [
            1 => [
                ['sort_no' => 1, 'title' => 'OJT Completion Rate (100%)',           'detail' => 'พนักงานใหม่และย้ายไลน์ทุกคนต้องสอบผ่าน OJT ก่อนลงปฏิบัติงานจริง'],
                ['sort_no' => 2, 'title' => 'Skills Matrix Accuracy (100%)',         'detail' => 'อัปเดตข้อมูลทักษะให้ตรงหน้างานจริง การันตี 0 NC จากการ Audit ของ Ford และ AAT'],
                ['sort_no' => 3, 'title' => 'Training Defect Correlation',           'detail' => 'ลดสถิติของเสีย (Defect) ที่เกิดจาก Human Error (ติดตามผลรวมกับ QC&QA)'],
                ['sort_no' => 4, 'title' => 'Absenteeism & Turnover Rate (<2.5%)',   'detail' => 'ควบคุมอัตราการขาดงานและลาออก (โดยเฉพาะกลุ่มมีทักษะ/ผ่านโปรแล้ว) ให้อยู่ในเกณฑ์ที่กำหนด'],
            ],
            2 => [
                ['sort_no' => 1, 'title' => 'Labor Cost Optimization (การปรับต้นทุนแรงงานให้เหมาะสม)',    'detail' => 'ควบคุมงบประมาณด้านบุคคล (ชั่วโมง OT, อัตรากำลังคน, ค่าใช้จ่ายแฝง) ให้คุ้มค่าที่สุด เพื่อสนับสนุนเป้าหมายการเพิ่มกำไรสุทธิ'],
                ['sort_no' => 2, 'title' => 'Productivity Enhancement (การเพิ่มผลิตภาพเพื่อดันยอดขาย)', 'detail' => 'พัฒนาทักษะบุคลากรให้มีความยืดหยุ่นและพร้อมตอบสนองแผนการผลิต เพื่อให้บริษัทผลิตและส่งมอบสินค้าได้ทันตามเป้ายอดขาย (100% OTD)'],
            ],
            3 => [
                ['sort_no' => 1, 'title' => 'Legal & Standard Compliance (TLS 8001 & IATF 16949)', 'detail' => 'พัฒนาระบบบริหารทรัพยากรบุคคลให้สอดคล้องกับข้อกำหนดทางกฎหมายแรงงานและมาตรฐานสากล (TLS 8001 & IATF 16949) แบบ 100% เพื่อสร้างความเชื่อมั่นให้กับลูกค้าและคู่ค้า'],
                ['sort_no' => 2, 'title' => 'Secure HR Information System',                        'detail' => 'ยกระดับการจัดเก็บและเข้าถึงข้อมูลพนักงานให้มีความปลอดภัยสูง (Data Security) ตามมาตรฐาน TISAX เพื่อป้องกันการรั่วไหลของข้อมูลความลับองค์กรและข้อมูลส่วนบุคคล'],
            ],
            4 => [
                ['sort_no' => 1, 'title' => 'ด้าน HR Process', 'detail' => 'ลดเวลาและขั้นตอนการทำงานซ้ำซ้อนในแผนก HR เอง (Zero Paper / Automation) เพื่อลดต้นทุนการบริหารจัดการ'],
            ],
        ],

        // ============================================================
        // QMS — ผูก L1 sort_no 5-12 (ของ QMS เอง)
        // ที่มา: OKR QMS.xlsx
        // L1 sort_no mapping:
        //   5  = Award Company Ford ,AAT
        //   6  = Award 5S
        //   7  = Sytem zero major NC
        //   8  = Innovation ,Kaizen, AI Implementation
        //   9  = C, C, T Commitment, Communication, Teamwork
        //   10 = Cyber Security
        //   11 = Profit&Sale
        //   12 = Globla engineering
        // ============================================================
        'QMS' => [
            // L1 #5: Award Company Ford ,AAT
            5 => [
                ['sort_no' => 1, 'title' => '1.Achieve & Ontime  Special process',               'detail' => 'Full Capable sytem (15 Score)'],
                ['sort_no' => 2, 'title' => '2.Available Certificate',                           'detail' => 'Full Capable sytem (15 Score)'],
                ['sort_no' => 3, 'title' => '3.MSA ( Manufactoring site assesment ) green 100%', 'detail' => 'Full Capable sytem (15 Score)'],
                ['sort_no' => 4, 'title' => '4.Compliace CSR (Customer Specific Requirements)',  'detail' => 'Full Capable sytem (15 Score)'],
            ],
            // L1 #6: Award 5S
            6 => [
                ['sort_no' => 1, 'title' => '1.ทำการตรวจประเมินภายในแบบเข้มข้น',           'detail' => 'วัฒนธรรมการมีส่วนร่วมของพนักงาน (ทำร่วมกับ GA, MFG)'],
                ['sort_no' => 2, 'title' => '2.พัฒนาอย่างต่อเนื่อง (Kaizen + Before-After)', 'detail' => 'วัฒนธรรมการมีส่วนร่วมของพนักงาน (ทำร่วมกับ GA, MFG)'],
            ],
            // L1 #7: Sytem zero major NC
            7 => [
                ['sort_no' => 1, 'title' => '1.Mandatory requirement Linkage (Frocess flow Control plan,  PFMEA ,JES IS,IR )', 'detail' => 'Major‑Risk Mapping จับจุดเสี่ยง Major ทุก Process'],
                ['sort_no' => 2, 'title' => '2.Audit Intelligence (Internal ) / ใช้ชุด Audit ที่ "ฉลาด" และ "เจาะจง Major"',     'detail' => 'Major‑Risk Mapping จับจุดเสี่ยง Major ทุก Process'],
                ['sort_no' => 3, 'title' => '3.Warning System (Pre‑NC) / ทำ Dashboard NC แบบ Real-time',                          'detail' => 'Major‑Risk Mapping จับจุดเสี่ยง Major ทุก Process'],
            ],
            // L1 #8: Innovation, Kaizen, AI Implementation
            8 => [
                ['sort_no' => 1, 'title' => 'Integration audit (การตรวจประเมินระบบแบบบูรณาการ)', 'detail' => 'Smart / Automated Integration Audit เพื่อ "บริหารความเสี่ยงและสมรรถนะองค์กร" ไม่ใช่แค่ Compliance'],
            ],
            // L1 #9: C, C, T (Commitment, Communication, Teamwork)
            9 => [
                ['sort_no' => 1, 'title' => 'Motivation = กระตุ้น "อยากทำ"',                       'detail' => 'Leadership behavior'],
                ['sort_no' => 2, 'title' => 'Penalty = ป้องกัน "ไม่กล้าทำผิดซ้ำ"',                  'detail' => 'Leadership behavior'],
                ['sort_no' => 3, 'title' => '(Project Progress Dashboard) เป้าหมายหลัก ตอบคำถามได้ภายใน 5 นาที', 'detail' => 'Leadership behavior'],
            ],
            // L1 #10: Cyber Security
            10 => [
                ['sort_no' => 1, 'title' => 'Critical Information Asset', 'detail' => 'Asset & Risk-driven: Customer data / Design Drawing / Production system / Supplier-Partner connection / Factory network'],
            ],
            // L1 #11: Profit&Sale
            11 => [
                ['sort_no' => 1, 'title' => 'Business plan & Sustainability', 'detail' => 'ระบบสนับสนุนให้ลูกค้าสนใจและเข้ามาหา'],
            ],
            // L1 #12: Globla engineering
            12 => [
                ['sort_no' => 1, 'title' => 'Global engineering', 'detail' => 'มาตรฐานที่ยอมรับและใช้ได้ทั่วโลก'],
            ],
        ],

        // ============================================================
        // เพิ่มแผนกใหม่ที่นี่ — pattern เดียวกัน
        // 'PD' => [ L1_sort_no => [{...}, ...], ... ],
        // ============================================================
    ];

    public function run(): void
    {
        $objBySortNo = OkrObjective::query()->get()->keyBy('sort_no');
        if ($objBySortNo->isEmpty()) {
            $this->command->warn('GoalTargetKeyResultSeeder: no L1 objectives found — run GoalTargetSeeder first.');
            return;
        }

        $deptCount = count(self::PLAN);
        $this->command->info("GoalTargetKeyResultSeeder: seeding L2 for {$deptCount} department(s).");

        $grandTotal = 0;
        DB::transaction(function () use ($objBySortNo, &$grandTotal): void {
            foreach (self::PLAN as $dept => $byL1) {
                $deptTotal = 0;
                $this->command->line('');
                $this->command->line("  ── {$dept} ──");

                foreach ($byL1 as $l1SortNo => $krList) {
                    $obj = $objBySortNo->get($l1SortNo);
                    if (! $obj) {
                        $this->command->warn("  [{$dept}] L1 sort#{$l1SortNo} not found — skipping.");
                        continue;
                    }

                    foreach ($krList as $krData) {
                        OkrKeyResult::query()->updateOrCreate(
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
                        $deptTotal++;
                    }
                }

                $this->command->line("  [{$dept}] {$deptTotal} L2 records ready.");
                $grandTotal += $deptTotal;
            }
        });

        $this->command->info("GoalTargetKeyResultSeeder: total {$grandTotal} L2 records across {$deptCount} dept(s).");
    }
}
