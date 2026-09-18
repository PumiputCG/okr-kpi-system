<?php

namespace Database\Seeders;

use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\KpiMonthScore;
use App\Models\OkrKeyResult;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Seeds Level 3 targets for HRD, HRM, and IT.
 *
 * Source: Level3/HRD,HRM,IT_OKR-KPI 1.xlsx
 * Each Level 3 target copies its connected Level 2 title and detail.
 *
 * Run after:
 *   php artisan migrate --force
 *   php artisan db:seed --class=GoalTargetSeeder --force
 *   php artisan db:seed --class=GoalTargetAllDeptSeeder --force
 *
 * Run this seeder:
 *   php artisan db:seed --class=GoalTargetAllDeptL3Seeder --force
 */
class GoalTargetAllDeptL3Seeder extends Seeder
{
    private const OWNER_EMPLOYEE_CODE = '71019';

    private const DEPARTMENTS = [
        'HRD',
        'HRM',
        'IT',
    ];

    public function run(): void
    {
        $this->ensureRequiredSchema();

        $cycle = Cycle::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first()
          ?? Cycle::query()->orderByDesc('id')->first();

        if (! $cycle) {
            throw new RuntimeException('GoalTargetAllDeptL3Seeder: no cycle found.');
        }

        $owner = AppUser::query()
            ->where('employee_code', self::OWNER_EMPLOYEE_CODE)
            ->first();

        if (! $owner) {
            throw new RuntimeException(
                'GoalTargetAllDeptL3Seeder: employee code '.self::OWNER_EMPLOYEE_CODE.' was not found.'
            );
        }

        $keyResults = OkrKeyResult::query()
            ->with('objective:id,cycle_id,sort_no,title')
            ->whereIn('dept_abbr_hr', self::DEPARTMENTS)
            ->whereHas('objective', function ($query) use ($cycle): void {
                $query->where('cycle_id', (int) $cycle->id);
            })
            ->orderBy('dept_abbr_hr')
            ->orderBy('okr_objective_id')
            ->orderBy('sort_no')
            ->get();

        $this->ensureEveryDepartmentHasLevelTwo($keyResults);

        DB::transaction(function () use ($cycle, $owner, $keyResults): void {
            foreach (self::DEPARTMENTS as $department) {
                $departmentKeyResults = $keyResults
                    ->where('dept_abbr_hr', $department)
                    ->values();
                $seededCount = 0;

                foreach ($departmentKeyResults as $keyResult) {
                    $target = $this->syncLevelThreeTarget(
                        (int) $cycle->id,
                        (int) $owner->id,
                        $department,
                        $keyResult
                    );

                    $this->command->line(
                        "  [{$department}] L1 #{$keyResult->objective->sort_no}"
                        ." -> L2 #{$keyResult->sort_no}"
                        ." -> L3 #{$target->id}: {$target->objective}"
                    );
                    $seededCount++;
                }

                $this->command->info(
                    "  [{$department}] synced {$seededCount} Level 3 target(s)."
                );
            }
        });

        $ownerName = trim((string) ($owner->full_name_th ?: $owner->full_name_en));
        $this->command->info(
            'GoalTargetAllDeptL3Seeder: completed for '
            .self::OWNER_EMPLOYEE_CODE
            .($ownerName !== '' ? " ({$ownerName})" : '')
            .'.'
        );
    }

    private function ensureRequiredSchema(): void
    {
        $requiredColumns = [
            'target_departments',
            'okr_objective_id',
            'okr_key_result_id',
            'parent_target_kpi_id',
            'mode_type',
            'has_criteria',
        ];

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn('kpi_month_scores', $column)) {
                throw new RuntimeException(
                    "GoalTargetAllDeptL3Seeder: missing kpi_month_scores.{$column}. Run php artisan migrate --force first."
                );
            }
        }
    }

    /**
     * @param  Collection<int, OkrKeyResult>  $keyResults
     */
    private function ensureEveryDepartmentHasLevelTwo(Collection $keyResults): void
    {
        foreach (self::DEPARTMENTS as $department) {
            if ($keyResults->where('dept_abbr_hr', $department)->isEmpty()) {
                throw new RuntimeException(
                    "GoalTargetAllDeptL3Seeder: no Level 2 data found for {$department}. "
                    .'Run GoalTargetAllDeptSeeder first.'
                );
            }
        }
    }

    private function syncLevelThreeTarget(
        int $cycleId,
        int $ownerId,
        string $department,
        OkrKeyResult $keyResult
    ): KpiMonthScore {
        $existingTargets = KpiMonthScore::query()
            ->where('cycle_id', $cycleId)
            ->where('okr_key_result_id', (int) $keyResult->id)
            ->where('mode_type', 'target')
            ->where('month_no', 0)
            ->whereNull('kpi_meta_id')
            ->orderBy('id')
            ->get();

        $target = $existingTargets->firstWhere('app_user_id', $ownerId)
          ?? $existingTargets->first()
          ?? new KpiMonthScore;

        $target->fill([
            'app_user_id' => $ownerId,
            'cycle_id' => $cycleId,
            'kpi_meta_id' => null,
            'month_no' => 0,
            'objective' => trim((string) $keyResult->title),
            'detail' => trim((string) ($keyResult->detail ?? '')),
            'target_departments' => [$department],
            'okr_objective_id' => (int) $keyResult->okr_objective_id,
            'okr_key_result_id' => (int) $keyResult->id,
            'parent_target_kpi_id' => null,
            'target_value' => null,
            'kpi_unit_id' => null,
            'criteria_operator' => '',
            'has_criteria' => false,
            'mode_type' => 'target',
            'score_value' => 0,
            'is_pass' => false,
            'result' => null,
            'evidence_files' => null,
            'action_plan_files' => null,
            'submitted_at' => null,
        ]);
        $target->save();

        $duplicateIds = $existingTargets
            ->where('id', '<>', (int) $target->id)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($duplicateIds !== []) {
            KpiMonthScore::query()
                ->whereIn('parent_target_kpi_id', $duplicateIds)
                ->update([
                    'parent_target_kpi_id' => (int) $target->id,
                    'okr_objective_id' => (int) $keyResult->okr_objective_id,
                    'okr_key_result_id' => (int) $keyResult->id,
                ]);

            KpiMonthScore::query()
                ->whereIn('id', $duplicateIds)
                ->delete();
        }

        return $target->fresh();
    }
}
