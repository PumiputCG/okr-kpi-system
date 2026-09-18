<?php

namespace Database\Seeders;

use App\Models\AdminDepartmentAssignment;
use App\Models\AppUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds admin_department_assignments per the official assignment plan
 * (qa-output/Manager_Assignment_Plan.xlsx, decided 2026-06-05).
 *
 * Keyed by employee_code (not id) so it's portable across environments.
 * 4 departments are intentionally skipped because they have no KPI:
 *   Management, MFG, R&D, Resident
 *
 * Run:
 *   php artisan db:seed --class=AdminDepartmentAssignmentSeeder
 *
 * Re-runnable: uses firstOrNew + save (idempotent on dept_abbr_hr).
 */
class AdminDepartmentAssignmentSeeder extends Seeder
{
    /**
     * Each row:
     *   'dept' => [
     *       'target'   => ['emp_code', ...],   // ผู้กำหนดเป้าหมาย Level 3
     *       'reviewer' => ['emp_code', ...],   // ผู้ตรวจรายงาน Level 4
     *   ]
     *
     * @var array<string, array{target: array<int, string>, reviewer: array<int, string>}>
     */
    private const PLAN = [
        'ACC'           => ['target' => ['65527'], 'reviewer' => ['65527']],
        'AME'           => ['target' => ['64244'], 'reviewer' => ['64244']],
        'BM'            => ['target' => ['68060'], 'reviewer' => ['68060']],
        'BOI'           => ['target' => ['65527'], 'reviewer' => ['65527']],
        'HRD'           => ['target' => ['71019'], 'reviewer' => ['71019']],
        'HRM'           => ['target' => ['71019'], 'reviewer' => ['71019']],
        'IM1'           => ['target' => ['68060'], 'reviewer' => ['64507']],
        'IM2'           => ['target' => ['68060'], 'reviewer' => ['61772']],
        'IM3'           => ['target' => ['68060'], 'reviewer' => ['60830']],
        'iNest'         => ['target' => ['70941'], 'reviewer' => ['70941', '70942', '71273']],
        'IQ'            => ['target' => ['60512'], 'reviewer' => ['60512']],
        'IT'            => ['target' => ['50000'], 'reviewer' => ['50000']],
        'LGD'           => ['target' => ['64020'], 'reviewer' => ['64020']],
        'LGE'           => ['target' => ['69071'], 'reviewer' => ['69071']],
        'MF'            => ['target' => ['63448', '63536', '64730', '71029'], 'reviewer' => ['63448', '63536', '64730', '71029']],
        'MFG (Center)'  => ['target' => ['68060'], 'reviewer' => ['68060']],
        'MK'            => ['target' => ['70014', '63580', '64974'], 'reviewer' => ['70014', '63580', '64974']],
        'ML'            => ['target' => ['63443'], 'reviewer' => ['63443']],
        'MMT'           => ['target' => ['68060'], 'reviewer' => ['68060']],
        'MT1'           => ['target' => ['65234', '60034', '63506'], 'reviewer' => ['65234', '60034', '63506']],
        'MT2'           => ['target' => ['60962', '64198'], 'reviewer' => ['60962', '64198']],
        'PC'            => ['target' => ['60013'], 'reviewer' => ['60013']],
        'PD'            => ['target' => ['63467', '71062'], 'reviewer' => ['63467', '71062']],
        'PDA1'          => ['target' => ['64511'], 'reviewer' => ['64511']],
        'PDA2'          => ['target' => ['61015'], 'reviewer' => ['61015']],
        'PDW'           => ['target' => ['64511'], 'reviewer' => ['64511']],
        'PE'            => ['target' => ['63534', '67494', '70951'], 'reviewer' => ['63534', '67494', '70951']],
        'PET'           => ['target' => ['68060'], 'reviewer' => ['68060']],
        'PF'            => ['target' => ['61015', '64332'], 'reviewer' => ['61015', '64332']],
        'PM'            => ['target' => ['68351'], 'reviewer' => ['68351', '67039']],
        'PU'            => ['target' => ['60863'], 'reviewer' => ['60863']],
        'QA'            => ['target' => ['62079'], 'reviewer' => ['62079']],
        'QAN'           => ['target' => ['64765'], 'reviewer' => ['64765']],
        'QC'            => ['target' => ['62079', '60512'], 'reviewer' => ['62079', '60512']],
        'QCP'           => ['target' => ['62079', '60512'], 'reviewer' => ['62079', '60512']],
        'QMS'           => ['target' => ['60861'], 'reviewer' => ['60861']],
        'SHE'           => ['target' => ['65359'], 'reviewer' => ['65359']],
        'SL'            => ['target' => ['61569', '64020'], 'reviewer' => ['61569', '64020']],
        'SQ'            => ['target' => ['62079'], 'reviewer' => ['62079']],
        'ST1'           => ['target' => ['66459'], 'reviewer' => ['66459']],
        'ST2'           => ['target' => ['60171'], 'reviewer' => ['60171']],
    ];

    public function run(): void
    {
        $adminUser = AppUser::query()
            ->whereRaw("LOWER(TRIM(COALESCE(role, ''))) = 'admin'")
            ->orderBy('id')
            ->first();
        $adminUserId = (int) ($adminUser?->id ?? 0);

        $totalPlans  = count(self::PLAN);
        $okCount     = 0;
        $missingRows = [];

        $this->command->info("AdminDepartmentAssignmentSeeder: assigning {$totalPlans} departments.");

        DB::transaction(function () use ($adminUserId, &$okCount, &$missingRows): void {
            foreach (self::PLAN as $dept => $cfg) {
                $targetIds   = $this->resolveIdsByEmployeeCodes($cfg['target']);
                $reviewerIds = $this->resolveIdsByEmployeeCodes($cfg['reviewer']);

                // Track any employee_code that was not found in DB
                $missingTargets   = array_diff($cfg['target'],   $this->codesFoundFromIds($targetIds));
                $missingReviewers = array_diff($cfg['reviewer'], $this->codesFoundFromIds($reviewerIds));
                if ($missingTargets !== [] || $missingReviewers !== []) {
                    $missingRows[] = [
                        'dept'              => $dept,
                        'missing_target'    => array_values($missingTargets),
                        'missing_reviewer'  => array_values($missingReviewers),
                    ];
                }

                if ($targetIds === [] && $reviewerIds === []) {
                    $this->command->warn("  [{$dept}] No valid users found — skipping.");
                    continue;
                }

                $assignment = AdminDepartmentAssignment::query()->firstOrNew([
                    'dept_abbr_hr' => $dept,
                ]);
                $assignment->target_user_id         = $targetIds[0]   ?? null;
                $assignment->target_user_ids_json   = $targetIds   !== [] ? $targetIds   : null;
                $assignment->reviewer_user_id       = $reviewerIds[0] ?? null;
                $assignment->reviewer_user_ids_json = $reviewerIds !== [] ? $reviewerIds : null;
                if ($adminUserId > 0) {
                    $assignment->assigned_by_admin_user_id = $adminUserId;
                }
                $assignment->save();

                $tag    = $assignment->wasRecentlyCreated ? '(new)' : '(updated)';
                $tCount = count($targetIds);
                $rCount = count($reviewerIds);
                $this->command->line("  [{$dept}] target={$tCount}, reviewer={$rCount} {$tag}");
                $okCount++;
            }
        });

        $this->command->info("AdminDepartmentAssignmentSeeder: applied {$okCount}/{$totalPlans} departments.");

        if ($missingRows !== []) {
            $this->command->warn('Some employee_code values were not found in app_users:');
            foreach ($missingRows as $row) {
                $missingT = implode(',', $row['missing_target']);
                $missingR = implode(',', $row['missing_reviewer']);
                $this->command->warn("  [{$row['dept']}] missing target=[{$missingT}], reviewer=[{$missingR}]");
            }
        }

        $this->command->info('Skipped (no KPI): Management, MFG, R&D, Resident.');
    }

    /**
     * Look up app_users.id for a list of employee_code values.
     * Order in the result matches the order of the input codes when found.
     *
     * @param array<int, string> $employeeCodes
     * @return array<int, int>
     */
    private function resolveIdsByEmployeeCodes(array $employeeCodes): array
    {
        if ($employeeCodes === []) {
            return [];
        }

        $rows = AppUser::query()
            ->whereIn('employee_code', $employeeCodes)
            ->whereNull('deleted_at')
            ->get(['id', 'employee_code']);

        $byCode = [];
        foreach ($rows as $row) {
            $byCode[trim((string) $row->employee_code)] = (int) $row->id;
        }

        $ids = [];
        foreach ($employeeCodes as $code) {
            $code = trim((string) $code);
            if (isset($byCode[$code]) && $byCode[$code] > 0) {
                $ids[] = $byCode[$code];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Reverse-lookup employee_codes from a list of id (for tracking missing).
     *
     * @param array<int, int> $ids
     * @return array<int, string>
     */
    private function codesFoundFromIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return AppUser::query()
            ->whereIn('id', $ids)
            ->pluck('employee_code')
            ->map(fn ($c) => trim((string) $c))
            ->all();
    }
}
