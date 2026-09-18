<?php

namespace Database\Seeders;

use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\CycleMonth;
use App\Models\KpiMonthScore;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Simulates "รายงาน" (report) KPI entries for HRM&HRD — all 5 sections from the KPI image.
 *   1. Payroll & Compensation         (7 items) → L1#2 / L2#1
 *   2. Time Attendance & Compliance   (7 items) → L1#3 / L2#1
 *   3. Admin & Migrant                (7 items) → L1#3 / L2#1
 *   4. Welfare & CSR Activity         (8 items) → L1#4 / L2#1
 *   5. Secretary of CEO               (7 items) → L1#1 / L2#4
 *
 * Scores are a realistic mix of passing and failing values.
 * Only open (is_active=true) cycle months receive records.
 * Users: supervisor / senior staff / staff / engineer / senior engineer in HRM&HRD.
 */
class KpiReportHrmWelfareSeeder extends Seeder
{
    private const DEPT = 'HRM&HRD';

    private const TARGET_POSITIONS = [
        'supervisor',
        'senior staff',
        'staff',
        'engineer',
        'senior engineer',
    ];

    /**
     * Sections — each maps to [l1_sort, l2_sort] and holds its KPI items.
     * score_samples: 12 values (one per month). Mix of pass (P) and fail (F).
     * unit_id: 2=% 3=คน 4=ราย 5=ครั้ง 7=งาน 9=เอกสาร 10=ชั่วโมง 12=เดือน 15=คะแนน
     *
     * @var list<array{name:string,l1_sort:int,l2_sort:int,items:list<array{...}>}>
     */
    private const SECTIONS = [
        // ══════════════════════════════════════════════════════════════════
        // 1. Payroll & Compensation  →  L1#2 Sales & Profit / L2#1 Labor Cost Optimization
        // ══════════════════════════════════════════════════════════════════
        [
            'name'         => 'Payroll & Compensation',
            'user_name_th' => 'นิลกมล',
            'l1_sort'      => 2,
            'l2_sort'      => 1,
            'items'   => [
                [
                    'objective'          => 'ความถูกต้องของการทำเงินเดือน',
                    'detail'             => 'คำนวณเงินเดือน OT, ค่าจ้าง และอื่นๆ ของพนักงาน 1,350 คน ให้ถูกต้องตามสัญญาจ้าง — อัตราความผิดพลาดไม่เกิน 0.5% ของพนักงานทั้งหมดต่อรอบการจ่าย',
                    'target_value'       => 0.5,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '<=',
                    // P=pass  F=fail (>0.5)
                    'score_samples'      => [0.2, 0.1, 0.6, 0.0, 0.3, 0.8, 0.1, 0.0, 0.5, 0.2, 0.9, 0.1],
                ],
                [
                    'objective'          => 'ระยะเวลาในการแก้ไขข้อผิดพลาดเงินเดือน',
                    'detail'             => 'แก้ไขปัญหาหรือส่งข้อมูลเงินเดือน/สวัสดิการ หลังผ่านการตรวจสอบผิดพลาด — แก้ไขแล้วเสร็จ 100% ภายใน 1-2 วันทำการ หลังได้รับแจ้ง',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 85.0, 100.0, 100.0, 75.0, 100.0, 100.0, 90.0, 100.0, 100.0, 80.0],
                ],
                [
                    'objective'          => 'การจ่ายค่าจ้างตรงเวลา',
                    'detail'             => 'จ่ายค่าจ้างให้พนักงานทุกคน (เจ้าหน้าที่/พนักงานประจำ) ในวันที่บริษัทกำหนด — 100% ตรงตามเวลา',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การนำส่งประกันสังคมและภาษี',
                    'detail'             => 'นำส่งประกันสังคม, กองทุนสำรองเลี้ยงชีพ และภาษี (ก.พ.ง.) ตามกำหนดของหน่วยงานรัฐทุกประเภท — 100% ทันเวลา',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การแจ้งเข้า-ออก ประกันสังคม (สปส. 1-03 / สปส. 6-09)',
                    'detail'             => 'แจ้งเข้า-แจ้งออกพนักงานในระบบประกันสังคมภายในวันที่ 15 ของทุกเดือน — 100% ทันกำหนด ไม่มีค่าปรับ',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 100.0, 80.0, 100.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การอัปโหลดข้อมูลในระบบ HRS',
                    'detail'             => 'อัปโหลดข้อมูลเงินเดือนและประวัติภาษีในระบบ HRS ให้เป็นปัจจุบัน — 100% ครบทุกพนักงานที่ต้องทำเงินเดือนของเดือนนั้น',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0, 100.0, 90.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การควบคุมและอัปเดตเอกสารระบบคุณภาพ (Payroll & Compensation)',
                    'detail'             => 'อัปเดต แจกจ่าย และขึ้นทะเบียนเอกสารควบคุม Payroll & Compensation ในระบบ Document Control ให้เป็นปัจจุบัน',
                    'target_value'       => 5.0,
                    'unit_id'            => null, // วันทำการ
                    'criteria_operator'  => '<=',
                    'score_samples'      => [3.0, 2.0, 7.0, 4.0, 2.0, 5.0, 3.0, 8.0, 2.0, 4.0, 3.0, 6.0],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════════
        // 2. Time Attendance & Compliance  →  L1#3 Standard System / L2#1 Legal Compliance
        // ══════════════════════════════════════════════════════════════════
        [
            'name'         => 'Time Attendance & Compliance',
            'user_name_th' => 'พิชญ์ศิณีรัตน์',
            'l1_sort'      => 3,
            'l2_sort'      => 1,
            'items'   => [
                [
                    'objective'          => 'ความถูกต้องของข้อมูลเวลาปฏิบัติงาน',
                    'detail'             => 'ตรวจสอบและสรุปข้อมูลเวลาเข้า-ออกงาน, OT แต่ละต่อ/ฝ่าย/แผนก ส่งให้ Payroll — 100% ถูกต้องและตรงเวลา ก่อนวันที่ 25 ของทุกเดือน',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 95.0, 100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 85.0, 100.0],
                ],
                [
                    'objective'          => 'การบริหารจัดการสิทธิการลาของพนักงาน',
                    'detail'             => 'ส่งสรุปรวม Time Attendance ให้กับพนักงานทุกคนในทุกแผนก — 100% ถูกต้องและตรงเวลา ก่อนวันที่ 25 ของทุกเดือน',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 95.0, 100.0, 100.0, 80.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0, 90.0],
                ],
                [
                    'objective'          => 'การออกใบอนุญาตเอกสารทำงานไม่ถูกต้อง',
                    'detail'             => 'ออกใบอนุญาตพนักงานที่มาสาย/ขาดงาน — ภายใน 48 ชม. และสรุปรายงานให้ผู้บริหารภายในวันที่ 8 ของทุกเดือน',
                    'target_value'       => 48.0,
                    'unit_id'            => 10,   // ชั่วโมง
                    'criteria_operator'  => '<=',
                    'score_samples'      => [24.0, 12.0, 48.0, 36.0, 72.0, 24.0, 12.0, 48.0, 60.0, 24.0, 36.0, 48.0],
                ],
                [
                    'objective'          => 'รายงานสถิติการปฏิบัติงานประจำเดือน',
                    'detail'             => 'จัดทำรายงานสถิติ ขาด ลา มา และแต่ละแผนก — รายงานสำเร็จภายในวันที่ 8 ของทุกเดือน',
                    'target_value'       => 8.0,
                    'unit_id'            => null, // วัน
                    'criteria_operator'  => '<=',
                    'score_samples'      => [5.0, 6.0, 8.0, 7.0, 10.0, 5.0, 8.0, 6.0, 9.0, 7.0, 8.0, 5.0],
                ],
                [
                    'objective'          => 'OT Compliance — การควบคุม OT ตามกฎหมาย',
                    'detail'             => 'ตรวจสอบพนักงานที่ทำ OT เกินกำหนดชั่วโมงตามที่บริษัทกำหนด มรส.8001:2563 — ความผิดพลาด 0%',
                    'target_value'       => 0.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '=',
                    'score_samples'      => [0.0, 0.0, 2.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 3.0, 0.0, 0.0],
                ],
                [
                    'objective'          => 'Time Dispute Resolution — แก้ไขปัญหาเวลา',
                    'detail'             => 'ตรวจสอบและแก้ไขข้อผิดพลาดด้านเวลา (เช่น ลืมสแกน บัตรหาย) — แก้ไข 100% ภายใน 2 วันทำการ หลังได้รับแจ้ง',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 85.0, 100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 75.0, 100.0],
                ],
                [
                    'objective'          => 'การควบคุมและอัปเดตเอกสารระบบคุณภาพ (Time Attendance)',
                    'detail'             => 'อัปเดต แจกจ่าย และขึ้นทะเบียนเอกสาร HRM&HRD ในระบบ Document Control ให้เป็นปัจจุบัน',
                    'target_value'       => 5.0,
                    'unit_id'            => null, // วันทำการ
                    'criteria_operator'  => '<=',
                    'score_samples'      => [4.0, 3.0, 5.0, 2.0, 6.0, 4.0, 3.0, 7.0, 2.0, 5.0, 3.0, 4.0],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════════
        // 3. Admin & Migrant  →  L1#3 Standard System / L2#1 Legal Compliance
        // ══════════════════════════════════════════════════════════════════
        [
            'name'         => 'Admin & Migrant',
            'user_name_th' => 'ลักษิกา',
            'l1_sort'      => 3,
            'l2_sort'      => 1,
            'items'   => [
                [
                    'objective'          => 'ต่ออายุวีซ่า/ใบอนุญาตทำงานแรงงานต่างด้าว',
                    'detail'             => 'ตรวจสอบและดำเนินการต่ออายุเอกสารแรงงานต่างด้าวก่อนหมดอายุ — 100% ครบก่อนกำหนด (0 เคส Overstay และ 0 บาทค่าปรับ)',
                    'target_value'       => 0.0,
                    'unit_id'            => 4,    // ราย
                    'criteria_operator'  => '=',
                    'score_samples'      => [0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 2.0, 0.0, 0.0, 0.0],
                ],
                [
                    'objective'          => 'การแจ้งเข้า-ออก แรงงานต่างด้าวต่อหน่วยงานรัฐ',
                    'detail'             => 'แจ้งเข้า-ออกแรงงานต่างด้าวต่อกรมการจัดหางาน — ดำเนินการตามกฎหมาย 100% ผิดพลาดไม่เกิน 1% ของพนักงานต่างด้าวทั้งหมด',
                    'target_value'       => 1.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '<=',
                    'score_samples'      => [0.0, 0.0, 0.5, 0.0, 1.5, 0.0, 0.0, 0.3, 0.0, 0.0, 2.0, 0.0],
                ],
                [
                    'objective'          => 'รายงานจำนวนแรงงานต่างด้าวทุก 90 วัน (มาด.47)',
                    'detail'             => 'รายงานจำนวนพนักงานต่างด้าวตามใบกำกับงาน 90 วัน — 100% ทันตามกำหนด (0 เลสรายงานล่าช้า, 0 บาทค่าปรับ)',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 100.0, 100.0, 80.0, 100.0, 100.0, 100.0, 100.0, 90.0, 100.0],
                ],
                [
                    'objective'          => 'ควบคุมคุณภาพโรงอาหาร (แม่ครัว/Canteen)',
                    'detail'             => 'ควบคุม ดูแล และประสานงานด้านบริการโรงอาหาร — 0% ข้อร้องเรียนจากพนักงาน',
                    'target_value'       => 0.0,
                    'unit_id'            => 5,    // ครั้ง
                    'criteria_operator'  => '=',
                    'score_samples'      => [0.0, 1.0, 0.0, 0.0, 0.0, 2.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0],
                ],
                [
                    'objective'          => 'ต่ออายุ พ.ร.บ. ประกันภัย และสวัสดิการต่างๆ',
                    'detail'             => 'ตรวจสอบและต่ออายุ พ.ร.บ. ประกันภัย และสุขภาพพยาบาลให้ครบก่อนหมดอายุ — 100% ครบตามกำหนด (0 เลสล่าช้า)',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 75.0],
                ],
                [
                    'objective'          => 'บันทึกและรายงานการใช้สาธารณูปโภค (น้ำ/ไฟ)',
                    'detail'             => 'จัดขั้นตอน/บันทึกการอ่านค่าน้ำและไฟ วัดปริมาณใช้จริง และสรุปรายงานรายเดือน — ตรวจสอบสม่ำเสมอทุกเดือน',
                    'target_value'       => 1.0,
                    'unit_id'            => 5,    // ครั้ง
                    'criteria_operator'  => '>=',
                    'score_samples'      => [1.0, 1.0, 0.0, 1.0, 1.0, 1.0, 0.0, 1.0, 1.0, 1.0, 1.0, 0.0],
                ],
                [
                    'objective'          => 'การควบคุมและอัปเดตเอกสารระบบคุณภาพ (Admin & Migrant)',
                    'detail'             => 'อัปเดต แจกจ่าย และขึ้นทะเบียนเอกสาร HRM ในระบบ Document Control ให้เป็นปัจจุบัน',
                    'target_value'       => 5.0,
                    'unit_id'            => null, // วันทำการ
                    'criteria_operator'  => '<=',
                    'score_samples'      => [3.0, 5.0, 4.0, 2.0, 6.0, 3.0, 5.0, 4.0, 7.0, 3.0, 5.0, 4.0],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════════
        // 4. Welfare & CSR Activity  →  L1#4 Innovation Organization / L2#1 HR Process
        // ══════════════════════════════════════════════════════════════════
        [
            'name'         => 'Welfare & CSR Activity',
            'user_name_th' => 'ซุ้มแก้ว',
            'l1_sort'      => 4,
            'l2_sort'      => 1,
            'items'   => [
                [
                    'objective'          => 'การดำเนินการตามมติคณะกรรมการสวัสดิการ',
                    'detail'             => 'จัดประชุมคณะกรรมการสวัสดิการและดำเนินการตามมติ > 85% ของข้อเสนอแนะในการประชุมทุกเดือน พร้อมรายงานผลให้ผู้บริหาร',
                    'target_value'       => 85.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [88.0, 90.0, 80.0, 92.0, 89.0, 75.0, 86.0, 93.0, 88.0, 91.0, 72.0, 89.0],
                ],
                [
                    'objective'          => 'การเบิกจ่ายสวัสดิการพนักงาน (เงินป่วย, คลอดบุตร, ฯลฯ)',
                    'detail'             => 'ตรวจสอบเอกสารและดำเนินการจ่ายสวัสดิการ 100% ภายใน 3 วันทำการ หลังได้รับเอกสารครบถ้วน',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 100.0, 80.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การบริหารสวัสดิการผู้บริหาร/ฟอร์ม',
                    'detail'             => 'ดำเนินการฟอร์มทุกชนิดและติดตามผู้บริหาร 100% จ่ายทันเวลาภายใน 7 วันทำการ',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 75.0, 100.0, 100.0, 100.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การดำเนินการบรรยายวัด/งานอาลัย',
                    'detail'             => 'สำรวจรายชื่อพนักงานและดำเนินการจัดกิจกรรมทางศาสนา 100% ทันตามกำหนด (0 เลทและ 0 บาทค่าปรับ)',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การสื่อสารและประกาศภายในบริษัท รวมถึงประชาสัมพันธ์กิจกรรม',
                    'detail'             => 'ประกาศประชาสัมพันธ์ทุกกิจกรรมภายในบริษัทผ่านทุกช่องทางภายใน 24 ชม. หลังได้รับคำสั่ง',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 85.0, 100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 70.0, 100.0],
                ],
                [
                    'objective'          => 'การดำเนินกิจกรรม CSR และชุมชนสัมพันธ์',
                    'detail'             => 'ดำเนินกิจกรรม CSR 100% ตามแผนสำเร็จ 1 ครั้ง/เดือน (หรือตามแผนงานที่อนุมัติ) พร้อมสรุปผลส่งผู้บริหาร',
                    'target_value'       => 1.0,
                    'unit_id'            => 5,    // ครั้ง
                    'criteria_operator'  => '>=',
                    'score_samples'      => [1.0, 1.0, 0.0, 1.0, 2.0, 1.0, 0.0, 1.0, 1.0, 2.0, 1.0, 1.0],
                ],
                [
                    'objective'          => 'การจัดการเรื่องร้องทุกข์และข้อเสนอแนะ',
                    'detail'             => 'รับฟัง ตรวจสอบ และแก้ไขเรื่องร้องทุกข์ 100% ภายใน 7 วันทำการ โดยมีอัตราร้องซ้ำ < 10%',
                    'target_value'       => 10.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '<=',
                    'score_samples'      => [5.0, 0.0, 8.0, 15.0, 0.0, 5.0, 12.0, 0.0, 0.0, 5.0, 20.0, 0.0],
                ],
                [
                    'objective'          => 'การควบคุมและอัปเดตเอกสารระบบคุณภาพ (ISO / IATF 16949:2016 / TLS8001:2563)',
                    'detail'             => 'อัปเดต แจกจ่าย และบันทึกการรับเอกสารควบคุมคุณภาพแล้วเสร็จภายใน 5 วันทำการ หลังได้รับแจ้งจากผู้รับผิดชอบ',
                    'target_value'       => 5.0,
                    'unit_id'            => null, // วันทำการ
                    'criteria_operator'  => '<=',
                    'score_samples'      => [3.0, 2.0, 4.0, 6.0, 2.0, 5.0, 3.0, 4.0, 7.0, 3.0, 4.0, 2.0],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════════
        // 5. Secretary of CEO  →  L1#1 Award Company / L2#4 Absenteeism & Turnover
        // ══════════════════════════════════════════════════════════════════
        [
            'name'         => 'Secretary of CEO',
            'user_name_th' => 'กัญฤทัย',
            'l1_sort'      => 1,
            'l2_sort'      => 4,
            'items'   => [
                [
                    'objective'          => 'การเข้าร่วมประชุมและจัดทำรายงานการประชุม (MoM)',
                    'detail'             => 'เข้าร่วมประชุมตามที่ CEO มอบหมาย จดบันทึก สรุปสาระสำคัญ และส่ง MoM ภายใน 24-48 ชม. หลังการประชุม — 100% ไม่ขาดประชุมโดยไม่มีเหตุผล',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 100.0, 100.0, 75.0, 100.0, 100.0, 100.0, 100.0, 100.0, 80.0],
                ],
                [
                    'objective'          => 'การติดตามผลงานที่ CEO สั่งการต่อฝ่ายบริหาร',
                    'detail'             => 'ติดตามความคืบหน้าของงานที่ CEO สั่งการให้ฝ่ายต่างๆ — 100% อัปเดตและส่งรายงานให้ CEO ทุกวันศุกร์',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 80.0, 100.0, 100.0, 100.0, 60.0, 100.0, 100.0, 90.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การบริหารและรายงานตัวเลขผู้บริหาร',
                    'detail'             => 'จัดทำและประสานงานรายงานสรุปตัวเลขผลการดำเนินงานให้ผู้บริหาร — อัปโหลดตรงเวลาตามรอบที่กำหนด',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 70.0, 100.0, 100.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การค้นหาและตรวจสอบเอกสาร/ข้อมูลสำหรับ CEO',
                    'detail'             => 'ค้นหา ตรวจสอบ และรวบรวมเอกสารสำคัญจากทุกแผนกตามที่ CEO ต้องการ — 100% ค้นสำเร็จ ไม่เกิน 2 ครั้ง/เดือน ที่ผิดพลาด',
                    'target_value'       => 2.0,
                    'unit_id'            => 5,    // ครั้ง (ผิดพลาด)
                    'criteria_operator'  => '<=',
                    'score_samples'      => [0.0, 1.0, 0.0, 2.0, 0.0, 3.0, 1.0, 0.0, 2.0, 0.0, 4.0, 1.0],
                ],
                [
                    'objective'          => 'การประสานงานและรับรองแขกบริษัท/ผู้เยี่ยมชม VIP',
                    'detail'             => 'ตรวจสอบและเตรียมการรับรองผู้เยี่ยมชม VIP — 100% ดำเนินการสำเร็จในทุกรอบ',
                    'target_value'       => 100.0,
                    'unit_id'            => 2,    // %
                    'criteria_operator'  => '>=',
                    'score_samples'      => [100.0, 100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 80.0, 100.0, 100.0, 100.0],
                ],
                [
                    'objective'          => 'การจัดการเดินทางผู้บริหาร (Executive Travel Management)',
                    'detail'             => 'ประสานงาน จองตั๋วเครื่องบิน โรงแรม และจัดทำ Itinerary ส่งมอบก่อนเดินทาง 48 ชม. — ความผิดพลาด 0% (Zero Error)',
                    'target_value'       => 0.0,
                    'unit_id'            => 5,    // ครั้ง (ผิดพลาด)
                    'criteria_operator'  => '=',
                    'score_samples'      => [0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 2.0],
                ],
                [
                    'objective'          => 'การควบคุมและอัปเดตเอกสารระบบคุณภาพ (Secretary of CEO)',
                    'detail'             => 'อัปเดต แจกจ่าย และขึ้นทะเบียนเอกสาร HRM ในระบบ Document Control ให้เป็นปัจจุบัน',
                    'target_value'       => 5.0,
                    'unit_id'            => null, // วันทำการ
                    'criteria_operator'  => '<=',
                    'score_samples'      => [2.0, 4.0, 3.0, 5.0, 3.0, 8.0, 4.0, 3.0, 5.0, 2.0, 6.0, 3.0],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $cycle = Cycle::query()->where('is_active', true)->orderByDesc('id')->first()
            ?? Cycle::query()->orderByDesc('id')->first();

        if (! $cycle) {
            $this->command->warn('KpiReportHrmWelfareSeeder: no cycle found — skipping.');
            return;
        }

        $openMonths = CycleMonth::query()
            ->where('cycle_id', (int) $cycle->id)
            ->where('is_active', true)
            ->orderBy('month_no')
            ->pluck('month_no')
            ->map(fn ($m) => (int) $m)
            ->all();

        if (empty($openMonths)) {
            $this->command->warn('KpiReportHrmWelfareSeeder: no open months in cycle #' . $cycle->id . ' — skipping.');
            return;
        }

        $this->command->info('KpiReportHrmWelfareSeeder: cycle #' . $cycle->id . ', open months: ' . implode(', ', $openMonths));

        // Fetch users by priority position order
        $positionOrder = array_flip(self::TARGET_POSITIONS);
        $users = AppUser::query()
            ->where('dept_abbr_hr', self::DEPT)
            ->where(function ($q) {
                $q->whereIn(DB::raw('LOWER(position)'), self::TARGET_POSITIONS);
            })
            ->orderBy('id')
            ->get()
            ->sortBy(fn ($u) => $positionOrder[strtolower($u->position ?? '')] ?? 99)
            ->values();

        if ($users->isEmpty()) {
            $this->command->warn('  No matching positions in ' . self::DEPT . '; using first 5 HRM&HRD users.');
            $users = AppUser::query()
                ->where('dept_abbr_hr', self::DEPT)
                ->orderBy('id')
                ->take(5)
                ->get()
                ->values();
        }

        if ($users->isEmpty()) {
            $this->command->warn('  No users in ' . self::DEPT . ' — skipping.');
            return;
        }

        $this->command->info('  Users (' . $users->count() . '): #' . $users->pluck('id')->join(', #'));

        $objBySortNo = OkrObjective::query()
            ->get()
            ->keyBy('sort_no');

        // Remove existing report records for this dept in this cycle to avoid duplication
        $deptKrIds = OkrKeyResult::query()
            ->where('dept_abbr_hr', self::DEPT)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($cycle, $openMonths, $users, $objBySortNo, $deptKrIds): void {
            // Delete all existing report records (roots + month children) for these users in this cycle
            $rootIdsToDelete = KpiMonthScore::query()
                ->where('mode_type', 'report')
                ->where('cycle_id', (int) $cycle->id)
                ->whereIn('okr_key_result_id', $deptKrIds)
                ->whereIn('app_user_id', $users->pluck('id'))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $deleted = 0;
            if (! empty($rootIdsToDelete)) {
                // Delete month children first (kpi_meta_id references root)
                $deleted += KpiMonthScore::query()
                    ->whereIn('kpi_meta_id', $rootIdsToDelete)
                    ->delete();
                // Then delete root records themselves
                $deleted += KpiMonthScore::query()
                    ->whereIn('id', $rootIdsToDelete)
                    ->delete();
            }
            if ($deleted > 0) {
                $this->command->line('  Removed ' . $deleted . ' previous report record(s).');
            }

            // Pre-resolve named users for each section so we can build a "remaining" pool
            $namedUserIds = [];
            foreach (self::SECTIONS as $section) {
                $hint = $section['user_name_th'] ?? '';
                if ($hint === '') {
                    continue;
                }
                $u = AppUser::query()
                    ->where('dept_abbr_hr', self::DEPT)
                    ->where('full_name_th', 'like', '%' . $hint . '%')
                    ->first();
                if ($u) {
                    $namedUserIds[] = (int) $u->id;
                }
            }

            // Pool of users not claimed by any named section (for fallback round-robin)
            $fallbackPool = $users->filter(fn ($u) => ! in_array((int) $u->id, $namedUserIds, true))->values();
            $fallbackPoolIdx = 0;

            $totalCreated = 0;
            $sectionCount = count(self::SECTIONS);

            foreach (self::SECTIONS as $sectionIdx => $section) {
                $obj = $objBySortNo->get($section['l1_sort']);
                if (! $obj) {
                    $this->command->warn('  [' . $section['name'] . '] L1 sort#' . $section['l1_sort'] . ' not found — skipping.');
                    continue;
                }

                $kr = OkrKeyResult::query()
                    ->where('okr_objective_id', (int) $obj->id)
                    ->where('dept_abbr_hr', self::DEPT)
                    ->where('sort_no', $section['l2_sort'])
                    ->first();

                if (! $kr) {
                    $this->command->warn('  [' . $section['name'] . '] L2 sort#' . $section['l2_sort'] . ' not found — skipping.');
                    continue;
                }

                $l3Target = KpiMonthScore::query()
                    ->where('mode_type', 'target')
                    ->where('month_no', 0)
                    ->where('okr_key_result_id', (int) $kr->id)
                    ->orderBy('id')
                    ->first();

                $parentId = $l3Target ? (int) $l3Target->id : null;

                // Resolve assigned users: prefer the named user from the image, fall back to round-robin
                $nameHint = $section['user_name_th'] ?? '';
                $namedUser = $nameHint !== ''
                    ? AppUser::query()
                        ->where('dept_abbr_hr', self::DEPT)
                        ->where('full_name_th', 'like', '%' . $nameHint . '%')
                        ->first()
                    : null;

                if ($namedUser) {
                    $assignedUsers = collect([$namedUser]);
                } else {
                    // Pick next user from fallback pool (users not claimed by any named section)
                    if ($fallbackPool->isNotEmpty()) {
                        $assignedUsers = $fallbackPool->slice($fallbackPoolIdx % $fallbackPool->count(), 1);
                        $fallbackPoolIdx++;
                    } else {
                        // Last resort: any user
                        $assignedUsers = $users->slice($sectionIdx % $users->count(), 1);
                    }
                }

                $this->command->line(
                    '  [' . $section['name'] . '] L1#' . $section['l1_sort']
                    . '/L2#' . $section['l2_sort']
                    . ($parentId ? ' → L3 #' . $parentId : ' (no L3 target)')
                    . ' | ' . count($section['items']) . ' items'
                    . ' | users: #' . $assignedUsers->pluck('id')->join(', #')
                );

                foreach ($section['items'] as $item) {
                    foreach ($assignedUsers as $user) {
                        // ── Root record (month_no=0, kpi_meta_id=null) ───────────────
                        // This is what the system uses as the "KPI card" visible in the UI.
                        $root = KpiMonthScore::query()->create([
                            'app_user_id'           => (int) $user->id,
                            'cycle_id'              => (int) $cycle->id,
                            'okr_objective_id'      => (int) $obj->id,
                            'okr_key_result_id'     => (int) $kr->id,
                            'parent_target_kpi_id'  => $parentId,
                            'month_no'              => 0,
                            'kpi_meta_id'           => null,
                            'objective'             => $item['objective'],
                            'detail'                => $item['detail'],
                            'target_value'          => $item['target_value'],
                            'kpi_unit_id'           => $item['unit_id'],
                            'criteria_operator'     => $item['criteria_operator'],
                            'mode_type'             => 'report',
                            'score_value'           => 0,
                            'is_pass'               => false,
                        ]);
                        $totalCreated++;

                        // ── Month records (kpi_meta_id=root->id, month_no=open month) ─
                        foreach ($openMonths as $monthNo) {
                            $sampleIdx  = ($monthNo - 1) % count($item['score_samples']);
                            $scoreValue = $item['score_samples'][$sampleIdx];

                            KpiMonthScore::query()->create([
                                'app_user_id'           => (int) $user->id,
                                'cycle_id'              => (int) $cycle->id,
                                'okr_objective_id'      => (int) $obj->id,
                                'okr_key_result_id'     => (int) $kr->id,
                                'parent_target_kpi_id'  => $parentId,
                                'kpi_meta_id'           => (int) $root->id,
                                'month_no'              => $monthNo,
                                'objective'             => $item['objective'],
                                'detail'                => $item['detail'],
                                'target_value'          => $item['target_value'],
                                'kpi_unit_id'           => $item['unit_id'],
                                'criteria_operator'     => $item['criteria_operator'],
                                'mode_type'             => 'report',
                                'score_value'           => $scoreValue,
                                'is_pass'               => $this->checkPass($scoreValue, $item['target_value'], $item['criteria_operator']),
                                'submitted_at'          => $this->resolveSubmittedAt($cycle, $monthNo),
                            ]);
                            $totalCreated++;
                        }
                    }
                }
            }

            $this->command->info('KpiReportHrmWelfareSeeder: created ' . $totalCreated . ' report record(s).');
        });
    }

    private function checkPass(float $score, float $target, string $operator): bool
    {
        return match ($operator) {
            '>='    => $score >= $target,
            '<='    => $score <= $target,
            '>'     => $score > $target,
            '<'     => $score < $target,
            '='     => abs($score - $target) < 0.001,
            default => false,
        };
    }

    private function resolveSubmittedAt(Cycle $cycle, int $monthNo): Carbon
    {
        $year = $cycle->start_date ? (int) $cycle->start_date->format('Y') : now()->year;
        $date = Carbon::create($year, $monthNo, 28, 10, 0, 0);
        if ($date->isFuture()) {
            $date = now()->subDay();
        }
        return $date;
    }
}
