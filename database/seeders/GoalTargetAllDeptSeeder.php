<?php

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Main Level 2 data source for every department, including HRM and HRD.
 *
 * Add data only in KEY_RESULTS. Each row is automatically connected to
 * the active cycle's Level 1 objective by level_1_title.
 *
 * Run:
 *   php artisan db:seed --class=GoalTargetAllDeptSeeder
 */
class GoalTargetAllDeptSeeder extends Seeder
{
  /**
   * Input format:
   *
   * [
   *   'department' => '',
   *   'level_1_title' => '',
   *   'level_2_title' => '',
   *   'level_2_detail' => '',
   * ],
   *
   * Level 2 sort numbers are assigned automatically from the order below.
   *
   * @var array<int, array{
   *   department: string,
   *   level_1_title: string,
   *   level_2_title: string,
   *   level_2_detail: string
   * }>
  */
  private const KEY_RESULTS = [
    // Source: Level2/HRD_OKR-KPI 1.xlsx
    [
      'department' => 'HRD',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'ยกระดับวินัยแรงงานและทักษะด้านคุณภาพ',
      'level_2_detail' => 'ยกระดับวินัยแรงงานและทักษะด้านคุณภาพของพนักงานระดับปฏิบัติการให้ผ่านเกณฑ์มาตรฐาน 100%',
    ],
    [
      'department' => 'HRD',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'มาตรฐานบริการสนับสนุนทรัพยากรบุคคล',
      'level_2_detail' => 'การให้บริการงานสนับสนุนทรัพยากรบุคคล (สรรหาและค่าตอบแทน) บรรลุตามมาตรฐาน SLA และไม่มีข้อผิดพลาด (Zero Error)',
    ],
    [
      'department' => 'HRD',
      'level_1_title' => 'Sales & Profit',
      'level_2_title' => 'บริหารความเสี่ยงและเสถียรภาพอัตรากำลัง',
      'level_2_detail' => 'บริหารความเสี่ยงทางกฎหมายและรักษาเสถียรภาพอัตรากำลังหลัก เพื่อลดต้นทุนความสูญเสีย (HR Cost Reduction) และขับเคลื่อนธุรกิจอย่างเต็มประสิทธิภาพ',
    ],
    [
      'department' => 'HRD',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'Zero External Audit NC',
      'level_2_detail' => 'จำนวนข้อบกพร่อง (NC) จากการ Audit ภายนอก เท่ากับ 0',
    ],
    [
      'department' => 'HRD',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'มาตรฐานขั้นตอนการปฏิบัติงาน',
      'level_2_detail' => 'จัดทำและทบทวนขั้นตอนการปฏิบัติงานมาตรฐานของทุกกระบวนการหลักในสายงานให้สมบูรณ์ 100%',
    ],
    [
      'department' => 'HRD',
      'level_1_title' => 'Innovation e.g. Kaizen / Innovation Organization',
      'level_2_title' => 'Kaizen และ Digital Transformation',
      'level_2_detail' => 'ทุกแผนกใต้สายงาน ต้องดำเนินโครงการปรับปรุงงาน Kaizen หรือนวัตกรรมเชิงระบบ Digital Transformation อย่างน้อย 1 โครงการ/ปี ที่สามารถวัดผลการลดต้นทุน, ลดเวลาการทำงาน, หรือลดการใช้กระดาษได้อย่างเป็นรูปธรรม',
    ],
    [
      'department' => 'HRD',
      'level_1_title' => 'C, C, T [Commitment, Communication, Teamwork]',
      'level_2_title' => 'มาตรฐานงานและโครงการข้ามสายงาน',
      'level_2_detail' => 'ทุกแผนกภายใต้สายงานบรรลุคำมั่นสัญญาหรือมาตรฐานชี้วัดระยะเวลา 100% และดำเนินโครงการข้ามสายงาน (Cross-functional Project) อย่างน้อย 1 โครงการ/ปี',
    ],
    [
      'department' => 'HRD',
      'level_1_title' => 'Resilience & Compliance e.g. Cyber Security / Information Security / Anti-Fraud / Anti-Corruption / TISAX / ISO27001 / ISO37001',
      'level_2_title' => 'ความมั่นคงข้อมูลและธรรมาภิบาลองค์กร',
      'level_2_detail' => 'ยกระดับความมั่นคงปลอดภัยทางข้อมูลและธรรมาภิบาลองค์กร ให้ผ่านเกณฑ์การตรวจสอบมาตรฐานสากล 100% (Zero Non-Compliance) เพื่อปกป้องความน่าเชื่อถือทางธุรกิจ',
    ],

    // Source: Level2/HRM_OKR-KPI 1.xlsx
    [
      'department' => 'HRM',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'ยกระดับวินัยแรงงานและทักษะด้านคุณภาพ',
      'level_2_detail' => 'ยกระดับวินัยแรงงานและทักษะด้านคุณภาพของพนักงานระดับปฏิบัติการให้ผ่านเกณฑ์มาตรฐาน 100%',
    ],
    [
      'department' => 'HRM',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'มาตรฐานบริการสนับสนุนทรัพยากรบุคคล',
      'level_2_detail' => 'การให้บริการงานสนับสนุนทรัพยากรบุคคล (สรรหาและค่าตอบแทน) บรรลุตามมาตรฐาน SLA และไม่มีข้อผิดพลาด (Zero Error)',
    ],
    [
      'department' => 'HRM',
      'level_1_title' => 'Sales & Profit',
      'level_2_title' => 'บริหารความเสี่ยงและเสถียรภาพอัตรากำลัง',
      'level_2_detail' => 'บริหารความเสี่ยงทางกฎหมายและรักษาเสถียรภาพอัตรากำลังหลัก เพื่อลดต้นทุนความสูญเสีย (HR Cost Reduction) และขับเคลื่อนธุรกิจอย่างเต็มประสิทธิภาพ',
    ],
    [
      'department' => 'HRM',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'Zero External Audit NC',
      'level_2_detail' => 'จำนวนข้อบกพร่อง (NC) จากการ Audit ภายนอก เท่ากับ 0',
    ],
    [
      'department' => 'HRM',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'มาตรฐานขั้นตอนการปฏิบัติงาน',
      'level_2_detail' => 'จัดทำและทบทวนขั้นตอนการปฏิบัติงานมาตรฐานของทุกกระบวนการหลักในสายงานให้สมบูรณ์ 100%',
    ],
    [
      'department' => 'HRM',
      'level_1_title' => 'Innovation e.g. Kaizen / Innovation Organization',
      'level_2_title' => 'Kaizen และ Digital Transformation',
      'level_2_detail' => 'ทุกแผนกใต้สายงาน ต้องดำเนินโครงการปรับปรุงงาน Kaizen หรือนวัตกรรมเชิงระบบ Digital Transformation อย่างน้อย 1 โครงการ/ปี ที่สามารถวัดผลการลดต้นทุน, ลดเวลาการทำงาน, หรือลดการใช้กระดาษได้อย่างเป็นรูปธรรม',
    ],
    [
      'department' => 'HRM',
      'level_1_title' => 'C, C, T [Commitment, Communication, Teamwork]',
      'level_2_title' => 'มาตรฐานงานและโครงการข้ามสายงาน',
      'level_2_detail' => 'ทุกแผนกภายใต้สายงานบรรลุคำมั่นสัญญาหรือมาตรฐานชี้วัดระยะเวลา 100% และดำเนินโครงการข้ามสายงาน (Cross-functional Project) อย่างน้อย 1 โครงการ/ปี',
    ],
    [
      'department' => 'HRM',
      'level_1_title' => 'Resilience & Compliance e.g. Cyber Security / Information Security / Anti-Fraud / Anti-Corruption / TISAX / ISO27001 / ISO37001',
      'level_2_title' => 'ความมั่นคงข้อมูลและธรรมาภิบาลองค์กร',
      'level_2_detail' => 'ยกระดับความมั่นคงปลอดภัยทางข้อมูลและธรรมาภิบาลองค์กร ให้ผ่านเกณฑ์การตรวจสอบมาตรฐานสากล 100% (Zero Non-Compliance) เพื่อปกป้องความน่าเชื่อถือทางธุรกิจ',
    ],

    // Source: Level2/IT_OKR-KPI 1.xlsx
    [
      'department' => 'IT',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'ยกระดับวินัยแรงงานและทักษะด้านคุณภาพ',
      'level_2_detail' => 'ยกระดับวินัยแรงงานและทักษะด้านคุณภาพของพนักงานระดับปฏิบัติการให้ผ่านเกณฑ์มาตรฐาน 100%',
    ],
    [
      'department' => 'IT',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'มาตรฐานบริการสนับสนุนทรัพยากรบุคคล',
      'level_2_detail' => 'การให้บริการงานสนับสนุนทรัพยากรบุคคล (สรรหาและค่าตอบแทน) บรรลุตามมาตรฐาน SLA และไม่มีข้อผิดพลาด (Zero Error)',
    ],
    [
      'department' => 'IT',
      'level_1_title' => 'Sales & Profit',
      'level_2_title' => 'บริหารความเสี่ยงและเสถียรภาพอัตรากำลัง',
      'level_2_detail' => 'บริหารความเสี่ยงทางกฎหมายและรักษาเสถียรภาพอัตรากำลังหลัก เพื่อลดต้นทุนความสูญเสีย (HR Cost Reduction) และขับเคลื่อนธุรกิจอย่างเต็มประสิทธิภาพ',
    ],
    [
      'department' => 'IT',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'Zero External Audit NC',
      'level_2_detail' => 'จำนวนข้อบกพร่อง (NC) จากการ Audit ภายนอก เท่ากับ 0',
    ],
    [
      'department' => 'IT',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'มาตรฐานขั้นตอนการปฏิบัติงาน',
      'level_2_detail' => 'จัดทำและทบทวนขั้นตอนการปฏิบัติงานมาตรฐานของทุกกระบวนการหลักในสายงานให้สมบูรณ์ 100%',
    ],
    [
      'department' => 'IT',
      'level_1_title' => 'Innovation e.g. Kaizen / Innovation Organization',
      'level_2_title' => 'Kaizen และ Digital Transformation',
      'level_2_detail' => 'ทุกแผนกใต้สายงาน ต้องดำเนินโครงการปรับปรุงงาน Kaizen หรือนวัตกรรมเชิงระบบ Digital Transformation อย่างน้อย 1 โครงการ/ปี ที่สามารถวัดผลการลดต้นทุน, ลดเวลาการทำงาน, หรือลดการใช้กระดาษได้อย่างเป็นรูปธรรม',
    ],
    [
      'department' => 'IT',
      'level_1_title' => 'C, C, T [Commitment, Communication, Teamwork]',
      'level_2_title' => 'มาตรฐานงานและโครงการข้ามสายงาน',
      'level_2_detail' => 'ทุกแผนกภายใต้สายงานบรรลุคำมั่นสัญญาหรือมาตรฐานชี้วัดระยะเวลา 100% และดำเนินโครงการข้ามสายงาน (Cross-functional Project) อย่างน้อย 1 โครงการ/ปี',
    ],
    [
      'department' => 'IT',
      'level_1_title' => 'Resilience & Compliance e.g. Cyber Security / Information Security / Anti-Fraud / Anti-Corruption / TISAX / ISO27001 / ISO37001',
      'level_2_title' => 'ความมั่นคงข้อมูลและธรรมาภิบาลองค์กร',
      'level_2_detail' => 'ยกระดับความมั่นคงปลอดภัยทางข้อมูลและธรรมาภิบาลองค์กร ให้ผ่านเกณฑ์การตรวจสอบมาตรฐานสากล 100% (Zero Non-Compliance) เพื่อปกป้องความน่าเชื่อถือทางธุรกิจ',
    ],
    // Source: Level2/QMS_OKR-KPI.xlsx
    [
      'department' => 'QMS',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'Q1 MSA (Q1 Manufacturing Site Assessment)',
      'level_2_detail' => 'full score 15',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'Certificate concern',
      'level_2_detail' => 'compliant',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'Special process ( All CQI)',
      'level_2_detail' => 'On plan & On target',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Award Company e.g. Top Supplier Ford & AAT / 5S / Zero PPM',
      'level_2_title' => 'Thailand 5S Award',
      'level_2_detail' => 'Silver Level award',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Sales & Profit',
      'level_2_title' => 'Global Sustainable system Ecovadis',
      'level_2_detail' => 'Score ≥ 65',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Sales & Profit',
      'level_2_title' => 'Global Sustainable system SQA',
      'level_2_detail' => 'Score ≥ 80',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Sales & Profit',
      'level_2_title' => 'Sustainable system carbon',
      'level_2_detail' => 'Certificate',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Sales & Profit',
      'level_2_title' => 'Sustainable system Health & Safety',
      'level_2_detail' => 'Certificate',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'Third party & Secound party audit',
      'level_2_detail' => 'Zero Major NC',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'Intenal audit ( IQA , Process ,Product )',
      'level_2_detail' => 'On time plan and on time closed',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'SMBR audit ( Supavut manufactoring basic requirement )',
      'level_2_detail' => 'On time plan and on time closed',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'System & SMBR e.g. QMS / Customer CAR / Internal Audit / 3rd Party',
      'level_2_title' => 'New customer assesment',
      'level_2_detail' => 'Pass 100 % refer criteria each customer',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Innovation e.g. Kaizen / Innovation Organization',
      'level_2_title' => 'มีการทำ Kaizen ในแผนก ที่เป็นแนวคิดของตัวเอง',
      'level_2_detail' => '1 เรื่อง /คน / Year',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Innovation e.g. Kaizen / Innovation Organization',
      'level_2_title' => 'มีการทำ Kaizen และส่งแข่งขันระดับโรงงาน / สำนักงาน',
      'level_2_detail' => 'ผ่านเข้ารอบ Present 4 เรื่อง /Year',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Innovation e.g. Kaizen / Innovation Organization',
      'level_2_title' => 'นวัตกรรมองค์กร Innovation Organization (มิติที่ 1 ยุทธศาสตร์นวัตกรรม)',
      'level_2_detail' => 'ผ่าน มิติที่ 1 ยุทธศาสตร์นวัตกรรม / Year',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Innovation e.g. Kaizen / Innovation Organization',
      'level_2_title' => 'นวัตกรรมองค์กร Innovation Organization (มิติที่ 4 องค์ความรู้)',
      'level_2_detail' => 'ผ่าน มิติที่ 4 องค์ความรู้  / Year',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'C, C, T [Commitment, Communication, Teamwork]',
      'level_2_title' => 'Job Communication',
      'level_2_detail' => 'Fast & Accuracy ค้นหาข้อมูลและเข้าถึงงานประจำภายใน  5 นาที',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'C, C, T [Commitment, Communication, Teamwork]',
      'level_2_title' => 'Panalize role Agreement',
      'level_2_detail' => 'มีข้อตกลงร่วมกันเรื่องหักคะแนนเมื่อ Audit พบ NC และคืนคะแนนเมื่อแก้ไข Reduce and Return score',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'C, C, T [Commitment, Communication, Teamwork]',
      'level_2_title' => 'Motivation role agreement',
      'level_2_detail' => 'ให้รางวัลหรือชมเชยเมื่อ ไม่พบ , NC  Anaul awards no fiding NC',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Resilience & Compliance e.g. Cyber Security / Information Security / Anti-Fraud / Anti-Corruption / TISAX / ISO27001 / ISO37001',
      'level_2_title' => 'Cyber security organization',
      'level_2_detail' => 'Tisax L3 certificate',
    ],
    [
      'department' => 'QMS',
      'level_1_title' => 'Resilience & Compliance e.g. Cyber Security / Information Security / Anti-Fraud / Anti-Corruption / TISAX / ISO27001 / ISO37001',
      'level_2_title' => 'อาชีวะอนามัยและความปลอดภัยในการทำงาน , Health & Safety',
      'level_2_detail' => 'Certificate ISO 45001',
    ],
  ];

  public function run(): void
  {
    if (self::KEY_RESULTS === []) {
      $this->command->warn('GoalTargetAllDeptSeeder: KEY_RESULTS is empty; no Level 2 data was changed.');

      return;
    }

    $cycle = Cycle::query()->where('is_active', true)->orderByDesc('id')->first()
      ?? Cycle::query()->orderByDesc('id')->first();

    if (! $cycle) {
      throw new RuntimeException('GoalTargetAllDeptSeeder: no cycle found.');
    }

    $objectives = OkrObjective::query()
      ->where('cycle_id', (int) $cycle->id)
      ->orderBy('sort_no')
      ->get();

    if ($objectives->isEmpty()) {
      throw new RuntimeException('GoalTargetAllDeptSeeder: no Level 1 objectives found. Run GoalTargetSeeder first.');
    }

    $preparedRows = $this->prepareRows($objectives);

    DB::transaction(function () use ($objectives, $preparedRows): void {
      $keptIds = [];

      foreach ($preparedRows as $row) {
        $keyResult = OkrKeyResult::query()->updateOrCreate(
          [
            'okr_objective_id' => $row['okr_objective_id'],
            'dept_abbr_hr' => $row['department'],
            'sort_no' => $row['sort_no'],
          ],
          [
            'title' => $row['level_2_title'],
            'detail' => $row['level_2_detail'] !== '' ? $row['level_2_detail'] : null,
          ]
        );

        $keptIds[] = (int) $keyResult->id;
        $this->command->line(
          "  [{$row['department']}] L1 #{$row['level_1_sort_no']} -> L2 #{$row['sort_no']}: {$row['level_2_title']}"
        );
      }

      $obsoleteIds = OkrKeyResult::query()
        ->whereIn('okr_objective_id', $objectives->pluck('id'))
        ->whereNotIn('id', $keptIds)
        ->pluck('id')
        ->map(static fn ($id): int => (int) $id)
        ->all();

      $this->detachObsoleteKeyResults($obsoleteIds);
      OkrKeyResult::query()->whereIn('id', $obsoleteIds)->delete();
    });

    $this->command->info(
      'GoalTargetAllDeptSeeder: synced '.count($preparedRows).' Level 2 record(s) for cycle #'.$cycle->id.'.'
    );
  }

  /**
   * @param Collection<int, OkrObjective> $objectives
   * @return array<int, array{
   *   department: string,
   *   level_1_sort_no: int,
   *   level_2_title: string,
   *   level_2_detail: string,
   *   okr_objective_id: int,
   *   sort_no: int
   * }>
   */
  private function prepareRows(Collection $objectives): array
  {
    $preparedRows = [];
    $sortNumbers = [];
    $seenRows = [];

    foreach (self::KEY_RESULTS as $index => $row) {
      $department = strtoupper(trim((string) ($row['department'] ?? '')));
      $levelOneTitle = trim((string) ($row['level_1_title'] ?? ''));
      $levelTwoTitle = trim((string) ($row['level_2_title'] ?? ''));
      $levelTwoDetail = trim((string) ($row['level_2_detail'] ?? ''));
      $rowNumber = $index + 1;

      if ($department === '' || $levelOneTitle === '' || $levelTwoTitle === '') {
        throw new RuntimeException(
          "GoalTargetAllDeptSeeder: row {$rowNumber} requires department, level_1_title, and level_2_title."
        );
      }

      $objective = $this->resolveObjective($objectives, $levelOneTitle, $rowNumber);
      $groupKey = $department.'|'.$objective->id;
      $duplicateKey = $groupKey.'|'.$this->normalizeTitle($levelTwoTitle);

      if (isset($seenRows[$duplicateKey])) {
        throw new RuntimeException("GoalTargetAllDeptSeeder: duplicate Level 2 title at row {$rowNumber}.");
      }

      $seenRows[$duplicateKey] = true;
      $sortNumbers[$groupKey] = ($sortNumbers[$groupKey] ?? 0) + 1;

      $preparedRows[] = [
        'department' => $department,
        'level_1_sort_no' => (int) $objective->sort_no,
        'level_2_title' => $levelTwoTitle,
        'level_2_detail' => $levelTwoDetail,
        'okr_objective_id' => (int) $objective->id,
        'sort_no' => $sortNumbers[$groupKey],
      ];
    }

    return $preparedRows;
  }

  /**
   * @param Collection<int, OkrObjective> $objectives
   */
  private function resolveObjective(Collection $objectives, string $inputTitle, int $rowNumber): OkrObjective
  {
    $normalizedInput = $this->normalizeTitle($inputTitle);

    $matches = $objectives->filter(function (OkrObjective $objective) use ($normalizedInput): bool {
      $fullTitle = $this->normalizeTitle((string) $objective->title);
      $firstLine = $this->normalizeTitle($this->firstLine((string) $objective->title));

      return $normalizedInput === $fullTitle || $normalizedInput === $firstLine;
    });

    if ($matches->count() !== 1) {
      $reason = $matches->isEmpty() ? 'was not found' : 'matched more than one Level 1 objective';

      throw new RuntimeException(
        "GoalTargetAllDeptSeeder: level_1_title at row {$rowNumber} {$reason}: {$inputTitle}"
      );
    }

    return $matches->first();
  }

  private function normalizeTitle(string $title): string
  {
    $normalized = preg_replace('/\s+/u', ' ', trim($title)) ?? trim($title);

    return function_exists('mb_strtolower')
      ? mb_strtolower($normalized, 'UTF-8')
      : strtolower($normalized);
  }

  private function firstLine(string $title): string
  {
    $lines = preg_split('/\R/u', trim($title)) ?: [];

    return (string) ($lines[0] ?? '');
  }

  /**
   * @param array<int, int> $obsoleteIds
   */
  private function detachObsoleteKeyResults(array $obsoleteIds): void
  {
    if (
      $obsoleteIds === []
      || ! Schema::hasTable('kpi_month_scores')
      || ! Schema::hasColumn('kpi_month_scores', 'okr_key_result_id')
    ) {
      return;
    }

    DB::table('kpi_month_scores')
      ->whereIn('okr_key_result_id', $obsoleteIds)
      ->update(['okr_key_result_id' => null]);
  }
}
