# OKR and KPI System

## OKR and KPI System คืออะไร / About

ระบบติดตาม OKR และ KPI รายเดือนของทุกแผนก รวมข้อมูลที่เคยกระจายอยู่ในไฟล์ Excel ของแต่ละแผนกมาไว้ในที่เดียว ผู้บริหารจึงเห็นภาพรวมทั้งองค์กรได้โดยไม่ต้องรอใครรวมไฟล์

A monthly OKR and KPI tracker for every department. It replaces the separate Excel files each department used to keep, so management can see the whole organization without waiting for someone to merge spreadsheets.

## ทำอะไรได้บ้าง / Features

- แบ่งปีเป็นรอบ แต่ละรอบเปิดให้กรอกทีละเดือน
- ตั้งเป้า KPI กรอกผลจริง แล้วระบบคำนวณคะแนนให้ รองรับหน่วยวัดหลายแบบ
- ติดตาม Objective และ Key Result แยกกัน สรุปได้ทั้งระดับแผนกและทั้งองค์กร
- หัวหน้าตรวจและให้ความเห็นก่อนปิดเดือน
- ตั้ง KPI ที่ต้องทำร่วมกันหลายแผนกได้
- หน้าแรกมีประกาศพร้อมไฟล์แนบ และแจ้งเตือนเมื่อถึงรอบกรอก

* The year is split into cycles, each opening one month at a time for entry
* Set a KPI target, enter the actual result and let the system score it, across several units of measure
* Objectives and key results tracked separately, summarized by department and company-wide
* Managers review and comment before a month is closed
* KPIs can be shared across several departments
* A home page with announcements and attachments, plus reminders when entry opens

## Tech Stack

**Backend:** PHP 8, Laravel 12, PhpSpreadsheet

**Frontend:** Blade, Tailwind CSS, AOS, Vite, Axios

**Database:** MySQL

## ติดตั้ง / Installation

ต้องมี PHP 8.2 ขึ้นไป, Composer, Node.js และ MySQL ก่อนรัน migrate ให้แก้ค่า `DB_*` ใน `.env` ให้ตรงกับฐานข้อมูลในเครื่อง

Requires PHP 8.2+, Composer, Node.js and MySQL. Set the `DB_*` values in `.env` before migrating.

```bash
git clone https://github.com/PumiputCG/okr-kpi-system.git
cd okr-kpi-system
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

ถ้าต้องการข้อมูลตัวอย่างไว้ทดลองใช้ ให้รัน `php artisan db:seed` เพิ่ม

To load sample data for trying it out, also run `php artisan db:seed`.
