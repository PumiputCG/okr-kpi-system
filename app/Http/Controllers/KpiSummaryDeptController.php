<?php

namespace App\Http\Controllers;

use App\Models\AdminDepartmentAssignment;
use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\KpiMonthReview;
use App\Models\KpiMonthScore;
use App\Models\KpiUnit;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use App\Support\DepartmentAssignmentResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KpiSummaryDeptController extends Controller
{
    private const KPI_DEPT_VIEWER_POSITIONS_DEFAULT = [
        'assist manager',
        'manager',
        'deputy general manager',
        'general manager',
    ];

    private const KPI_TARGET_DEPARTMENT_POSITIONS = [
        'assist manager',
        'assistant manager',
        'manager',
    ];

    public function index(Request $request)
    {
        $lang = strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';
        $q = trim((string) $request->query('q', ''));
        $requestedGoalTopicId = (int) $request->query('goal_topic_id', 0);

        /** @var AppUser|null $authUser */
        $authUser = $request->user();
        if (! $authUser) {
            abort(401);
        }

        $isAdmin = $this->isAdminRole($authUser->role);
        if (! $this->canAccessDepartmentSummary($authUser, $isAdmin)) {
            abort(403);
        }

        $accessibleDepartments = $isAdmin ? null : $this->resolveAccessibleDepartments($authUser);
        $departmentOptions = $this->resolveDepartmentOptions($accessibleDepartments);
        $requestedDepartment = $this->normalizeDepartmentCode((string) $request->query('dept', ''));
        $selectedDepartment = $requestedDepartment !== '' && in_array($requestedDepartment, $departmentOptions, true)
            ? $requestedDepartment
            : '';
        $departmentLabel = $isAdmin
            ? ($lang === 'th' ? 'ทุกแผนก' : 'All Departments')
            : ($selectedDepartment !== ''
                ? $selectedDepartment
                : ($departmentOptions !== [] ? implode(', ', $departmentOptions) : '-'));
        if ($selectedDepartment !== '') {
            $departmentLabel = $selectedDepartment;
        }

        $cycles = Cycle::query()
            ->orderByDesc('id')
            ->get(['id', 'name', 'start_date', 'end_date', 'is_active']);

        $fallbackCycleId = (int) ($cycles->firstWhere('is_active', true)?->id ?? ($cycles->first()->id ?? 0));
        $requestedCycleId = (int) $request->query('cycle_id', $fallbackCycleId);
        $selectedCycle = $requestedCycleId > 0
            ? $cycles->firstWhere('id', $requestedCycleId)
            : null;

        if (! $selectedCycle && $fallbackCycleId > 0) {
            $selectedCycle = $cycles->firstWhere('id', $fallbackCycleId);
        }

        $rows = [];
        $goalTopicOptions = [];
        $selectedGoalTopicId = 0;
        $unitLabelMap = $this->buildUnitLabelMap($lang);
        if (($isAdmin || $accessibleDepartments !== []) && $selectedCycle) {
            $rows = $this->buildRows(
                (int) $selectedCycle->id,
                $accessibleDepartments,
                $lang,
                $q,
                $unitLabelMap,
                $selectedDepartment
            );
            $goalTopicOptions = $this->buildGoalTopicOptions($rows);
            $availableGoalTopicIds = array_map(
                static fn (array $option): int => (int) ($option['id'] ?? 0),
                $goalTopicOptions
            );
            if ($requestedGoalTopicId > 0 && in_array($requestedGoalTopicId, $availableGoalTopicIds, true)) {
                $selectedGoalTopicId = $requestedGoalTopicId;
                $rows = $this->filterRowsByGoalTopic($rows, $selectedGoalTopicId);
            }
        }

        return view('kpi-summary-dept', [
            'lang' => $lang,
            'q' => $q,
            'departmentLabel' => $departmentLabel,
            'departmentOptions' => $departmentOptions,
            'selectedDepartment' => $selectedDepartment,
            'goalTopicOptions' => $goalTopicOptions,
            'selectedGoalTopicId' => $selectedGoalTopicId,
            'isAdmin' => $isAdmin,
            'cycles' => $cycles,
            'selectedCycle' => $selectedCycle,
            'rows' => $rows,
        ]);
    }

    public function monthlySummary(Request $request, int $root): JsonResponse
    {
        $lang = strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';

        /** @var AppUser|null $authUser */
        $authUser = $request->user();
        if (! $authUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $rootRow = $this->findRootSummaryRow($root);
        if (! $rootRow) {
            return response()->json([
                'message' => $lang === 'th' ? 'ไม่พบรายการ KPI' : 'KPI item not found.',
            ], 404);
        }

        $targetUser = AppUser::query()
            ->whereKey((int) $rootRow->app_user_id)
            ->first(['id', 'employee_code', 'full_name_th', 'full_name_en', 'position', 'dept_abbr_hr', 'role']);

        if (! $targetUser) {
            return response()->json([
                'message' => $lang === 'th' ? 'ไม่พบข้อมูลพนักงาน' : 'Employee not found.',
            ], 404);
        }

        $isAdmin = $this->isAdminRole($authUser->role);
        if (! $this->canAccessDepartmentSummary($authUser, $isAdmin)) {
            return response()->json([
                'message' => 'You do not have permission to access this page.',
            ], 403);
        }

        $accessibleDepartments = $isAdmin ? null : $this->resolveAccessibleDepartments($authUser);
        if (! $this->canViewKpiRootDepartment($targetUser, $rootRow, $isAdmin, $accessibleDepartments)) {
            return response()->json([
                'message' => $lang === 'th'
                    ? 'คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้'
                    : 'You do not have permission to access this data.',
            ], 403);
        }


        $operator = trim((string) $rootRow->criteria_operator);
        $targetValue = $rootRow->target_value !== null ? (float) $rootRow->target_value : null;
        $unitLabelMap = $this->buildUnitLabelMap($lang);
        $unitText = $this->resolveUnitLabel($rootRow->kpi_unit_id, $unitLabelMap);
        $criteriaText = $this->formatMonthlyCriteriaText($targetValue, $operator, $unitText);

        $monthRows = KpiMonthScore::query()
            ->where('app_user_id', (int) $targetUser->id)
            ->where('cycle_id', (int) $rootRow->cycle_id)
            ->where('kpi_meta_id', (int) $rootRow->id)
            ->whereBetween('month_no', [1, 12])
            ->orderBy('month_no')
            ->get();
        $monthRowsByNo = $monthRows->keyBy('month_no');
        $reviewRows = $monthRows->isEmpty()
            ? collect()
            : KpiMonthReview::query()
                ->whereIn('kpi_month_score_id', $monthRows->pluck('id')->all())
                ->get(['kpi_month_score_id', 'status', 'reviewed_by_user_id']);
        $reviewRowsByScoreId = $reviewRows->keyBy('kpi_month_score_id');

        $reviewerUserIds = $reviewRows->pluck('reviewed_by_user_id')
            ->filter(fn ($id) => (int) $id > 0)->unique()->values()->all();
        $reviewerUsersById = $reviewerUserIds !== []
            ? AppUser::query()->whereIn('id', $reviewerUserIds)
                ->get(['id', 'full_name_th', 'full_name_en'])->keyBy('id')
            : collect();

        $months = [];
        $netAverageTotal = 0.0;
        $netAverageCount = 0;
        for ($monthNo = 1; $monthNo <= 12; $monthNo++) {
            /** @var KpiMonthScore|null $monthRow */
            $monthRow = $monthRowsByNo->get($monthNo);
            $scoreValue = $monthRow && $monthRow->score_value !== null
                ? (float) $monthRow->score_value
                : null;
            $reviewStatus = $monthRow
                ? $this->normalizeReviewStatus($reviewRowsByScoreId->get((int) $monthRow->id)?->status)
                : KpiMonthReview::STATUS_PENDING;
            $status = $this->resolveMonthlyRowStatus($monthRow, $scoreValue, $targetValue, $operator, $reviewStatus);

            $canUseScore = $reviewStatus === KpiMonthReview::STATUS_APPROVED && $scoreValue !== null;
            if ($canUseScore) {
                $netAverageTotal += $scoreValue;
                $netAverageCount++;
            }
            $approvedEvidenceFiles = $reviewStatus === KpiMonthReview::STATUS_APPROVED
                ? $this->buildMonthlyEvidenceFiles($monthRow, $lang)
                : [];
            $approvedActionPlanFiles = $reviewStatus === KpiMonthReview::STATUS_APPROVED
                ? $this->buildMonthlyEvidenceFiles($monthRow, $lang, 'action_plan')
                : [];

            $reviewRow = $monthRow ? $reviewRowsByScoreId->get((int) $monthRow->id) : null;
            $reviewerName = '';
            if ($reviewStatus === KpiMonthReview::STATUS_APPROVED && $reviewRow && (int) ($reviewRow->reviewed_by_user_id ?? 0) > 0) {
                $reviewerUser = $reviewerUsersById->get((int) $reviewRow->reviewed_by_user_id);
                if ($reviewerUser) {
                    $rn = $lang === 'th'
                        ? trim((string) $reviewerUser->full_name_th)
                        : trim((string) $reviewerUser->full_name_en);
                    if ($rn === '') {
                        $rn = trim((string) ($reviewerUser->full_name_en ?: $reviewerUser->full_name_th));
                    }
                    $reviewerName = $rn;
                }
            }

            $months[] = [
                'score_id' => $monthRow ? (int) $monthRow->id : null,
                'can_delete' => $isAdmin && $monthRow !== null,
                'month_no' => $monthNo,
                'month_label' => $this->monthLabel($monthNo, $lang),
                'score' => $canUseScore ? number_format($scoreValue, 2) : '-',
                'files' => $approvedEvidenceFiles,
                'action_plan_files' => $approvedActionPlanFiles,
                'criteria' => $criteriaText,
                'result' => $this->formatMonthlyResultText($status, $lang),
                'result_class' => $this->formatMonthlyResultClass($status),
                'reviewer_name' => $reviewerName,
            ];
        }

        $netAverageValue = $netAverageCount > 0
            ? ($netAverageTotal / $netAverageCount)
            : null;
        $netAverageScore = $netAverageValue !== null
            ? number_format($netAverageValue, 2)
            : '-';
        $netAverageResult = $rootRow->result !== null
            ? number_format((float) $rootRow->result, 2).'%'
            : ($netAverageValue !== null ? number_format($netAverageValue, 2).'%' : '-');

        return response()->json([
            'title' => $lang === 'th' ? 'สรุปผล KPI รายเดือน' : 'KPI Monthly Summary',
            'employee' => $this->formatEmployeeText($targetUser, $lang),
            'objective' => trim((string) $rootRow->objective) !== '' ? (string) $rootRow->objective : '-',
            'detail' => trim((string) $rootRow->detail) !== '' ? (string) $rootRow->detail : '-',
            'months' => $months,
            'net_average_score' => $netAverageScore,
            'net_average_result' => $netAverageResult,
        ]);
    }

    public function showEvidenceFile(Request $request, KpiMonthScore $score, int $index)
    {
        /** @var AppUser|null $authUser */
        $authUser = $request->user();
        if (! $authUser) {
            abort(401);
        }

        if ($index < 0) {
            abort(404);
        }

        $targetUser = AppUser::query()
            ->whereKey((int) $score->app_user_id)
            ->first(['id', 'position', 'dept_abbr_hr', 'role']);

        if (! $targetUser) {
            abort(404);
        }

        $isAdmin = $this->isAdminRole($authUser->role);
        if (! $this->canAccessDepartmentSummary($authUser, $isAdmin)) {
            abort(403);
        }

        $rootRow = $this->findRootSummaryRow((int) ($score->kpi_meta_id ?: $score->id));
        if (! $rootRow) {
            abort(404);
        }

        $accessibleDepartments = $isAdmin ? null : $this->resolveAccessibleDepartments($authUser);
        if (! $this->canViewKpiRootDepartment($targetUser, $rootRow, $isAdmin, $accessibleDepartments)) {
            abort(403);
        }


        $fileType = strtolower(trim((string) $request->query('type', 'evidence')));
        $filesSource = $fileType === 'action_plan'
            ? $score->action_plan_files
            : $score->evidence_files;
        $files = is_array($filesSource) ? array_values($filesSource) : [];
        $target = $files[$index] ?? null;
        if (! is_array($target)) {
            abort(404);
        }

        $path = trim((string) ($target['path'] ?? ''));
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($path);
        $mime = (string) (Storage::disk('public')->mimeType($path) ?: '');
        if ($mime === '' || $mime === 'application/octet-stream') {
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
                'heic' => 'image/heic',
                'heif' => 'image/heif',
                'pdf' => 'application/pdf',
                default => 'application/octet-stream',
            };
        }

        $downloadName = trim((string) ($target['name'] ?? ''));
        if ($downloadName === '') {
            $downloadName = basename($path);
        }
        $downloadName = str_replace(["\r", "\n"], '', $downloadName);
        if ($downloadName === '') {
            $downloadName = basename($path);
        }

        return response()->download(
            $absolutePath,
            $downloadName,
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, no-cache',
            ],
            'inline'
        );
    }

    private function buildRows(
        int $cycleId,
        ?array $accessibleDepartments,
        string $lang,
        string $q = '',
        array $unitLabelMap = [],
        string $selectedDepartment = ''
    ): array
    {
        $normalizedAccessibleDepartments = $accessibleDepartments === null
            ? null
            : array_values(array_unique(array_filter(array_map(
                fn ($departmentCode) => $this->normalizeDepartmentCode((string) $departmentCode),
                $accessibleDepartments
            ))));
        $normalizedSelectedDepartment = $this->normalizeDepartmentCode($selectedDepartment);

        $usersQuery = AppUser::query()
            ->whereNotNull('position')
            ->where('position', '!=', '');

        if ($q !== '') {
            $like = '%'.$q.'%';
            $usersQuery->where(function ($query) use ($like) {
                $query->where('employee_code', 'like', $like)
                    ->orWhere('full_name_th', 'like', $like)
                    ->orWhere('full_name_en', 'like', $like);
            });
        }

        $users = $usersQuery
            ->orderBy('dept_abbr_hr')
            ->orderBy('position')
            ->orderBy('employee_code')
            ->get(['id', 'employee_code', 'full_name_th', 'full_name_en', 'position', 'dept_abbr_hr']);

        if ($users->isEmpty()) {
            return [];
        }

        $userIds = $users->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        $usersById = $users->keyBy('id');

        $rootRows = KpiMonthScore::query()
            ->where('cycle_id', $cycleId)
            ->whereIn('app_user_id', $userIds)
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('month_no', 0)
                        ->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            })
            ->whereNotNull('result')
            ->orderBy('id')
            ->get();

        if ($rootRows->isEmpty()) {
            return [];
        }

        // ── Reviewer name per dept ───────────────────────────────────────────────
        $assignments = AdminDepartmentAssignment::query()
            ->whereNotNull('reviewer_user_id')
            ->where('reviewer_user_id', '>', 0)
            ->get(['dept_abbr_hr', 'reviewer_user_id']);

        $reviewerUserIds = $assignments->pluck('reviewer_user_id')
            ->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $reviewerUsersById = $reviewerUserIds !== []
            ? AppUser::query()->whereIn('id', $reviewerUserIds)
                ->get(['id', 'full_name_th', 'full_name_en'])->keyBy('id')
            : collect();

        $reviewerNameByDept = [];
        foreach ($assignments as $assignment) {
            $dept = $this->normalizeDepartmentCode((string) ($assignment->dept_abbr_hr ?? ''));
            if ($dept === '') {
                continue;
            }
            $reviewerUser = $reviewerUsersById->get((int) ($assignment->reviewer_user_id ?? 0));
            if (! $reviewerUser) {
                continue;
            }
            $rName = $lang === 'th'
                ? trim((string) $reviewerUser->full_name_th)
                : trim((string) $reviewerUser->full_name_en);
            if ($rName === '') {
                $rName = trim((string) ($reviewerUser->full_name_en ?: $reviewerUser->full_name_th));
            }
            $reviewerNameByDept[$dept] = $rName ?: '-';
        }

        // ── Per-root approved / pending flags ────────────────────────────────────
        $rootIds = $rootRows->pluck('id')->map(fn ($id) => (int) $id)->all();

        $submittedMonths = KpiMonthScore::query()
            ->whereIn('kpi_meta_id', $rootIds)
            ->whereBetween('month_no', [1, 12])
            ->whereNotNull('submitted_at')
            ->get(['id', 'kpi_meta_id']);

        $monthIdsByRootId = [];
        foreach ($submittedMonths as $sm) {
            $monthIdsByRootId[(int) $sm->kpi_meta_id][] = (int) $sm->id;
        }

        $allMonthIds = collect($monthIdsByRootId)->flatten()->unique()->values()->all();
        $reviewsByMonthId = $allMonthIds !== []
            ? KpiMonthReview::query()
                ->whereIn('kpi_month_score_id', $allMonthIds)
                ->get(['kpi_month_score_id', 'status'])
                ->keyBy('kpi_month_score_id')
            : collect();

        $hasApprovedByRootId = [];
        $hasPendingByRootId  = [];
        foreach ($monthIdsByRootId as $rootId => $monthIds) {
            $hasApproved = false;
            $hasPending  = false;
            foreach ($monthIds as $monthId) {
                $status = $this->normalizeReviewStatus($reviewsByMonthId->get($monthId)?->status);
                if ($status === KpiMonthReview::STATUS_APPROVED) {
                    $hasApproved = true;
                } elseif ($status === KpiMonthReview::STATUS_PENDING) {
                    $hasPending = true;
                }
            }
            $hasApprovedByRootId[$rootId] = $hasApproved;
            $hasPendingByRootId[$rootId]  = $hasPending;
        }

        $goalTopicLabels = $this->buildGoalTopicLabelMap($rootRows->pluck('parent_target_kpi_id')->all(), $lang);
        $rowBuckets = [];
        foreach ($rootRows as $root) {
            $user = $usersById->get((int) ($root->app_user_id ?? 0));
            if (! $user instanceof AppUser) {
                continue;
            }

            $ownerDepartment = $this->normalizeDepartmentCode((string) ($user->dept_abbr_hr ?? ''));
            $selectedDepartmentForRow = $this->resolveRootSelectedDepartment($user, $root);
            if ($selectedDepartmentForRow === '') {
                continue;
            }
            $canShowSelectedDepartment = $this->canUseSelectedDepartmentByPosition(
                $this->normalizePositionKey((string) ($user->position ?? ''))
            );
            $selectedDepartmentDisplay = '-';
            if ($canShowSelectedDepartment) {
                foreach ((array) ($root->target_departments ?? []) as $departmentCode) {
                    $normalizedDepartment = $this->normalizeDepartmentCode((string) $departmentCode);
                    if ($normalizedDepartment !== '') {
                        $selectedDepartmentDisplay = $normalizedDepartment;
                        break;
                    }
                }
            }
            $groupDepartmentForRow = $selectedDepartmentForRow !== ''
                ? $selectedDepartmentForRow
                : ($ownerDepartment !== '' ? $ownerDepartment : '-');

            if (
                $normalizedAccessibleDepartments !== null
                && ! in_array($selectedDepartmentForRow, $normalizedAccessibleDepartments, true)
            ) {
                continue;
            }

            if ($normalizedSelectedDepartment !== '' && $selectedDepartmentForRow !== $normalizedSelectedDepartment) {
                continue;
            }

            $result = $root->result !== null ? (float) $root->result : null;
            if ($result === null) {
                continue;
            }

            $bucketKey = ((int) $user->id).'|'.$selectedDepartmentForRow;
            if (! isset($rowBuckets[$bucketKey])) {
                $rowBuckets[$bucketKey] = [
                    'user_id' => (int) $user->id,
                    'department' => $ownerDepartment !== '' ? $ownerDepartment : '-',
                    'group_department' => $groupDepartmentForRow,
                    'selected_department' => $selectedDepartmentDisplay,
                    'employee_parts' => $this->formatEmployeeParts($user, $lang),
                    'entries' => [],
                    'result_values' => [],
                ];
            }
            if (
                ($rowBuckets[$bucketKey]['selected_department'] ?? '-') === '-'
                && $selectedDepartmentDisplay !== '-'
            ) {
                $rowBuckets[$bucketKey]['selected_department'] = $selectedDepartmentDisplay;
            }

            $operator = trim((string) $root->criteria_operator);
            $target = $root->target_value !== null ? (float) $root->target_value : null;
            $unitText = $this->resolveUnitLabel($root->kpi_unit_id, $unitLabelMap);
            $goalTopicId = (int) ($root->parent_target_kpi_id ?? 0);
            $fallbackLabel = $goalTopicId > 0 ? $this->formatFallbackGoalTopicLabel($goalTopicId, $lang) : '-';
            $goalTopicMap = $goalTopicId > 0
                ? ($goalTopicLabels[$goalTopicId] ?? ['l1' => '', 'l2' => '', 'l3' => $fallbackLabel, 'label' => $fallbackLabel])
                : ['l1' => '', 'l2' => '', 'l3' => '-', 'label' => '-'];

            $rootIntId = (int) $root->id;
            $rowBuckets[$bucketKey]['entries'][] = [
                'root_id' => $rootIntId,
                'goal_topic_id' => $goalTopicId,
                'goal_topic' => $goalTopicMap['label'],
                'goal_topic_l1' => $goalTopicMap['l1'],
                'goal_topic_l2' => $goalTopicMap['l2'],
                'goal_topic_l3' => $goalTopicMap['l3'],
                'objective' => trim((string) $root->objective) !== '' ? (string) $root->objective : '-',
                'detail' => trim((string) $root->detail) !== '' ? (string) $root->detail : '-',
                'target' => $this->formatTargetText($target, $operator, $unitText, $lang),
                'result_value' => $result,
                'result' => number_format($result, 2).'%',
                'has_approved' => $hasApprovedByRootId[$rootIntId] ?? false,
                'has_pending' => $hasPendingByRootId[$rootIntId] ?? false,
                'reviewer_name' => $reviewerNameByDept[$selectedDepartmentForRow] ?? '-',
            ];
            $rowBuckets[$bucketKey]['result_values'][] = $result;
        }

        if ($rowBuckets === []) {
            return [];
        }

        $rows = [];
        foreach ($rowBuckets as $bucket) {
            $entries = is_array($bucket['entries'] ?? null) ? $bucket['entries'] : [];
            $resultValues = is_array($bucket['result_values'] ?? null) ? $bucket['result_values'] : [];
            if ($entries === [] || $resultValues === []) {
                continue;
            }

            $averageResult = array_sum($resultValues) / count($resultValues);
            $rows[] = [
                'no' => 0,
                'employee_count' => 0,
                'department' => (string) ($bucket['department'] ?? '-'),
                'group_department' => (string) ($bucket['group_department'] ?? ($bucket['department'] ?? '-')),
                'selected_department' => (string) ($bucket['selected_department'] ?? '-'),
                'employee_parts' => $bucket['employee_parts'] ?? [
                    'code' => '-',
                    'name' => '-',
                    'position' => '-',
                ],
                'entries' => $entries,
                'avg_result' => number_format((float) $averageResult, 2).'%',
                '_user_id' => (int) ($bucket['user_id'] ?? 0),
            ];
        }

        if ($rows === []) {
            return [];
        }

        usort($rows, function (array $left, array $right): int {
            $leftDepartment = strtoupper(trim((string) ($left['group_department'] ?? ($left['department'] ?? ''))));
            $rightDepartment = strtoupper(trim((string) ($right['group_department'] ?? ($right['department'] ?? ''))));
            $departmentCompare = strcmp($leftDepartment, $rightDepartment);
            if ($departmentCompare !== 0) {
                return $departmentCompare;
            }

            $leftCode = strtolower(trim((string) ($left['employee_parts']['code'] ?? '')));
            $rightCode = strtolower(trim((string) ($right['employee_parts']['code'] ?? '')));
            $codeCompare = strcmp($leftCode, $rightCode);
            if ($codeCompare !== 0) {
                return $codeCompare;
            }

            $leftSelectedDepartment = strtoupper(trim((string) ($left['selected_department'] ?? '')));
            $rightSelectedDepartment = strtoupper(trim((string) ($right['selected_department'] ?? '')));
            return strcmp($leftSelectedDepartment, $rightSelectedDepartment);
        });

        return $this->renumberSummaryRows($rows);
    }

    private function buildGoalTopicLabelMap(array $rawIds, string $lang): array
    {
        $ids = collect($rawIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $scores = KpiMonthScore::query()
            ->whereIn('id', $ids)
            ->get(['id', 'objective', 'okr_objective_id', 'okr_key_result_id']);

        $objectiveIds = $scores
            ->pluck('okr_objective_id')
            ->map(static fn ($v): int => (int) $v)
            ->filter(static fn (int $v): bool => $v > 0)
            ->unique()->values()->all();

        $keyResultIds = $scores
            ->pluck('okr_key_result_id')
            ->map(static fn ($v): int => (int) $v)
            ->filter(static fn (int $v): bool => $v > 0)
            ->unique()->values()->all();

        $l1ByObjectiveId = $objectiveIds !== []
            ? OkrObjective::query()
                ->whereIn('id', $objectiveIds)
                ->get(['id', 'title'])
                ->mapWithKeys(fn (OkrObjective $obj): array => [
                    (int) $obj->id => trim((string) ($obj->title ?? '')),
                ])
                ->all()
            : [];

        $l2ByKeyResultId = $keyResultIds !== []
            ? OkrKeyResult::query()
                ->whereIn('id', $keyResultIds)
                ->get(['id', 'title'])
                ->mapWithKeys(fn (OkrKeyResult $kr): array => [
                    (int) $kr->id => trim((string) ($kr->title ?? '')),
                ])
                ->all()
            : [];

        return $scores
            ->mapWithKeys(function (KpiMonthScore $score) use ($lang, $l1ByObjectiveId, $l2ByKeyResultId): array {
                $id = (int) $score->id;
                $l3 = trim((string) ($score->objective ?? ''));
                if ($l3 === '') {
                    $l3 = $this->formatFallbackGoalTopicLabel($id, $lang);
                }
                $l1 = ($v = (int) ($score->okr_objective_id ?? 0)) > 0 ? ($l1ByObjectiveId[$v] ?? '') : '';
                $l2 = ($v = (int) ($score->okr_key_result_id ?? 0)) > 0 ? ($l2ByKeyResultId[$v] ?? '') : '';

                return [
                    $id => [
                        'l1' => $l1,
                        'l2' => $l2,
                        'l3' => $l3,
                        'label' => $l3,
                    ],
                ];
            })
            ->all();
    }

    private function formatFallbackGoalTopicLabel(int $id, string $lang): string
    {
        return $lang === 'th'
            ? 'หัวข้อเป้าหมาย #'.$id
            : 'Goal Topic #'.$id;
    }

    private function buildGoalTopicOptions(array $rows): array
    {
        $optionsById = [];
        foreach ($rows as $row) {
            foreach ((array) ($row['entries'] ?? []) as $entry) {
                $id = (int) ($entry['goal_topic_id'] ?? 0);
                if ($id < 1 || isset($optionsById[$id])) {
                    continue;
                }

                $label = trim((string) ($entry['goal_topic'] ?? ''));
                $optionsById[$id] = [
                    'id' => $id,
                    'label' => $label !== '' ? $label : 'Goal Topic #'.$id,
                ];
            }
        }

        $options = array_values($optionsById);
        usort($options, static function (array $left, array $right): int {
            return strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        });

        return $options;
    }

    private function filterRowsByGoalTopic(array $rows, int $goalTopicId): array
    {
        if ($goalTopicId < 1) {
            return $rows;
        }

        $filteredRows = [];
        foreach ($rows as $row) {
            $entries = array_values(array_filter(
                (array) ($row['entries'] ?? []),
                static fn (array $entry): bool => (int) ($entry['goal_topic_id'] ?? 0) === $goalTopicId
            ));

            if ($entries === []) {
                continue;
            }

            $resultValues = array_values(array_filter(
                array_map(static fn (array $entry): ?float => isset($entry['result_value']) ? (float) $entry['result_value'] : null, $entries),
                static fn (?float $value): bool => $value !== null
            ));

            $row['entries'] = $entries;
            if ($resultValues !== []) {
                $row['avg_result'] = number_format(array_sum($resultValues) / count($resultValues), 2).'%';
            }
            $filteredRows[] = $row;
        }

        return $this->renumberSummaryRows($filteredRows);
    }

    private function renumberSummaryRows(array $rows): array
    {
        $departmentEmployeeCounts = [];
        foreach ($rows as $row) {
            $departmentKey = strtoupper(trim((string) ($row['group_department'] ?? ($row['department'] ?? ''))));
            if ($departmentKey === '') {
                $departmentKey = '__EMPTY__';
            }

            $userId = (int) ($row['_user_id'] ?? 0);
            if ($userId < 1) {
                continue;
            }

            $departmentEmployeeCounts[$departmentKey] ??= [];
            $departmentEmployeeCounts[$departmentKey][$userId] = true;
        }

        foreach ($rows as $index => $row) {
            $groupDepartment = trim((string) ($row['group_department'] ?? ($row['department'] ?? '')));
            $departmentKey = $groupDepartment !== '' ? strtoupper($groupDepartment) : '__EMPTY__';
            $rows[$index]['no'] = $index + 1;
            $rows[$index]['employee_count'] = isset($departmentEmployeeCounts[$departmentKey])
                ? count($departmentEmployeeCounts[$departmentKey])
                : 0;
            $rows[$index]['group_department'] = $groupDepartment !== '' ? strtoupper($groupDepartment) : '-';
        }

        return array_values($rows);
    }

    private function formatEmployeeParts(AppUser $user, string $lang): array
    {
        $name = $this->resolveEmployeeName($user, $lang);
        $position = $this->formatPositionText($user->position);

        return [
            'code' => trim((string) ($user->employee_code ?? '')),
            'name' => $name !== '' ? $name : '-',
            'position' => $position,
        ];
    }

    private function formatEmployeeText(AppUser $user, string $lang): string
    {
        $parts = $this->formatEmployeeParts($user, $lang);
        return trim($parts['code'].' '.$parts['name'].' '.$parts['position']);
    }

    private function resolveEmployeeName(AppUser $user, string $lang): string
    {
        $name = $lang === 'th'
            ? trim((string) $user->full_name_th)
            : trim((string) $user->full_name_en);

        if ($name === '') {
            $name = trim((string) ($user->full_name_en ?: $user->full_name_th));
        }

        return $name;
    }

    private function formatPositionText(mixed $value): string
    {
        $position = trim((string) ($value ?? ''));
        if ($position === '') {
            return '-';
        }

        $normalized = preg_replace('/\s+/', ' ', strtolower($position)) ?? strtolower($position);
        return ucwords($normalized);
    }

    private function buildUnitLabelMap(string $lang): array
    {
        return KpiUnit::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name_th', 'name_en'])
            ->mapWithKeys(function (KpiUnit $unit) use ($lang) {
                $th = trim((string) $unit->name_th);
                $en = trim((string) $unit->name_en);
                $code = trim((string) $unit->code);
                $label = $lang === 'th'
                    ? ($th !== '' ? $th : ($en !== '' ? $en : $code))
                    : ($en !== '' ? $en : ($th !== '' ? $th : $code));

                return [(int) $unit->id => $label];
            })
            ->all();
    }

    private function resolveUnitLabel(mixed $unitId, array $unitLabelMap): string
    {
        $id = (int) ($unitId ?? 0);
        if ($id < 1) {
            return '';
        }

        return trim((string) ($unitLabelMap[$id] ?? ''));
    }

    private function buildMonthlyEvidenceFiles(?KpiMonthScore $monthRow, string $lang, string $type = 'evidence'): array
    {
        $items = [];

        if (! $monthRow instanceof KpiMonthScore) {
            return $items;
        }

        $filesSource = $type === 'action_plan'
            ? $monthRow->action_plan_files
            : $monthRow->evidence_files;
        $files = is_array($filesSource) ? array_values($filesSource) : [];
        foreach ($files as $index => $file) {
            if (! is_array($file)) {
                continue;
            }

            $path = trim((string) ($file['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $name = trim((string) ($file['name'] ?? ''));
            if ($name === '') {
                $name = basename($path);
            }

            $items[] = [
                'name' => $name,
                'url' => route('kpi.summary.dept.evidence.show', [
                    'score' => (int) $monthRow->id,
                    'index' => (int) $index,
                    'lang' => $lang,
                    'type' => $type,
                ]),
            ];
        }

        return $items;
    }

    private function formatTargetText(?float $target, string $operator, string $unitText, string $lang): string
    {
        if ($target === null) {
            return '-';
        }

        $targetNumber = number_format($target, 2);
        $targetWithUnit = trim($targetNumber.' '.$unitText);
        if ($operator === '') {
            return $targetWithUnit;
        }

        $labelsTh = [
            '>' => 'มากกว่า',
            '>=' => 'มากกว่าหรือเท่ากับ',
            '<' => 'น้อยกว่า',
            '<=' => 'น้อยกว่าหรือเท่ากับ',
            '=' => 'เท่ากับ',
            '!=' => 'ไม่เท่ากับ',
        ];
        $labelsEn = [
            '>' => 'Greater than',
            '>=' => 'Greater than or equal to',
            '<' => 'Less than',
            '<=' => 'Less than or equal to',
            '=' => 'Equal to',
            '!=' => 'Not equal to',
        ];

        $text = $lang === 'th'
            ? ($labelsTh[$operator] ?? 'เงื่อนไข')
            : ($labelsEn[$operator] ?? 'Criteria');

        return $text.' '.$targetWithUnit.' ('.$this->operatorSymbol($operator).')';
    }

    private function findRootSummaryRow(int $rootId): ?KpiMonthScore
    {
        return KpiMonthScore::query()
            ->whereKey($rootId)
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('month_no', 0)
                        ->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            })
            ->first();
    }

    private function canViewKpiRootDepartment(
        AppUser $targetUser,
        KpiMonthScore $rootRow,
        bool $isAdmin,
        ?array $accessibleDepartments
    ): bool {
        if ($isAdmin) {
            return true;
        }

        if ($accessibleDepartments === null || $accessibleDepartments === []) {
            return false;
        }

        $normalizedAccessibleDepartments = array_values(array_unique(array_filter(array_map(
            fn ($departmentCode) => $this->normalizeDepartmentCode((string) $departmentCode),
            $accessibleDepartments
        ))));
        if ($normalizedAccessibleDepartments === []) {
            return false;
        }

        $selectedDepartment = $this->resolveRootSelectedDepartment($targetUser, $rootRow);
        return $selectedDepartment !== '' && in_array($selectedDepartment, $normalizedAccessibleDepartments, true);
    }

    private function resolveRootSelectedDepartment(AppUser $targetUser, KpiMonthScore $rootRow): string
    {
        $ownerDepartment = $this->normalizeDepartmentCode((string) ($targetUser->dept_abbr_hr ?? ''));
        $position = $this->normalizePositionKey((string) ($targetUser->position ?? ''));

        if ($this->canUseSelectedDepartmentByPosition($position)) {
            foreach ((array) ($rootRow->target_departments ?? []) as $departmentCode) {
                $normalizedDepartment = $this->normalizeDepartmentCode((string) $departmentCode);
                if ($normalizedDepartment !== '') {
                    return $normalizedDepartment;
                }
            }
        }

        return $ownerDepartment;
    }

    private function canUseSelectedDepartmentByPosition(string $position): bool
    {
        $normalizedPosition = $this->normalizePositionKey($position);
        if ($normalizedPosition === '') {
            return false;
        }

        return in_array($normalizedPosition, $this->targetDepartmentPositions(), true);
    }

    private function targetDepartmentPositions(): array
    {
        static $positions = null;

        if (is_array($positions)) {
            return $positions;
        }

        $normalized = [];
        foreach (self::KPI_TARGET_DEPARTMENT_POSITIONS as $position) {
            $key = $this->normalizePositionKey($position);
            if ($key !== '') {
                $normalized[] = $key;
            }
        }

        $positions = array_values(array_unique($normalized));
        return $positions;
    }

    private function normalizeDepartmentCode(mixed $value): string
    {
        $text = strtoupper(trim((string) ($value ?? '')));
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return $text;
    }

    private function formatMonthlyCriteriaText(?float $targetValue, string $operator, string $unitText): string
    {
        if ($targetValue === null) {
            return '-';
        }

        $targetNumber = number_format($targetValue, 2);
        $targetWithUnit = trim($targetNumber.' '.$unitText);
        if ($operator === '') {
            return $targetWithUnit;
        }

        return trim($this->operatorSymbol($operator).' '.$targetWithUnit);
    }

    private function normalizeReviewStatus(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        return match ($value) {
            KpiMonthReview::STATUS_APPROVED => KpiMonthReview::STATUS_APPROVED,
            KpiMonthReview::STATUS_REJECTED => KpiMonthReview::STATUS_REJECTED,
            default => KpiMonthReview::STATUS_PENDING,
        };
    }

    private function resolveMonthlyRowStatus(
        ?KpiMonthScore $monthRow,
        ?float $scoreValue,
        ?float $targetValue,
        string $operator,
        string $reviewStatus
    ): string {
        if (! $monthRow || $scoreValue === null) {
            return 'not_saved';
        }

        if ($reviewStatus === KpiMonthReview::STATUS_REJECTED) {
            return 'rejected';
        }
        if ($reviewStatus !== KpiMonthReview::STATUS_APPROVED) {
            return 'pending_review';
        }

        if ($targetValue !== null && $operator !== '') {
            return $this->resolveCriteriaStatus($scoreValue, $targetValue, $operator);
        }

        if ($monthRow->is_pass !== null) {
            return (bool) $monthRow->is_pass ? 'pass' : 'fail';
        }

        return 'pending';
    }

    private function formatMonthlyResultText(string $status, string $lang): string
    {
        return match ($status) {
            'pending_review' => $lang === 'th' ? 'รอการดำเนินการ' : 'Pending action',
            'rejected' => $lang === 'th' ? 'ปฏิเสธรายงาน' : 'Rejected',
            'pass' => $lang === 'th' ? 'ผ่านเกณฑ์' : 'Pass criteria',
            'fail' => $lang === 'th' ? 'ไม่ผ่านเกณฑ์' : 'Not pass criteria',
            'not_saved' => $lang === 'th' ? 'ยังไม่บันทึก' : 'Not saved',
            default => '-',
        };
    }

    private function formatMonthlyResultClass(string $status): string
    {
        return match ($status) {
            'pass' => 'kpi-summary-result-pass',
            'fail', 'rejected' => 'kpi-summary-result-fail',
            'pending_review' => 'kpi-summary-result-pending-review',
            default => 'kpi-summary-result-pending',
        };
    }

    private function resolveCriteriaStatus(?float $score, ?float $target, string $operator): string
    {
        if ($score === null || $target === null || $operator === '') {
            return 'pending';
        }

        $isPass = match ($operator) {
            '>' => $score > $target,
            '>=' => $score >= $target,
            '<=' => $score <= $target,
            '<' => $score < $target,
            '=' => $score === $target,
            '!=' => $score !== $target,
            default => false,
        };

        return $isPass ? 'pass' : 'fail';
    }

    private function monthLabel(int $monthNo, string $lang): string
    {
        $labelsTh = [
            1 => 'มกราคม',
            2 => 'กุมภาพันธ์',
            3 => 'มีนาคม',
            4 => 'เมษายน',
            5 => 'พฤษภาคม',
            6 => 'มิถุนายน',
            7 => 'กรกฎาคม',
            8 => 'สิงหาคม',
            9 => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม',
        ];
        $labelsEn = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        return ($lang === 'th' ? $labelsTh[$monthNo] ?? null : $labelsEn[$monthNo] ?? null) ?? (string) $monthNo;
    }

    private function operatorSymbol(string $operator): string
    {
        return match ($operator) {
            '>=' => '≥',
            '<=' => '≤',
            '!=' => '≠',
            default => $operator,
        };
    }

    private function isAdminRole(?string $role): bool
    {
        return strtolower(trim((string) $role)) === 'admin';
    }

    private function canAccessDepartmentSummary(?AppUser $authUser, bool $isAdmin): bool
    {
        if (! $authUser) {
            return false;
        }

        if ($isAdmin) {
            return true;
        }

        if (in_array(
            $this->normalizePositionKey($authUser->position),
            $this->allowedViewerPositions(),
            true
        )) {
            return true;
        }

        return DepartmentAssignmentResolver::resolveTargetDepartmentsByUserId((int) $authUser->id) !== [];
    }

    private function allowedViewerPositions(): array
    {
        $configured = config('menu_visibility.buttons.menuKpiDept.visible_to.positions', []);
        $candidates = is_array($configured) && $configured !== []
            ? $configured
            : self::KPI_DEPT_VIEWER_POSITIONS_DEFAULT;

        $positions = [];
        foreach ($candidates as $position) {
            $normalized = $this->normalizePositionKey($position);
            if ($normalized !== '') {
                $positions[] = $normalized;
            }
        }

        return array_values(array_unique($positions));
    }

    private function resolveDepartmentOptions(?array $accessibleDepartments): array
    {
        if ($accessibleDepartments !== null) {
            $options = array_values(array_unique(array_filter(array_map(
                fn ($departmentCode) => $this->normalizeDepartmentCode((string) $departmentCode),
                $accessibleDepartments
            ))));
            sort($options, SORT_STRING);

            return $options;
        }

        $options = AppUser::query()
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->pluck('dept_abbr_hr')
            ->map(fn ($departmentCode) => $this->normalizeDepartmentCode((string) $departmentCode))
            ->filter(fn ($departmentCode) => $departmentCode !== '')
            ->unique()
            ->values()
            ->all();
        sort($options, SORT_STRING);

        return $options;
    }

    private function resolveAccessibleDepartments(?AppUser $authUser): array
    {
        if (! $authUser) {
            return [];
        }

        $departments = [];
        $ownDepartment = strtoupper(trim((string) ($authUser->dept_abbr_hr ?? '')));
        if ($ownDepartment !== '') {
            $departments[] = $ownDepartment;
        }

        foreach (DepartmentAssignmentResolver::resolveTargetDepartmentsByUserId((int) $authUser->id) as $departmentCode) {
            $normalizedCode = $this->normalizeDepartmentCode((string) $departmentCode);
            if ($normalizedCode !== '') {
                $departments[] = $normalizedCode;
            }
        }

        return array_values(array_unique($departments));
    }

    private function normalizePositionKey(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return strtolower($text);
    }
}




