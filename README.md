# OKR & KPI System

**TH:** ระบบติดตาม OKR และ KPI รายเดือน — ตั้งเป้า กรอกผล ทบทวน แล้วเห็นภาพรวมทั้งองค์กร
**EN:** Monthly OKR and KPI tracking — set targets, record results, review them, and see the whole organization at a glance.

`PHP 8.2` · `Laravel 12` · `MySQL` · `Tailwind CSS 4` · `Vite 7`

---

## 🇹🇭 ภาษาไทย

### เริ่มจากไฟล์ Excel

ทุกแผนกมีไฟล์ OKR-KPI ของตัวเอง กรอกทุกเดือน แล้วส่งให้ผู้บริหารดู ปัญหาคือแต่ละไฟล์หน้าตาไม่เหมือนกัน หน่วยวัดไม่ตรงกัน และไม่มีใครเห็นภาพรวมได้จริงๆ จนกว่าจะมีคนนั่งรวมมือ

ระบบนี้ย้ายทั้งกระบวนการขึ้นเว็บ โดยยังคงวิธีคิดแบบเดิมที่คนคุ้นเคยไว้

### ทำอะไรได้บ้าง

- **รอบและเดือน (Cycle / CycleMonth)** — แบ่งปีเป็นรอบ แต่ละรอบมีเดือนที่เปิดให้กรอก
- **KPI รายเดือน** — ตั้งเป้า กรอกผลจริง ระบบคำนวณคะแนน รองรับหน่วยวัดหลายแบบ (`KpiUnit`)
- **OKR** — Objective และ Key Result แยกกัน สรุปได้ทั้งระดับแผนกและระดับองค์กร
- **ทบทวนผล (KpiMonthReview)** — หัวหน้าตรวจและให้ความเห็นก่อนปิดเดือน
- **KPI ข้ามแผนก** — กำหนด target department ได้ สำหรับ KPI ที่ต้องอาศัยหลายฝ่าย
- **หน้าแรกจัดเองได้** — ผู้ดูแลตั้งค่าโครงสร้างลำดับชั้นและประกาศพร้อมไฟล์แนบ
- **แจ้งเตือนในระบบ** — เตือนเมื่อถึงรอบกรอกหรือมีเรื่องรออนุมัติ
- **มอบหมายผู้ดูแลแผนก** — ระบุผู้รับผิดชอบได้หลายคนต่อแผนก

### สิ่งที่เรียนรู้จากโปรเจคนี้

Migration ชุดนี้สะท้อนการออกแบบที่ปรับไปเรื่อยๆ ตามความเข้าใจที่มากขึ้น เช่น ตอนแรกผูก `kpi_month_scores` เข้ากับ `kpi_items` แล้วพบว่ามันทำให้ระบบแข็งเกินไป เลย refactor ออก (`refactor_kpi_month_scores_remove_kpi_items_dependency`) และมีการขยายช่วงค่าของ target/score ทีหลังเมื่อพบว่าของจริงเกินที่คาดไว้

### ติดตั้ง

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate && npm run build && php artisan serve
```

---

## 🇬🇧 English

### It started as a spreadsheet

Every department kept its own OKR-KPI file, filled it in monthly, and sent it up. The trouble was that no two files looked alike, units didn't match, and nobody saw the full picture until someone merged it all by hand.

This moves the whole process onto the web while keeping the mental model people already had.

### What it does

- **Cycles and months** — the year is split into cycles, each with months open for entry
- **Monthly KPIs** — set a target, record the actual, let the system score it, across multiple unit types (`KpiUnit`)
- **OKRs** — objectives and key results tracked separately, summarized by department and organization-wide
- **Monthly review** — managers check and comment before a month closes
- **Cross-department KPIs** via target departments, for goals that need more than one team
- **Configurable home page** — admins set the hierarchy and post announcements with attachments
- **In-app notifications** for entry windows and pending items
- **Multi-owner department assignments**

### What the migration history shows

The schema evolved as understanding did. `kpi_month_scores` was initially tied to `kpi_items`, which turned out to be too rigid, so the dependency was refactored out (`refactor_kpi_month_scores_remove_kpi_items_dependency`). Target and score ranges were widened later, once real data exceeded the original assumptions.

### Note

Code only. Real OKR/KPI spreadsheets and the database are excluded.
