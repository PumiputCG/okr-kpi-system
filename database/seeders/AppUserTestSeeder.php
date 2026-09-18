<?php

namespace Database\Seeders;

use App\Models\AppUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AppUserTestSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $departments = [
            'ACC' => 'Accounting',
            'BM' => 'Business Management',
            'QC' => 'Quality Control',
            'IT' => 'Information Technology',
            'PM' => 'Program Management',
            'HR' => 'Human Resources',
        ];

        $positions = [
            'cooking',
            'driver',
            'maid',
            'operator',
            'senior operator',
            'support mat',
            'tp man',
            'foreman',
            'leader',
            'senior technician',
            'technician',
            'senior staff',
            'staff',
            'engineer',
            'senior engineer',
            'supervisor',
            'assist manager',
            'assistant manager',
            'manager',
            'deputy general manager',
            'general manager',
            'cfo',
            'ceo',
            'president',
        ];

        $thaiFirstNames = [
            'กิตติพงศ์', 'ชนกานต์', 'ภูมิพัฒน์', 'ศิริพร', 'ปกรณ์', 'สุพัตรา',
            'ธนากร', 'กนกวรรณ', 'อนุชา', 'ณิชา', 'ปิยะรัตน์', 'ชานนท์',
            'สุธิดา', 'นภัสวรรณ', 'พีรวัฒน์', 'ณัฐพล', 'พิมพ์ชนก', 'ศุภกร',
            'อรทัย', 'นิศารัตน์', 'กมลชนก', 'ธวัชชัย', 'วรัญญา', 'เมธาวี',
            'ชลธิชา', 'ปรียานุช', 'ภัทรพล', 'สิรภพ',
        ];

        $thaiLastNames = [
            'นิลมณี', 'ไชยชาติ', 'พูลผล', 'วงศ์ประเสริฐ', 'เจริญสุข', 'อินทร์แก้ว',
            'รุ่งเรือง', 'ศรีสวัสดิ์', 'รัตนกุล', 'จิตต์มณี', 'บุญมี', 'วัฒนชัย',
            'สุขสวัสดิ์', 'ปัญญาวงศ์', 'แสงแก้ว', 'ทวีทรัพย์', 'คงคา', 'ทองใบ',
            'จันทร์หอม', 'ศรีสุวรรณ', 'ชูศรี', 'กาญจนา', 'ปานทอง', 'ภักดี',
            'วิไลรัตน์', 'ศรีประเสริฐ', 'อุดมทรัพย์', 'เกษมสุข',
        ];

        $enFirstNames = [
            'Kittipong', 'Chanokkan', 'Pumiput', 'Siriporn', 'Pakorn', 'Supattra',
            'Thanakorn', 'Kanokwan', 'Anucha', 'Nicha', 'Piyarat', 'Chanon',
            'Sutida', 'Napaswan', 'Peerawat', 'Nattaphon', 'Pimchanok', 'Supakorn',
            'Orathai', 'Nisarat', 'Kamonchanok', 'Thawatchai', 'Waranya', 'Methawee',
            'Chonthicha', 'Preeyanuch', 'Phattharaphon', 'Siraphop',
        ];

        $enLastNames = [
            'Ninmanee', 'Chaichat', 'Phonphol', 'Wongprasert', 'Charoensuk', 'Inkaew',
            'Rungreang', 'Srisawat', 'Rattanakul', 'Jitmanee', 'Boonmee', 'Wattanachai',
            'Suksawat', 'Panyawong', 'Sangkaew', 'Thaweesap', 'Khongkha', 'Thongbai',
            'Chanhom', 'Srisuwan', 'Choosri', 'Kanjana', 'Panthong', 'Phakdee',
            'Wilairat', 'Sriprasert', 'Udomsap', 'Kasemsuk',
        ];

        $nextNamePair = static function (int $index) use (
            $thaiFirstNames,
            $thaiLastNames,
            $enFirstNames,
            $enLastNames
        ): array {
            $firstIndex = $index % count($thaiFirstNames);
            $lastIndex = ($index * 3) % count($thaiLastNames);

            return [
                trim($thaiFirstNames[$firstIndex].' '.$thaiLastNames[$lastIndex]),
                trim($enFirstNames[$firstIndex].' '.$enLastNames[$lastIndex]),
            ];
        };

        $specialAccounts = [
            'BM|senior staff' => [
                'employee_code' => '71064',
                'password' => '000000',
                'full_name_th' => 'ชนกานต์ นิลมณี',
                'full_name_en' => 'Chanokkan Ninmanee',
                'employee_type' => 'month',
                'position' => 'senior staff',
                'department' => 'Business Management',
                'dept_abbr_qms' => 'BM',
                'dept_abbr_hr' => 'BM',
                'email' => 'emp71064@example.com',
                'id_thai_hash' => '1000000000009',
            ],
        ];

        $reservedCodes = [];
        $reservedIdentities = [];
        foreach ($specialAccounts as $account) {
            $reservedCodes[(string) $account['employee_code']] = true;
            $reservedIdentities[(string) $account['id_thai_hash']] = true;
        }

        $usedCodes = [];
        $usedIdentities = [];
        $accounts = [];
        $nameIndex = 0;
        $nextEmployeeCode = 71064;
        $nextIdentity = 1000000000009;

        foreach ($departments as $deptAbbr => $departmentName) {
            foreach ($positions as $position) {
                $specialKey = $deptAbbr.'|'.$position;

                if (isset($specialAccounts[$specialKey])) {
                    $account = $specialAccounts[$specialKey];
                    $accounts[] = $account;
                    $usedCodes[(string) $account['employee_code']] = true;
                    $usedIdentities[(string) $account['id_thai_hash']] = true;
                    continue;
                }

                while (isset($reservedCodes[(string) $nextEmployeeCode]) || isset($usedCodes[(string) $nextEmployeeCode])) {
                    $nextEmployeeCode++;
                }
                $employeeCode = (string) $nextEmployeeCode;
                $nextEmployeeCode++;

                while (isset($reservedIdentities[(string) $nextIdentity]) || isset($usedIdentities[(string) $nextIdentity])) {
                    $nextIdentity++;
                }
                $identityHash = (string) $nextIdentity;
                $nextIdentity++;

                [$fullNameTh, $fullNameEn] = $nextNamePair($nameIndex);
                $nameIndex++;

                $account = [
                    'employee_code' => $employeeCode,
                    'password' => '000000',
                    'full_name_th' => $fullNameTh,
                    'full_name_en' => $fullNameEn,
                    'employee_type' => 'month',
                    'position' => $position,
                    'department' => $departmentName,
                    'dept_abbr_qms' => $deptAbbr,
                    'dept_abbr_hr' => $deptAbbr,
                    'email' => 'emp'.$employeeCode.'@example.com',
                    'id_thai_hash' => $identityHash,
                ];

                $accounts[] = $account;
                $usedCodes[$employeeCode] = true;
                $usedIdentities[$identityHash] = true;
            }
        }

        $employeeCodes = array_map(
            static fn (array $account): string => (string) $account['employee_code'],
            $accounts
        );

        // Keep only this seed set (and admin account) for predictable test data.
        AppUser::withTrashed()
            ->where('employee_code', '!=', 'admin')
            ->whereNotIn('employee_code', $employeeCodes)
            ->forceDelete();

        foreach ($accounts as $account) {
            $user = AppUser::withTrashed()->firstOrNew([
                'employee_code' => $account['employee_code'],
            ]);

            $user->password = $account['password'];
            $user->full_name_th = $account['full_name_th'];
            $user->full_name_en = $account['full_name_en'];
            $user->employee_type = $account['employee_type'];
            $user->position = $account['position'];
            $user->department = $account['department'];
            $user->dept_abbr_qms = $account['dept_abbr_qms'];
            $user->dept_abbr_hr = $account['dept_abbr_hr'];
            $user->email = $account['email'];
            $user->id_thai_hash = $account['id_thai_hash'];
            $user->role = 'user';
            $user->is_registered = true;
            $user->registered_at = $now;
            $user->deleted_at = null;
            $user->save();
        }
    }
}
