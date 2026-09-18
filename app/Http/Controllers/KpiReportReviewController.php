<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\CycleMonth;
use App\Models\KpiMonthReview;
use App\Models\KpiMonthScore;
use App\Models\KpiResult;
use App\Models\KpiUnit;
use App\Models\OkrAllResult;
use App\Models\OkrResult;
use App\Support\DepartmentAssignmentResolver;
use App\Support\PlainTextNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KpiReportReviewController extends Controller
{
    public function index(Request $request)
    {
        /** @var AppUser|null $user */
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $lang = $this->resolveLang($request);
        $reviewerDepartments = DepartmentAssignmentResolver::resolveReviewerDepartmentsByUserId((int) $user->id);

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

        $selectedDepartment = $this->normalizeDepartmentCode((string) $request->query('dept', ''));
        if (
            $selectedDepartment !== ''
            && ! in_array($selectedDepartment, $reviewerDepartments, true)
        ) {
            $selectedDepartment = '';
        }

        $searchText = trim((string) $request->query('q', ''));
        $rows = [];
        $stats = [
            'submitted' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        if ($selectedCycle && $reviewerDepartments !== []) {
            [$rows, $stats] = $this->buildReviewRows(
                (int) $selectedCycle->id,
                $reviewerDepartments,
                $selectedDepartment,
                $searchText,
                $lang
            );
        }

        return view('kpi-review', [
            'lang' => $lang,
            'cycles' => $cycles,
            'selectedCycle' => $selectedCycle,
            'reviewerDepartments' => $reviewerDepartments,
            'selectedDepartment' => $selectedDepartment,
            'searchText' => $searchText,
            'rows' => $rows,
            'stats' => $stats,
            'canReview' => $reviewerDepartments !== [],
        ]);
    }

    public function approve(Request $request, KpiMonthScore $score): JsonResponse
    {
        return $this->applyDecision($request, $score, KpiMonthReview::STATUS_APPROVED);
    }

    public function reject(Request $request, KpiMonthScore $score): JsonResponse
    {
        $validated = $request->validate([
            'reject_detail' => ['nullable', 'string', 'max:5000'],
        ]);

        return $this->applyDecision(
            $request,
            $score,
            KpiMonthReview::STATUS_REJECTED,
            PlainTextNormalizer::normalize($validated['reject_detail'] ?? '')
        );
    }

    public function showEvidenceFile(Request $request, KpiMonthScore $score, int $index)
    {
        /** @var AppUser|null $reviewer */
        $reviewer = $request->user();
        if (! $reviewer) {
            abort(401);
        }

        if ($index < 0) {
            abort(404);
        }

        if ((int) ($score->month_no ?? 0) < 1 || (int) ($score->month_no ?? 0) > 12) {
            abort(404);
        }
        if (! $score->submitted_at) {
            abort(404);
        }

        $rootId = (int) ($score->kpi_meta_id ?? 0);
        if ($rootId < 1) {
            abort(404);
        }

        $reviewerDepartments = DepartmentAssignmentResolver::resolveReviewerDepartmentsByUserId((int) $reviewer->id);
        if ($reviewerDepartments === []) {
            abort(403);
        }

        /** @var KpiMonthScore|null $root */
        $root = KpiMonthScore::query()
            ->whereKey($rootId)
            ->first([
                'id',
                'app_user_id',
                'cycle_id',
                'target_departments',
            ]);
        if (! $root) {
            abort(404);
        }

        if (
            (int) ($score->app_user_id ?? 0) !== (int) ($root->app_user_id ?? 0)
            || (int) ($score->cycle_id ?? 0) !== (int) ($root->cycle_id ?? 0)
        ) {
            abort(404);
        }

        /** @var AppUser|null $owner */
        $owner = AppUser::query()
            ->whereKey((int) $root->app_user_id)
            ->first([
                'id',
                'dept_abbr_hr',
            ]);
        if (! $owner) {
            abort(404);
        }

        $rootDepartment = $this->resolveRootDepartmentCode($root, $owner);
        if ($rootDepartment === '' || ! in_array($rootDepartment, $reviewerDepartments, true)) {
            abort(403);
        }
        $fileType = strtolower(trim((string) $request->query('type', 'evidence')));
        $fileType = $fileType === 'action_plan' ? 'action_plan' : 'evidence';
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

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, int>}
     */
    private function buildReviewRows(
        int $cycleId,
        array $reviewerDepartments,
        string $selectedDepartment,
        string $searchText,
        string $lang
    ): array {
        $allowedDepartments = $selectedDepartment !== ''
            ? [$selectedDepartment]
            : $reviewerDepartments;
        $allowedSet = array_fill_keys($allowedDepartments, true);

        $submittedMonthRows = KpiMonthScore::query()
            ->where('cycle_id', $cycleId)
            ->whereBetween('month_no', [1, 12])
            ->whereNotNull('kpi_meta_id')
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get([
                'id',
                'app_user_id',
                'cycle_id',
                'kpi_meta_id',
                'month_no',
                'score_value',
                'is_pass',
                'submitted_at',
                'evidence_files',
                'action_plan_files',
            ]);

        if ($submittedMonthRows->isEmpty()) {
            return [[], [
                'submitted' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
            ]];
        }

        $rootIds = $submittedMonthRows->pluck('kpi_meta_id')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        $roots = KpiMonthScore::query()
            ->whereIn('id', $rootIds)
            ->get([
                'id',
                'app_user_id',
                'objective',
                'detail',
                'target_departments',
                'result',
                'criteria_operator',
                'target_value',
                'kpi_unit_id',
            ])
            ->keyBy('id');

        if ($roots->isEmpty()) {
            return [[], [
                'submitted' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
            ]];
        }

        $ownerIds = $roots->pluck('app_user_id')
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
        $owners = AppUser::query()
            ->whereIn('id', $ownerIds)
            ->get([
                'id',
                'employee_code',
                'full_name_th',
                'full_name_en',
                'position',
                'dept_abbr_hr',
            ])
            ->keyBy('id');

        $unitLabelMap = KpiUnit::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'id',
                'code',
                'name_th',
                'name_en',
            ])
            ->mapWithKeys(function (KpiUnit $unit) use ($lang): array {
                $th = trim((string) ($unit->name_th ?? ''));
                $en = trim((string) ($unit->name_en ?? ''));
                $code = trim((string) ($unit->code ?? ''));
                $label = $lang === 'th'
                    ? ($th !== '' ? $th : ($en !== '' ? $en : $code))
                    : ($en !== '' ? $en : ($th !== '' ? $th : $code));

                return [(int) $unit->id => $label];
            })
            ->all();

        $reviewRows = KpiMonthReview::query()
            ->whereIn('kpi_month_score_id', $submittedMonthRows->pluck('id')->all())
            ->get([
                'kpi_month_score_id',
                'status',
                'reviewed_by_user_id',
                'reviewed_at',
                'reject_detail',
            ])
            ->keyBy('kpi_month_score_id');

        $allMonthRows = KpiMonthScore::query()
            ->where('cycle_id', $cycleId)
            ->whereBetween('month_no', [1, 12])
            ->whereIn('kpi_meta_id', $rootIds)
            ->orderBy('id')
            ->get([
                'id',
                'app_user_id',
                'cycle_id',
                'kpi_meta_id',
                'month_no',
                'score_value',
                'is_pass',
                'submitted_at',
                'evidence_files',
                'action_plan_files',
            ]);
        $monthRowsByRootIdAndMonthNo = [];
        /** @var KpiMonthScore $monthRow */
        foreach ($allMonthRows as $monthRow) {
            $rootId = (int) ($monthRow->kpi_meta_id ?? 0);
            $monthNo = (int) ($monthRow->month_no ?? 0);
            if ($rootId < 1 || $monthNo < 1 || $monthNo > 12) {
                continue;
            }

            $current = $monthRowsByRootIdAndMonthNo[$rootId][$monthNo] ?? null;
            if (! $current || (int) ($monthRow->id ?? 0) > (int) ($current->id ?? 0)) {
                $monthRowsByRootIdAndMonthNo[$rootId][$monthNo] = $monthRow;
            }
        }

        $openedMonthMap = array_fill(1, 12, false);
        $cycleMonthRows = CycleMonth::query()
            ->where('cycle_id', $cycleId)
            ->whereBetween('month_no', [1, 12])
            ->get([
                'month_no',
                'open_at',
                'is_active',
            ])
            ->keyBy('month_no');
        $now = now();
        for ($monthNo = 1; $monthNo <= 12; $monthNo++) {
            /** @var CycleMonth|null $cycleMonth */
            $cycleMonth = $cycleMonthRows->get($monthNo);
            if (! $cycleMonth) {
                continue;
            }

            $hasOpenDate = $cycleMonth->open_at !== null;
            $isOpened = (bool) ($cycleMonth->is_active ?? false)
                || ($hasOpenDate && $cycleMonth->open_at?->lte($now));
            $openedMonthMap[$monthNo] = $isOpened;
        }

        $normalizedSearch = $this->normalizeSearch($searchText);
        $employeeBuckets = [];
        $stats = [
            'submitted' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        /** @var KpiMonthScore $monthRow */
        foreach ($submittedMonthRows as $monthRow) {
            $rootId = (int) ($monthRow->kpi_meta_id ?? 0);
            /** @var KpiMonthScore|null $root */
            $root = $roots->get($rootId);
            if (! $root) {
                continue;
            }

            /** @var AppUser|null $owner */
            $owner = $owners->get((int) $root->app_user_id);
            if (! $owner) {
                continue;
            }

            if ($normalizedSearch !== '' && ! $this->matchesSearch($owner, $normalizedSearch)) {
                continue;
            }

            $department = $this->resolveRootDepartmentCode($root, $owner);
            if ($department === '' || ! isset($allowedSet[$department])) {
                continue;
            }

            /** @var KpiMonthReview|null $review */
            $review = $reviewRows->get((int) $monthRow->id);
            $status = $this->normalizeReviewStatus($review?->status);
            $rejectDetail = trim((string) ($review?->reject_detail ?? ''));

            $stats['submitted']++;
            if ($status === KpiMonthReview::STATUS_APPROVED) {
                $stats['approved']++;
            } elseif ($status === KpiMonthReview::STATUS_REJECTED) {
                $stats['rejected']++;
            } else {
                $stats['pending']++;
            }

            $employeeKey = (int) $owner->id.'|'.$department;
            if (! isset($employeeBuckets[$employeeKey])) {
                $employeeBuckets[$employeeKey] = [
                    'employee_id' => (int) $owner->id,
                    'employee_code' => trim((string) ($owner->employee_code ?? '')),
                    'employee_name' => $this->resolveUserName($owner, $lang),
                    'position' => $this->formatPosition((string) ($owner->position ?? '')),
                    'department' => $department,
                    'submitted_count' => 0,
                    'pending_count' => 0,
                    'approved_count' => 0,
                    'rejected_count' => 0,
                    'waiting_score_count' => 0,
                    'report_count' => 0,
                    'reports' => [],
                ];
            }

            $bucket = &$employeeBuckets[$employeeKey];
            $bucket['submitted_count']++;
            if ($status === KpiMonthReview::STATUS_APPROVED) {
                $bucket['approved_count']++;
            } elseif ($status === KpiMonthReview::STATUS_REJECTED) {
                $bucket['rejected_count']++;
            } else {
                $bucket['pending_count']++;
            }

            $reportKey = (string) $rootId;
            if (! isset($bucket['reports'][$reportKey])) {
                $bucket['reports'][$reportKey] = [
                    'root_id' => $rootId,
                    'objective' => trim((string) ($root->objective ?? '')),
                    'detail' => trim((string) ($root->detail ?? '')),
                    'criteria_operator' => $this->normalizeCriteriaOperator((string) ($root->criteria_operator ?? '')),
                    'target_value' => $root->target_value !== null ? (float) $root->target_value : null,
                    'unit_label' => trim((string) ($unitLabelMap[(int) ($root->kpi_unit_id ?? 0)] ?? '')),
                    'submitted_count' => 0,
                    'pending_count' => 0,
                    'approved_count' => 0,
                    'rejected_count' => 0,
                    'waiting_score_count' => 0,
                    'average_result' => null,
                    'department' => $department,
                    'owner_id' => (int) $owner->id,
                    'months' => [],
                ];
            }

            $report = &$bucket['reports'][$reportKey];
            $report['submitted_count']++;
            if ($status === KpiMonthReview::STATUS_APPROVED) {
                $report['approved_count']++;
            } elseif ($status === KpiMonthReview::STATUS_REJECTED) {
                $report['rejected_count']++;
            } else {
                $report['pending_count']++;
            }
            $report['months'][(int) ($monthRow->month_no ?? 0)] = [
                'score_id' => (int) $monthRow->id,
                'month_no' => (int) $monthRow->month_no,
                'score_value' => $monthRow->score_value !== null ? (float) $monthRow->score_value : null,
                'is_pass' => (bool) $monthRow->is_pass,
                'status' => $status,
                'reject_detail' => $rejectDetail,
                'submitted_at' => $monthRow->submitted_at?->format('d/m/Y H:i'),
                'reviewed_at' => optional($review?->reviewed_at)->format('d/m/Y H:i'),
                'evidence_files' => $this->buildMonthlyReviewFiles($monthRow, $lang),
                'action_plan_files' => $this->buildMonthlyReviewFiles($monthRow, $lang, 'action_plan'),
                'opened' => true,
                'can_review' => $status === KpiMonthReview::STATUS_PENDING,
            ];

            unset($report, $bucket);
        }

        if ($employeeBuckets === []) {
            return [[], $stats];
        }

        $rows = array_values($employeeBuckets);
        usort($rows, function (array $left, array $right): int {
            $deptCompare = strcmp(
                strtoupper((string) ($left['department'] ?? '')),
                strtoupper((string) ($right['department'] ?? ''))
            );
            if ($deptCompare !== 0) {
                return $deptCompare;
            }

            return strcmp(
                strtolower((string) ($left['employee_code'] ?? '')),
                strtolower((string) ($right['employee_code'] ?? ''))
            );
        });

        foreach ($rows as &$row) {
            $reports = array_values($row['reports'] ?? []);
            usort($reports, function (array $left, array $right): int {
                return (int) ($left['root_id'] ?? 0) <=> (int) ($right['root_id'] ?? 0);
            });

            foreach ($reports as &$report) {
                $rootId = (int) ($report['root_id'] ?? 0);
                $rootMonthRows = $monthRowsByRootIdAndMonthNo[$rootId] ?? [];
                $months = [];
                $submittedCount = 0;
                $approvedCount = 0;
                $pendingCount = 0;
                $rejectedCount = 0;
                $waitingScoreCount = 0;
                $approvedPassCount = 0;

                for ($monthNo = 1; $monthNo <= 12; $monthNo++) {
                    /** @var KpiMonthScore|null $targetMonthRow */
                    $targetMonthRow = $rootMonthRows[$monthNo] ?? null;
                    $isOpened = (bool) ($openedMonthMap[$monthNo] ?? false);
                    $isSubmitted = $targetMonthRow instanceof KpiMonthScore
                        && $targetMonthRow->submitted_at !== null;

                    if ($isSubmitted) {
                        /** @var KpiMonthReview|null $review */
                        $review = $reviewRows->get((int) $targetMonthRow->id);
                        $status = $this->normalizeReviewStatus($review?->status);
                        $rejectDetail = trim((string) ($review?->reject_detail ?? ''));

                        $submittedCount++;
                        if ($status === KpiMonthReview::STATUS_APPROVED) {
                            $approvedCount++;
                            if ((bool) $targetMonthRow->is_pass) {
                                $approvedPassCount++;
                            }
                        } elseif ($status === KpiMonthReview::STATUS_REJECTED) {
                            $rejectedCount++;
                        } else {
                            $pendingCount++;
                        }

                        $months[] = [
                            'score_id' => (int) $targetMonthRow->id,
                            'month_no' => $monthNo,
                            'score_value' => $targetMonthRow->score_value !== null ? (float) $targetMonthRow->score_value : null,
                            'is_pass' => (bool) $targetMonthRow->is_pass,
                            'status' => $status,
                            'status_key' => $status,
                            'reject_detail' => $rejectDetail,
                            'submitted_at' => $targetMonthRow->submitted_at?->format('d/m/Y H:i'),
                            'reviewed_at' => optional($review?->reviewed_at)->format('d/m/Y H:i'),
                            'evidence_files' => $this->buildMonthlyReviewFiles($targetMonthRow, $lang),
                            'action_plan_files' => $this->buildMonthlyReviewFiles($targetMonthRow, $lang, 'action_plan'),
                            'opened' => true,
                            'can_review' => $status === KpiMonthReview::STATUS_PENDING,
                        ];
                        continue;
                    }

                    if ($isOpened) {
                        $waitingScoreCount++;
                    }

                    $months[] = [
                        'score_id' => $targetMonthRow ? (int) $targetMonthRow->id : null,
                        'month_no' => $monthNo,
                        'score_value' => null,
                        'is_pass' => null,
                        'status' => $isOpened ? 'waiting_score' : 'not_open',
                        'status_key' => $isOpened ? 'waiting_score' : 'not_open',
                        'reject_detail' => '',
                        'submitted_at' => null,
                        'reviewed_at' => null,
                        'evidence_files' => [],
                        'action_plan_files' => [],
                        'opened' => $isOpened,
                        'can_review' => false,
                    ];
                }

                $report['submitted_count'] = $submittedCount;
                $report['approved_count'] = $approvedCount;
                $report['pending_count'] = $pendingCount;
                $report['rejected_count'] = $rejectedCount;
                $report['waiting_score_count'] = $waitingScoreCount;
                // Average KPI Result in review page must reflect only approved months.
                $report['average_result'] = $approvedCount > 0
                    ? round(($approvedPassCount / $approvedCount) * 100, 2)
                    : null;
                $report['months'] = $months;
            }
            unset($report);

            $row['report_count'] = count($reports);
            $row['waiting_score_count'] = array_sum(array_map(
                static fn (array $report): int => (int) ($report['waiting_score_count'] ?? 0),
                $reports
            ));
            // Employee header summary must be the total of all KPI item statuses.
            $row['report_status_approved_count'] = array_sum(array_map(
                static fn (array $report): int => (int) ($report['approved_count'] ?? 0),
                $reports
            ));
            $row['report_status_pending_count'] = array_sum(array_map(
                static fn (array $report): int => (int) ($report['pending_count'] ?? 0),
                $reports
            ));
            $row['report_status_rejected_count'] = array_sum(array_map(
                static fn (array $report): int => (int) ($report['rejected_count'] ?? 0),
                $reports
            ));
            $row['reports'] = $reports;
        }
        unset($row);

        return [$rows, $stats];
    }

    private function applyDecision(
        Request $request,
        KpiMonthScore $score,
        string $decision,
        string $rejectDetail = ''
    ): JsonResponse {
        /** @var AppUser|null $reviewer */
        $reviewer = $request->user();
        if (! $reviewer) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! in_array($decision, [KpiMonthReview::STATUS_APPROVED, KpiMonthReview::STATUS_REJECTED], true)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid decision.',
            ], 422);
        }

        if ((int) ($score->month_no ?? 0) < 1 || (int) ($score->month_no ?? 0) > 12) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid month row.',
            ], 422);
        }
        if ((int) ($score->kpi_meta_id ?? 0) < 1) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid KPI row.',
            ], 422);
        }
        if (! $score->submitted_at) {
            return response()->json([
                'ok' => false,
                'message' => 'This report month has not been submitted yet.',
            ], 422);
        }

        $reviewerDepartments = DepartmentAssignmentResolver::resolveReviewerDepartmentsByUserId((int) $reviewer->id);
        if ($reviewerDepartments === []) {
            return response()->json([
                'ok' => false,
                'message' => 'You are not assigned as report reviewer.',
            ], 403);
        }

        /** @var KpiMonthScore|null $root */
        $root = KpiMonthScore::query()
            ->whereKey((int) $score->kpi_meta_id)
            ->first([
                'id',
                'app_user_id',
                'cycle_id',
                'objective',
                'target_departments',
            ]);
        if (! $root) {
            return response()->json([
                'ok' => false,
                'message' => 'KPI root not found.',
            ], 404);
        }

        /** @var AppUser|null $owner */
        $owner = AppUser::query()
            ->whereKey((int) $root->app_user_id)
            ->first([
                'id',
                'employee_code',
                'full_name_th',
                'full_name_en',
                'position',
                'dept_abbr_hr',
            ]);
        if (! $owner) {
            return response()->json([
                'ok' => false,
                'message' => 'Report owner not found.',
            ], 404);
        }

        $rootDepartment = $this->resolveRootDepartmentCode($root, $owner);
        if ($rootDepartment === '' || ! in_array($rootDepartment, $reviewerDepartments, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'You do not have access to review this report.',
            ], 403);
        }
        $lang = $this->resolveLang($request);

        try {
            DB::transaction(function () use (
                $score,
                $decision,
                $rejectDetail,
                $reviewer,
                $owner,
                $root,
                $lang
            ): void {
                $review = KpiMonthReview::query()
                    ->where('kpi_month_score_id', (int) $score->id)
                    ->lockForUpdate()
                    ->first();

                if (! $review) {
                    $review = KpiMonthReview::query()->create([
                        'kpi_month_score_id' => (int) $score->id,
                        'status' => KpiMonthReview::STATUS_PENDING,
                    ]);
                }

                $currentStatus = $this->normalizeReviewStatus((string) ($review->status ?? ''));
                if (in_array($currentStatus, [KpiMonthReview::STATUS_APPROVED, KpiMonthReview::STATUS_REJECTED], true)) {
                    throw new \RuntimeException('This month has already been reviewed.');
                }

                $review->status = $decision;
                $review->reviewed_by_user_id = (int) $reviewer->id;
                $review->reviewed_at = now();
                $review->reject_detail = $decision === KpiMonthReview::STATUS_REJECTED && $rejectDetail !== ''
                    ? $rejectDetail
                    : null;
                $review->save();

                $this->syncRootResultByApprovedMonths((int) $root->id, (int) $root->app_user_id, (int) $root->cycle_id);
                $decisionText = $decision === KpiMonthReview::STATUS_APPROVED
                    ? ($lang === 'th' ? 'อนุมัติ' : 'approved')
                    : ($lang === 'th' ? 'ปฏิเสธ' : 'rejected');
                $decisionPhrase = $decision === KpiMonthReview::STATUS_APPROVED
                    ? ($lang === 'th' ? 'ถูกอนุมัติแล้ว' : 'has been approved')
                    : ($lang === 'th' ? 'ถูกปฏิเสธแล้ว' : 'has been rejected');

                $objective = trim((string) ($root->objective ?? ''));
                if ($objective === '') {
                    $objective = $lang === 'th' ? 'ไม่ระบุหัวข้อ' : 'Untitled KPI';
                }
                $objectiveForTitle = Str::limit($objective, 80, '...');

                $reviewerName = $this->resolveUserName($reviewer, $lang);
                $title = $lang === 'th'
                    ? "รายงาน KPI หัวข้อ: {$objectiveForTitle} เดือน {$score->month_no} {$decisionPhrase} จาก: {$reviewerName}"
                    : "KPI report topic: {$objectiveForTitle}, month {$score->month_no}, {$decisionPhrase}, from: {$reviewerName}";
                $message = $lang === 'th'
                    ? "สถานะ: {$decisionText}"
                    : "Status: {$decisionText}";

                AppNotification::query()->create([
                    'recipient_user_id' => (int) $owner->id,
                    'actor_user_id' => (int) $reviewer->id,
                    'type' => 'kpi_review_result',
                    'title' => $title,
                    'message' => $message,
                    'link_url' => route('kpi.input', ['lang' => $lang]),
                    'payload_json' => [
                        'score_id' => (int) $score->id,
                        'month_no' => (int) $score->month_no,
                        'decision' => $decision,
                        'objective' => $objective,
                        'reviewer_name' => $reviewerName,
                    ],
                    'is_read' => false,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
        return response()->json([
            'ok' => true,
            'status' => $decision,
            'score_id' => (int) $score->id,
            'reviewed_at' => now()->format('d/m/Y H:i'),
            'reject_detail' => $decision === KpiMonthReview::STATUS_REJECTED ? $rejectDetail : '',
        ]);
    }

    private function syncRootResultByApprovedMonths(int $rootId, int $ownerId, int $cycleId): void
    {
        $monthRows = KpiMonthScore::query()
            ->where('app_user_id', $ownerId)
            ->where('cycle_id', $cycleId)
            ->where('kpi_meta_id', $rootId)
            ->whereBetween('month_no', [1, 12])
            ->get([
                'id',
                'is_pass',
            ]);

        $reviewRows = KpiMonthReview::query()
            ->whereIn('kpi_month_score_id', $monthRows->pluck('id')->all())
            ->get([
                'kpi_month_score_id',
                'status',
            ])
            ->keyBy('kpi_month_score_id');

        $approvedRows = $monthRows->filter(function (KpiMonthScore $monthRow) use ($reviewRows): bool {
            /** @var KpiMonthReview|null $review */
            $review = $reviewRows->get((int) $monthRow->id);
            return $this->normalizeReviewStatus($review?->status) === KpiMonthReview::STATUS_APPROVED;
        });

        $result = null;
        if ($approvedRows->count() > 0) {
            $point = 0;
            /** @var KpiMonthScore $approvedRow */
            foreach ($approvedRows as $approvedRow) {
                $point += (bool) $approvedRow->is_pass ? 100 : 0;
            }
            $max = $approvedRows->count() * 100;
            $result = $max > 0 ? round(($point / $max) * 100, 2) : null;
        }

        KpiMonthScore::query()
            ->where('app_user_id', $ownerId)
            ->where('cycle_id', $cycleId)
            ->where(function ($query) use ($rootId): void {
                $query->where('id', $rootId)
                    ->orWhere('kpi_meta_id', $rootId);
            })
            ->update([
                'result' => $result,
            ]);

        $this->syncUserKpiAndOkrResultsForCycle($ownerId, $cycleId);
    }

    private function syncUserKpiAndOkrResultsForCycle(int $userId, int $cycleId): void
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
            ->get([
                'id',
                'target_departments',
                'result',
            ]);

        if ($rootRows->isEmpty()) {
            KpiResult::query()
                ->where('app_user_id', $userId)
                ->where('cycle_id', $cycleId)
                ->delete();
            $this->syncDepartmentOkrAverageForCycle($cycleId);
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
            KpiResult::query()
                ->where('app_user_id', $userId)
                ->where('cycle_id', $cycleId)
                ->delete();
            $this->syncDepartmentOkrAverageForCycle($cycleId);
            return;
        }

        $hasResultDepartmentColumn = Schema::hasColumn('kpi_result', 'dept_abbr_hr');
        if ($hasResultDepartmentColumn) {
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
                KpiResult::query()
                    ->where('app_user_id', $userId)
                    ->where('cycle_id', $cycleId)
                    ->delete();
            }
        } else {
            $allScores = [];
            foreach ($scoresByDepartment as $scores) {
                $allScores = array_merge($allScores, is_array($scores) ? $scores : []);
            }

            if ($allScores === []) {
                KpiResult::query()
                    ->where('app_user_id', $userId)
                    ->where('cycle_id', $cycleId)
                    ->delete();
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

        $this->syncDepartmentOkrAverageForCycle($cycleId);
    }

    private function syncDepartmentOkrAverageForCycle(int $cycleId): void
    {
        $hasResultDepartmentColumn = Schema::hasColumn('kpi_result', 'dept_abbr_hr');
        if ($hasResultDepartmentColumn) {
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
            OkrResult::query()
                ->where('cycle_id', $cycleId)
                ->delete();
            OkrAllResult::query()
                ->where('cycle_id', $cycleId)
                ->delete();
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
            OkrResult::query()
                ->where('cycle_id', $cycleId)
                ->delete();
            OkrAllResult::query()
                ->where('cycle_id', $cycleId)
                ->delete();
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

        $avg = OkrResult::query()
            ->where('cycle_id', $cycleId)
            ->whereNotNull('result')
            ->avg('result');

        if ($avg === null) {
            OkrAllResult::query()
                ->where('cycle_id', $cycleId)
                ->delete();
            return;
        }

        OkrAllResult::query()->updateOrCreate(
            ['cycle_id' => $cycleId],
            ['result' => round((float) $avg, 2)]
        );
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

    /**
     * @return array<int, array{name: string, url: string}>
     */
    private function buildMonthlyReviewFiles(?KpiMonthScore $monthRow, string $lang, string $type = 'evidence'): array
    {
        $items = [];
        if (! $monthRow instanceof KpiMonthScore) {
            return $items;
        }

        $fileType = $type === 'action_plan' ? 'action_plan' : 'evidence';
        $filesSource = $fileType === 'action_plan'
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
                'url' => route('kpi.review.evidence.show', [
                    'score' => (int) $monthRow->id,
                    'index' => (int) $index,
                    'lang' => $lang,
                    'type' => $fileType,
                ]),
            ];
        }

        return $items;
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

    private function normalizeCriteriaOperator(string $value): string
    {
        $operator = trim($value);
        return match ($operator) {
            '>', '>=', '<', '<=', '=', '!=' => $operator,
            default => '-',
        };
    }

    private function normalizeDepartmentCode(string $value): string
    {
        $text = strtoupper(trim($value));
        $text = preg_replace('/\s+/u', '', $text) ?? $text;
        return $text;
    }

    private function resolveUserName(AppUser $user, string $lang): string
    {
        $name = $lang === 'th'
            ? trim((string) ($user->full_name_th ?? ''))
            : trim((string) ($user->full_name_en ?? ''));
        if ($name !== '') {
            return $name;
        }

        $fallback = trim((string) ($user->full_name_en ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        $fallback = trim((string) ($user->full_name_th ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        return trim((string) ($user->employee_code ?? ''));
    }

    private function formatPosition(string $value): string
    {
        $position = trim($value);
        if ($position === '') {
            return '-';
        }
        $normalized = preg_replace('/\s+/u', ' ', strtolower($position)) ?? strtolower($position);
        return ucwords($normalized);
    }

    private function normalizeSearch(string $value): string
    {
        $text = mb_strtolower(trim($value));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return $text;
    }

    private function matchesSearch(AppUser $owner, string $normalizedSearch): bool
    {
        if ($normalizedSearch === '') {
            return true;
        }

        $haystack = $this->normalizeSearch(implode(' ', [
            trim((string) ($owner->employee_code ?? '')),
            trim((string) ($owner->full_name_th ?? '')),
            trim((string) ($owner->full_name_en ?? '')),
        ]));

        return str_contains($haystack, $normalizedSearch);
    }

    private function resolveLang(Request $request): string
    {
        return strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';
    }
}


