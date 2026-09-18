<?php

namespace App\Support;

use App\Models\AppUser;
use App\Models\KpiMonthReview;
use App\Models\KpiMonthScore;
use App\Models\KpiResult;
use App\Models\OkrAllResult;
use App\Models\OkrResult;
use Illuminate\Support\Facades\Schema;

class KpiResultSynchronizer
{
    public function syncRootAndUserResults(int $rootId, int $userId, int $cycleId): void
    {
        $monthRows = KpiMonthScore::query()
            ->where('app_user_id', $userId)
            ->where('cycle_id', $cycleId)
            ->where('kpi_meta_id', $rootId)
            ->whereBetween('month_no', [1, 12])
            ->get(['id', 'is_pass']);

        $reviewRows = $monthRows->isEmpty()
            ? collect()
            : KpiMonthReview::query()
                ->whereIn('kpi_month_score_id', $monthRows->pluck('id')->all())
                ->get(['kpi_month_score_id', 'status'])
                ->keyBy('kpi_month_score_id');

        $approvedCount = 0;
        $point = 0;
        /** @var KpiMonthScore $monthRow */
        foreach ($monthRows as $monthRow) {
            $status = strtolower(trim((string) ($reviewRows->get((int) $monthRow->id)?->status ?? '')));
            if ($status !== KpiMonthReview::STATUS_APPROVED) {
                continue;
            }

            $approvedCount++;
            $point += (bool) $monthRow->is_pass ? 100 : 0;
        }

        $maximum = $approvedCount * 100;
        $result = $maximum > 0 ? round(($point / $maximum) * 100, 2) : null;

        KpiMonthScore::query()
            ->where('app_user_id', $userId)
            ->where('cycle_id', $cycleId)
            ->where(function ($query) use ($rootId): void {
                $query->whereKey($rootId)
                    ->orWhere('kpi_meta_id', $rootId);
            })
            ->update(['result' => $result]);

        $this->syncUserAndDepartmentResults($userId, $cycleId);
    }

    public function syncUserAndDepartmentResults(int $userId, int $cycleId): void
    {
        /** @var AppUser|null $owner */
        $owner = AppUser::query()
            ->whereKey($userId)
            ->first(['id', 'dept_abbr_hr']);
        if (! $owner) {
            return;
        }

        $rootRows = KpiMonthScore::query()
            ->where('app_user_id', $userId)
            ->where('cycle_id', $cycleId)
            ->whereNotNull('result')
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->where('month_no', 0)
                        ->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            })
            ->get(['id', 'target_departments', 'result']);

        if ($rootRows->isEmpty()) {
            $this->deleteUserResults($userId, $cycleId);
            $this->syncDepartmentOkrAverage($cycleId);
            return;
        }

        $scoresByDepartment = [];
        /** @var KpiMonthScore $rootRow */
        foreach ($rootRows as $rootRow) {
            $department = $this->resolveRootDepartmentCode($rootRow, $owner);
            if ($department === '' || $rootRow->result === null) {
                continue;
            }

            $scoresByDepartment[$department] ??= [];
            $scoresByDepartment[$department][] = (float) $rootRow->result;
        }

        if ($scoresByDepartment === []) {
            $this->deleteUserResults($userId, $cycleId);
            $this->syncDepartmentOkrAverage($cycleId);
            return;
        }

        if (Schema::hasColumn('kpi_result', 'dept_abbr_hr')) {
            $payload = [];
            $departments = [];
            $now = now();
            foreach ($scoresByDepartment as $department => $scores) {
                if (! is_array($scores) || $scores === []) {
                    continue;
                }

                $departments[] = $department;
                $payload[] = [
                    'app_user_id' => $userId,
                    'cycle_id' => $cycleId,
                    'dept_abbr_hr' => $department,
                    'result' => round(array_sum($scores) / count($scores), 2),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($payload !== []) {
                KpiResult::query()->upsert(
                    $payload,
                    ['app_user_id', 'cycle_id', 'dept_abbr_hr'],
                    ['result', 'updated_at']
                );

                KpiResult::query()
                    ->where('app_user_id', $userId)
                    ->where('cycle_id', $cycleId)
                    ->whereNotIn('dept_abbr_hr', $departments)
                    ->delete();
            } else {
                $this->deleteUserResults($userId, $cycleId);
            }
        } else {
            $allScores = [];
            foreach ($scoresByDepartment as $scores) {
                $allScores = array_merge($allScores, is_array($scores) ? $scores : []);
            }

            if ($allScores === []) {
                $this->deleteUserResults($userId, $cycleId);
            } else {
                KpiResult::query()->updateOrCreate(
                    [
                        'app_user_id' => $userId,
                        'cycle_id' => $cycleId,
                    ],
                    [
                        'result' => round(array_sum($allScores) / count($allScores), 2),
                    ]
                );
            }
        }

        $this->syncDepartmentOkrAverage($cycleId);
    }

    private function deleteUserResults(int $userId, int $cycleId): void
    {
        KpiResult::query()
            ->where('app_user_id', $userId)
            ->where('cycle_id', $cycleId)
            ->delete();
    }

    private function syncDepartmentOkrAverage(int $cycleId): void
    {
        if (Schema::hasColumn('kpi_result', 'dept_abbr_hr')) {
            $rows = KpiResult::query()
                ->where('cycle_id', $cycleId)
                ->whereNotNull('result')
                ->whereNotNull('dept_abbr_hr')
                ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
                ->groupBy('dept_abbr_hr')
                ->selectRaw('dept_abbr_hr as dept_abbr_hr, ROUND(AVG(result), 2) as result')
                ->get();
        } else {
            $rows = KpiResult::query()
                ->join('app_users', 'app_users.id', '=', 'kpi_result.app_user_id')
                ->where('kpi_result.cycle_id', $cycleId)
                ->whereNotNull('app_users.dept_abbr_hr')
                ->whereRaw("TRIM(app_users.dept_abbr_hr) <> ''")
                ->groupBy('app_users.dept_abbr_hr')
                ->selectRaw('app_users.dept_abbr_hr as dept_abbr_hr, ROUND(AVG(kpi_result.result), 2) as result')
                ->get();
        }

        if ($rows->isEmpty()) {
            $this->deleteCycleOkrResults($cycleId);
            return;
        }

        $payload = [];
        $departments = [];
        $now = now();
        foreach ($rows as $row) {
            $department = $this->normalizeDepartmentCode((string) ($row->dept_abbr_hr ?? ''));
            if ($department === '') {
                continue;
            }

            $departments[] = $department;
            $payload[] = [
                'dept_abbr_hr' => $department,
                'cycle_id' => $cycleId,
                'result' => round((float) $row->result, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($payload === []) {
            $this->deleteCycleOkrResults($cycleId);
            return;
        }

        OkrResult::query()->upsert(
            $payload,
            ['dept_abbr_hr', 'cycle_id'],
            ['result', 'updated_at']
        );

        OkrResult::query()
            ->where('cycle_id', $cycleId)
            ->whereNotIn('dept_abbr_hr', $departments)
            ->delete();

        $average = OkrResult::query()
            ->where('cycle_id', $cycleId)
            ->whereNotNull('result')
            ->avg('result');

        if ($average === null) {
            OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
            return;
        }

        OkrAllResult::query()->updateOrCreate(
            ['cycle_id' => $cycleId],
            ['result' => round((float) $average, 2)]
        );
    }

    private function deleteCycleOkrResults(int $cycleId): void
    {
        OkrResult::query()->where('cycle_id', $cycleId)->delete();
        OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
    }

    private function resolveRootDepartmentCode(KpiMonthScore $root, AppUser $owner): string
    {
        $departments = is_array($root->target_departments) ? $root->target_departments : [];
        foreach ($departments as $departmentCode) {
            $normalized = $this->normalizeDepartmentCode((string) $departmentCode);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return $this->normalizeDepartmentCode((string) ($owner->dept_abbr_hr ?? ''));
    }

    private function normalizeDepartmentCode(string $value): string
    {
        $text = strtoupper(trim($value));

        return preg_replace('/\s+/u', '', $text) ?? $text;
    }
}
