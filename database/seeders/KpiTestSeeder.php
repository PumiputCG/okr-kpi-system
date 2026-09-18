<?php

namespace Database\Seeders;

use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\CycleMonth;
use App\Models\KpiMonthScore;
use App\Models\KpiResult;
use App\Models\KpiUnit;
use App\Models\OkrAllResult;
use App\Models\OkrResult;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KpiTestSeeder extends Seeder
{
    private const SPECIAL_PENDING_EMPLOYEE_CODE = '71019';
    private const SPECIAL_PENDING_DEFAULT_DEPARTMENT = 'HRM&HRD';

    /**
     * Only these positions receive seeded KPI reports.
     *
     * @var array<int, string>
     */
    private const ALLOWED_POSITIONS = [
        'supervisor',
        'senior staff',
        'staff',
        'engineer',
        'senior engineer',
        'assist manager',
        'assistant manager',
        'manager',
        'deputy general manager',
        'general manager',
    ];

    /**
     * Positions that can explicitly target department in KPI card.
     *
     * @var array<int, string>
     */
    private const TARGET_DEPARTMENT_POSITIONS = [
        'assist manager',
        'assistant manager',
        'manager',
        'deputy general manager',
        'general manager',
    ];

    public function run(): void
    {
        $cycles = $this->ensureCycles();
        if ($cycles->isEmpty()) {
            return;
        }

        $users = $this->eligibleUsers();
        if ($users->isEmpty()) {
            return;
        }

        $unitIds = $this->activeUnitIds();
        if ($unitIds === []) {
            return;
        }

        $allNonAdminUserIds = AppUser::query()
            ->where('employee_code', '!=', 'admin')
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($cycles, $users, $unitIds, $allNonAdminUserIds): void {
            $cycleIds = $cycles->pluck('id')->all();

            KpiMonthScore::query()
                ->whereIn('app_user_id', $allNonAdminUserIds)
                ->whereIn('cycle_id', $cycleIds)
                ->delete();

            KpiResult::query()
                ->whereIn('app_user_id', $allNonAdminUserIds)
                ->whereIn('cycle_id', $cycleIds)
                ->delete();

            OkrResult::query()
                ->whereIn('cycle_id', $cycleIds)
                ->delete();

            OkrAllResult::query()
                ->whereIn('cycle_id', $cycleIds)
                ->delete();

            foreach ($cycles as $cycle) {
                $this->seedCycleKpi((int) $cycle->id, $cycle, $users, $unitIds);
                $this->seedPendingIndicatorsForSpecialAccount((int) $cycle->id, $users, $unitIds);
                $this->syncResultsByCycle((int) $cycle->id);
                $this->assertCycleCalculationSeeded((int) $cycle->id);
            }
        });
    }

    private function ensureCycles(): Collection
    {
        $cycles = Cycle::query()->orderBy('id')->get();
        if ($cycles->isNotEmpty()) {
            return $cycles;
        }

        $cycle = Cycle::query()->create([
            'name' => 'Test Cycle 2026',
            'code' => 'TEST-2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => Cycle::STATUS_OPEN,
            'is_active' => true,
            'opened_at' => now(),
            'closed_at' => null,
            'note' => 'Seeded automatically by KpiTestSeeder',
        ]);

        for ($month = 1; $month <= 12; $month++) {
            CycleMonth::query()->updateOrCreate(
                [
                    'cycle_id' => (int) $cycle->id,
                    'month_no' => $month,
                ],
                [
                    'is_active' => true,
                    'open_at' => Carbon::parse('2026-01-01')->addMonthsNoOverflow($month - 1)->startOfMonth(),
                ]
            );
        }

        return Cycle::query()->orderBy('id')->get();
    }

    private function eligibleUsers(): Collection
    {
        $positionPlaceholders = implode(',', array_fill(0, count(self::ALLOWED_POSITIONS), '?'));

        return AppUser::query()
            ->where('employee_code', '!=', 'admin')
            ->whereRaw('LOWER(TRIM(COALESCE(position, ""))) IN ('.$positionPlaceholders.')', self::ALLOWED_POSITIONS)
            ->orderBy('id')
            ->get([
                'id',
                'employee_code',
                'full_name_th',
                'full_name_en',
                'position',
                'dept_abbr_hr',
            ]);
    }

    /**
     * @return array<int, int>
     */
    private function activeUnitIds(): array
    {
        $active = KpiUnit::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($active !== []) {
            return $active;
        }

        return KpiUnit::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param array<int, int> $unitIds
     */
    private function seedCycleKpi(int $cycleId, Cycle $cycle, Collection $users, array $unitIds): void
    {
        $templates = $this->kpiTemplates();
        $templateCount = count($templates);
        $unitCount = count($unitIds);

        foreach ($users as $userIndex => $user) {
            if ((string) ($user->employee_code ?? '') === self::SPECIAL_PENDING_EMPLOYEE_CODE) {
                continue;
            }

            $targetDepartments = $this->resolveSeedTargetDepartments($user);

            // 2 or 3 KPI reports per eligible user.
            $reportCount = 2 + (($userIndex + $cycleId) % 2);

            for ($itemIndex = 0; $itemIndex < $reportCount; $itemIndex++) {
                $template = $templates[($userIndex * 3 + $itemIndex + $cycleId) % $templateCount];
                $unitId = $unitIds[($userIndex + $itemIndex + $cycleId) % $unitCount];

                $monthRows = [];
                $passCount = 0;

                for ($monthNo = 1; $monthNo <= 12; $monthNo++) {
                    $target = (float) $template['target'];
                    $operator = (string) $template['operator'];

                    $score = $this->generateScoreByRule(
                        $target,
                        $operator,
                        (int) $userIndex,
                        $itemIndex,
                        $monthNo,
                        $cycleId
                    );
                    $isPass = $this->evaluateScore($score, $target, $operator);
                    if ($isPass) {
                        $passCount++;
                    }

                    $monthRows[] = [
                        'month_no' => $monthNo,
                        'score_value' => $score,
                        'is_pass' => $isPass,
                        'submitted_at' => $this->resolveSubmittedAt($cycle, $monthNo),
                    ];
                }

                $result = round(($passCount / 12) * 100, 2);

                $root = KpiMonthScore::query()->create([
                    'app_user_id' => (int) $user->id,
                    'cycle_id' => $cycleId,
                    'kpi_meta_id' => null,
                    'month_no' => 0,
                    'objective' => (string) $template['objective'],
                    'detail' => (string) $template['detail'],
                    'target_departments' => $targetDepartments,
                    'target_value' => (float) $template['target'],
                    'kpi_unit_id' => $unitId,
                    'criteria_operator' => (string) $template['operator'],
                    'score_value' => 0,
                    'is_pass' => false,
                    'result' => $result,
                    'evidence_files' => null,
                    'submitted_at' => null,
                ]);

                $payload = [];
                foreach ($monthRows as $monthRow) {
                    $payload[] = [
                        'app_user_id' => (int) $user->id,
                        'cycle_id' => $cycleId,
                        'kpi_meta_id' => (int) $root->id,
                        'month_no' => (int) $monthRow['month_no'],
                        'objective' => (string) $template['objective'],
                        'detail' => (string) $template['detail'],
                        'target_departments' => $targetDepartments === []
                            ? null
                            : json_encode($targetDepartments, JSON_UNESCAPED_UNICODE),
                        'target_value' => (float) $template['target'],
                        'kpi_unit_id' => $unitId,
                        'criteria_operator' => (string) $template['operator'],
                        'score_value' => (float) $monthRow['score_value'],
                        'is_pass' => (bool) $monthRow['is_pass'],
                        'result' => $result,
                        'evidence_files' => null,
                        'submitted_at' => $monthRow['submitted_at'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($payload !== []) {
                    DB::table('kpi_month_scores')->insert($payload);
                }
            }
        }
    }

    /**
     * @param array<int, int> $fallbackUnitIds
     */
    private function seedPendingIndicatorsForSpecialAccount(int $cycleId, Collection $users, array $fallbackUnitIds): void
    {
        /** @var AppUser|null $targetUser */
        $targetUser = $users->first(function (AppUser $user): bool {
            return (string) ($user->employee_code ?? '') === self::SPECIAL_PENDING_EMPLOYEE_CODE;
        });

        if (! $targetUser) {
            $targetUser = AppUser::query()
                ->where('employee_code', self::SPECIAL_PENDING_EMPLOYEE_CODE)
                ->first(['id', 'employee_code', 'dept_abbr_hr']);
        }

        if (! $targetUser) {
            return;
        }

        $targetDepartment = $this->normalizeDepartmentCode((string) ($targetUser->dept_abbr_hr ?? ''));
        if ($targetDepartment === '') {
            $targetDepartment = self::SPECIAL_PENDING_DEFAULT_DEPARTMENT;
        }

        $rows = $this->specialPendingIndicators();
        if ($rows === []) {
            return;
        }

        foreach ($rows as $row) {
            $objective = trim((string) ($row['objective'] ?? ''));
            $detail = trim((string) ($row['detail'] ?? ''));
            if ($objective === '' || $detail === '') {
                continue;
            }

            $unitCode = trim((string) ($row['unit_code'] ?? '%'));
            $unitId = $this->resolveUnitIdByCodeOrFallback($unitCode, $fallbackUnitIds);
            if ($unitId < 1) {
                continue;
            }

            KpiMonthScore::query()->create([
                'app_user_id' => (int) $targetUser->id,
                'cycle_id' => $cycleId,
                'kpi_meta_id' => null,
                'month_no' => 0,
                'objective' => $objective,
                'detail' => $detail,
                'target_departments' => [$targetDepartment],
                'target_value' => (float) ($row['target'] ?? 100),
                'kpi_unit_id' => $unitId,
                'criteria_operator' => (string) ($row['operator'] ?? '>='),
                'score_value' => 0,
                'is_pass' => false,
                'result' => null,
                'evidence_files' => null,
                'action_plan_files' => null,
                'submitted_at' => null,
            ]);
        }
    }

    /**
     * @return array<int, array{objective:string,detail:string,target:float,operator:string,unit_code:string}>
     */
    private function specialPendingIndicators(): array
    {
        return [
            [
                'objective' => 'OJT Gate Control & Trainer/Buddy Readiness',
                'detail' => "1.1 หากพนักงานยังสอบ OJT ไม่ผ่าน HR จะไม่อนุญาตให้เข้าพื้นที่ไลน์ผลิตเด็ดขาด\n1.2 ต้องมี Trainer หรือ Buddy ที่เป็นชาวไทยและพม่า และมีเอกสาร/ข้อสอบประเมิน OJT เป็นภาษาพม่า เพื่อให้พนักงานเข้าใจเนื้องานจริงๆ",
                'target' => 100,
                'operator' => '>=',
                'unit_code' => '%',
            ],
            [
                'objective' => 'Real-time Skill Reporting & Audit-Ready Records',
                'detail' => "2.1 ระบบรายงานผลแบบ Real-time กำหนดรอบให้หัวหน้างาน (Line Leader) ต้องส่งอัปเดตระดับทักษะพนักงานให้ HR ภายในวันที่ 25 ของทุกเดือน หากหัวหน้างานไม่ส่ง จะมีผลต่อ KPI ของหัวหน้างานเอง\n2.2 การระบบ Audit-Ready จัดเก็บเอกสารระบบอัติโนมัติ แบบฟอร์มประเมินทักษะที่มีใบ Transcript เพื่อระบุว่าผู้เรียนนั้น เรียนครบถ้วน พร้อมให้ Auditor ขอ ตรวจสอบได้ภายใน 3 นาที",
                'target' => 25,
                'operator' => '<=',
                'unit_code' => 'วัน',
            ],
            [
                'objective' => 'Re-Training Loop & One Point Lesson (OPL)',
                'detail' => "3.1 สร้างระบบวงจรฝึกอบรมซ้ำ (Re-Training Loop): เมื่อ QC&QA พบของเสียจาก Human Error HR ต้องลงพื้นที่ร่วมกับ QA ทันที เพื่อวิเคราะห์ว่าพนักงานพลาดเพราะ \"ไม่รู้\" หรือ \"ประมาท\" หากไม่รู้ ต้องดึงตัวมา Re-train ทันที\n3.2 ใช้ One Point Lesson (OPL): ทำป้ายภาพถ่ายใบเล็กๆ (จุดที่ถูก-จุดที่ผิด) พร้อมภาษาไทย-พม่า แปะไว้ที่โต๊ะทำงานของพนักงานจุดที่เกิดปัญหาบ่อยๆ เพื่อย้ำเตือนความจำ",
                'target' => 1,
                'operator' => '<=',
                'unit_code' => 'วัน',
            ],
            [
                'objective' => 'Proactive Floor Walk & Leader Soft Skill Development',
                'detail' => "4.1 ลงพื้นที่เชิงรุก (Proactive Floor Walk): HR ต้องเดินสายคุยกับพนักงานหน้าไลน์สัปดาห์ละ 1-2 ครั้ง เพื่อรับฟังปัญหา แล้วรีบแก้ก่อนที่ปัญหาเหล่านั้นจะกลายเป็นใบลาออก\n4.2 พัฒนา \"ทักษะ\" ให้หัวหน้างาน เพราะพนักงานมักลาออกจากหัวหน้างาน ดังนั้น HR ต้องจัดอบรมหัวหน้างานด้าน Soft Skill และเรื่องจิตวิทยาการสื่อสาร และการบริหารคน เพื่อลดความขัดแย้งหน้างานครับ",
                'target' => 2,
                'operator' => '>=',
                'unit_code' => 'ครั้ง',
            ],
            [
                'objective' => 'Lean Manpower, OT Control & Zero Fines',
                'detail' => "5.1 Lean Manpower & OT Control: บริหารจัดการอัตรากำลังคนและชั่วโมงล่วงเวลา (OT) ให้สอดคล้องกับยอดออเดอร์จริงอย่างแม่นยำ (ลดการจ้างคนเผื่อ/ลด OT ที่ไม่จำเป็น)\n5.2 Zero Fines (จุดรอยรั่วทางปรับกฎหมาย): จัดการเอกสารแรงงานต่างด้าว ภาษี และกฎหมายแรงงานให้ถูกต้อง 100% ทันเวลา เพื่อป้องกันค่าไรของบริษัทรั่วไหลไปกับค่าปรับหลักแสน\n5.3 การสร้าง Internal Trainer ในส่วนของ Direct Labour และ In-direct Labour โดยการลดค่าใช้จ่ายจากการใช้สถาบันฝึกอบรมภายนอก",
                'target' => 0,
                'operator' => '<=',
                'unit_code' => 'บาท',
            ],
            [
                'objective' => 'Multi-Skill Training & Office Curriculum Coverage',
                'detail' => "6.1 Multi-Skill Training: อบรมพนักงานให้ทำได้หลายหน้าที่ (Cross-training) เพื่อให้สามารถสลับสับเปลี่ยนกำลังคนแก้ปัญหาคอขวดในไลน์ผลิตได้ทันที โดยไม่ต้องเพิ่มต้นทุนจ้างคนใหม่\n6.2 หลักสูตรฝึกอบรมพนักงานในส่วนสำนักงานต้องครอบคลุม VDA [Germany+Europe], CQI [American], Core Tools Automotive [Mandatory]",
                'target' => 100,
                'operator' => '>=',
                'unit_code' => '%',
            ],
            [
                'objective' => 'Skills Matrix, Training Record & Self-Audit Readiness',
                'detail' => "7.1 ข้อมูลทักษะพนักงานประเภท Blue Collar (อัปเดต Skills Matrix), ประวัติการฝึกอบรมทั้ง White & Blue Collar (Training Record) และใบคำอธิบายลักษณะงาน (JD) ของพนักงานทั้งบริษัท ต้องอัปเดตและสอดคล้องกัน 100%\n7.2 กำหนดรอบการตรวจสอบความถูกต้องของเอกสารภายในแผนก (Self-Audit) ทุกเดือน เพื่อให้มั่นใจว่าเมื่อลูกค้าหรือผู้ตรวจประเมินสุ่มตรวจ พนักงานคนใดก็ตาม จะสามารถดึงหลักฐานการฝึกอบรมที่ถูกต้องออกมาแสดงได้ทันทีภายใน 3 นาที",
                'target' => 100,
                'operator' => '>=',
                'unit_code' => '%',
            ],
            [
                'objective' => 'Pre-employment Screening & Information Security Awareness',
                'detail' => "8.1 การคัดเลือกบุคลากร (Pre-employment): เพิ่มขั้นตอนการตรวจสอบประวัติ (Background Check) สำหรับตำแหน่งที่เข้าถึงข้อมูลความลับสูง และระบุข้อตกลงการรักษาความลับ (NDA) ตั้งแต่วันเซ็นสัญญาจ้าง\n8.2 พนักงานส่วนสำนักงานต้องผ่านการอบรม \"Information Security Awareness\" 100%\n8.3 ประชาสัมพันธ์เรื่องความปลอดภัยข้อมูลผ่านช่องทางต่างๆ (Line Group, ป้ายประกาศ, บอร์ดโรงอาหาร) อย่างต่อเนื่อง ไม่ใช่ทำแค่ช่วง Audit",
                'target' => 100,
                'operator' => '>=',
                'unit_code' => '%',
            ],
            [
                'objective' => 'HR Tech & Kaizen Capability Development',
                'detail' => "9.1 นำร่องนวัตกรรมด้วยงาน HR (HR Tech & Automation)\n9.2 จัดอบรมหลักสูตร \"Lean\", \"Kaizen\", หรือ \"7 QC Tools\" ให้กับหัวหน้างาน (Line Leader) และพนักงานส่วนสำนักงานทุกท่าน เพื่อให้พวกเขามีทักษะในการมองหาจุดบกพร่อง และนำทีมลูกน้องทำ Kaizen ได้อย่างถูกต้อง",
                'target' => 2,
                'operator' => '>=',
                'unit_code' => 'โครงการ',
            ],
        ];
    }

    /**
     * @param array<int, int> $fallbackUnitIds
     */
    private function resolveUnitIdByCodeOrFallback(string $unitCode, array $fallbackUnitIds): int
    {
        $trimmedUnitCode = trim($unitCode);
        if ($trimmedUnitCode !== '') {
            $unitId = (int) (KpiUnit::query()
                ->where('code', $trimmedUnitCode)
                ->value('id') ?? 0);
            if ($unitId > 0) {
                return $unitId;
            }
        }

        return (int) ($fallbackUnitIds[0] ?? 0);
    }

    private function resolveSubmittedAt(Cycle $cycle, int $monthNo): Carbon
    {
        $base = Carbon::create(2026, 1, 1, 0, 0, 0)->startOfMonth();
        if ($cycle->start_date) {
            try {
                $parsed = Carbon::parse((string) $cycle->start_date)->startOfMonth();
                if ((int) $parsed->year >= 2400) {
                    $parsed = $parsed->copy()->subYears(543);
                }
                if ((int) $parsed->year >= 1971 && (int) $parsed->year <= 2037) {
                    $base = $parsed;
                }
            } catch (\Throwable) {
                // Keep default base date.
            }
        }

        return $base
            ->copy()
            ->addMonthsNoOverflow($monthNo - 1)
            ->setDay(15)
            ->setTime(12, 0, 0);
    }

    private function generateScoreByRule(
        float $target,
        string $operator,
        int $userIndex,
        int $itemIndex,
        int $monthNo,
        int $cycleId
    ): float {
        $seed = (($userIndex + 1) * 13) + (($itemIndex + 1) * 17) + ($monthNo * 11) + ($cycleId * 7);
        $wantsPass = ($seed % 5) !== 0; // ~80% pass
        $delta = 0.5 + (($seed % 70) / 10); // 0.5..7.4
        $targetSafe = max($target, 0.01);

        $score = match ($operator) {
            '>=' => $wantsPass ? $targetSafe + $delta : max(0, $targetSafe - $delta),
            '>' => $wantsPass ? $targetSafe + max(0.1, $delta) : max(0, $targetSafe - $delta),
            '<=' => $wantsPass ? max(0, $targetSafe - $delta) : $targetSafe + $delta,
            '<' => $wantsPass ? max(0, $targetSafe - max(0.1, $delta)) : $targetSafe + $delta,
            '=' => $wantsPass ? $targetSafe : $targetSafe + max(1, $delta),
            '!=' => $wantsPass ? $targetSafe + max(1, $delta) : $targetSafe,
            default => $targetSafe,
        };

        // Keep each seeded score unique per user/report/month while preserving pass/fail intent.
        if ($operator !== '=') {
            $uniqueNudge = (
                (($userIndex + 1) * 1000)
                + (($itemIndex + 1) * 100)
                + ($monthNo * 3)
                + ($cycleId % 97)
            ) / 100000000;

            $score += $uniqueNudge;
        }

        return round($score, 6);
    }

    private function evaluateScore(float $score, float $target, string $operator): bool
    {
        return match ($operator) {
            '>' => $score > $target,
            '>=' => $score >= $target,
            '<=' => $score <= $target,
            '<' => $score < $target,
            '=' => $score === $target,
            '!=' => $score !== $target,
            default => false,
        };
    }

    /**
     * @return array<int, array{objective:string,detail:string,operator:string,target:float}>
     */
    private function kpiTemplates(): array
    {
        return [
            [
                'objective' => 'On-Time Delivery',
                'detail' => 'Deliver assigned work within timeline.',
                'operator' => '>=',
                'target' => 90.00,
            ],
            [
                'objective' => 'Quality Defect Rate',
                'detail' => 'Control defect rate to stay low.',
                'operator' => '<=',
                'target' => 6.00,
            ],
            [
                'objective' => 'Task Completion Count',
                'detail' => 'Complete planned task count each month.',
                'operator' => '>=',
                'target' => 22.00,
            ],
            [
                'objective' => 'Response Time',
                'detail' => 'Respond to requests quickly.',
                'operator' => '<=',
                'target' => 3.00,
            ],
            [
                'objective' => 'Process Audit Score',
                'detail' => 'Maintain good internal audit score.',
                'operator' => '>=',
                'target' => 85.00,
            ],
            [
                'objective' => 'Improvement Proposals',
                'detail' => 'Submit improvement proposals.',
                'operator' => '>=',
                'target' => 2.00,
            ],
            [
                'objective' => 'Cost Control',
                'detail' => 'Keep monthly operating cost within target.',
                'operator' => '<=',
                'target' => 100.00,
            ],
        ];
    }

    private function syncResultsByCycle(int $cycleId): void
    {
        $now = now();

        $rootRows = KpiMonthScore::query()
            ->join('app_users', 'app_users.id', '=', 'kpi_month_scores.app_user_id')
            ->where('cycle_id', $cycleId)
            ->whereNotNull('kpi_month_scores.result')
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('kpi_month_scores.month_no', 0)
                        ->whereNull('kpi_month_scores.kpi_meta_id');
                })->orWhereColumn('kpi_month_scores.kpi_meta_id', 'kpi_month_scores.id');
            })
            ->get([
                'kpi_month_scores.app_user_id',
                'kpi_month_scores.cycle_id',
                'kpi_month_scores.target_departments',
                'kpi_month_scores.result',
                'app_users.position',
                'app_users.dept_abbr_hr',
            ]);

        if ($rootRows->isEmpty()) {
            KpiResult::query()->where('cycle_id', $cycleId)->delete();
            OkrResult::query()->where('cycle_id', $cycleId)->delete();
            OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
            return;
        }

        $kpiBuckets = [];
        foreach ($rootRows as $row) {
            $appUserId = (int) ($row->app_user_id ?? 0);
            if ($appUserId < 1) {
                continue;
            }

            $department = $this->resolveDepartmentForSeedSync($row);
            if ($department === '') {
                continue;
            }

            $result = $row->result !== null ? (float) $row->result : null;
            if ($result === null) {
                continue;
            }

            $bucketKey = $appUserId.'|'.$department;
            if (! isset($kpiBuckets[$bucketKey])) {
                $kpiBuckets[$bucketKey] = [
                    'app_user_id' => $appUserId,
                    'cycle_id' => $cycleId,
                    'dept_abbr_hr' => $department,
                    'sum' => 0.0,
                    'count' => 0,
                ];
            }

            $kpiBuckets[$bucketKey]['sum'] += $result;
            $kpiBuckets[$bucketKey]['count']++;
        }

        if ($kpiBuckets === []) {
            KpiResult::query()->where('cycle_id', $cycleId)->delete();
            OkrResult::query()->where('cycle_id', $cycleId)->delete();
            OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
            return;
        }

        $kpiPayload = [];
        foreach ($kpiBuckets as $bucket) {
            $count = (int) ($bucket['count'] ?? 0);
            if ($count < 1) {
                continue;
            }

            $kpiPayload[] = [
                'app_user_id' => (int) $bucket['app_user_id'],
                'cycle_id' => $cycleId,
                'dept_abbr_hr' => (string) $bucket['dept_abbr_hr'],
                'result' => round(((float) $bucket['sum']) / $count, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        KpiResult::query()->where('cycle_id', $cycleId)->delete();
        if ($kpiPayload !== []) {
            KpiResult::query()->insert($kpiPayload);
        }

        $deptRows = KpiResult::query()
            ->where('cycle_id', $cycleId)
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->whereNotNull('result')
            ->groupBy('dept_abbr_hr')
            ->selectRaw('dept_abbr_hr as dept_abbr_hr, ROUND(AVG(result), 2) as result')
            ->get();

        if ($deptRows->isEmpty()) {
            OkrResult::query()->where('cycle_id', $cycleId)->delete();
            OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
            return;
        }

        $okrPayload = [];
        foreach ($deptRows as $row) {
            $department = $this->normalizeDepartmentCode((string) ($row->dept_abbr_hr ?? ''));
            if ($department === '') {
                continue;
            }

            $okrPayload[] = [
                'dept_abbr_hr' => $department,
                'cycle_id' => $cycleId,
                'result' => round((float) $row->result, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($okrPayload === []) {
            OkrResult::query()->where('cycle_id', $cycleId)->delete();
            OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
            return;
        }

        OkrResult::query()->where('cycle_id', $cycleId)->delete();
        OkrResult::query()->insert($okrPayload);

        $overall = OkrResult::query()
            ->where('cycle_id', $cycleId)
            ->whereNotNull('result')
            ->avg('result');

        if ($overall === null) {
            OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
            return;
        }

        OkrAllResult::query()->updateOrCreate(
            ['cycle_id' => $cycleId],
            ['result' => round((float) $overall, 2)]
        );
    }

    private function normalizeDepartmentCode(mixed $value): string
    {
        $department = strtoupper(trim((string) $value));
        $department = preg_replace('/\s+/u', '', $department) ?? $department;

        return $department;
    }

    private function normalizePositionKey(mixed $value): string
    {
        $position = strtolower(trim((string) $value));
        $position = preg_replace('/\s+/u', ' ', $position) ?? $position;

        return $position;
    }

    private function canUseTargetDepartmentByPosition(string $position): bool
    {
        if ($position === '') {
            return false;
        }

        return in_array($position, self::TARGET_DEPARTMENT_POSITIONS, true);
    }

    private function firstDepartmentCodeFromMixed(mixed $rawDepartments): string
    {
        $items = is_array($rawDepartments) ? $rawDepartments : [];
        foreach ($items as $item) {
            $department = $this->normalizeDepartmentCode($item);
            if ($department !== '') {
                return $department;
            }
        }

        return '';
    }

    private function resolveSeedTargetDepartments(AppUser $user): array
    {
        $position = $this->normalizePositionKey((string) ($user->position ?? ''));
        if (! $this->canUseTargetDepartmentByPosition($position)) {
            return [];
        }

        $department = $this->normalizeDepartmentCode((string) ($user->dept_abbr_hr ?? ''));
        return $department !== '' ? [$department] : [];
    }

    private function resolveDepartmentForSeedSync(object $row): string
    {
        $ownerDepartment = $this->normalizeDepartmentCode((string) ($row->dept_abbr_hr ?? ''));
        $position = $this->normalizePositionKey((string) ($row->position ?? ''));

        if ($this->canUseTargetDepartmentByPosition($position)) {
            $selectedDepartment = $this->firstDepartmentCodeFromMixed($row->target_departments ?? []);
            if ($selectedDepartment !== '') {
                return $selectedDepartment;
            }
        }

        return $ownerDepartment;
    }

    private function assertCycleCalculationSeeded(int $cycleId): void
    {
        $rootCount = KpiMonthScore::query()
            ->where('cycle_id', $cycleId)
            ->whereNotNull('result')
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('month_no', 0)
                        ->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            })
            ->count();

        if ($rootCount < 1) {
            return;
        }

        $kpiCount = KpiResult::query()
            ->where('cycle_id', $cycleId)
            ->whereNotNull('result')
            ->count();

        $okrCount = OkrResult::query()
            ->where('cycle_id', $cycleId)
            ->whereNotNull('result')
            ->count();

        $allResult = OkrAllResult::query()
            ->where('cycle_id', $cycleId)
            ->value('result');

        if ($kpiCount < 1 || $okrCount < 1 || $allResult === null) {
            throw new \RuntimeException(
                sprintf(
                    'KpiTestSeeder calculation check failed for cycle %d (kpi_result=%d, okr_result=%d, okr_all_result=%s)',
                    $cycleId,
                    $kpiCount,
                    $okrCount,
                    $allResult === null ? 'null' : (string) $allResult
                )
            );
        }

        if ($this->command) {
            $this->command->line(sprintf(
                'Cycle %d calculation check: kpi_result=%d, okr_result=%d, okr_all_result=%s',
                $cycleId,
                $kpiCount,
                $okrCount,
                number_format((float) $allResult, 2)
            ));
        }
    }
}
