<?php

namespace Database\Seeders;

use App\Models\AdminDepartmentAssignment;
use App\Models\Cycle;
use App\Models\KpiMonthScore;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds L2 Key Results + L3 KPI Targets for HRM and HRD (separately, same content).
 * Self-contained: creates L2 KRs if they don't exist, then seeds L3 targets.
 * Target owner: นายฐานพัฒน์ พิมายกลาง (71019) — ดึงจาก admin_department_assignments.
 */
class GoalTargetHrmL3Seeder extends Seeder
{
    private const DEPTS = ['HRM', 'HRD'];

    /**
     * L2 Key Results — เหมือนกันทั้ง HRM และ HRD
     *
     * @var array<int, array<int, array{sort_no: int, title: string, detail: string}>>
     */
    private const KEY_RESULTS = [
        // ── L1 #1: Award Company ──
        1 => [
            ['sort_no' => 1, 'title' => 'OJT Completion Rate (100%)',             'detail' => 'พนักงานใหม่และย้ายไลน์ทุกคนต้องสอบผ่าน OJT ก่อนลงปฏิบัติงานจริง'],
            ['sort_no' => 2, 'title' => 'Skills Matrix Accuracy (100%)',           'detail' => 'อัปเดตข้อมูลทักษะให้ตรงหน้างานจริง การันตี 0 NC จากการ Audit ของ Ford และ AAT'],
            ['sort_no' => 3, 'title' => 'Training Defect Correlation',             'detail' => 'ลดสถิติของเสีย (Defect) ที่เกิดจาก Human Error (ติดตามผลรวมกับ QC&QA)'],
            ['sort_no' => 4, 'title' => 'Absenteeism & Turnover Rate (<2.5%)',     'detail' => 'ควบคุมอัตราการขาดงานและลาออก (โดยเฉพาะกลุ่มมีทักษะ/ผ่านโปรแล้ว) ให้อยู่ในเกณฑ์ที่กำหนด'],
        ],
        // ── L1 #2: Sales & Profit ──
        2 => [
            ['sort_no' => 1, 'title' => 'Labor Cost Optimization (การปรับต้นทุนแรงงานให้เหมาะสม)',    'detail' => 'ควบคุมงบประมาณด้านบุคคล (ชั่วโมง OT, อัตรากำลังคน, ค่าใช้จ่ายแฝง) ให้คุ้มค่าที่สุด เพื่อสนับสนุนเป้าหมายการเพิ่มกำไรสุทธิ'],
            ['sort_no' => 2, 'title' => 'Productivity Enhancement (การเพิ่มผลิตภาพเพื่อดันยอดขาย)', 'detail' => 'พัฒนาทักษะบุคลากรให้มีความยืดหยุ่นและพร้อมตอบสนองแผนการผลิต เพื่อให้บริษัทผลิตและส่งมอบสินค้าได้ทันตามเป้ายอดขาย (100% OTD)'],
        ],
        // ── L1 #3: Standard System & Cyber Security ──
        3 => [
            ['sort_no' => 1, 'title' => 'Legal & Standard Compliance (TLS 8001 & IATF 16949)', 'detail' => 'พัฒนาระบบบริหารทรัพยากรบุคคลให้สอดคล้องกับข้อกำหนดทางกฎหมายแรงงานและมาตรฐานสากล (TLS 8001 & IATF 16949) แบบ 100% เพื่อสร้างความเชื่อมั่นให้กับลูกค้าและคู่ค้า'],
            ['sort_no' => 2, 'title' => 'Secure HR Information System',                        'detail' => 'ยกระดับการจัดเก็บและเข้าถึงข้อมูลพนักงานให้มีความปลอดภัยสูง (Data Security) ตามมาตรฐาน TISAX เพื่อป้องกันการรั่วไหลของข้อมูลความลับองค์กรและข้อมูลส่วนบุคคล'],
        ],
        // ── L1 #4: Innovation Organization ──
        4 => [
            ['sort_no' => 1, 'title' => 'ด้าน HR Process', 'detail' => 'ลดเวลาและขั้นตอนการทำงานซ้ำซ้อนในแผนก HR เอง (Zero Paper / Automation) เพื่อลดต้นทุนการบริหารจัดการ'],
        ],
    ];

    /**
     * L3 Targets — เหมือนกันทั้ง HRM และ HRD
     * จัดกลุ่มตาม [l1_sort_no][l2_sort_no]
     *
     * unit_id: 2=% 3=คน 4=ราย 5=ครั้ง 7=งาน 9=เอกสาร 10=ชั่วโมง 12=เดือน 15=คะแนน
     *
     * @var array<int, array<int, list<array{objective: string, detail: string, target_value: float, unit_id: int|null, criteria_operator: string}>>>
     */
    private const TARGETS = [
        // ── L1 #1: Award Company ──────────────────────────────────────
        1 => [
            1 => [
                ['objective' => 'พนักงานที่เข้าไลน์ผลิตก่อนผ่าน OJT (Zero Tolerance)',    'detail' => 'หากพนักงานยังสอบ OJT ไม่ผ่าน HR จะไม่อนุญาตให้เข้าพื้นที่ไลน์ผลิตเด็ดขาด วัดจากจำนวนพนักงานที่ถูกพบว่าเข้าไลน์โดยยังไม่ผ่าน OJT ต้องเป็น 0 ราย', 'target_value' => 0.0,   'unit_id' => 4,    'criteria_operator' => '='],
                ['objective' => 'จำนวน Trainer/Buddy ที่พูดภาษาพม่าได้ต่อไลน์',           'detail' => 'ต้องมี Trainer หรือ Buddy ที่เป็นชาวไทยและพม่า และมีเอกสาร/ข้อสอบประเมิน OJT เป็นภาษาพม่า เพื่อให้พนักงานชาวพม่าเข้าใจในงานจริงๆ', 'target_value' => 1.0,   'unit_id' => 3,    'criteria_operator' => '>='],
            ],
            2 => [
                ['objective' => 'อัตราส่งอัปเดต Skills Matrix ตามกำหนด (ภายในวันที่ 25/เดือน)', 'detail' => 'ระบบรายงานผลแบบ Real-time กำหนดรอบให้หัวหน้างาน (Line Leader) ต้องส่งอัปเดตระดับทักษะพนักงานให้ HR ภายในวันที่ 25 ของทุกเดือน หากหัวหน้างานไม่ส่ง จะมีผลต่อ KPI ของหัวหน้างานเอง', 'target_value' => 100.0, 'unit_id' => 2,    'criteria_operator' => '>='],
                ['objective' => 'เวลาเตรียมเอกสาร Skills Matrix ให้ Auditor ≤ 3 นาที',       'detail' => 'ต้องจัด Audit-Ready โดยจัดเตรียมเอกสารระบบอัตโนมัติ แบบฟอร์มประเมินทักษะที่มีใบ Transcript ระบุว่าผู้เรียนเรียนครบ พร้อมให้ Auditor ตรวจสอบได้ภายใน 3 นาที', 'target_value' => 3.0,   'unit_id' => null, 'criteria_operator' => '<='],
            ],
            3 => [
                ['objective' => 'อัตราดำเนิน Re-training ทันทีหลังพบ Human Error', 'detail' => 'สร้างระบบวงจรฝึกอบรมซ้ำ (Re-Training Loop): เมื่อ QC&QA พบของเสียจาก Human Error HR ต้องลงพื้นที่ร่วมกับ QA ทันที เพื่อวิเคราะห์ว่าพนักงานพลาดเพราะ "ไม่รู้" หรือ "ประมาท" หากเกิดจากไม่รู้ ต้อง Re-train ทันที วัดจาก % กรณีที่ดำเนินการครบ', 'target_value' => 100.0, 'unit_id' => 2,    'criteria_operator' => '>='],
                ['objective' => 'จำนวน One Point Lesson (OPL) ที่ติดตั้งต่อจุดเสี่ยง',        'detail' => 'ใช้ One Point Lesson (OPL): ทำป้ายภาพถ่ายใบเล็กๆ (จุดที่ถูก-จุดที่ผิด) พร้อมภาษาไทย-พม่า แปะไว้ที่โต๊ะทำงานของพนักงานจุดที่เกิดปัญหาบ่อยๆ เพื่อเตือนความจำ วัดจาก ≥ 1 OPL ต่อจุดที่มี Human Error', 'target_value' => 1.0,   'unit_id' => 9,    'criteria_operator' => '>='],
            ],
            4 => [
                ['objective' => 'ความถี่ Proactive Floor Walk ของ HR ต่อสัปดาห์',           'detail' => 'ลงพื้นที่เชิงรุก (Proactive Floor Walk): HR ต้องเดินสายคุยกับพนักงานหน้าไลน์สัปดาห์ละ 1-2 ครั้ง เพื่อรับฟังปัญหา ก่อนที่ปัญหาเหล่านั้นจะกลายเป็นเหตุในใบลาออก', 'target_value' => 1.0,   'unit_id' => 5,    'criteria_operator' => '>='],
                ['objective' => 'อัตราหัวหน้างานผ่านอบรม Soft Skill / การบริหารคน',          'detail' => 'พัฒนา "ทักษะ" ให้หัวหน้างาน เพราะพนักงานมักลาออกจากหัวหน้างาน HR ต้องจัดอบรมหัวหน้างานด้าน Soft Skill และจิตวิทยาการสื่อสาร/การบริหารคน เพื่อลดความขัดแย้งหน้างาน', 'target_value' => 100.0, 'unit_id' => 2,    'criteria_operator' => '>='],
            ],
        ],
        // ── L1 #2: Sales & Profit ─────────────────────────────────────
        2 => [
            1 => [
                ['objective' => 'OT จริงเทียบแผน ไม่เกินที่กำหนด (Lean Manpower & OT Control)', 'detail' => 'Lean Manpower & OT Control: บริหารจัดการอัตรากำลังคนและชั่วโมง OT ให้สอดคล้องกับยอดออเดอร์จริงอย่างแม่นยำ ลดการจ้างคนเกิน/ลด OT ที่ไม่จำเป็น วัดจาก OT จริง ≤ แผน+5%', 'target_value' => 5.0,   'unit_id' => 2,    'criteria_operator' => '<='],
                ['objective' => 'จำนวนค่าปรับด้านกฎหมายแรงงาน (Zero Fines)',                   'detail' => 'Zero Fines: จัดการเอกสารงานต่างๆ ภาษี และกฎหมายแรงงานให้ถูกต้อง 100% ทันเวลา เพื่อป้องกันค่าปรับจากหน่วยงานรัฐที่ทำให้บริษัทสูญเสียงบประมาณ', 'target_value' => 0.0,   'unit_id' => 5,    'criteria_operator' => '='],
                ['objective' => 'จำนวน Internal Trainer ที่สร้างขึ้นภายใน (Direct & In-direct Labour)', 'detail' => 'การสร้าง Internal Trainer ในส่วนของ Direct Labour และ In-direct Labour โดยการลดค่าใช้จ่ายจากการใช้สถาบันฝึกอบรมภายนอก วัดจากจำนวน Internal Trainer ที่ผ่านการรับรองและสอนได้จริง', 'target_value' => 2.0,   'unit_id' => 3,    'criteria_operator' => '>='],
            ],
            2 => [
                ['objective' => 'อัตราพนักงานผ่าน Multi-Skill Cross-Training ≥ 1 งาน',           'detail' => 'Multi-Skill Training: อบรมพนักงานให้ทำได้หลายหน้าที่ (Cross-training) เพื่อให้สามารถสลับสับเปลี่ยนกำลังคนแก้ปัญหา Bottleneck ในไลน์ผลิตได้ทันที โดยไม่ต้องเพิ่มต้นทุนจ้างคนใหม่', 'target_value' => 80.0,  'unit_id' => 2,    'criteria_operator' => '>='],
                ['objective' => 'อัตราพนักงานสำนักงานผ่านหลักสูตร Automotive (VDA / CQI / Core Tools)', 'detail' => 'หลักสูตรฝึกอบรมพนักงานส่วนสำนักงานต้องครอบคลุม VDA [Germany+Europe], CQI [American], Core Tools Automotive [Mandatory] ครบ 100% ของพนักงานที่เกี่ยวข้อง', 'target_value' => 100.0, 'unit_id' => 2,    'criteria_operator' => '>='],
            ],
        ],
        // ── L1 #3: Standard System & Cyber Security ───────────────────
        3 => [
            1 => [
                ['objective' => 'ความถูกต้องและครบถ้วนของ Skills Matrix, Training Record และ JD', 'detail' => 'ข้อมูลทักษะพนักงานประเภท Blue Collar (อัปเดต Skills Matrix), ประวัติการฝึกอบรมทั้ง White & Blue Collar (Training Record) และใบคำอธิบายลักษณะงาน (JD) ของพนักงานทั้งบริษัท ต้องอัปเดตและสอดคล้องกับข้อกำหนด 100%', 'target_value' => 100.0, 'unit_id' => 2,    'criteria_operator' => '>='],
                ['objective' => 'ความถี่ Self-Audit เอกสาร HR ภายในแผนก (ทุกเดือน)',             'detail' => 'กำหนดรอบการตรวจสอบความถูกต้องของเอกสารภายในแผนก (Self-Audit) ทุกเดือน เพื่อให้มั่นใจว่าเมื่อลูกค้าหรือผู้ตรวจประเมินมาตรวจ สามารถแสดงหลักฐานที่ถูกต้องได้ภายใน 3 นาที', 'target_value' => 1.0,   'unit_id' => 12,   'criteria_operator' => '>='],
            ],
            2 => [
                ['objective' => 'อัตราตรวจสอบประวัติ Background Check ตำแหน่งเข้าถึงข้อมูลลับ',    'detail' => 'การคัดเลือกบุคลากร (Pre-employment): เพิ่มขั้นตอนการตรวจสอบประวัติ (Background Check) สำหรับตำแหน่งที่เข้าถึงข้อมูลความลับสูง และระบุข้อตกลงการรักษาความลับ (NDA) ตั้งแต่วันที่เซ็นสัญญาจ้าง', 'target_value' => 100.0, 'unit_id' => 2,    'criteria_operator' => '>='],
                ['objective' => 'อัตราพนักงานสำนักงานผ่านอบรม Information Security Awareness',    'detail' => 'พนักงานส่วนสำนักงานต้องผ่านการอบรม "Information Security Awareness" 100% ต่อปี ติดตามหลักฐานการเข้าอบรมและผลทดสอบ', 'target_value' => 100.0, 'unit_id' => 2,    'criteria_operator' => '>='],
                ['objective' => 'ความถี่ประชาสัมพันธ์ Information Security (ทุกเดือน ทุกช่องทาง)', 'detail' => 'ประชาสัมพันธ์เรื่องความปลอดภัยข้อมูลผ่านช่องทางต่างๆ (Line Group, ป้ายประกาศ, บอร์ดโรงอาหาร) อย่างต่อเนื่อง ไม่ใช่แค่ช่วง Audit — ต้องดำเนินการสม่ำเสมอ ≥ 1 ครั้ง/เดือน', 'target_value' => 1.0,   'unit_id' => 12,   'criteria_operator' => '>='],
            ],
        ],
        // ── L1 #4: Innovation Organization ────────────────────────────
        4 => [
            1 => [
                ['objective' => 'จำนวนกระบวนการ HR ที่นำ Digital / Automation มาใช้',            'detail' => 'นำนวัตกรรมด้วย HR (HR Tech & Automation): ปรับกระบวนการงาน HR ที่ซ้ำซ้อนให้ใช้ระบบดิจิทัลแทน เช่น ระบบลา ระบบ OJT Online ระบบประเมิน เพื่อลดขั้นตอน Paper และเพิ่มความแม่นยำ', 'target_value' => 3.0,   'unit_id' => 7,    'criteria_operator' => '>='],
                ['objective' => 'อัตราหัวหน้างานและพนักงานสำนักงานผ่านอบรม Lean / Kaizen / 7Q Tools', 'detail' => 'จัดอบรมหัวหน้างาน (Line Leader) และพนักงานส่วนสำนักงานด้าน "Lean", "Kaizen", หรือ "7Q Tools" เพื่อให้มีทักษะในการมองหาจุดบกพร่อง และนำทีมลูกน้องทำ Kaizen ได้อย่างถูกต้อง', 'target_value' => 100.0, 'unit_id' => 2,    'criteria_operator' => '>='],
            ],
        ],
    ];

    public function run(): void
    {
        $cycle = Cycle::query()->where('is_active', true)->orderByDesc('id')->first()
            ?? Cycle::query()->orderByDesc('id')->first();

        if (! $cycle) {
            $this->command->warn('GoalTargetHrmL3Seeder: no cycle found — skipping.');
            return;
        }

        $this->command->info('GoalTargetHrmL3Seeder: cycle #' . $cycle->id . ' — ' . implode(', ', self::DEPTS));

        $objBySortNo = OkrObjective::query()
            ->get()
            ->keyBy('sort_no');

        if ($objBySortNo->isEmpty()) {
            $this->command->warn('  No L1 objectives found — run GoalTargetSeeder first.');
            return;
        }

        DB::transaction(function () use ($cycle, $objBySortNo): void {
            // ── 1. ลบ L3 เก่าของ HRM, HRD และ HRM&HRD ทั้งหมดก่อน ──
            $clearDepts = array_merge(self::DEPTS, ['HRM&HRD']);
            $allKrIds   = OkrKeyResult::query()
                ->whereIn('dept_abbr_hr', $clearDepts)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->values()
                ->all();

            if ($allKrIds !== []) {
                $deleted = KpiMonthScore::query()
                    ->where('mode_type', 'target')
                    ->whereIn('okr_key_result_id', $allKrIds)
                    ->delete();
                if ($deleted > 0) {
                    $this->command->line('  Cleared ' . $deleted . ' old L3 record(s).');
                }
            }

            $grandTotal = 0;

            foreach (self::DEPTS as $dept) {
                $this->command->line('');
                $this->command->line('  ── ' . $dept . ' ──');

                $userId = $this->resolveUserId($dept);
                if ($userId === 0) {
                    $this->command->warn('  [' . $dept . '] ไม่พบ target_user ใน admin_department_assignments — skipping.');
                    continue;
                }
                $this->command->line('  [' . $dept . '] target user #' . $userId);

                // ── 2. สร้าง L2 KRs สำหรับ dept นี้ (updateOrCreate) ──
                foreach (self::KEY_RESULTS as $l1SortNo => $krList) {
                    $obj = $objBySortNo->get($l1SortNo);
                    if (! $obj) {
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
                    }
                }
                $this->command->line('  [' . $dept . '] L2 KRs ready.');

                // ── 3. สร้าง L3 Targets ──
                $total = 0;
                foreach (self::TARGETS as $l1SortNo => $l2Map) {
                    $obj = $objBySortNo->get($l1SortNo);
                    if (! $obj) {
                        $this->command->warn('  L1 sort#' . $l1SortNo . ' not found — skipping.');
                        continue;
                    }

                    foreach ($l2Map as $l2SortNo => $targets) {
                        $kr = OkrKeyResult::query()
                            ->where('okr_objective_id', (int) $obj->id)
                            ->where('dept_abbr_hr', $dept)
                            ->where('sort_no', $l2SortNo)
                            ->first();

                        if (! $kr) {
                            $this->command->warn('  [' . $dept . '] L2 (L1 #' . $l1SortNo . ' sort#' . $l2SortNo . ') not found — skipping.');
                            continue;
                        }

                        foreach ($targets as $t) {
                            KpiMonthScore::query()->create([
                                'app_user_id'       => $userId,
                                'cycle_id'          => (int) $cycle->id,
                                'okr_objective_id'  => (int) $obj->id,
                                'okr_key_result_id' => (int) $kr->id,
                                'month_no'          => 0,
                                'objective'         => $t['objective'],
                                'detail'            => $t['detail'],
                                'target_value'      => $t['target_value'],
                                'kpi_unit_id'       => $t['unit_id'],
                                'criteria_operator' => $t['criteria_operator'],
                                'mode_type'         => 'target',
                                'score_value'       => 0,
                                'is_pass'           => false,
                            ]);
                            $total++;
                        }
                    }
                }

                $this->command->info('  [' . $dept . '] L3 records created: ' . $total);
                $grandTotal += $total;
            }

            $this->command->info('  Total: ' . $grandTotal . ' L3 records.');
        });

        $this->command->info('GoalTargetHrmL3Seeder: done.');
    }

    private function resolveUserId(string $dept): int
    {
        $assignment = AdminDepartmentAssignment::query()
            ->where('dept_abbr_hr', $dept)
            ->first();

        if ($assignment && $assignment->target_user_id > 0) {
            return (int) $assignment->target_user_id;
        }

        // fallback: HRM&HRD (ชื่อเดิมก่อนแยก)
        $assignment = AdminDepartmentAssignment::query()
            ->where('dept_abbr_hr', 'HRM&HRD')
            ->first();

        return (int) ($assignment?->target_user_id ?? 0);
    }
}
