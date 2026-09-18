<?php

namespace Database\Seeders;

use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\OkrObjective;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the six CEO-defined Level 1 organization objectives.
 *
 * Re-runnable: updates objectives 1-6 and removes other Level 1 objectives
 * from the selected cycle.
 *
 * Run:
 *   php artisan db:seed --class=GoalTargetSeeder
 */
class GoalTargetSeeder extends Seeder
{
    private const CEO_PDF_SOURCE = 'C:/Users/MS/OneDrive/เดสก์ท็อป/Supavut_work/OKR-KPI System/Document/CEO.pdf';

    private const CEO_PDF_NAME = 'CEO.pdf';

    /**
     * @var array<int, array{title: string, detail: string, source: string}>
     */
    private const OBJECTIVES = [
        1 => [
            'title' => "Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM\n\nยกระดับบริษัทสู่รางวัลและมาตรฐานสากล",
            'detail' => "มุ่งยกระดับคุณภาพ มาตรฐาน วินัย และผลลัพธ์การทำงานของทั้งองค์กร เพื่อให้บริษัทได้รับการยอมรับจากลูกค้าและหน่วยงานภายนอก เช่น Top Supplier, 5S, Zero PPM และมาตรฐานการดำเนินงานที่เป็นเลิศ\n\n(Aim to elevate the quality, standards, discipline, and overall work performance of the entire organization, so that the company is recognized by customers and external organizations for achievements such as Top Supplier, 5S, Zero PPM, and operational excellence standards.)",
            'source' => 'CEO',
        ],
        2 => [
            'title' => "Sales & Profit\n\nเพิ่มยอดขายและผลกำไร",
            'detail' => "เพิ่มยอดขาย กำไร ลดต้นทุน ลดความสูญเสีย และเพิ่มประสิทธิภาพการทำงาน เพื่อให้บริษัทเติบโตอย่างมั่นคง\n\n(Increase sales and profits, reduce costs and losses, and improve work efficiency to support the company’s stable growth.)",
            'source' => 'CEO',
        ],
        3 => [
            'title' => "System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party\n\nพัฒนาระบบมาตรฐานองค์กร",
            'detail' => "พัฒนาระบบการทำงานให้เป็นมาตรฐาน ตรวจสอบย้อนกลับได้ และปรับปรุงอย่างต่อเนื่อง ครอบคลุม QMS, Customer CAR, Internal Audit, 3rd Party Audit, และ SMBR\n\n(Develop work systems to be standardized, traceable, and continuously improved, covering QMS, Customer CAR, Internal Audit, 3rd Party Audit, and SMBR.)",
            'source' => 'CEO',
        ],
        4 => [
            'title' => "Innovation e.g. Kaizen / Innovation Organization\n\nขับเคลื่อนนวัตกรรมและการปรับปรุงงาน",
            'detail' => "ส่งเสริม Kaizen และนวัตกรรมภายในองค์กร เพื่อปรับปรุงวิธีการทำงาน ลดความสูญเสีย เพิ่มประสิทธิภาพ และสร้างแนวทางการทำงานใหม่ที่ดีกว่าเดิมอย่างต่อเนื่อง\n\n(Promote Kaizen and internal innovation to continuously improve work methods, reduce waste, increase efficiency, and create better ways of working.)",
            'source' => 'CEO',
        ],
        5 => [
            'title' => "C, C, T [Commitment, Communication, Teamwork]\n\nสร้างความรับผิดชอบ การสื่อสาร และการทำงานเป็นทีม",
            'detail' => "สร้างวัฒนธรรมการทำงานที่ดี ผ่านความรับผิดชอบต่อเป้าหมาย การสื่อสารที่ชัดเจน และการทำงานร่วมกันเป็นทีม เพื่อให้ทุกฝ่ายขับเคลื่อนไปในทิศทางเดียวกัน\n\n(Build a positive work culture through responsibility toward goals, clear communication, and teamwork, so all parties can move forward in the same direction.)",
            'source' => 'CEO',
        ],
        6 => [
            'title' => "Resilience & Compliance e.g. Cyber Security / Information Security / Anti-Fraud / Anti-Corruption / TISAX / ISO27001 / ISO37001\n\nเสริมความมั่นคง ความปลอดภัย และการป้องกันทุจริตขององค์กร",
            'detail' => "เสริมความพร้อมขององค์กรในการป้องกันความเสี่ยงด้านระบบ ข้อมูล และการทุจริต ครอบคลุม Cyber Security, Information Security, Anti-Fraud, Anti-Corruption, TISAX, ISO27001 และ ISO37001 เพื่อให้ธุรกิจดำเนินต่อได้อย่างปลอดภัย โปร่งใส ตรวจสอบได้ และต่อเนื่อง\n\n(Strengthen the organization’s readiness to prevent risks related to systems, information, and fraud, covering Cyber Security, Information Security, Anti-Fraud, Anti-Corruption, TISAX, ISO27001, and ISO37001, to ensure secure, transparent, auditable, and continuous business operations.)",
            'source' => 'CEO',
        ],
    ];

    public function run(): void
    {
        $cycle = Cycle::query()->where('is_active', true)->orderByDesc('id')->first()
            ?? Cycle::query()->orderByDesc('id')->first();

        if (! $cycle) {
            $this->command->warn('GoalTargetSeeder: no cycle found, skipping.');

            return;
        }

        $admin = AppUser::query()
            ->where('employee_code', 'admin')
            ->orWhere('role', 'admin')
            ->orderBy('id')
            ->first();
        $adminId = (int) ($admin?->id ?? 0);
        $removedCount = 0;

        $this->command->info(
            'GoalTargetSeeder (L1): cycle #'.$cycle->id.' "'.$cycle->name.'" - '.count(self::OBJECTIVES).' CEO objectives.'
        );

        DB::transaction(function () use ($cycle, $adminId, &$removedCount): void {
            $removedCount = $this->removeObsoleteObjectives((int) $cycle->id);

            foreach (self::OBJECTIVES as $sortNo => $data) {
                $objective = OkrObjective::query()->updateOrCreate(
                    [
                        'cycle_id' => (int) $cycle->id,
                        'sort_no' => $sortNo,
                    ],
                    [
                        'title' => $data['title'],
                        'detail' => $data['detail'],
                        'created_by_admin_id' => $adminId ?: null,
                    ]
                );

                $status = $objective->wasRecentlyCreated ? 'new' : 'updated';
                $this->command->line("  L1 #{$sortNo} [{$data['source']}] ({$status}): ".$data['title']);
                $this->copyFile($objective);
            }
        });

        if ($removedCount > 0) {
            $this->command->line("  Removed {$removedCount} obsolete Level 1 objective(s).");
        }

        $this->command->info('GoalTargetSeeder (L1): done.');
    }

    private function removeObsoleteObjectives(int $cycleId): int
    {
        $objectiveIds = OkrObjective::query()
            ->where('cycle_id', $cycleId)
            ->whereNotIn('sort_no', array_keys(self::OBJECTIVES))
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($objectiveIds === []) {
            return 0;
        }

        $keyResultIds = DB::table('okr_key_results')
            ->whereIn('okr_objective_id', $objectiveIds)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if (Schema::hasTable('kpi_month_scores')) {
            if ($keyResultIds !== [] && Schema::hasColumn('kpi_month_scores', 'okr_key_result_id')) {
                DB::table('kpi_month_scores')
                    ->whereIn('okr_key_result_id', $keyResultIds)
                    ->update(['okr_key_result_id' => null]);
            }

            if (Schema::hasColumn('kpi_month_scores', 'okr_objective_id')) {
                DB::table('kpi_month_scores')
                    ->whereIn('okr_objective_id', $objectiveIds)
                    ->update(['okr_objective_id' => null]);
            }
        }

        return OkrObjective::query()->whereIn('id', $objectiveIds)->delete();
    }

    private function copyFile(OkrObjective $objective): void
    {
        $source = self::CEO_PDF_SOURCE;
        if ($source === '' || ! file_exists($source)) {
            return;
        }

        $relativePath = 'goal-targets/objectives/'.$objective->id.'/'.self::CEO_PDF_NAME;
        $absolutePath = storage_path('app/public/'.$relativePath);
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (! copy($source, $absolutePath)) {
            return;
        }

        $objective->update([
            'file_path' => $relativePath,
            'file_original_name' => self::CEO_PDF_NAME,
        ]);
    }
}
