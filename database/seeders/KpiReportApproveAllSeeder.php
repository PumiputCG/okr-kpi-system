<?php

namespace Database\Seeders;

use App\Models\AdminDepartmentAssignment;
use App\Models\AppUser;
use App\Models\KpiMonthReview;
use App\Models\KpiMonthScore;
use App\Models\KpiResult;
use App\Models\OkrAllResult;
use App\Models\OkrKeyResult;
use App\Models\OkrResult;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Approves all pending KPI report month records for every department,
 * then syncs result scores exactly as the manual approve button does:
 *   1. Create/update KpiMonthReview → status='approved'
 *   2. syncRootResultByApprovedMonths  → update KpiMonthScore.result per root
 *   3. syncUserKpiAndOkrResultsForCycle → update kpi_result per user/dept
 *   4. syncDepartmentOkrAverageForCycle → update okr_result + okr_all_result
 *
 * Command: php artisan db:seed --class=KpiReportApproveAllSeeder
 */
class KpiReportApproveAllSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Build dept → reviewer_user_id map ────────────────────────────
        $assignments = AdminDepartmentAssignment::query()
            ->whereNotNull('reviewer_user_id')
            ->get(['dept_abbr_hr', 'reviewer_user_id']);

        $reviewerByDept = [];
        foreach ($assignments as $a) {
            $dept = $a->dept_abbr_hr;
            if (! isset($reviewerByDept[$dept])) {
                $reviewerByDept[$dept] = (int) $a->reviewer_user_id;
            }
        }

        if (empty($reviewerByDept)) {
            $this->command->warn('KpiReportApproveAllSeeder: no reviewer assignments found — skipping.');
            return;
        }

        $this->command->info('KpiReportApproveAllSeeder: reviewers loaded for ' . count($reviewerByDept) . ' dept(s).');

        // ── 2. Build okr_key_result_id → dept_abbr_hr map ───────────────────
        $krDeptMap = OkrKeyResult::query()
            ->whereNotNull('dept_abbr_hr')
            ->pluck('dept_abbr_hr', 'id')
            ->map(fn ($d) => (string) $d)
            ->all();

        // ── 3. Load all report month scores (month_no > 0) ──────────────────
        $scores = KpiMonthScore::query()
            ->where('mode_type', 'report')
            ->where('month_no', '>', 0)
            ->whereNotNull('submitted_at')
            ->get(['id', 'okr_key_result_id', 'kpi_meta_id', 'app_user_id', 'cycle_id', 'submitted_at']);

        $this->command->info('KpiReportApproveAllSeeder: ' . $scores->count() . ' report month record(s) found.');

        // ── 4. Load existing reviews ─────────────────────────────────────────
        $existingReviews = KpiMonthReview::query()
            ->whereIn('kpi_month_score_id', $scores->pluck('id'))
            ->get(['id', 'kpi_month_score_id', 'status'])
            ->keyBy('kpi_month_score_id');

        // ── 5. Approve & collect affected roots ──────────────────────────────
        $created    = 0;
        $updated    = 0;
        $skipped    = 0;
        $noReviewer = 0;

        // Track unique (root_id, user_id, cycle_id) to sync after approval
        $affectedRoots = []; // key = "rootId|userId|cycleId"

        DB::transaction(function () use (
            $scores, $existingReviews, $krDeptMap, $reviewerByDept,
            &$created, &$updated, &$skipped, &$noReviewer, &$affectedRoots
        ): void {
            foreach ($scores as $score) {
                $dept       = $krDeptMap[(int) $score->okr_key_result_id] ?? null;
                $reviewerId = $dept ? ($reviewerByDept[$dept] ?? null) : null;

                if (! $reviewerId) {
                    $noReviewer++;
                    continue;
                }

                $reviewedAt = $score->submitted_at instanceof Carbon
                    ? $score->submitted_at->copy()->addHours(2)
                    : Carbon::parse($score->submitted_at)->addHours(2);

                if ($reviewedAt->isFuture()) {
                    $reviewedAt = now()->subDay();
                }

                $existing = $existingReviews->get((int) $score->id);

                if (! $existing) {
                    KpiMonthReview::query()->create([
                        'kpi_month_score_id'  => (int) $score->id,
                        'status'              => KpiMonthReview::STATUS_APPROVED,
                        'reviewed_by_user_id' => $reviewerId,
                        'reviewed_at'         => $reviewedAt,
                    ]);
                    $created++;
                } elseif ($existing->status === KpiMonthReview::STATUS_PENDING) {
                    $existing->update([
                        'status'              => KpiMonthReview::STATUS_APPROVED,
                        'reviewed_by_user_id' => $reviewerId,
                        'reviewed_at'         => $reviewedAt,
                    ]);
                    $updated++;
                } else {
                    $skipped++;
                    // Still must sync even already-approved roots
                }

                $rootId  = (int) ($score->kpi_meta_id ?? 0);
                $userId  = (int) ($score->app_user_id ?? 0);
                $cycleId = (int) ($score->cycle_id ?? 0);
                if ($rootId > 0 && $userId > 0 && $cycleId > 0) {
                    $affectedRoots[$rootId . '|' . $userId . '|' . $cycleId] = [
                        'root_id'  => $rootId,
                        'user_id'  => $userId,
                        'cycle_id' => $cycleId,
                    ];
                }
            }
        });

        $this->command->line('  Created : ' . $created);
        $this->command->line('  Updated : ' . $updated);
        $this->command->line('  Skipped (already approved/rejected): ' . $skipped);
        if ($noReviewer > 0) {
            $this->command->warn('  No reviewer found: ' . $noReviewer);
        }

        // ── 6. Sync result scores — mirrors controller logic exactly ─────────
        $this->command->line('');
        $this->command->line('  Syncing result scores for ' . count($affectedRoots) . ' root(s)...');

        $affectedCycleIds = [];
        foreach ($affectedRoots as $entry) {
            $this->syncRootResultByApprovedMonths(
                $entry['root_id'],
                $entry['user_id'],
                $entry['cycle_id']
            );
            $affectedCycleIds[$entry['cycle_id']] = true;
        }

        // ── 7. Sync OKR averages per cycle ───────────────────────────────────
        foreach (array_keys($affectedCycleIds) as $cycleId) {
            $this->syncDepartmentOkrAverageForCycle((int) $cycleId);
        }

        $this->command->info('KpiReportApproveAllSeeder: done — scores synced.');
    }

    // ── Mirrors KpiReportReviewController::syncRootResultByApprovedMonths ────
    private function syncRootResultByApprovedMonths(int $rootId, int $ownerId, int $cycleId): void
    {
        $monthRows = KpiMonthScore::query()
            ->where('app_user_id', $ownerId)
            ->where('cycle_id', $cycleId)
            ->where('kpi_meta_id', $rootId)
            ->whereBetween('month_no', [1, 12])
            ->get(['id', 'is_pass']);

        $reviewRows = KpiMonthReview::query()
            ->whereIn('kpi_month_score_id', $monthRows->pluck('id')->all())
            ->get(['kpi_month_score_id', 'status'])
            ->keyBy('kpi_month_score_id');

        $approvedRows = $monthRows->filter(function (KpiMonthScore $row) use ($reviewRows): bool {
            $review = $reviewRows->get((int) $row->id);
            return strtolower(trim((string) ($review?->status ?? ''))) === KpiMonthReview::STATUS_APPROVED;
        });

        $result = null;
        if ($approvedRows->count() > 0) {
            $point = 0;
            foreach ($approvedRows as $row) {
                $point += (bool) $row->is_pass ? 100 : 0;
            }
            $max    = $approvedRows->count() * 100;
            $result = $max > 0 ? round(($point / $max) * 100, 2) : null;
        }

        KpiMonthScore::query()
            ->where('app_user_id', $ownerId)
            ->where('cycle_id', $cycleId)
            ->where(function ($q) use ($rootId): void {
                $q->where('id', $rootId)->orWhere('kpi_meta_id', $rootId);
            })
            ->update(['result' => $result]);

        $this->syncUserKpiAndOkrResultsForCycle($ownerId, $cycleId);
    }

    // ── Mirrors KpiReportReviewController::syncUserKpiAndOkrResultsForCycle ──
    private function syncUserKpiAndOkrResultsForCycle(int $userId, int $cycleId): void
    {
        /** @var AppUser|null $owner */
        $owner = AppUser::query()->whereKey($userId)->first(['id', 'dept_abbr_hr']);
        if (! $owner) {
            return;
        }

        $rootRows = KpiMonthScore::query()
            ->where('app_user_id', $userId)
            ->where('cycle_id', $cycleId)
            ->whereNotNull('result')
            ->where(function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('month_no', 0)->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            })
            ->get(['id', 'target_departments', 'result']);

        if ($rootRows->isEmpty()) {
            KpiResult::query()->where('app_user_id', $userId)->where('cycle_id', $cycleId)->delete();
            return;
        }

        $scoresByDept = [];
        foreach ($rootRows as $rootRow) {
            $dept = $this->resolveRootDept($rootRow, $owner);
            if ($dept === '' || $rootRow->result === null) {
                continue;
            }
            $scoresByDept[$dept][] = (float) $rootRow->result;
        }

        if ($scoresByDept === []) {
            KpiResult::query()->where('app_user_id', $userId)->where('cycle_id', $cycleId)->delete();
            return;
        }

        $hasResultDeptCol = Schema::hasColumn('kpi_result', 'dept_abbr_hr');

        if ($hasResultDeptCol) {
            $payload     = [];
            $departments = [];
            $now         = now();
            foreach ($scoresByDept as $dept => $scores) {
                $departments[] = $dept;
                $payload[]     = [
                    'app_user_id' => $userId,
                    'cycle_id'    => $cycleId,
                    'dept_abbr_hr' => $dept,
                    'result'      => round(array_sum($scores) / count($scores), 2),
                    'created_at'  => $now,
                    'updated_at'  => $now,
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
                KpiResult::query()->where('app_user_id', $userId)->where('cycle_id', $cycleId)->delete();
            }
        } else {
            $allScores = array_merge(...array_values($scoresByDept));
            if ($allScores === []) {
                KpiResult::query()->where('app_user_id', $userId)->where('cycle_id', $cycleId)->delete();
            } else {
                KpiResult::query()->updateOrCreate(
                    ['app_user_id' => $userId, 'cycle_id' => $cycleId],
                    ['result' => round(array_sum($allScores) / count($allScores), 2)]
                );
            }
        }
    }

    // ── Mirrors KpiReportReviewController::syncDepartmentOkrAverageForCycle ──
    private function syncDepartmentOkrAverageForCycle(int $cycleId): void
    {
        $hasResultDeptCol = Schema::hasColumn('kpi_result', 'dept_abbr_hr');

        if ($hasResultDeptCol) {
            $rows = KpiResult::query()
                ->where('cycle_id', $cycleId)
                ->whereNotNull('result')
                ->whereNotNull('dept_abbr_hr')
                ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
                ->groupBy('dept_abbr_hr')
                ->selectRaw('dept_abbr_hr, ROUND(AVG(result), 2) as result')
                ->get();
        } else {
            $rows = KpiResult::query()
                ->join('app_users', 'app_users.id', '=', 'kpi_result.app_user_id')
                ->where('kpi_result.cycle_id', $cycleId)
                ->whereNotNull('app_users.dept_abbr_hr')
                ->whereRaw("TRIM(app_users.dept_abbr_hr) <> ''")
                ->groupBy('app_users.dept_abbr_hr')
                ->selectRaw('app_users.dept_abbr_hr, ROUND(AVG(kpi_result.result), 2) as result')
                ->get();
        }

        if ($rows->isEmpty()) {
            OkrResult::query()->where('cycle_id', $cycleId)->delete();
            OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
            return;
        }

        $payload     = [];
        $departments = [];
        $now         = now();
        foreach ($rows as $row) {
            $dept = strtoupper(trim((string) ($row->dept_abbr_hr ?? '')));
            if ($dept === '') {
                continue;
            }
            $departments[] = $dept;
            $payload[]     = [
                'dept_abbr_hr' => $dept,
                'cycle_id'     => $cycleId,
                'result'       => round((float) $row->result, 2),
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        if ($payload === []) {
            OkrResult::query()->where('cycle_id', $cycleId)->delete();
            OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
            return;
        }

        OkrResult::query()->upsert(
            $payload,
            ['dept_abbr_hr', 'cycle_id'],
            ['result', 'updated_at']
        );
        OkrResult::query()->where('cycle_id', $cycleId)->whereNotIn('dept_abbr_hr', $departments)->delete();

        $avg = OkrResult::query()->where('cycle_id', $cycleId)->whereNotNull('result')->avg('result');

        if ($avg === null) {
            OkrAllResult::query()->where('cycle_id', $cycleId)->delete();
            return;
        }

        OkrAllResult::query()->updateOrCreate(
            ['cycle_id' => $cycleId],
            ['result' => round((float) $avg, 2)]
        );
    }

    // ── Resolve dept code from root score or owner ────────────────────────────
    private function resolveRootDept(KpiMonthScore $root, AppUser $owner): string
    {
        $departments = is_array($root->target_departments) ? $root->target_departments : [];
        foreach ($departments as $code) {
            $normalized = strtoupper(trim((string) $code));
            if ($normalized !== '') {
                return $normalized;
            }
        }
        return strtoupper(trim((string) ($owner->dept_abbr_hr ?? '')));
    }
}
