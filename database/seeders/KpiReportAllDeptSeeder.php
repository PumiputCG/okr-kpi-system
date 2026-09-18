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
 * Seeds "รายงาน" KPI entries for all departments (excluding HRM&HRD which has its own seeder).
 * Run after: GoalTargetSeeder → GoalTargetAllDeptSeeder → AdminDepartmentAssignmentSeeder → GoalTargetAllDeptL3Seeder
 * Command: php artisan db:seed --class=KpiReportAllDeptSeeder
 */
class KpiReportAllDeptSeeder extends Seeder
{
    /** Skip HRM&HRD — covered by KpiReportHrmWelfareSeeder */
    private const SKIP_DEPT = 'HRM&HRD';

    /**
     * DEPT_SECTIONS: dept_abbr_hr → list of sections.
     * Each section: name, user_name_th (hint for user matching), l1_sort, l2_sort, items[].
     * Each item: objective, detail, target_value, unit_id, criteria_operator, score_samples[12].
     * unit_id: 2=% 3=คน 4=ราย 5=ครั้ง 7=งาน 9=เอกสาร 10=ชั่วโมง 12=เดือน 15=คะแนน null=default
     *
     * @var array<string, list<array{name:string,user_name_th:string,l1_sort:int,l2_sort:int,items:list<array{...}>}>>
     */
    private const DEPT_SECTIONS = [

        // ══════════════════════════════════════════════════════════════
        // ACC — Accounting
        // ══════════════════════════════════════════════════════════════
        'ACC' => [
            [
                'name'         => 'Cost & Budget Control',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ควบคุมงบประมาณรายจ่ายองค์กร',
                        'detail'            => 'ติดตามและควบคุมงบประมาณค่าใช้จ่ายรายเดือนให้อยู่ในกรอบ ±2% ของงบที่ตั้งไว้ พร้อมรายงาน Variance Analysis ให้ผู้บริหารภายในวันที่ 10',
                        'target_value'      => 2.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '<=',
                        'score_samples'     => [1.2, 0.8, 2.5, 1.5, 0.5, 3.1, 1.8, 0.9, 2.2, 1.1, 0.7, 1.9],
                    ],
                    [
                        'objective'         => 'รายงานการเงินรายเดือนถูกต้องและทันเวลา',
                        'detail'            => 'จัดทำและส่งรายงานการเงินรายเดือน (P&L, Balance Sheet, Cash Flow) ถูกต้อง 100% ภายในวันที่ 10 ของเดือนถัดไป',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 80.0, 100.0, 100.0, 100.0],
                    ],
                    [
                        'objective'         => 'การจัดทำ Cash Flow Forecast',
                        'detail'            => 'จัดทำ Cash Flow Forecast รายเดือนล่วงหน้า 3 เดือน ความแม่นยำ ≥90% เทียบกับ Actual',
                        'target_value'      => 90.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [92.0, 88.0, 95.0, 85.0, 91.0, 87.0, 93.0, 90.0, 84.0, 92.0, 89.0, 94.0],
                    ],
                    [
                        'objective'         => 'การปิดบัญชีรายเดือน',
                        'detail'            => 'ปิดบัญชีรายเดือนให้แล้วเสร็จภายในวันที่ 7 ของเดือนถัดไป 100% ของเดือนทั้งหมด',
                        'target_value'      => 7.0,
                        'unit_id'           => null,
                        'criteria_operator' => '<=',
                        'score_samples'     => [5.0, 6.0, 7.0, 8.0, 5.0, 7.0, 6.0, 10.0, 5.0, 7.0, 6.0, 7.0],
                    ],
                ],
            ],
            [
                'name'         => 'Tax & Legal Compliance',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'การนำส่งภาษีตรงเวลาและถูกต้อง',
                        'detail'            => 'นำส่ง ภ.ง.ด.1, ภ.ง.ด.3, ภ.ง.ด.53, ภพ.30 และภาษีอื่นๆ ตามกำหนดของสรรพากร — 0 ครั้งที่ล่าช้าหรือถูกปรับ',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                    [
                        'objective'         => 'การตรวจสอบเอกสารบัญชีให้พร้อมรับ Audit',
                        'detail'            => 'จัดเตรียมเอกสารประกอบการบันทึกบัญชีให้ครบถ้วน 100% พร้อมรับการตรวจจากผู้สอบบัญชีภายนอก',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // QC — Quality Control
        // ══════════════════════════════════════════════════════════════
        'QC' => [
            [
                'name'         => 'Customer Quality',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Customer PPM (Zero Defect)',
                        'detail'            => 'ควบคุมของเสียที่หลุดถึงลูกค้าให้ได้ 0 PPM โดยเฉพาะลูกค้า Ford และ AAT ด้วยการเสริมจุด Final Inspection',
                        'target_value'      => 0.0,
                        'unit_id'           => null,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 10.0, 0.0, 0.0, 0.0, 5.0, 0.0, 0.0, 0.0, 15.0, 0.0],
                    ],
                    [
                        'objective'         => 'การปิด 8D / CAR ตามกำหนด',
                        'detail'            => 'ดำเนินการแก้ไขและปิด 8D Report / Customer Corrective Action ≥95% ภายในกำหนดที่ลูกค้ากำหนด',
                        'target_value'      => 95.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 95.0, 80.0, 100.0, 95.0, 100.0, 90.0, 100.0, 85.0, 100.0, 95.0, 100.0],
                    ],
                    [
                        'objective'         => 'ของเสียภายใน (Internal Defect Rate)',
                        'detail'            => 'ควบคุมของเสียระหว่างกระบวนการให้ต่ำกว่า 500 PPM ด้วยการใช้ SPC และ Poka-Yoke',
                        'target_value'      => 500.0,
                        'unit_id'           => null,
                        'criteria_operator' => '<=',
                        'score_samples'     => [320.0, 410.0, 580.0, 290.0, 470.0, 640.0, 380.0, 310.0, 520.0, 280.0, 450.0, 360.0],
                    ],
                    [
                        'objective'         => 'การตอบสนองต่อ Customer Complaint',
                        'detail'            => 'ส่ง Containment Action ต่อข้อร้องเรียนลูกค้าภายใน 24 ชั่วโมง และ Root Cause Analysis ภายใน 7 วัน — 100%',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 75.0, 100.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
            [
                'name'         => 'Process Quality Audit',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ผ่าน IATF 16949 Audit ไม่มี Major NC',
                        'detail'            => 'ผ่านการตรวจ Audit IATF 16949 จากลูกค้าและบุคคลที่สาม โดยไม่มี Major NC และปิด Minor NC ภายใน 30 วัน',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                    [
                        'objective'         => 'การควบคุมเครื่องมือวัด (MSA / Calibration)',
                        'detail'            => 'ดำเนินการ Calibration เครื่องมือวัดตามแผนครบ 100% และดำเนินการ MSA ตามกำหนด',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 95.0, 100.0, 100.0, 90.0, 100.0, 100.0, 100.0, 85.0, 100.0, 100.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // QA — Quality Assurance
        // ══════════════════════════════════════════════════════════════
        'QA' => [
            [
                'name'         => 'Customer Complaint & Audit',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'จำนวน Major Claim จากลูกค้า',
                        'detail'            => 'ไม่มี Major Claim หรือ Warranty Claim จากลูกค้า ด้วยการวิเคราะห์ Root Cause เชิงรุกและ CAPA ที่มีประสิทธิผล',
                        'target_value'      => 0.0,
                        'unit_id'           => 4,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                    [
                        'objective'         => 'Internal Audit Completion Rate',
                        'detail'            => 'ดำเนินการ Internal Audit ตามแผนประจำปีครบ 100% และปิด NC ภายในกำหนด',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 100.0, 100.0, 75.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0],
                    ],
                    [
                        'objective'         => 'อัตราการปิด CAPA ตามกำหนด',
                        'detail'            => 'ปิด CAPA จากการ Audit และ Complaint ≥90% ภายในกำหนด ป้องกันการเกิดซ้ำ',
                        'target_value'      => 90.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [95.0, 90.0, 80.0, 100.0, 92.0, 85.0, 100.0, 88.0, 95.0, 90.0, 75.0, 100.0],
                    ],
                    [
                        'objective'         => 'การจัดทำและรักษามาตรฐาน Control Plan',
                        'detail'            => 'อัปเดต Control Plan ให้สอดคล้อง Engineering Change ภายใน 30 วัน ครบ 100% ของ Change ที่เกิดขึ้น',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 85.0, 100.0, 100.0, 90.0, 100.0, 100.0, 80.0, 100.0, 100.0, 95.0],
                    ],
                ],
            ],
            [
                'name'         => 'QMS Certification',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'รักษาใบรับรอง IATF 16949',
                        'detail'            => 'รักษาใบรับรอง IATF 16949 ผ่าน Surveillance Audit ทุกปี โดยไม่มี Major NC และปิด Minor NC ภายใน 90 วัน',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // SHE — Safety, Health & Environment
        // ══════════════════════════════════════════════════════════════
        'SHE' => [
            [
                'name'         => 'Safety Incident Control',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Lost Time Injury Frequency Rate (LTIFR)',
                        'detail'            => 'ไม่มีอุบัติเหตุที่ทำให้หยุดงาน (LTI) ตลอดทั้งปี LTIFR = 0 ด้วยการดำเนินการ Near Miss Reporting และ Safety Walk-through',
                        'target_value'      => 0.0,
                        'unit_id'           => null,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                    [
                        'objective'         => 'คะแนน 5S & Safety Audit',
                        'detail'            => 'ผ่านการตรวจ 5S และ Safety Walk-through ได้คะแนน ≥90% ทุกเดือน',
                        'target_value'      => 90.0,
                        'unit_id'           => 15,
                        'criteria_operator' => '>=',
                        'score_samples'     => [93.0, 91.0, 88.0, 95.0, 90.0, 85.0, 92.0, 94.0, 87.0, 91.0, 89.0, 96.0],
                    ],
                    [
                        'objective'         => 'การฝึกซ้อมดับเพลิงและอพยพ',
                        'detail'            => 'ดำเนินการซ้อมดับเพลิงและอพยพหนีไฟอย่างน้อย 1 ครั้ง/ปี และซ้อมอพยพย่อยทุกไตรมาส',
                        'target_value'      => 1.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '>=',
                        'score_samples'     => [0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0],
                    ],
                    [
                        'objective'         => 'การรายงาน Near Miss และ Hazard',
                        'detail'            => 'รับรายงาน Near Miss ≥3 รายการ/เดือน และดำเนินการแก้ไข 100% ภายใน 7 วัน',
                        'target_value'      => 3.0,
                        'unit_id'           => 4,
                        'criteria_operator' => '>=',
                        'score_samples'     => [4.0, 3.0, 2.0, 5.0, 3.0, 4.0, 3.0, 6.0, 3.0, 4.0, 2.0, 5.0],
                    ],
                ],
            ],
            [
                'name'         => 'Environmental Compliance',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'การปฏิบัติตามกฎหมายสิ่งแวดล้อม',
                        'detail'            => 'ไม่มีการละเมิดกฎหมายสิ่งแวดล้อม (น้ำทิ้ง อากาศ กากของเสีย) และผ่านการตรวจจากหน่วยงานราชการโดยไม่มีค่าปรับ',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                    [
                        'objective'         => 'การกำจัดและขนส่งกากของเสียอุตสาหกรรม',
                        'detail'            => 'กำจัดกากของเสียอุตสาหกรรมผ่านผู้รับกำจัดที่ได้รับอนุญาต 100% พร้อมใบกำกับการขนส่ง',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // IT — Information Technology
        // ══════════════════════════════════════════════════════════════
        'IT' => [
            [
                'name'         => 'System Availability & Security',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'System Uptime (ความพร้อมใช้งานระบบ)',
                        'detail'            => 'รักษาเสถียรภาพระบบ IT ทั้งหมด (ERP, Network, Server) ให้มี Uptime ≥99.5% ต่อเดือน และแก้ไข Critical Incident ภายใน SLA 4 ชั่วโมง',
                        'target_value'      => 99.5,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.8, 99.9, 99.2, 99.7, 99.5, 98.9, 99.6, 99.8, 99.4, 99.7, 99.3, 99.9],
                    ],
                    [
                        'objective'         => 'ไม่มี Cybersecurity Incident (Data Breach)',
                        'detail'            => 'ไม่มีเหตุการณ์ละเมิดความปลอดภัยข้อมูล (Data Breach / Ransomware) ดำเนินการ Patch Management ตามแผน 100%',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                    [
                        'objective'         => 'Helpdesk Ticket Resolution Rate',
                        'detail'            => 'แก้ไข IT Helpdesk Ticket ≥95% ภายใน SLA ที่กำหนด (P1: 4 ชม., P2: 8 ชม., P3: 3 วัน)',
                        'target_value'      => 95.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [97.0, 95.0, 92.0, 98.0, 96.0, 90.0, 97.0, 95.0, 93.0, 98.0, 94.0, 96.0],
                    ],
                    [
                        'objective'         => 'Backup & Recovery Test',
                        'detail'            => 'ดำเนินการทดสอบ Backup Recovery ระบบ Critical อย่างน้อย 1 ครั้ง/เดือน และบันทึกผลการทดสอบ',
                        'target_value'      => 1.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '>=',
                        'score_samples'     => [1.0, 1.0, 1.0, 1.0, 0.0, 1.0, 1.0, 1.0, 0.0, 1.0, 1.0, 1.0],
                    ],
                ],
            ],
            [
                'name'         => 'Digital Transformation',
                'user_name_th' => '',
                'l1_sort'      => 4,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'โครงการ Digitalization ตามแผน',
                        'detail'            => 'ดำเนินโครงการ Digitalization/IT Development ส่งมอบ Milestone ตรงกำหนด ≥80% ของโครงการทั้งหมด',
                        'target_value'      => 80.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [85.0, 90.0, 75.0, 88.0, 80.0, 70.0, 85.0, 92.0, 78.0, 83.0, 80.0, 87.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // PC — Production Control
        // ══════════════════════════════════════════════════════════════
        'PC' => [
            [
                'name'         => 'Production Schedule & OTD',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Production Schedule Adherence',
                        'detail'            => 'แผนการผลิตที่ออกไปถูกปฏิบัติตาม ≥98% ลด Last-Minute Change และ Expedite ให้ต่ำสุด',
                        'target_value'      => 98.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.0, 98.5, 96.0, 99.2, 98.0, 95.0, 98.7, 99.0, 97.0, 99.5, 98.0, 99.1],
                    ],
                    [
                        'objective'         => 'Customer OTD จาก Production Control',
                        'detail'            => 'ส่งมอบตรงเวลาตามที่ลูกค้ากำหนด ≥99% ด้วยการวางแผนการผลิตที่แม่นยำและประสานงานเชิงรุก',
                        'target_value'      => 99.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.5, 99.0, 97.5, 100.0, 99.2, 98.0, 99.7, 99.5, 98.5, 100.0, 99.0, 99.8],
                    ],
                    [
                        'objective'         => 'การออกแผนการผลิต Master Production Schedule',
                        'detail'            => 'ออก MPS ล่วงหน้า 4 สัปดาห์ครบทุกสายการผลิต ภายในวันศุกร์ของสัปดาห์ก่อน — 100% ทันเวลา',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 100.0, 100.0, 80.0, 100.0, 100.0, 100.0, 100.0, 90.0, 100.0],
                    ],
                ],
            ],
            [
                'name'         => 'Logistics Cost from PC',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'การลด Expedite Fee จากการเปลี่ยนแผนกะทันหัน',
                        'detail'            => 'ลดค่าใช้จ่าย Expedite / Emergency Shipment จากการเปลี่ยนแผนการผลิตกะทันหัน ≤1% ของ Revenue',
                        'target_value'      => 1.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '<=',
                        'score_samples'     => [0.5, 0.8, 1.2, 0.6, 0.9, 1.5, 0.7, 0.4, 1.1, 0.8, 1.3, 0.6],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // PU — Purchasing
        // ══════════════════════════════════════════════════════════════
        'PU' => [
            [
                'name'         => 'Purchase Lead Time & Availability',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'บริหาร Lead Time การสั่งซื้อ',
                        'detail'            => 'บริหาร Lead Time การสั่งซื้อให้อยู่ในเกณฑ์ที่กำหนด ไม่มีการหยุดสายผลิตจากการจัดซื้อล่าช้า — 0 Line Stop',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 2.0, 0.0, 0.0, 0.0],
                    ],
                    [
                        'objective'         => 'Supplier On-Time Delivery Rate',
                        'detail'            => 'ติดตามให้ Supplier ส่งมอบตรงเวลา ≥95% ของ PO ทั้งหมด และดำเนินการกับ Supplier ที่ผิดนัดซ้ำ',
                        'target_value'      => 95.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [97.0, 95.0, 92.0, 98.0, 94.0, 90.0, 96.0, 95.0, 93.0, 97.0, 94.0, 96.0],
                    ],
                    [
                        'objective'         => 'ความถูกต้องของการออก Purchase Order',
                        'detail'            => 'ออก PO ถูกต้อง 100% (ราคา, จำนวน, สเปค, เงื่อนไขชำระ) ไม่มีการแก้ไข PO หลังอนุมัติ',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 98.0, 100.0, 95.0, 100.0, 100.0, 98.0, 100.0, 97.0, 100.0, 100.0, 99.0],
                    ],
                ],
            ],
            [
                'name'         => 'Cost Saving & Negotiation',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ลดต้นทุนการจัดซื้อผ่านการเจรจา',
                        'detail'            => 'ลดต้นทุนการจัดซื้อรวม ≥3% ต่อปี ผ่านการเจรจาราคา การรวม PO และการคัดเลือก Supplier ทางเลือก',
                        'target_value'      => 3.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [3.5, 2.8, 4.2, 3.1, 2.5, 3.8, 3.0, 4.5, 2.9, 3.3, 2.7, 3.6],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // MF — Manufacturing Floor
        // ══════════════════════════════════════════════════════════════
        'MF' => [
            [
                'name'         => 'Line Efficiency & Quality',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Line Efficiency',
                        'detail'            => 'รักษาประสิทธิภาพสายการผลิต (Line Efficiency) ให้ >90% ลด Idle Time, Changeover Time และ Minor Stoppage',
                        'target_value'      => 90.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [92.0, 91.0, 88.0, 93.0, 90.0, 86.0, 91.0, 94.0, 89.0, 92.0, 90.0, 93.0],
                    ],
                    [
                        'objective'         => 'In-Process Defect Rate',
                        'detail'            => 'ควบคุมของเสียระหว่างกระบวนการให้ต่ำกว่า 300 PPM ด้วย Poka-Yoke, SPC และ Operator Training',
                        'target_value'      => 300.0,
                        'unit_id'           => null,
                        'criteria_operator' => '<=',
                        'score_samples'     => [180.0, 240.0, 350.0, 210.0, 280.0, 420.0, 190.0, 260.0, 310.0, 175.0, 290.0, 230.0],
                    ],
                    [
                        'objective'         => 'First Pass Yield (FPY)',
                        'detail'            => 'อัตราผลผลิตที่ผ่านการตรวจสอบในรอบแรก (FPY) ≥99% ลดการ Rework และ Scrap',
                        'target_value'      => 99.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.5, 99.2, 98.7, 99.6, 99.0, 98.4, 99.3, 99.7, 98.9, 99.4, 99.1, 99.5],
                    ],
                    [
                        'objective'         => 'คะแนน 5S ในพื้นที่รับผิดชอบ',
                        'detail'            => 'ผ่านการตรวจ 5S ในพื้นที่รับผิดชอบได้คะแนน ≥90 คะแนน จากการ Audit ประจำเดือน',
                        'target_value'      => 90.0,
                        'unit_id'           => 15,
                        'criteria_operator' => '>=',
                        'score_samples'     => [93.0, 91.0, 87.0, 94.0, 90.0, 85.0, 92.0, 95.0, 88.0, 93.0, 89.0, 92.0],
                    ],
                ],
            ],
            [
                'name'         => 'Production Cost Reduction',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ลดต้นทุนการผลิตต่อหน่วย',
                        'detail'            => 'ลด Unit Manufacturing Cost ≥2% เทียบแผนรายเดือน ผ่านการลด Scrap, Rework และ Energy Consumption',
                        'target_value'      => 2.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [2.5, 1.8, 3.0, 2.2, 1.5, 2.8, 2.0, 3.5, 1.9, 2.4, 1.7, 2.6],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // MMT — Maintenance
        // ══════════════════════════════════════════════════════════════
        'MMT' => [
            [
                'name'         => 'Planned Maintenance & Reliability',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Planned Maintenance (PM) Completion Rate',
                        'detail'            => 'ดำเนินการบำรุงรักษาตามแผน (PM) ให้แล้วเสร็จ ≥95% ของรายการ PM ที่กำหนดในแต่ละเดือน',
                        'target_value'      => 95.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [97.0, 96.0, 93.0, 98.0, 95.0, 91.0, 96.0, 98.0, 94.0, 97.0, 95.0, 96.5],
                    ],
                    [
                        'objective'         => 'Machine Breakdown (Unplanned Downtime)',
                        'detail'            => 'ลด Unplanned Downtime จากเครื่องจักรเสียให้ต่ำกว่า 2% ของเวลาการผลิตรวม',
                        'target_value'      => 2.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '<=',
                        'score_samples'     => [1.2, 1.5, 2.3, 0.8, 1.9, 2.8, 1.4, 1.0, 2.1, 1.3, 1.8, 1.6],
                    ],
                    [
                        'objective'         => 'Mean Time To Repair (MTTR)',
                        'detail'            => 'ลด MTTR ของเครื่องจักรหลักให้ ≤4 ชั่วโมง/เคส เพื่อลดผลกระทบต่อสายการผลิต',
                        'target_value'      => 4.0,
                        'unit_id'           => 10,
                        'criteria_operator' => '<=',
                        'score_samples'     => [2.5, 3.0, 4.5, 2.0, 3.8, 5.2, 3.2, 2.8, 4.1, 2.3, 3.5, 3.0],
                    ],
                    [
                        'objective'         => 'Spare Part Availability',
                        'detail'            => 'รักษา Spare Part สำคัญให้มีพร้อมใช้งาน ≥95% ลด Emergency Purchase ที่ส่งผลต่องบประมาณ',
                        'target_value'      => 95.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [97.0, 96.0, 94.0, 98.0, 95.0, 92.0, 96.0, 97.0, 93.0, 98.0, 95.0, 96.0],
                    ],
                ],
            ],
            [
                'name'         => 'Maintenance Safety',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'อุบัติเหตุระหว่างการบำรุงรักษา (LOTO Compliance)',
                        'detail'            => 'ไม่มีอุบัติเหตุระหว่างการบำรุงรักษาเครื่องจักร LOTO Compliance 100% ทุก Work Order',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // SL — Sales
        // ══════════════════════════════════════════════════════════════
        'SL' => [
            [
                'name'         => 'Sales Revenue & Quotation',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ยอดขายรายเดือน vs เป้าหมาย',
                        'detail'            => 'บรรลุเป้าหมายยอดขายรายเดือน ≥100% ของ Quota ที่กำหนด ติดตามและรายงานผลรายสัปดาห์',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [105.0, 98.0, 92.0, 110.0, 103.0, 88.0, 107.0, 101.0, 95.0, 112.0, 99.0, 108.0],
                    ],
                    [
                        'objective'         => 'Customer Quotation Win Rate',
                        'detail'            => 'อัตราการได้รับคำสั่งซื้อจากใบเสนอราคาที่ออกไป ≥40% ด้วยการเสนอ Solution ที่ตรงความต้องการ',
                        'target_value'      => 40.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [45.0, 42.0, 38.0, 50.0, 43.0, 35.0, 48.0, 41.0, 37.0, 52.0, 40.0, 46.0],
                    ],
                    [
                        'objective'         => 'การรักษาฐานลูกค้าเดิม (Retention)',
                        'detail'            => 'รักษาลูกค้าเดิมให้ไม่ต่ำกว่า 95% ของรายได้ฐาน ลด Churn ด้วยการ Visit และ Follow Up',
                        'target_value'      => 95.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [97.0, 96.0, 93.0, 98.0, 95.0, 91.0, 97.0, 96.0, 94.0, 99.0, 95.0, 97.0],
                    ],
                    [
                        'objective'         => 'รายงานการเข้าพบลูกค้า (Customer Visit)',
                        'detail'            => 'เข้าพบลูกค้าหรือดำเนินการ Customer Touch Point ≥4 ครั้ง/เดือน/คน และส่งรายงานภายในวันศุกร์',
                        'target_value'      => 4.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '>=',
                        'score_samples'     => [5.0, 4.0, 3.0, 6.0, 4.0, 2.0, 5.0, 4.0, 4.0, 7.0, 4.0, 5.0],
                    ],
                ],
            ],
            [
                'name'         => 'New Customer Acquisition',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 2,
                'items' => [
                    [
                        'objective'         => 'หาลูกค้าใหม่ตามเป้า',
                        'detail'            => 'นำเสนอ Proposal ลูกค้าใหม่ ≥2 ราย/เดือน และปิดได้ ≥1 ราย/ไตรมาส',
                        'target_value'      => 2.0,
                        'unit_id'           => 4,
                        'criteria_operator' => '>=',
                        'score_samples'     => [2.0, 3.0, 1.0, 2.0, 2.0, 0.0, 3.0, 2.0, 1.0, 4.0, 2.0, 3.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // LGD — Logistics Domestic
        // ══════════════════════════════════════════════════════════════
        'LGD' => [
            [
                'name'         => 'Domestic Delivery Performance',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'On-Time Delivery Domestic (OTD)',
                        'detail'            => 'จัดส่งสินค้าในประเทศตรงเวลา ≥98% ของรายการทั้งหมด วัดผลรายสัปดาห์ และรายงาน Delay Root Cause',
                        'target_value'      => 98.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.0, 98.5, 97.0, 99.2, 98.0, 96.5, 98.8, 99.0, 97.5, 99.5, 98.0, 99.0],
                    ],
                    [
                        'objective'         => 'ความเสียหายระหว่างขนส่ง (Damage in Transit)',
                        'detail'            => 'อัตราความเสียหายของสินค้าระหว่างขนส่ง ≤0.1% ของมูลค่าสินค้าที่จัดส่ง',
                        'target_value'      => 0.1,
                        'unit_id'           => 2,
                        'criteria_operator' => '<=',
                        'score_samples'     => [0.05, 0.08, 0.12, 0.04, 0.09, 0.15, 0.06, 0.03, 0.11, 0.07, 0.10, 0.05],
                    ],
                    [
                        'objective'         => 'ความถูกต้องของเอกสารจัดส่ง',
                        'detail'            => 'เอกสารจัดส่ง (ใบกำกับสินค้า, Packing List, Delivery Note) ถูกต้อง 100% ไม่มีการส่งคืนจากเอกสารผิดพลาด',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 98.0, 100.0, 99.0, 100.0, 100.0, 97.0, 100.0, 100.0, 99.0, 100.0],
                    ],
                ],
            ],
            [
                'name'         => 'Logistics Cost Control',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ต้นทุนขนส่งต่อหน่วยไม่เกินงบประมาณ',
                        'detail'            => 'ควบคุมต้นทุนการขนส่งต่อหน่วย (Cost per Unit Delivered) ไม่เกินงบประมาณ พร้อมรายงาน Trend รายเดือน',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '<=',
                        'score_samples'     => [95.0, 97.0, 103.0, 96.0, 98.0, 105.0, 94.0, 97.0, 102.0, 95.0, 99.0, 96.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // LGE — Logistics Export
        // ══════════════════════════════════════════════════════════════
        'LGE' => [
            [
                'name'         => 'Export Shipment Performance',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Export Shipment On-Time (OTS)',
                        'detail'            => 'จัดส่งสินค้าส่งออกทางเรือ/อากาศตรงตาม Booking ≥98% ไม่มี Mis-shipment และไม่มี Container Rolling',
                        'target_value'      => 98.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.0, 98.5, 96.5, 99.5, 98.0, 97.0, 98.8, 99.2, 97.5, 99.8, 98.0, 99.0],
                    ],
                    [
                        'objective'         => 'ความถูกต้องของ Shipping Documents',
                        'detail'            => 'เอกสารส่งออก (BL, Invoice, Packing List, CO, Form D/E) ถูกต้อง 100% ไม่มี Amendment หลังออกเอกสาร',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 97.0, 100.0, 98.0, 100.0, 99.0, 100.0, 98.0, 100.0, 100.0, 99.0],
                    ],
                    [
                        'objective'         => 'Customs Compliance ไม่มีค่าปรับ',
                        'detail'            => 'ปฏิบัติตาม Customs Regulation และกฎหมาย BOI/FTA 100% ไม่มีค่าปรับหรือสินค้าถูกกักขัง',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                ],
            ],
            [
                'name'         => 'Freight Cost Optimization',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ลดค่าระวางส่งออก',
                        'detail'            => 'ลดค่าระวางส่งออกสุทธิ ≥5% เทียบปีก่อน ผ่านการประกวดราคา การรวม Shipment และการเจรจา Long-Term Rate',
                        'target_value'      => 5.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [5.5, 4.8, 6.2, 5.0, 4.5, 5.8, 5.2, 6.0, 4.9, 5.5, 4.7, 5.3],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // IQ — Incoming Quality
        // ══════════════════════════════════════════════════════════════
        'IQ' => [
            [
                'name'         => 'Incoming Defect Control',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Incoming Defect Rate (<500 PPM)',
                        'detail'            => 'ควบคุมของเสียจากวัตถุดิบขาเข้าให้ต่ำกว่า 500 PPM ด้วยการตรวจรับและ Sampling Plan ที่มีประสิทธิภาพ',
                        'target_value'      => 500.0,
                        'unit_id'           => null,
                        'criteria_operator' => '<=',
                        'score_samples'     => [280.0, 420.0, 550.0, 310.0, 480.0, 610.0, 350.0, 290.0, 520.0, 270.0, 460.0, 330.0],
                    ],
                    [
                        'objective'         => 'Supplier CAR Close Rate',
                        'detail'            => 'ติดตามให้ Supplier ปิด 8D / CAR ภายในกำหนด ≥95% และลด Recurring Defect',
                        'target_value'      => 95.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 95.0, 85.0, 100.0, 92.0, 88.0, 97.0, 95.0, 90.0, 100.0, 93.0, 96.0],
                    ],
                    [
                        'objective'         => 'การตรวจรับวัตถุดิบตาม Sampling Plan',
                        'detail'            => 'ดำเนินการตรวจรับ Lot ทั้งหมดตาม AQL Sampling Plan ครบ 100% ทุก Lot ที่เข้ารับ',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 98.0, 100.0, 100.0, 95.0, 100.0, 100.0, 99.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
            [
                'name'         => 'IQC Process Audit',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ผ่าน IQC Process Audit ไม่มี Major NC',
                        'detail'            => 'ผ่านการ Audit กระบวนการ IQC โดยไม่มี Major NC จากทั้งลูกค้าและ Internal Audit',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // SQ — Supplier Quality
        // ══════════════════════════════════════════════════════════════
        'SQ' => [
            [
                'name'         => 'Supplier PPM & Development',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Supplier PPM (<200 PPM)',
                        'detail'            => 'ควบคุมของเสียจาก Supplier รวมให้ต่ำกว่า 200 PPM ผ่านการพัฒนา Supplier และ Audit ประจำปี',
                        'target_value'      => 200.0,
                        'unit_id'           => null,
                        'criteria_operator' => '<=',
                        'score_samples'     => [120.0, 180.0, 250.0, 90.0, 170.0, 280.0, 140.0, 110.0, 230.0, 100.0, 190.0, 130.0],
                    ],
                    [
                        'objective'         => 'Supplier Development Visit',
                        'detail'            => 'เข้าพัฒนา Supplier ที่มีความเสี่ยงสูง ≥2 ครั้ง/ราย/ปี และติดตาม CAPA ให้ปิดตามกำหนด',
                        'target_value'      => 2.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '>=',
                        'score_samples'     => [2.0, 2.0, 1.0, 3.0, 2.0, 1.0, 2.0, 3.0, 2.0, 2.0, 1.0, 3.0],
                    ],
                ],
            ],
            [
                'name'         => 'Supplier Audit Pass Rate',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Supplier Audit Pass Rate (>80%)',
                        'detail'            => 'Supplier ผ่านการ Audit ด้าน QMS ≥80% ของรายการที่ตรวจ โดยไม่มี Critical Finding ที่ยังไม่ปิด',
                        'target_value'      => 80.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [85.0, 82.0, 78.0, 88.0, 80.0, 75.0, 84.0, 87.0, 79.0, 86.0, 81.0, 83.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // ML — Material & Logistics
        // ══════════════════════════════════════════════════════════════
        'ML' => [
            [
                'name'         => 'Material Availability & Inventory',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Material Availability Rate',
                        'detail'            => 'วัตถุดิบพร้อมใช้งาน ≥99% ของเวลาที่สายการผลิตต้องการ ไม่มี Line Stop จากขาดวัสดุ',
                        'target_value'      => 99.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.5, 99.2, 98.5, 99.8, 99.0, 98.0, 99.4, 99.7, 98.8, 99.6, 99.0, 99.3],
                    ],
                    [
                        'objective'         => 'Inventory Accuracy (Cycle Count)',
                        'detail'            => 'ความถูกต้องของสินค้าคงคลัง ≥99% จากการนับ Cycle Count รายเดือน ลด Inventory Discrepancy',
                        'target_value'      => 99.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.5, 99.3, 98.7, 99.8, 99.1, 98.4, 99.4, 99.6, 98.9, 99.7, 99.0, 99.4],
                    ],
                    [
                        'objective'         => 'ลด Excess & Obsolete Inventory',
                        'detail'            => 'ลดมูลค่า Slow-Moving / Excess Inventory ≥10% เทียบต้นปี ผ่านการวางแผน Material ที่แม่นยำ',
                        'target_value'      => 10.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [12.0, 8.0, 11.0, 15.0, 9.0, 7.0, 13.0, 10.0, 8.5, 14.0, 9.5, 11.5],
                    ],
                ],
            ],
            [
                'name'         => 'Inventory Cost Optimization',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Inventory Turnover (รอบการหมุนเวียนสินค้า)',
                        'detail'            => 'รักษา Inventory Turnover ≥12 ครั้ง/ปี ลดเงินทุนจม (Working Capital) โดยไม่กระทบ Material Availability',
                        'target_value'      => 12.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '>=',
                        'score_samples'     => [13.0, 11.5, 12.8, 14.0, 12.0, 11.0, 13.5, 12.5, 11.8, 14.2, 12.0, 13.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // BOI — BOI & Legal
        // ══════════════════════════════════════════════════════════════
        'BOI' => [
            [
                'name'         => 'BOI Privilege Utilization',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ใช้สิทธิประโยชน์ BOI เต็ม 100%',
                        'detail'            => 'ใช้สิทธิประโยชน์ BOI ได้เต็ม 100% ของสิทธิ์ที่ได้รับอนุมัติ ไม่มีการสูญเสียสิทธิ์จากความล่าช้าหรือเอกสารผิดพลาด',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0],
                    ],
                    [
                        'objective'         => 'การรายงาน BOI ตามกำหนด',
                        'detail'            => 'ส่งรายงาน BOI รายปี รายครึ่งปี และรายไตรมาสตามกำหนดครบถ้วน 100% ไม่มีค่าปรับ',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
            [
                'name'         => 'Regulatory Compliance',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ปฏิบัติตามกฎหมายและระเบียบ BOI — 0 Violation',
                        'detail'            => 'ปฏิบัติตามกฎหมายและระเบียบ BOI อย่างครบถ้วน ไม่มีการละเมิดหรือสูญเสียสิทธิ์ ผ่านการตรวจจาก BOI',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // EX — Export
        // ══════════════════════════════════════════════════════════════
        'EX' => [
            [
                'name'         => 'Export On-Time Delivery',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ส่งออกตรงเวลา (OTD Export)',
                        'detail'            => 'ส่งมอบสินค้าส่งออกตรงเวลา ≥98% ลด Claim จากความล่าช้าให้เป็นศูนย์',
                        'target_value'      => 98.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.0, 98.5, 97.0, 99.5, 98.0, 96.5, 98.8, 99.2, 97.8, 99.7, 98.0, 99.0],
                    ],
                    [
                        'objective'         => 'เอกสารส่งออกถูกต้อง 100%',
                        'detail'            => 'จัดทำเอกสารส่งออก (CI, PL, BL, CO, Form D/E/FTA) ถูกต้อง 100% ไม่มีการแก้ไขหลังออกเอกสาร',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 98.0, 100.0, 99.0, 100.0, 100.0, 98.0, 100.0, 100.0, 99.0, 100.0],
                    ],
                ],
            ],
            [
                'name'         => 'Export Revenue Growth',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'มูลค่าการส่งออกเติบโต ≥8%',
                        'detail'            => 'เพิ่มมูลค่าการส่งออกรวมไม่น้อยกว่า 8% เทียบปีก่อน ผ่านการขยายตลาดและลูกค้าใหม่',
                        'target_value'      => 8.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [10.0, 7.5, 9.0, 11.0, 8.0, 6.5, 9.5, 8.5, 7.8, 12.0, 8.0, 10.5],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // PE — Process Engineering
        // ══════════════════════════════════════════════════════════════
        'PE' => [
            [
                'name'         => 'Process Capability',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Process Capability (Cpk ≥1.67)',
                        'detail'            => 'รักษา Cpk ของ Critical Characteristic ในกระบวนการหลักให้ ≥1.67 ตลอดทั้งปี ด้วยการ Monitor SPC รายสัปดาห์',
                        'target_value'      => 1.67,
                        'unit_id'           => null,
                        'criteria_operator' => '>=',
                        'score_samples'     => [1.75, 1.70, 1.62, 1.80, 1.67, 1.58, 1.72, 1.78, 1.65, 1.82, 1.68, 1.73],
                    ],
                    [
                        'objective'         => 'การดำเนิน Process FMEA ครบถ้วน',
                        'detail'            => 'อัปเดต Process FMEA ให้ครอบคลุม 100% ของกระบวนการที่มีความเสี่ยงสูง (RPN >100) ภายในกำหนด',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 90.0, 100.0, 100.0, 85.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
            [
                'name'         => 'Kaizen & Cost Reduction',
                'user_name_th' => '',
                'l1_sort'      => 4,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'โครงการ Kaizen / Cost Reduction',
                        'detail'            => 'ดำเนินโครงการ Kaizen หรือ Cost Reduction ≥4 โครงการ/ปี พร้อมรายงานผลเป็น Monetary Saving',
                        'target_value'      => 1.0,
                        'unit_id'           => 7,
                        'criteria_operator' => '>=',
                        'score_samples'     => [1.0, 1.0, 0.0, 1.0, 1.0, 0.0, 1.0, 1.0, 1.0, 1.0, 0.0, 1.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // QMS — Quality Management System
        // ══════════════════════════════════════════════════════════════
        'QMS' => [
            [
                'name'         => 'IATF 16949 & Internal Audit',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'IATF 16949 Recertification / Surveillance Pass',
                        'detail'            => 'ผ่านการ Recertification / Surveillance IATF 16949 โดยไม่มี Major NC และปิด Minor NC ภายใน 90 วัน',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                    [
                        'objective'         => 'Internal Quality Audit Completion',
                        'detail'            => 'ดำเนินการ Internal Audit ตามแผนประจำปีครบ 100% ครอบคลุมทุกกระบวนการ และปิด NC ภายใน 30 วัน',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 100.0, 100.0, 80.0, 100.0, 100.0, 100.0, 100.0, 95.0, 100.0],
                    ],
                ],
            ],
            [
                'name'         => 'Document Control',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 2,
                'items' => [
                    [
                        'objective'         => 'อัปเดตเอกสารควบคุมคุณภาพตามกำหนด',
                        'detail'            => 'อัปเดต QMS Document หลังมี Engineering Change ภายใน 30 วัน ครบ 100% ของ Change ทั้งหมด',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 90.0, 100.0, 100.0, 85.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // MT1 — Maintenance 1
        // ══════════════════════════════════════════════════════════════
        'MT1' => [
            [
                'name'         => 'PM & Reliability Zone 1',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'PM Compliance Rate Zone 1 (>95%)',
                        'detail'            => 'ดำเนินการ Preventive Maintenance ตามแผนครบ ≥95% ของรายการทั้งหมดในโซนที่รับผิดชอบ',
                        'target_value'      => 95.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [97.0, 96.0, 93.0, 98.0, 95.0, 91.0, 96.0, 98.0, 94.0, 97.5, 95.0, 96.5],
                    ],
                    [
                        'objective'         => 'MTBF Improvement Zone 1',
                        'detail'            => 'เพิ่ม MTBF ของเครื่องจักรหลักในโซน MT1 ≥10% เทียบกับปีก่อน ด้วยการบำรุงรักษาเชิงรุก',
                        'target_value'      => 10.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [12.0, 9.0, 11.0, 15.0, 10.0, 8.0, 13.0, 11.0, 9.5, 14.0, 10.0, 12.5],
                    ],
                    [
                        'objective'         => 'Breakdown Response Time Zone 1',
                        'detail'            => 'ตอบสนองต่อ Breakdown ในโซน MT1 ภายใน 30 นาทีหลังได้รับแจ้ง 100% ของเคส',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 95.0, 100.0, 100.0, 90.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // MT2 — Maintenance 2
        // ══════════════════════════════════════════════════════════════
        'MT2' => [
            [
                'name'         => 'PM & Spare Part Zone 2',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'PM Compliance Rate Zone 2 (>95%)',
                        'detail'            => 'ดำเนินการ Preventive Maintenance ตามแผนครบ ≥95% ของรายการทั้งหมดในโซนที่รับผิดชอบ',
                        'target_value'      => 95.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [96.0, 95.0, 92.0, 97.5, 95.0, 90.0, 96.5, 97.0, 93.0, 98.0, 95.0, 96.0],
                    ],
                    [
                        'objective'         => 'Spare Part Cost vs Budget Zone 2',
                        'detail'            => 'บริหารค่าใช้จ่ายอะไหล่และวัสดุสิ้นเปลืองในโซน MT2 ให้อยู่ในงบประมาณ ±5%',
                        'target_value'      => 5.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '<=',
                        'score_samples'     => [3.0, 4.0, 5.5, 2.5, 4.5, 6.0, 3.5, 3.0, 5.2, 2.8, 4.2, 3.8],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // QCP — Quality Control Process
        // ══════════════════════════════════════════════════════════════
        'QCP' => [
            [
                'name'         => 'In-Process Inspection & Scrap',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'In-Process Inspection Coverage (100%)',
                        'detail'            => 'ตรวจสอบกระบวนการผลิตตาม Control Plan ครบ 100% ในทุก Critical Process ทุกกะการผลิต',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 98.0, 100.0, 100.0, 95.0, 100.0, 100.0, 99.0, 100.0, 100.0, 100.0],
                    ],
                    [
                        'objective'         => 'ลดมูลค่าของเสียในกระบวนการ (Scrap Cost)',
                        'detail'            => 'ลดมูลค่าของเสียในกระบวนการ ≥10% เทียบปีก่อน ผ่านการวิเคราะห์ Pareto และ RCA',
                        'target_value'      => 10.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [12.0, 8.0, 11.0, 15.0, 10.0, 7.0, 13.0, 11.0, 9.0, 14.0, 10.0, 12.0],
                    ],
                ],
            ],
            [
                'name'         => 'SPC Implementation',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'SPC Implementation Rate (>80% of CC)',
                        'detail'            => 'ใช้ SPC ในการควบคุม Critical Characteristic ≥80% ของรายการที่กำหนดใน Control Plan',
                        'target_value'      => 80.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [85.0, 82.0, 78.0, 88.0, 80.0, 75.0, 83.0, 87.0, 79.0, 86.0, 81.0, 84.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // QAN — Quality Assurance New
        // ══════════════════════════════════════════════════════════════
        'QAN' => [
            [
                'name'         => 'New Model Launch Quality',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'New Model Launch — 0 Customer Concern',
                        'detail'            => 'ควบคุมคุณภาพชิ้นส่วนใหม่ในช่วง Launch ให้ไม่มี Customer Concern ภายใน 3 เดือนแรก',
                        'target_value'      => 0.0,
                        'unit_id'           => 5,
                        'criteria_operator' => '=',
                        'score_samples'     => [0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
                    ],
                    [
                        'objective'         => 'PPAP First Time Approval Rate (>90%)',
                        'detail'            => 'PPAP ได้รับการอนุมัติในครั้งแรก ≥90% ลดการ Re-submit และความล่าช้าในการผลิต',
                        'target_value'      => 90.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [95.0, 90.0, 85.0, 100.0, 92.0, 88.0, 97.0, 90.0, 87.0, 100.0, 91.0, 95.0],
                    ],
                ],
            ],
            [
                'name'         => 'Control Plan & FMEA Update',
                'user_name_th' => '',
                'l1_sort'      => 3,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Control Plan & FMEA Update On-Time (100%)',
                        'detail'            => 'อัปเดต Control Plan และ FMEA ให้สอดคล้องกับ Engineering Change ภายใน 30 วัน ครบ 100%',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 90.0, 100.0, 100.0, 85.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // ST1 — Store 1
        // ══════════════════════════════════════════════════════════════
        'ST1' => [
            [
                'name'         => 'Stock Accuracy & Issuance',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Stock Accuracy ST1 (>99%)',
                        'detail'            => 'ความถูกต้องของสต็อกในคลัง ST1 ≥99% จากการนับ Cycle Count รายสัปดาห์',
                        'target_value'      => 99.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.5, 99.3, 98.7, 99.8, 99.1, 98.5, 99.4, 99.6, 98.9, 99.7, 99.0, 99.4],
                    ],
                    [
                        'objective'         => 'FIFO Compliance ST1 (100%)',
                        'detail'            => 'ปฏิบัติตามหลัก FIFO ในการเบิก-จ่ายวัตถุดิบและสินค้า 100% ป้องกันวัตถุดิบหมดอายุ',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 100.0, 100.0, 95.0, 100.0, 100.0, 100.0, 100.0, 98.0, 100.0],
                    ],
                    [
                        'objective'         => 'Material Issue Lead Time (≤30 นาที)',
                        'detail'            => 'เบิกจ่ายวัตถุดิบ/ชิ้นส่วนให้สายการผลิตภายใน 30 นาทีหลังได้รับ Request',
                        'target_value'      => 30.0,
                        'unit_id'           => null,
                        'criteria_operator' => '<=',
                        'score_samples'     => [20.0, 25.0, 35.0, 18.0, 28.0, 40.0, 22.0, 20.0, 32.0, 19.0, 27.0, 24.0],
                    ],
                ],
            ],
            [
                'name'         => 'Storage Utilization',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Space Utilization ≥80%',
                        'detail'            => 'รักษาอัตราการใช้พื้นที่คลัง (Space Utilization) ≥80% และลด Dead Stock ≥10%/ปี',
                        'target_value'      => 80.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [83.0, 81.0, 78.0, 85.0, 80.0, 77.0, 82.0, 84.0, 79.0, 86.0, 80.0, 83.0],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // ST2 — Store 2
        // ══════════════════════════════════════════════════════════════
        'ST2' => [
            [
                'name'         => 'Stock Accuracy & FIFO',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Stock Accuracy ST2 (>99%)',
                        'detail'            => 'ความถูกต้องของสต็อกในคลัง ST2 ≥99% จากการนับ Cycle Count รายสัปดาห์',
                        'target_value'      => 99.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.4, 99.2, 98.8, 99.7, 99.0, 98.4, 99.3, 99.5, 98.9, 99.6, 99.1, 99.4],
                    ],
                    [
                        'objective'         => 'FIFO Compliance ST2 (100%)',
                        'detail'            => 'ปฏิบัติตามหลัก FIFO ในการเบิก-จ่ายวัตถุดิบและสินค้า 100% ป้องกันวัตถุดิบหมดอายุ',
                        'target_value'      => 100.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [100.0, 100.0, 100.0, 100.0, 98.0, 100.0, 100.0, 100.0, 97.0, 100.0, 100.0, 100.0],
                    ],
                ],
            ],
            [
                'name'         => 'Excess Inventory Reduction',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'ลด Excess Inventory ST2',
                        'detail'            => 'ลดมูลค่า Excess/Slow-Moving Stock ในคลัง ST2 ≥10%/ปี',
                        'target_value'      => 10.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [11.0, 8.5, 12.0, 14.0, 10.0, 7.0, 13.0, 10.5, 9.0, 15.0, 10.0, 11.5],
                    ],
                ],
            ],
        ],

        // ══════════════════════════════════════════════════════════════
        // PF — Production Floor
        // ══════════════════════════════════════════════════════════════
        'PF' => [
            [
                'name'         => '5S & FPY Production Floor',
                'user_name_th' => '',
                'l1_sort'      => 1,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => '5S Audit Score (≥90 คะแนน)',
                        'detail'            => 'ผ่านการตรวจ 5S ในพื้นที่รับผิดชอบได้คะแนน ≥90 คะแนน จากการ Audit ประจำเดือน',
                        'target_value'      => 90.0,
                        'unit_id'           => 15,
                        'criteria_operator' => '>=',
                        'score_samples'     => [93.0, 91.0, 87.0, 95.0, 90.0, 85.0, 92.0, 94.0, 88.0, 93.0, 90.0, 92.0],
                    ],
                    [
                        'objective'         => 'First Pass Yield (>99%)',
                        'detail'            => 'อัตราผลผลิตที่ผ่านการตรวจสอบในรอบแรก (FPY) ≥99% ลดการ Rework และ Scrap ในสายการผลิต',
                        'target_value'      => 99.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.5, 99.2, 98.7, 99.6, 99.0, 98.3, 99.3, 99.7, 98.8, 99.4, 99.0, 99.5],
                    ],
                    [
                        'objective'         => 'ผลผลิตตามแผนรายวัน (≥98%)',
                        'detail'            => 'ผลิตได้ตามแผนรายวัน ≥98% เพื่อสนับสนุนเป้าหมายยอดขายของบริษัท และรายงาน Downtime รายวัน',
                        'target_value'      => 98.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.0, 98.5, 97.0, 99.2, 98.0, 96.5, 98.8, 99.0, 97.5, 99.4, 98.0, 98.8],
                    ],
                ],
            ],
            [
                'name'         => 'Production Output vs Plan',
                'user_name_th' => '',
                'l1_sort'      => 2,
                'l2_sort'      => 1,
                'items' => [
                    [
                        'objective'         => 'Output vs Plan (≥98%)',
                        'detail'            => 'ผลิตได้ตามแผนการผลิตที่กำหนด ≥98% โดยไม่ส่งผลกระทบต่อการส่งมอบให้ลูกค้า',
                        'target_value'      => 98.0,
                        'unit_id'           => 2,
                        'criteria_operator' => '>=',
                        'score_samples'     => [99.0, 98.5, 97.0, 99.2, 98.0, 95.5, 98.7, 99.0, 97.5, 99.3, 98.0, 98.7],
                    ],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $cycle = Cycle::query()->where('is_active', true)->orderByDesc('id')->first()
            ?? Cycle::query()->orderByDesc('id')->first();

        if (! $cycle) {
            $this->command->warn('KpiReportAllDeptSeeder: no cycle found — skipping.');
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
            $this->command->warn('KpiReportAllDeptSeeder: no open months in cycle #' . $cycle->id . ' — skipping.');
            return;
        }

        $this->command->info('KpiReportAllDeptSeeder: cycle #' . $cycle->id . ', open months: ' . implode(', ', $openMonths));

        $objBySortNo = OkrObjective::query()->get()->keyBy('sort_no');

        DB::transaction(function () use ($cycle, $openMonths, $objBySortNo): void {
            $grandTotal = 0;

            foreach (self::DEPT_SECTIONS as $dept => $sections) {
                if ($dept === self::SKIP_DEPT) {
                    continue;
                }

                $this->command->line('');
                $this->command->line('  ── ' . $dept . ' ──');

                $users = AppUser::query()
                    ->where('dept_abbr_hr', $dept)
                    ->orderBy('id')
                    ->get()
                    ->values();

                if ($users->isEmpty()) {
                    $this->command->warn('  No users in ' . $dept . ' — skipping dept.');
                    continue;
                }

                // Delete existing report records for this dept in this cycle
                $deptKrIds = OkrKeyResult::query()
                    ->where('dept_abbr_hr', $dept)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (! empty($deptKrIds)) {
                    $rootIds = KpiMonthScore::query()
                        ->where('mode_type', 'report')
                        ->where('cycle_id', (int) $cycle->id)
                        ->whereIn('okr_key_result_id', $deptKrIds)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    if (! empty($rootIds)) {
                        $deleted  = KpiMonthScore::query()->whereIn('kpi_meta_id', $rootIds)->delete();
                        $deleted += KpiMonthScore::query()->whereIn('id', $rootIds)->delete();
                        if ($deleted > 0) {
                            $this->command->line('  Removed ' . $deleted . ' existing record(s) for ' . $dept);
                        }
                    }
                }

                $deptTotal = 0;
                $userIdx   = 0;

                foreach ($sections as $section) {
                    $obj = $objBySortNo->get($section['l1_sort']);
                    if (! $obj) {
                        $this->command->warn('  [' . $dept . '/' . $section['name'] . '] L1 sort#' . $section['l1_sort'] . ' not found — skipping.');
                        continue;
                    }

                    $kr = OkrKeyResult::query()
                        ->where('okr_objective_id', (int) $obj->id)
                        ->where('dept_abbr_hr', $dept)
                        ->where('sort_no', $section['l2_sort'])
                        ->first();

                    if (! $kr) {
                        $this->command->warn('  [' . $dept . '/' . $section['name'] . '] L2 sort#' . $section['l2_sort'] . ' not found — skipping.');
                        continue;
                    }

                    $l3Target = KpiMonthScore::query()
                        ->where('mode_type', 'target')
                        ->where('month_no', 0)
                        ->where('okr_key_result_id', (int) $kr->id)
                        ->orderBy('id')
                        ->first();
                    $parentId = $l3Target ? (int) $l3Target->id : null;

                    $hint      = $section['user_name_th'] ?? '';
                    $namedUser = $hint !== ''
                        ? AppUser::query()
                            ->where('dept_abbr_hr', $dept)
                            ->where('full_name_th', 'like', '%' . $hint . '%')
                            ->first()
                        : null;

                    $user = $namedUser ?? $users[$userIdx % $users->count()];
                    $userIdx++;

                    $this->command->line(
                        '  [' . $dept . '/' . $section['name'] . '] L1#' . $section['l1_sort']
                        . '/L2#' . $section['l2_sort']
                        . ' | ' . count($section['items']) . ' items | user #' . $user->id
                    );

                    foreach ($section['items'] as $item) {
                        $root = KpiMonthScore::query()->create([
                            'app_user_id'          => (int) $user->id,
                            'cycle_id'             => (int) $cycle->id,
                            'okr_objective_id'     => (int) $obj->id,
                            'okr_key_result_id'    => (int) $kr->id,
                            'parent_target_kpi_id' => $parentId,
                            'month_no'             => 0,
                            'kpi_meta_id'          => null,
                            'objective'            => $item['objective'],
                            'detail'               => $item['detail'],
                            'target_value'         => $item['target_value'],
                            'kpi_unit_id'          => $item['unit_id'],
                            'criteria_operator'    => $item['criteria_operator'],
                            'mode_type'            => 'report',
                            'score_value'          => 0,
                            'is_pass'              => false,
                        ]);
                        $deptTotal++;

                        foreach ($openMonths as $monthNo) {
                            $sampleIdx  = ($monthNo - 1) % count($item['score_samples']);
                            $scoreValue = $item['score_samples'][$sampleIdx];

                            KpiMonthScore::query()->create([
                                'app_user_id'          => (int) $user->id,
                                'cycle_id'             => (int) $cycle->id,
                                'okr_objective_id'     => (int) $obj->id,
                                'okr_key_result_id'    => (int) $kr->id,
                                'parent_target_kpi_id' => $parentId,
                                'kpi_meta_id'          => (int) $root->id,
                                'month_no'             => $monthNo,
                                'objective'            => $item['objective'],
                                'detail'               => $item['detail'],
                                'target_value'         => $item['target_value'],
                                'kpi_unit_id'          => $item['unit_id'],
                                'criteria_operator'    => $item['criteria_operator'],
                                'mode_type'            => 'report',
                                'score_value'          => $scoreValue,
                                'is_pass'              => $this->checkPass($scoreValue, $item['target_value'], $item['criteria_operator']),
                                'submitted_at'         => $this->resolveSubmittedAt($cycle, $monthNo),
                            ]);
                            $deptTotal++;
                        }
                    }
                }

                $this->command->line('  ' . $dept . ': created ' . $deptTotal . ' record(s).');
                $grandTotal += $deptTotal;
            }

            $this->command->line('');
            $this->command->info('KpiReportAllDeptSeeder: total ' . $grandTotal . ' record(s) created.');
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
