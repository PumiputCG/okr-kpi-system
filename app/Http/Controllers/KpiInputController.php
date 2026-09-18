<?php

namespace App\Http\Controllers;

use App\Models\AdminDepartmentAssignment;
use App\Models\AdminHomeAnnouncement;
use App\Models\AdminHomeAnnouncementFile;
use App\Models\AdminHomeHierarchyConfig;
use App\Models\AppNotification;
use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\CycleMonth;
use App\Models\KpiMonthReview;
use App\Models\KpiMonthScore;
use App\Models\KpiResult;
use App\Models\KpiUnit;
use App\Models\OkrAllResult;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use App\Models\OkrResult;
use App\Support\DepartmentAssignmentResolver;
use App\Support\PlainTextNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class KpiInputController extends Controller
{
    private const NOTICE_LEVEL_THREE_MANAGER_POSITIONS = [
        'assist manager',
        'assistant manager',
        'manager',
    ];

    private const NOTICE_LEVEL_TWO_OVERRIDE_POSITIONS = [
        'DEPUTY GENERAL MANAGER',
        'GENERAL MANAGER',
    ];

    private const KPI_CONFIRM_WITHOUT_SCORE_POSITIONS = [
        'deputy general manager',
        'general manager',
    ];

    private ?array $cachedTargetDepartmentsByUserId = null;

    private ?array $cachedTargetUserIdsByDepartment = null;

    private ?bool $kpiMonthScoreHasModeTypeColumn = null;

    private ?bool $kpiMonthScoreHasOkrColumns = null;

    private ?bool $kpiMonthScoreHasParentTargetColumn = null;

    private ?bool $kpiMonthScoreHasCriteriaFlagColumn = null;

    private function formatDateDmy(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
            return $raw;
        }

        try {
            return \Illuminate\Support\Carbon::parse($raw)->format('d/m/Y');
        } catch (\Throwable) {
            return null;
        }
    }

    public function index(Request $request)
    {
        $activeCycle = Cycle::active();
        $authUserId = (int) auth()->id();
        $authUser = $request->user();
        $lang = $this->resolveLangFromRequest($request);

        $position = $this->normalizePosition($authUser?->position);
        $kpiUnits = $this->availableKpiUnits();
        $noticeVisibilityLevel = $this->resolveNoticeVisibilityLevel($authUser);

        $noticeVisibleAnnouncementLevels = [];
        if ($noticeVisibilityLevel >= 1) {
            $noticeVisibleAnnouncementLevels[] = 1;
        }
        if ($noticeVisibilityLevel >= 2) {
            $noticeVisibleAnnouncementLevels[] = 2;
        }

        $noticeAnnouncementsByLevel = $this->loadNoticeAnnouncementsByLevel(
            $noticeVisibleAnnouncementLevels,
            $request
        );

        $noticeLevelThreeCard = null;
        $noticeLevelThreeRows = [];
        $noticeLevelThreeCycleLabel = '';
        $kpiDepartmentTargetPolicy = $this->buildKpiDepartmentTargetPolicy($authUser);
        if ($noticeVisibilityLevel >= 3) {
            $viewerDepartmentCode = strtoupper(trim((string) ($authUser?->dept_abbr_hr ?? '')));
            if ($viewerDepartmentCode !== '') {
                $noticeLevelThreeData = $this->loadNoticeLevelThreeDepartmentRows($viewerDepartmentCode, $lang);
                $noticeLevelThreeCard = [
                    'dept_abbr_hr' => $viewerDepartmentCode,
                    'rows_count' => count($noticeLevelThreeData['rows']),
                ];
                $noticeLevelThreeRows = $noticeLevelThreeData['rows'];
                $noticeLevelThreeCycleLabel = $noticeLevelThreeData['cycle_label'];
            }
        }

        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[$month] = [
                'is_active' => false,
                'open_at' => null,
            ];
        }

        if ($activeCycle) {
            $cycleMonths = CycleMonth::query()
                ->where('cycle_id', $activeCycle->id)
                ->whereBetween('month_no', [1, 12])
                ->get()
                ->keyBy('month_no');

            for ($month = 1; $month <= 12; $month++) {
                $row = $cycleMonths->get($month);
                if (! $row) {
                    continue;
                }

                $months[$month] = [
                    'is_active' => (bool) $row->is_active,
                    'open_at' => $this->formatDateDmy($row->open_at),
                ];
            }
        }

        $kpiAverage = null;
        $kpiAverageByDepartment = [];
        if ($activeCycle && $authUserId > 0) {
            $kpiAverage = $this->syncUserKpiAverageByCycle($authUserId, (int) $activeCycle->id);
            $kpiAverageByDepartment = $this->resolveUserKpiAverageByDepartment(
                $authUserId,
                (int) $activeCycle->id,
                $authUser
            );
        }

        return view('kpi-input', [
            'activeCycle' => $activeCycle,
            'kpiUnits' => $kpiUnits,
            'kpiInputPolicy' => [
                'position' => $position,
                'can_skip_evidence' => $this->canSkipEvidenceByPosition($position),
                'show_action_plan_on_fail' => $this->showActionPlanOnFailByPosition($position),
                'require_action_plan_on_fail' => $this->mustUploadActionPlanByPosition($position),
            ],
            'cycleContext' => [
                'has_active_cycle' => (bool) $activeCycle,
                'cycle_id' => $activeCycle?->id,
                'cycle_name' => $activeCycle?->name,
                'cycle_code' => $activeCycle?->code,
                'months' => $months,
            ],
            'kpiAverage' => $kpiAverage,
            'kpiAverageByDepartment' => $kpiAverageByDepartment,
            'savedCards' => $this->buildSavedCards($activeCycle),
            'noticeVisibilityLevel' => $noticeVisibilityLevel,
            'noticeAnnouncementsByLevel' => $noticeAnnouncementsByLevel,
            'noticeLevelThreeCard' => $noticeLevelThreeCard,
            'noticeLevelThreeRows' => $noticeLevelThreeRows,
            'noticeLevelThreeCycleLabel' => $noticeLevelThreeCycleLabel,
            'okrHierarchy' => $this->buildOkrHierarchyForNotice($authUser, $lang),
            'kpiDepartmentTargetPolicy' => $kpiDepartmentTargetPolicy,
        ]);
    }

    public function noticePayload(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $lang = $this->resolveLangFromRequest($request);
        $noticeVisibilityLevel = $this->resolveNoticeVisibilityLevel($authUser);

        $noticeVisibleAnnouncementLevels = [];
        if ($noticeVisibilityLevel >= 1) {
            $noticeVisibleAnnouncementLevels[] = 1;
        }
        if ($noticeVisibilityLevel >= 2) {
            $noticeVisibleAnnouncementLevels[] = 2;
        }

        $noticeAnnouncementsByLevel = $this->loadNoticeAnnouncementsByLevel(
            $noticeVisibleAnnouncementLevels,
            $request
        );

        $noticeLevelThreeCard = null;
        $noticeLevelThreeRows = [];
        $noticeLevelThreeCycleLabel = '';

        if ($noticeVisibilityLevel >= 3) {
            $viewerDepartmentCode = $this->resolveNoticeLevelThreeDepartmentForRequest(
                $request,
                $authUser,
                $noticeVisibilityLevel
            );
            if ($viewerDepartmentCode !== '') {
                $noticeLevelThreeData = $this->loadNoticeLevelThreeDepartmentRows($viewerDepartmentCode, $lang);
                $noticeLevelThreeCard = [
                    'dept_abbr_hr' => $viewerDepartmentCode,
                    'rows_count' => count($noticeLevelThreeData['rows']),
                ];
                $noticeLevelThreeRows = $noticeLevelThreeData['rows'];
                $noticeLevelThreeCycleLabel = $noticeLevelThreeData['cycle_label'];
            }
        }

        $okrHierarchy = $this->buildOkrHierarchyForNotice($authUser, $lang);

        return response()->json([
            'ok' => true,
            'visibility_level' => $noticeVisibilityLevel,
            'announcements_by_level' => $noticeAnnouncementsByLevel,
            'level_three_card' => $noticeLevelThreeCard,
            'level_three_rows' => $noticeLevelThreeRows,
            'level_three_cycle_label' => $noticeLevelThreeCycleLabel,
            'okr_hierarchy' => $okrHierarchy,
        ]);
    }

    public function saveStepOne(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cycle_id' => ['required', 'integer', 'exists:cycles,id'],
            'item_id' => ['nullable', 'integer', 'exists:kpi_month_scores,id'],
            'objective' => ['required', 'string', 'max:60000'],
            'detail' => ['required', 'string', 'max:60000'],
            'mode_type' => ['nullable', 'in:report,target'],
            'target_departments' => ['nullable', 'array'],
            'target_departments.*' => ['nullable', 'string', 'max:60'],
            'okr_objective_id' => ['nullable', 'integer', 'exists:okr_objectives,id'],
            'okr_key_result_id' => ['nullable', 'integer', 'exists:okr_key_results,id'],
            'parent_target_kpi_id' => ['nullable', 'integer', 'exists:kpi_month_scores,id'],
        ]);
        $lang = $this->resolveLangFromRequest($request);

        $cycle = Cycle::query()->findOrFail((int) $validated['cycle_id']);
        if (! (bool) $cycle->is_active) {
            return response()->json([
                'ok' => false,
                'message' => $this->kpiInputMessage($lang, 'cannotSaveActivateCycle'),
            ], 422);
        }

        $authUserId = (int) auth()->id();
        /** @var AppUser|null $authUser */
        $authUser = $request->user();
        $itemId = isset($validated['item_id']) ? (int) $validated['item_id'] : 0;
        $objective = PlainTextNormalizer::normalize($validated['objective']);
        $detail = PlainTextNormalizer::normalize($validated['detail']);
        $targetDepartments = $this->resolveCardTargetDepartmentsForSave(
            $authUser,
            $validated['target_departments'] ?? []
        );
        $targetDepartmentsPayload = $targetDepartments !== [] ? $targetDepartments : null;
        $requestedModeType = $validated['mode_type'] ?? null;

        $root = null;
        if ($itemId > 0) {
            $root = $this->findCardRoot($itemId, $authUserId);
            if ($root && (int) $root->cycle_id !== (int) $cycle->id) {
                $root = null;
            }
        }
        $modeType = $this->normalizeKpiModeType(
            $requestedModeType ?? ($root?->mode_type ?? 'report')
        );
        $okrSelection = [
            'objective_id' => null,
            'key_result_id' => null,
        ];
        $parentTargetKpiId = null;
        if ($modeType === 'target') {
            $okrSelection = $this->resolveOkrSelectionForSave(
                $authUser,
                $validated['okr_objective_id'] ?? null,
                $validated['okr_key_result_id'] ?? null
            );

            if (
                $okrSelection === null ||
                (
                    $this->hasSelectableOkrHierarchyForUser($authUser) &&
                    $okrSelection['key_result_id'] === null
                )
            ) {
                return response()->json([
                    'ok' => false,
                    'message' => $this->kpiInputMessage($lang, 'invalidOkrHierarchy'),
                ], 422);
            }
        } else {
            $parentSelection = $this->resolveParentTargetKpiSelectionForSave(
                $authUser,
                $cycle,
                $validated['parent_target_kpi_id'] ?? null,
                $root
            );

            if ($parentSelection === null) {
                return response()->json([
                    'ok' => false,
                    'message' => $this->kpiInputMessage($lang, 'invalidOkrLevelThree'),
                ], 422);
            }

            $parentTargetKpiId = $parentSelection['parent_target_kpi_id'];
            $okrSelection = [
                'objective_id' => $parentSelection['objective_id'],
                'key_result_id' => $parentSelection['key_result_id'],
            ];
        }

        if (! $root) {
            $root = KpiMonthScore::query()->create($this->filterKpiMonthScoreWriteAttributes([
                'app_user_id' => $authUserId,
                'cycle_id' => (int) $cycle->id,
                'kpi_meta_id' => null,
                'month_no' => 0,
                'objective' => $objective,
                'detail' => $detail,
                'target_departments' => $targetDepartmentsPayload,
                'okr_objective_id' => $okrSelection['objective_id'],
                'okr_key_result_id' => $okrSelection['key_result_id'],
                'parent_target_kpi_id' => $parentTargetKpiId,
                'target_value' => 0,
                'kpi_unit_id' => null,
                'criteria_operator' => '',
                'has_criteria' => null,
                'mode_type' => $modeType,
                'score_value' => 0,
                'is_pass' => false,
                'result' => null,
                'evidence_files' => null,
                'action_plan_files' => null,
                'submitted_at' => null,
            ]));
        } else {
            $root->update($this->filterKpiMonthScoreWriteAttributes([
                'objective' => $objective,
                'detail' => $detail,
                'target_departments' => $targetDepartmentsPayload,
                'okr_objective_id' => $okrSelection['objective_id'],
                'okr_key_result_id' => $okrSelection['key_result_id'],
                'parent_target_kpi_id' => $parentTargetKpiId,
                'mode_type' => $modeType,
            ]));
        }

        KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', (int) $cycle->id)
            ->where(function ($query) use ($root) {
                $query->where('id', (int) $root->id)
                    ->orWhere('kpi_meta_id', (int) $root->id);
            })
            ->update($this->filterKpiMonthScoreWriteAttributes([
                'objective' => $objective,
                'detail' => $detail,
                'target_departments' => $targetDepartmentsPayload,
                'okr_objective_id' => $okrSelection['objective_id'],
                'okr_key_result_id' => $okrSelection['key_result_id'],
                'parent_target_kpi_id' => $parentTargetKpiId,
                'mode_type' => $modeType,
                'result' => null,
            ]));

        $this->syncUserKpiAverageByCycle($authUserId, (int) $cycle->id);

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => (int) $root->id,
                'cycle_id' => (int) $root->cycle_id,
                'mode_type' => $modeType,
                'target_departments' => $targetDepartments,
                'okr_objective_id' => $okrSelection['objective_id'],
                'okr_key_result_id' => $okrSelection['key_result_id'],
                'parent_target_kpi_id' => $parentTargetKpiId,
            ],
        ]);
    }

    public function saveStepTwo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:kpi_month_scores,id'],
            'has_criteria' => ['required', 'boolean'],
            'target' => ['nullable', 'required_if:has_criteria,1', 'numeric'],
            'kpi_unit_id' => ['nullable', 'required_if:has_criteria,1', 'integer', 'exists:kpi_units,id'],
            'custom_unit' => ['nullable', 'string', 'max:60'],
            'criteria_operator' => ['nullable', 'required_if:has_criteria,1', 'in:>,>=,<=,<,=,!='],
        ]);
        $lang = $this->resolveLangFromRequest($request);

        $authUserId = (int) auth()->id();
        $root = $this->findCardRoot((int) $validated['item_id'], $authUserId);

        if (! $root) {
            return response()->json([
                'ok' => false,
                'message' => $this->kpiInputMessage($lang, 'kpiItemNotFound'),
            ], 422);
        }

        $cycle = Cycle::query()->find((int) $root->cycle_id);
        if (! $cycle || ! (bool) $cycle->is_active) {
            return response()->json([
                'ok' => false,
                'message' => $this->kpiInputMessage($lang, 'cannotSaveActivateCycle'),
            ], 422);
        }

        $hasCriteria = (bool) $validated['has_criteria'];
        if (! $hasCriteria && $this->normalizeKpiModeType($root->mode_type ?? 'report') !== 'target') {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th'
                    ? 'เฉพาะ KPI ประเภทเป้าหมายเท่านั้นที่สามารถเลือกไม่กำหนดเกณฑ์ได้'
                    : 'Only target-type KPIs can be saved without criteria.',
            ], 422);
        }

        $targetValue = $hasCriteria ? (float) $validated['target'] : null;
        $kpiUnitId = $hasCriteria ? (int) $validated['kpi_unit_id'] : null;
        $operator = $hasCriteria ? (string) $validated['criteria_operator'] : '';
        if ($hasCriteria) {
            $customUnitText = PlainTextNormalizer::normalize($validated['custom_unit'] ?? '');
            if ($customUnitText !== '') {
                $kpiUnitId = $this->resolveOrCreateCustomUnitId($customUnitText);
            }
        }

        $root->update($this->filterKpiMonthScoreWriteAttributes([
            'target_value' => $targetValue,
            'kpi_unit_id' => $kpiUnitId,
            'criteria_operator' => $operator,
            'has_criteria' => $hasCriteria,
            'result' => null,
        ]));

        KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', (int) $cycle->id)
            ->where(function ($query) use ($root) {
                $query->where('id', (int) $root->id)
                    ->orWhere('kpi_meta_id', (int) $root->id);
            })
            ->update($this->filterKpiMonthScoreWriteAttributes([
                'objective' => (string) $root->objective,
                'detail' => (string) $root->detail,
                'target_departments' => $root->target_departments,
                'okr_objective_id' => $root->okr_objective_id,
                'okr_key_result_id' => $root->okr_key_result_id,
                'parent_target_kpi_id' => $root->parent_target_kpi_id,
                'target_value' => $targetValue,
                'kpi_unit_id' => $kpiUnitId,
                'criteria_operator' => $operator,
                'has_criteria' => $hasCriteria,
                'result' => null,
            ]));

        $monthRows = KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', (int) $cycle->id)
            ->where(function ($query) use ($root) {
                $query->where('id', (int) $root->id)
                    ->orWhere('kpi_meta_id', (int) $root->id);
            })
            ->whereBetween('month_no', [1, 12])
            ->get();

        /** @var KpiMonthScore $monthRow */
        foreach ($monthRows as $monthRow) {
            $monthRow->update([
                'is_pass' => $hasCriteria
                    ? $this->evaluateScore((float) $monthRow->score_value, (float) $targetValue, $operator)
                    : false,
            ]);
        }

        $this->syncUserKpiAverageByCycle($authUserId, (int) $cycle->id);

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => (int) $root->id,
            ],
        ]);
    }

    public function saveMonth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:kpi_month_scores,id'],
            'month_no' => ['required', 'integer', 'between:1,12'],
            'mode_type' => ['nullable', 'in:report,target'],
            'score' => ['required', 'numeric'],
            'evidence_files' => ['nullable', 'array'],
            'evidence_files.*' => ['file', 'mimes:doc,docx,pdf,png,jpg,jpeg,heic,heif,webp,gif,bmp,xls,xlsx', 'max:102400'],
            'action_plan_files' => ['nullable', 'array'],
            'action_plan_files.*' => ['file', 'mimes:doc,docx,pdf,png,jpg,jpeg,heic,heif,webp,gif,bmp,xls,xlsx', 'max:102400'],
        ]);
        $lang = $this->resolveLangFromRequest($request);

        $authUserId = (int) auth()->id();
        $root = $this->findCardRoot((int) $validated['item_id'], $authUserId);

        if (! $root) {
            return response()->json([
                'ok' => false,
                'message' => $this->kpiInputMessage($lang, 'kpiItemNotFound'),
            ], 422);
        }

        $cycle = Cycle::query()->find((int) $root->cycle_id);
        if (! $cycle || ! (bool) $cycle->is_active) {
            return response()->json([
                'ok' => false,
                'message' => $this->kpiInputMessage($lang, 'cannotSaveKpiActivateCycle'),
            ], 422);
        }

        $operator = trim((string) $root->criteria_operator);
        $kpiUnitId = $root->kpi_unit_id !== null ? (int) $root->kpi_unit_id : 0;
        if ($operator === '' || $kpiUnitId < 1) {
            return response()->json([
                'ok' => false,
                'message' => $this->kpiInputMessage($lang, 'saveTargetCriteriaAndUnitFirst'),
            ], 422);
        }

        $monthNo = (int) $validated['month_no'];
        $monthRow = CycleMonth::query()
            ->where('cycle_id', (int) $cycle->id)
            ->where('month_no', $monthNo)
            ->first();

        if (! $monthRow || ! (bool) $monthRow->is_active) {
            return response()->json([
                'ok' => false,
                'message' => $this->kpiInputMessage($lang, 'monthNotOpenedByAdmin'),
            ], 422);
        }

        $existingMonthRowForReview = KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', (int) $cycle->id)
            ->where('month_no', $monthNo)
            ->where(function ($query) use ($root, $monthNo): void {
                $query->where('kpi_meta_id', (int) $root->id)
                    ->orWhere(function ($inner) use ($root, $monthNo): void {
                        $inner->where('id', (int) $root->id)
                            ->where('month_no', $monthNo);
                    });
            })
            ->orderByDesc('id')
            ->first(['id']);
        $wasRejected = false;
        if ($existingMonthRowForReview) {
            $existingReviewStatus = $this->normalizeReviewStatus(
                KpiMonthReview::query()
                    ->where('kpi_month_score_id', (int) $existingMonthRowForReview->id)
                    ->value('status')
            );
            if ($existingReviewStatus === KpiMonthReview::STATUS_APPROVED) {
                return response()->json([
                    'ok' => false,
                    'message' => $lang === 'th'
                        ? 'เดือนนี้อนุมัติแล้ว ไม่สามารถแก้ไขได้'
                        : 'This month is already approved and cannot be edited.',
                ], 422);
            }
            $wasRejected = $existingReviewStatus === KpiMonthReview::STATUS_REJECTED;
        }

        $scoreValue = (float) $validated['score'];
        $targetValue = (float) $root->target_value;
        $isPass = $this->evaluateScore($scoreValue, $targetValue, $operator);
        $position = $this->normalizePosition($request->user()?->position);
        $canSkipEvidence = $this->canSkipEvidenceByPosition($position);
        $showActionPlanOnFail = $this->showActionPlanOnFailByPosition($position);
        $mustUploadActionPlanOnFail = $this->mustUploadActionPlanByPosition($position);
        $requiresActionPlan = $mustUploadActionPlanOnFail && ! $isPass;
        $allowActionPlanOnFail = $showActionPlanOnFail && ! $isPass;
        $requiresEvidence = ! $canSkipEvidence;
        $evidenceRequiredMessage = $this->kpiInputMessage($lang, 'attachEvidenceBeforeSaveMonth');
        $rootModeType = $this->normalizeKpiModeType($validated['mode_type'] ?? ($root->mode_type ?? 'report'));

        $existingScore = null;
        $score = null;

        // First month save: convert draft row into the actual month row, so no extra draft row remains.
        if ((int) $root->month_no === 0 && $root->kpi_meta_id === null) {
            $existingChildren = KpiMonthScore::query()
                ->where('app_user_id', $authUserId)
                ->where('cycle_id', (int) $cycle->id)
                ->where('kpi_meta_id', (int) $root->id)
                ->whereBetween('month_no', [1, 12])
                ->orderBy('id')
                ->get();

            if ($existingChildren->isEmpty()) {
                $mergedEvidence = $this->collectUploadedFiles($request, 'evidence_files', null, 'kpi-evidence', 'evidence_files');
                if ($requiresEvidence && (! is_array($mergedEvidence) || count($mergedEvidence) < 1)) {
                    return response()->json([
                        'ok' => false,
                        'message' => $evidenceRequiredMessage,
                    ], 422);
                }
                $mergedActionPlan = $this->collectUploadedFiles($request, 'action_plan_files', null, 'kpi-action-plan', 'action_plan_files');
                if (! $allowActionPlanOnFail) {
                    $this->deleteStoredFiles($mergedActionPlan);
                    $mergedActionPlan = null;
                }

                $root->update($this->filterKpiMonthScoreWriteAttributes([
                    'kpi_meta_id' => (int) $root->id,
                    'month_no' => $monthNo,
                    'objective' => trim((string) $root->objective),
                    'detail' => trim((string) $root->detail),
                    'target_departments' => $root->target_departments,
                    'okr_objective_id' => $root->okr_objective_id,
                    'okr_key_result_id' => $root->okr_key_result_id,
                    'parent_target_kpi_id' => $root->parent_target_kpi_id,
                    'target_value' => $targetValue,
                    'kpi_unit_id' => $kpiUnitId,
                    'criteria_operator' => $operator,
                    'mode_type' => $rootModeType,
                    'score_value' => $scoreValue,
                    'is_pass' => $isPass,
                    'result' => null,
                    'evidence_files' => $mergedEvidence,
                    'action_plan_files' => $mergedActionPlan,
                    'submitted_at' => null,
                ]));

                $score = $root->fresh();
            } else {
                $targetRow = $existingChildren->firstWhere('month_no', $monthNo);
                $mergedEvidence = $this->collectUploadedFiles($request, 'evidence_files', $targetRow, 'kpi-evidence', 'evidence_files');
                if ($requiresEvidence && (! is_array($mergedEvidence) || count($mergedEvidence) < 1)) {
                    return response()->json([
                        'ok' => false,
                        'message' => $evidenceRequiredMessage,
                    ], 422);
                }
                $mergedActionPlan = $this->collectUploadedFiles($request, 'action_plan_files', $targetRow, 'kpi-action-plan', 'action_plan_files');
                if (! $allowActionPlanOnFail) {
                    $this->deleteStoredFiles($mergedActionPlan);
                    $mergedActionPlan = null;
                }

                if (! $targetRow) {
                    $targetRow = KpiMonthScore::query()->create($this->filterKpiMonthScoreWriteAttributes([
                        'app_user_id' => $authUserId,
                        'cycle_id' => (int) $cycle->id,
                        'kpi_meta_id' => (int) $root->id,
                        'month_no' => $monthNo,
                        'objective' => trim((string) $root->objective),
                        'detail' => trim((string) $root->detail),
                        'target_departments' => $root->target_departments,
                        'okr_objective_id' => $root->okr_objective_id,
                        'okr_key_result_id' => $root->okr_key_result_id,
                        'parent_target_kpi_id' => $root->parent_target_kpi_id,
                        'target_value' => $targetValue,
                        'kpi_unit_id' => $kpiUnitId,
                        'criteria_operator' => $operator,
                        'mode_type' => $rootModeType,
                        'score_value' => $scoreValue,
                        'is_pass' => $isPass,
                        'result' => null,
                        'evidence_files' => $mergedEvidence,
                        'action_plan_files' => $mergedActionPlan,
                        'submitted_at' => null,
                    ]));
                } else {
                    $targetRow->update($this->filterKpiMonthScoreWriteAttributes([
                        'objective' => trim((string) $root->objective),
                        'detail' => trim((string) $root->detail),
                        'target_departments' => $root->target_departments,
                        'okr_objective_id' => $root->okr_objective_id,
                        'okr_key_result_id' => $root->okr_key_result_id,
                        'parent_target_kpi_id' => $root->parent_target_kpi_id,
                        'target_value' => $targetValue,
                        'kpi_unit_id' => $kpiUnitId,
                        'criteria_operator' => $operator,
                        'mode_type' => $rootModeType,
                        'score_value' => $scoreValue,
                        'is_pass' => $isPass,
                        'result' => null,
                        'evidence_files' => $mergedEvidence,
                        'action_plan_files' => $mergedActionPlan,
                        'submitted_at' => $targetRow->submitted_at,
                    ]));
                }

                $children = KpiMonthScore::query()
                    ->where('app_user_id', $authUserId)
                    ->where('cycle_id', (int) $cycle->id)
                    ->where('kpi_meta_id', (int) $root->id)
                    ->whereBetween('month_no', [1, 12])
                    ->get();

                /** @var KpiMonthScore $child */
                foreach ($children as $child) {
                    $childPass = $this->evaluateScore((float) $child->score_value, $targetValue, $operator);
                    $child->update($this->filterKpiMonthScoreWriteAttributes([
                        'kpi_meta_id' => (int) $targetRow->id,
                        'objective' => trim((string) $root->objective),
                        'detail' => trim((string) $root->detail),
                        'target_departments' => $root->target_departments,
                        'okr_objective_id' => $root->okr_objective_id,
                        'okr_key_result_id' => $root->okr_key_result_id,
                        'parent_target_kpi_id' => $root->parent_target_kpi_id,
                        'target_value' => $targetValue,
                        'kpi_unit_id' => $kpiUnitId,
                        'criteria_operator' => $operator,
                        'mode_type' => $rootModeType,
                        'is_pass' => $childPass,
                        'result' => null,
                    ]));
                }

                $targetRow->refresh();
                $targetRow->update([
                    'kpi_meta_id' => (int) $targetRow->id,
                ]);

                $root->delete();
                $score = $targetRow->fresh();
            }
        } else {
            $existingScore = KpiMonthScore::query()
                ->where('app_user_id', $authUserId)
                ->where('cycle_id', (int) $cycle->id)
                ->where('kpi_meta_id', (int) $root->id)
                ->where('month_no', $monthNo)
                ->first();

            $mergedEvidence = $this->collectUploadedFiles($request, 'evidence_files', $existingScore, 'kpi-evidence', 'evidence_files');
            if ($requiresEvidence && (! is_array($mergedEvidence) || count($mergedEvidence) < 1)) {
                return response()->json([
                    'ok' => false,
                    'message' => $evidenceRequiredMessage,
                ], 422);
            }
            $mergedActionPlan = $this->collectUploadedFiles($request, 'action_plan_files', $existingScore, 'kpi-action-plan', 'action_plan_files');
            if (! $allowActionPlanOnFail) {
                $this->deleteStoredFiles($mergedActionPlan);
                $mergedActionPlan = null;
            }

            $score = KpiMonthScore::query()->updateOrCreate(
                [
                    'app_user_id' => $authUserId,
                    'cycle_id' => (int) $cycle->id,
                    'kpi_meta_id' => (int) $root->id,
                    'month_no' => $monthNo,
                ],
                $this->filterKpiMonthScoreWriteAttributes([
                    'objective' => trim((string) $root->objective),
                    'detail' => trim((string) $root->detail),
                    'target_departments' => $root->target_departments,
                    'okr_objective_id' => $root->okr_objective_id,
                    'okr_key_result_id' => $root->okr_key_result_id,
                    'parent_target_kpi_id' => $root->parent_target_kpi_id,
                    'target_value' => $targetValue,
                    'kpi_unit_id' => $kpiUnitId,
                    'criteria_operator' => $operator,
                    'mode_type' => $rootModeType,
                    'score_value' => $scoreValue,
                    'is_pass' => $isPass,
                    'result' => null,
                    'evidence_files' => $mergedEvidence,
                    'action_plan_files' => $mergedActionPlan,
                    'submitted_at' => $existingScore?->submitted_at,
                ])
            );
        }

        $itemIdForUi = (int) $score->id;
        if ((int) $score->kpi_meta_id > 0 && (int) $score->kpi_meta_id !== (int) $score->id) {
            $itemIdForUi = (int) $score->kpi_meta_id;
        }
        $rootIdForReview = (int) ($score->kpi_meta_id ?? 0);
        if ($rootIdForReview < 1) {
            $rootIdForReview = (int) $score->id;
        }

        $evidenceFiles = $this->buildEvidenceFilePayload($score->evidence_files, (int) $score->id);
        $actionPlanFiles = $this->buildEvidenceFilePayload($score->action_plan_files, (int) $score->id, 'action_plan');
        $review = $this->upsertPendingReviewForSavedMonth(
            $score,
            $rootIdForReview,
            $request->user(),
            $lang,
            $wasRejected
        );
        $this->syncRootResultFromApprovedMonths($rootIdForReview, $authUserId, (int) $cycle->id);
        $this->syncUserKpiAverageByCycle($authUserId, (int) $cycle->id);

        return response()->json([
            'ok' => true,
            'score' => [
                'id' => (int) $score->id,
                'item_id' => $itemIdForUi,
                'is_pass' => (bool) $score->is_pass,
                'month_no' => (int) $score->month_no,
                'evidence_count' => count($evidenceFiles),
                'evidence_files' => $evidenceFiles,
                'action_plan_count' => count($actionPlanFiles),
                'action_plan_files' => $actionPlanFiles,
                'submitted_at' => $score->submitted_at?->format('d/m/Y H:i'),
                'review_status' => $this->normalizeReviewStatus($review?->status),
                'review_reject_detail' => trim((string) ($review?->reject_detail ?? '')),
                'reviewed_at' => $review?->reviewed_at?->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function confirmResult(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:kpi_month_scores,id'],
            'mode_type' => ['nullable', 'in:report,target'],
            'target_departments' => ['nullable', 'array'],
            'target_departments.*' => ['nullable', 'string', 'max:60'],
        ]);
        $lang = $this->resolveLangFromRequest($request);

        $authUserId = (int) auth()->id();
        /** @var AppUser|null $authUser */
        $authUser = $request->user();
        $root = $this->findCardRoot((int) $validated['item_id'], $authUserId);
        if (! $root) {
            return response()->json([
                'ok' => false,
                'message' => $this->kpiInputMessage($lang, 'kpiItemNotFound'),
            ], 422);
        }
        $modeType = $this->normalizeKpiModeType($validated['mode_type'] ?? ($root->mode_type ?? 'report'));

        $targetDepartments = $this->resolveCardTargetDepartmentsForSave(
            $authUser,
            $validated['target_departments'] ?? []
        );
        $targetDepartmentsPayload = $targetDepartments !== [] ? $targetDepartments : null;
        KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', (int) $root->cycle_id)
            ->where(function ($query) use ($root) {
                $query->where('id', (int) $root->id)
                    ->orWhere('kpi_meta_id', (int) $root->id);
            })
            ->update($this->filterKpiMonthScoreWriteAttributes([
                'target_departments' => $targetDepartmentsPayload,
                'mode_type' => $modeType,
            ]));

        $position = $this->normalizePosition($request->user()?->position);
        $canConfirmWithoutSavedMonth = $modeType === 'target'
            || $this->canConfirmWithoutSavedMonthByPosition($position);

        $monthRows = KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', (int) $root->cycle_id)
            ->where('kpi_meta_id', (int) $root->id)
            ->whereBetween('month_no', [1, 12])
            ->get();

        if ($monthRows->isEmpty()) {
            if (! $canConfirmWithoutSavedMonth) {
                return response()->json([
                    'ok' => false,
                    'message' => $this->kpiInputMessage($lang, 'saveAtLeastOneMonth'),
                ], 422);
            }

            KpiMonthScore::query()
                ->where('app_user_id', $authUserId)
                ->where('cycle_id', (int) $root->cycle_id)
                ->where(function ($query) use ($root) {
                    $query->where('id', (int) $root->id)
                        ->orWhere('kpi_meta_id', (int) $root->id);
                })
                ->update($this->filterKpiMonthScoreWriteAttributes([
                    'mode_type' => $modeType,
                    'result' => null,
                ]));

            $kpiAverage = $this->syncUserKpiAverageByCycle($authUserId, (int) $root->cycle_id);
            $kpiAverageByDepartment = $this->resolveUserKpiAverageByDepartment(
                $authUserId,
                (int) $root->cycle_id,
                $authUser
            );

            return response()->json([
                'ok' => true,
                'result' => [
                    'item_id' => (int) $root->id,
                    'value' => null,
                    'kpi_avg' => $kpiAverage,
                    'kpi_avg_by_department' => $kpiAverageByDepartment,
                    'saved_count' => 0,
                    'point' => 0,
                    'max' => 0,
                    'excluded_from_calculation' => true,
                ],
            ]);
        }

        if ($this->mustUploadActionPlanByPosition($position)) {
            $missingActionPlanRow = $monthRows
                ->filter(function (KpiMonthScore $monthRow) {
                    return ! (bool) $monthRow->is_pass;
                })
                ->sortBy('month_no')
                ->first(function (KpiMonthScore $monthRow) {
                    $files = is_array($monthRow->action_plan_files) ? $monthRow->action_plan_files : [];

                    return count($files) < 1;
                });

            if ($missingActionPlanRow instanceof KpiMonthScore) {
                return response()->json([
                    'ok' => false,
                    'message' => $this->kpiInputMessage($lang, 'attachActionPlanBeforeConfirmResult'),
                    'missing_action_plan_month_no' => (int) $missingActionPlanRow->month_no,
                    'missing_action_plan_score_id' => (int) $missingActionPlanRow->id,
                ], 422);
            }
        }

        $rowsForSubmission = $monthRows->filter(static function (KpiMonthScore $monthRow): bool {
            return $monthRow->submitted_at === null;
        })->values();

        $submittedAt = now();
        DB::transaction(function () use ($rowsForSubmission, $root, $authUser, $lang, $submittedAt): void {
            $monthRowIds = $rowsForSubmission->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values()
                ->all();

            if ($monthRowIds !== []) {
                KpiMonthScore::query()
                    ->whereIn('id', $monthRowIds)
                    ->update([
                        'submitted_at' => $submittedAt,
                    ]);
            }

            /** @var KpiMonthScore $monthRow */
            foreach ($rowsForSubmission as $monthRow) {
                $this->upsertPendingReviewForSavedMonth(
                    $monthRow,
                    (int) $root->id,
                    $authUser,
                    $lang,
                    true
                );
            }
        });

        $approvedSummary = $this->syncRootResultFromApprovedMonths(
            (int) $root->id,
            $authUserId,
            (int) $root->cycle_id
        );
        $savedCount = (int) ($approvedSummary['approved_count'] ?? 0);
        $point = (int) ($approvedSummary['point'] ?? 0);
        $max = (int) ($approvedSummary['max'] ?? 0);
        $result = $approvedSummary['result'] !== null ? (float) $approvedSummary['result'] : null;

        $kpiAverage = $this->syncUserKpiAverageByCycle($authUserId, (int) $root->cycle_id);
        $kpiAverageByDepartment = $this->resolveUserKpiAverageByDepartment(
            $authUserId,
            (int) $root->cycle_id,
            $authUser
        );

        return response()->json([
            'ok' => true,
            'result' => [
                'item_id' => (int) $root->id,
                'value' => $result,
                'kpi_avg' => $kpiAverage,
                'kpi_avg_by_department' => $kpiAverageByDepartment,
                'saved_count' => $savedCount,
                'point' => $point,
                'max' => $max,
                'approved_count' => $savedCount,
                'pending_count' => (int) $monthRows->count() - $savedCount,
            ],
        ]);
    }

    public function deleteItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:kpi_month_scores,id'],
        ]);
        $lang = $this->resolveLangFromRequest($request);

        $authUserId = (int) auth()->id();
        $root = $this->findCardRoot((int) $validated['item_id'], $authUserId);
        if (! $root) {
            return response()->json([
                'ok' => false,
                'message' => $this->kpiInputMessage($lang, 'kpiItemNotFound'),
            ], 422);
        }

        DB::transaction(function () use ($authUserId, $root) {
            $rows = KpiMonthScore::query()
                ->where('app_user_id', $authUserId)
                ->where('cycle_id', (int) $root->cycle_id)
                ->where(function ($query) use ($root) {
                    $query->where('id', (int) $root->id)
                        ->orWhere('kpi_meta_id', (int) $root->id);
                })
                ->get();

            /** @var KpiMonthScore $row */
            foreach ($rows as $row) {
                $this->deleteStoredFiles($row->evidence_files);
                $this->deleteStoredFiles($row->action_plan_files);
            }

            KpiMonthScore::query()
                ->where('app_user_id', $authUserId)
                ->where('cycle_id', (int) $root->cycle_id)
                ->where(function ($query) use ($root) {
                    $query->where('id', (int) $root->id)
                        ->orWhere('kpi_meta_id', (int) $root->id);
                })
                ->delete();
        });

        $this->syncUserKpiAverageByCycle($authUserId, (int) $root->cycle_id);

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => (int) $root->id,
            ],
        ]);
    }

    public function showEvidenceFile(Request $request, KpiMonthScore $score, int $index)
    {
        if ((int) $score->app_user_id !== (int) auth()->id()) {
            abort(403);
        }

        if ($index < 0) {
            abort(404);
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

        // Serve the stored file directly so inline image preview works consistently.
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

        $displayName = trim((string) ($target['name'] ?? ''));
        if ($displayName === '') {
            $displayName = basename($path);
        }
        $safeDisplayName = str_replace(['"', "\r", "\n"], '', $displayName);
        $disposition = 'inline; filename="'.$safeDisplayName.'"';
        $disposition .= "; filename*=UTF-8''".rawurlencode($safeDisplayName);

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'private, no-cache',
        ]);
    }

    public function downloadNoticeFile(Request $request, AdminHomeAnnouncementFile $file)
    {
        $path = $this->resolveNoticeFilePathForViewer($request, $file);

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
                'svg' => 'image/svg+xml',
                'pdf' => 'application/pdf',
                'txt' => 'text/plain; charset=UTF-8',
                'csv' => 'text/csv; charset=UTF-8',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => 'application/octet-stream',
            };
        }

        $displayName = trim((string) ($file->original_name ?? ''));
        if ($displayName === '') {
            $displayName = basename($path);
        }
        $safeDisplayName = str_replace(['"', "\r", "\n"], '', $displayName);
        $disposition = 'inline; filename="'.$safeDisplayName.'"';
        $disposition .= "; filename*=UTF-8''".rawurlencode($safeDisplayName);

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'private, no-cache',
        ]);
    }

    public function previewNoticeFile(Request $request, AdminHomeAnnouncementFile $file)
    {
        $path = $this->resolveNoticeFilePathForViewer($request, $file);
        $absolutePath = Storage::disk('public')->path($path);
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $lang = $this->resolveLangFromRequest($request);
        $title = trim((string) ($file->original_name ?? ''));
        if ($title === '') {
            $title = basename($path);
        }

        if (in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            return $this->renderNoticeSpreadsheetPreview($absolutePath, $title, $lang);
        }

        if ($extension === 'pptx') {
            return $this->renderNoticePptxPreview($absolutePath, $title, $lang);
        }

        return $this->downloadNoticeFile($request, $file);
    }

    public function downloadGoalObjectiveFile(Request $request, int $id)
    {
        if (! $request->user()) {
            abort(403);
        }

        $objective = OkrObjective::query()->findOrFail($id);

        return $this->streamGoalTargetFile($objective->file_path, $objective->file_original_name);
    }

    public function downloadGoalKeyResultFile(Request $request, int $id)
    {
        if (! $request->user()) {
            abort(403);
        }

        $keyResult = OkrKeyResult::query()->findOrFail($id);

        return $this->streamGoalTargetFile($keyResult->file_path, $keyResult->file_original_name);
    }

    private function resolveNoticeFilePathForViewer(Request $request, AdminHomeAnnouncementFile $file): string
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $announcement = $file->announcement()->first();
        if (! $announcement) {
            abort(404);
        }

        $viewerVisibleLevel = $this->resolveNoticeVisibilityLevel($user);
        $announcementLevel = (int) ($announcement->level_no ?? 0);
        if ($viewerVisibleLevel < 1 || $announcementLevel < 1 || $announcementLevel > 2 || $announcementLevel > $viewerVisibleLevel) {
            abort(403);
        }

        $path = trim((string) ($file->storage_path ?? ''));
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return $path;
    }

    private function streamGoalTargetFile(?string $rawPath, ?string $rawName)
    {
        $path = trim((string) ($rawPath ?? ''));
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
                'svg' => 'image/svg+xml',
                'pdf' => 'application/pdf',
                'txt' => 'text/plain; charset=UTF-8',
                'csv' => 'text/csv; charset=UTF-8',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => 'application/octet-stream',
            };
        }

        $displayName = trim((string) ($rawName ?? ''));
        if ($displayName === '') {
            $displayName = basename($path);
        }
        $safeDisplayName = str_replace(['"', "\r", "\n"], '', $displayName);
        $disposition = 'inline; filename="'.$safeDisplayName.'"';
        $disposition .= "; filename*=UTF-8''".rawurlencode($safeDisplayName);

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'private, no-cache',
        ]);
    }

    private function renderNoticeSpreadsheetPreview(string $absolutePath, string $title, string $lang)
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($absolutePath);
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = max(1, min((int) $sheet->getHighestDataRow(), 500));
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                (string) $sheet->getHighestDataColumn()
            );
            $highestColumnIndex = max(1, min($highestColumnIndex, 40));

            $rows = [];
            for ($row = 1; $row <= $highestRow; $row++) {
                $line = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $value = $sheet->getCell([$col, $row])->getFormattedValue();
                    if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                        $value = $value->getPlainText();
                    }
                    $line[] = trim((string) $value);
                }
                $rows[] = $line;
            }

            $message = '';
            if ((int) $sheet->getHighestDataRow() > $highestRow) {
                $message = $lang === 'th'
                    ? 'แสดงเฉพาะ 500 แถวแรกเพื่อความรวดเร็ว'
                    : 'Showing first 500 rows for faster preview';
            }

            return view('notice-file-preview', [
                'lang' => $lang,
                'title' => $title,
                'type' => 'spreadsheet',
                'rows' => $rows,
                'slides' => [],
                'message' => $message,
            ]);
        } catch (\Throwable) {
            return view('notice-file-preview', [
                'lang' => $lang,
                'title' => $title,
                'type' => 'error',
                'rows' => [],
                'slides' => [],
                'message' => $lang === 'th'
                    ? 'ไม่สามารถเปิดดูไฟล์ตารางนี้ได้'
                    : 'Unable to preview this spreadsheet file.',
            ]);
        }
    }

    private function renderNoticePptxPreview(string $absolutePath, string $title, string $lang)
    {
        try {
            if (! class_exists(\ZipArchive::class)) {
                throw new \RuntimeException('ZipArchive not available');
            }

            $zip = new \ZipArchive;
            if ($zip->open($absolutePath) !== true) {
                throw new \RuntimeException('Unable to open pptx');
            }

            $slidesRaw = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = (string) $zip->getNameIndex($i);
                if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $entryName, $matches) !== 1) {
                    continue;
                }
                $slideNo = (int) ($matches[1] ?? 0);
                if ($slideNo < 1) {
                    continue;
                }
                $slidesRaw[$slideNo] = (string) ($zip->getFromName($entryName) ?: '');
            }
            $zip->close();

            ksort($slidesRaw, SORT_NUMERIC);
            $slides = [];
            foreach ($slidesRaw as $slideNo => $xml) {
                if ($xml === '') {
                    continue;
                }
                preg_match_all('/<a:t[^>]*>(.*?)<\/a:t>/u', $xml, $matches);
                $texts = array_map(static function (string $text): string {
                    $decoded = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $decoded = preg_replace('/\s+/u', ' ', $decoded) ?? $decoded;

                    return trim($decoded);
                }, $matches[1] ?? []);
                $texts = array_values(array_filter($texts, static fn (string $text): bool => $text !== ''));
                $slides[] = [
                    'no' => (int) $slideNo,
                    'text' => count($texts) > 0 ? implode("\n", $texts) : '-',
                ];
            }

            if (count($slides) < 1) {
                throw new \RuntimeException('No slide text found');
            }

            return view('notice-file-preview', [
                'lang' => $lang,
                'title' => $title,
                'type' => 'slides',
                'rows' => [],
                'slides' => $slides,
                'message' => $lang === 'th'
                    ? 'แสดงตัวอย่างข้อความจากสไลด์ (ถ้ามีรูปภาพจะไม่แสดง)'
                    : 'Showing extracted slide text preview (images are not rendered).',
            ]);
        } catch (\Throwable) {
            return view('notice-file-preview', [
                'lang' => $lang,
                'title' => $title,
                'type' => 'error',
                'rows' => [],
                'slides' => [],
                'message' => $lang === 'th'
                    ? 'ไม่สามารถเปิดดูไฟล์ PowerPoint นี้ได้'
                    : 'Unable to preview this PowerPoint file.',
            ]);
        }
    }

    private function resolveNoticeVisibilityLevel(?AppUser $user): int
    {
        if (! $user) {
            return 0;
        }

        if (AppUserAuthController::isAdminRole($user->role)) {
            return 3;
        }

        // Requirement: every authenticated user should be able to see
        // Level 1 and Level 2 announcements in the "Organization Targets" notice.
        $minimumVisibilityLevel = 2;
        $normalizedUserPosition = $this->normalizeNoticeHierarchyPosition($user->position);
        if ($normalizedUserPosition === '') {
            return $minimumVisibilityLevel;
        }

        // Assist/Assistant Manager and Manager should always be able to see Level 3
        // notice card for their own department.
        $levelThreeManagerPositions = array_values(array_unique(array_map(
            fn (string $position): string => $this->normalizeNoticeHierarchyPosition($position),
            self::NOTICE_LEVEL_THREE_MANAGER_POSITIONS
        )));
        if (in_array($normalizedUserPosition, $levelThreeManagerPositions, true)) {
            return 3;
        }

        $matchedLevels = [];
        $configs = AdminHomeHierarchyConfig::query()->get([
            'levels_count',
            'layout_json',
        ]);

        foreach ($configs as $config) {
            $levelsCount = max(1, (int) ($config->levels_count ?? 1));
            $layout = is_array($config->layout_json) ? $config->layout_json : [];
            $matchedLevel = $this->resolveNoticeMatchedLevel($layout, $levelsCount, $normalizedUserPosition);
            if ($matchedLevel > 0) {
                $matchedLevels[] = $matchedLevel;
            }
        }

        if (count($matchedLevels) < 1) {
            return $minimumVisibilityLevel;
        }

        $matchedLevel = min(4, (int) max($matchedLevels));
        $matchedVisibilityLevel = 0;
        if ($matchedLevel >= 4) {
            $matchedVisibilityLevel = 3;
        } elseif ($matchedLevel === 3) {
            $matchedVisibilityLevel = 2;
        } elseif ($matchedLevel === 2) {
            $matchedVisibilityLevel = 1;
        }

        return max($matchedVisibilityLevel, $minimumVisibilityLevel);
    }

    private function resolveNoticeLevelThreeDepartmentForRequest(
        Request $request,
        ?AppUser $user,
        int $noticeVisibilityLevel
    ): string {
        if (! $user || $noticeVisibilityLevel < 3) {
            return '';
        }

        $requestedDepartment = $this->normalizeNoticeLevelThreeDepartmentCode(
            (string) $request->query('dept', '')
        );
        $ownDepartment = $this->normalizeNoticeLevelThreeDepartmentCode(
            (string) ($user->dept_abbr_hr ?? '')
        );

        if (AppUserAuthController::isAdminRole($user->role)) {
            return $requestedDepartment !== '' ? $requestedDepartment : $ownDepartment;
        }

        $targetDepartments = DepartmentAssignmentResolver::resolveTargetDepartmentsByUserId((int) $user->id);
        $reviewerDepartments = DepartmentAssignmentResolver::resolveReviewerDepartmentsByUserId((int) $user->id);
        $allowedDepartments = $this->normalizeNoticeLevelThreeDepartmentCodes(array_merge(
            $targetDepartments,
            $reviewerDepartments
        ));

        if ($ownDepartment !== '' && ! in_array($ownDepartment, $allowedDepartments, true)) {
            $allowedDepartments[] = $ownDepartment;
        }

        if ($requestedDepartment !== '' && in_array($requestedDepartment, $allowedDepartments, true)) {
            return $requestedDepartment;
        }

        if ($ownDepartment !== '') {
            return $ownDepartment;
        }

        return $allowedDepartments[0] ?? '';
    }

    private function resolveNoticeMatchedLevel(array $layout, int $levelsCount, string $normalizedUserPosition): int
    {
        if ($normalizedUserPosition === '') {
            return 0;
        }

        $matchedLevel = 0;
        for ($level = 1; $level <= $levelsCount; $level++) {
            $rawPositions = $layout[(string) $level] ?? [];
            if (! is_array($rawPositions)) {
                continue;
            }

            $normalizedPositions = collect($rawPositions)
                ->map(fn ($value): string => $this->normalizeNoticeHierarchyPosition((string) $value))
                ->filter()
                ->values()
                ->all();

            if (in_array($normalizedUserPosition, $normalizedPositions, true)) {
                $matchedLevel = max($matchedLevel, $level);
            }
        }

        return $matchedLevel;
    }

    private function normalizeNoticeHierarchyPosition(?string $position): string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $position)) ?? trim((string) $position);

        return strtoupper($text);
    }

    private function isNoticeLevelTwoOverridePosition(string $normalizedUserPosition): bool
    {
        return in_array($normalizedUserPosition, self::NOTICE_LEVEL_TWO_OVERRIDE_POSITIONS, true);
    }

    private function loadNoticeAnnouncementsByLevel(array $visibleLevels, Request $request): array
    {
        $result = [
            1 => [],
            2 => [],
        ];

        $levels = [];
        foreach ($visibleLevels as $rawLevel) {
            $level = (int) $rawLevel;
            if (! in_array($level, [1, 2], true)) {
                continue;
            }
            if (! in_array($level, $levels, true)) {
                $levels[] = $level;
            }
        }
        sort($levels);

        if (count($levels) === 0) {
            return $result;
        }

        $announcements = AdminHomeAnnouncement::query()
            ->with([
                'files' => fn ($query) => $query->orderByDesc('id'),
            ])
            ->whereIn('level_no', $levels)
            ->orderBy('level_no')
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->get();

        foreach ($announcements as $announcement) {
            $level = (int) ($announcement->level_no ?? 0);
            if (! isset($result[$level])) {
                continue;
            }

            $result[$level][] = $this->toNoticeAnnouncementPayload($announcement, $request);
        }

        // Level 1 list: show older records first and the most recent at the bottom.
        if (count($result[1]) > 1) {
            $result[1] = array_values(array_reverse($result[1]));
        }

        return $result;
    }

    private function toNoticeAnnouncementPayload(AdminHomeAnnouncement $announcement, Request $request): array
    {
        return [
            'id' => (int) $announcement->id,
            'level_no' => (int) $announcement->level_no,
            'parent_announcement_id' => (int) ($announcement->parent_announcement_id ?? 0),
            'dept_abbr_hr' => strtoupper(trim((string) ($announcement->dept_abbr_hr ?? ''))),
            'title' => trim((string) ($announcement->title ?? '')),
            'detail' => (string) ($announcement->detail ?? ''),
            'posted_at' => optional($announcement->posted_at)->toIso8601String(),
            'files' => $announcement->files
                ->map(function (AdminHomeAnnouncementFile $file) use ($request): array {
                    return [
                        'id' => (int) $file->id,
                        'name' => trim((string) ($file->original_name ?? '')),
                        'size_text' => $this->formatNoticeFileSize($file->size_bytes),
                        'url' => Route::has('kpi.input.notice.files.download')
                            ? route('kpi.input.notice.files.download', [
                                'file' => $file->id,
                                'lang' => $request->query('lang', 'en'),
                            ])
                            : '',
                        'preview_url' => Route::has('kpi.input.notice.files.preview')
                            ? route('kpi.input.notice.files.preview', [
                                'file' => $file->id,
                                'lang' => $request->query('lang', 'en'),
                            ])
                            : '',
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function formatNoticeFileSize(mixed $sizeBytes): string
    {
        $bytes = (int) $sizeBytes;
        if ($bytes <= 0) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $unitIndex = 0;
        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        if ($unitIndex === 0) {
            return number_format($value, 0).' '.$units[$unitIndex];
        }

        return number_format($value, 2).' '.$units[$unitIndex];
    }

    private function buildGoalObjectiveFileUrl(int $objectiveId, mixed $filePath): string
    {
        if ($objectiveId < 1 || trim((string) ($filePath ?? '')) === '') {
            return '';
        }

        return Route::has('kpi.input.goal_targets.objectives.file')
            ? route('kpi.input.goal_targets.objectives.file', ['id' => $objectiveId])
            : '';
    }

    private function buildGoalKeyResultFileUrl(int $keyResultId, mixed $filePath): string
    {
        if ($keyResultId < 1 || trim((string) ($filePath ?? '')) === '') {
            return '';
        }

        return Route::has('kpi.input.goal_targets.key_results.file')
            ? route('kpi.input.goal_targets.key_results.file', ['id' => $keyResultId])
            : '';
    }

    private function buildOkrHierarchyForNotice(?AppUser $user, string $lang): array
    {
        if (! $user) {
            return [];
        }

        $objectives = OkrObjective::query()
            ->orderBy('sort_no')
            ->orderBy('id')
            ->with(['keyResults' => fn ($q) => $q->orderBy('sort_no')->orderBy('id')])
            ->get();

        if ($objectives->isEmpty()) {
            return [];
        }

        $keyResultIds = $objectives
            ->flatMap(fn (OkrObjective $objective) => $objective->keyResults->pluck('id'))
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $kpiEntriesByKeyResultId = $this->loadNoticeLevelThreeEntriesByKeyResultIds($keyResultIds, $lang);

        $result = [];
        foreach ($objectives as $obj) {
            $krs = $obj->keyResults;

            $krRows = [];
            foreach ($krs as $kr) {
                $krDept = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($kr->dept_abbr_hr ?? ''));
                $keyResultId = (int) $kr->id;
                $kpiEntries = $kpiEntriesByKeyResultId[$keyResultId] ?? [];

                $krRows[] = [
                    'id' => $keyResultId,
                    'sort_no' => (int) $kr->sort_no,
                    'title' => (string) ($kr->title ?? ''),
                    'detail' => (string) ($kr->detail ?? ''),
                    'dept_abbr_hr' => $krDept,
                    'file_original_name' => (string) ($kr->file_original_name ?? ''),
                    'file_url' => $this->buildGoalKeyResultFileUrl($keyResultId, $kr->file_path ?? null),
                    'kpi_entries' => $kpiEntries,
                ];
            }

            $result[] = [
                'id' => (int) $obj->id,
                'sort_no' => (int) $obj->sort_no,
                'title' => (string) ($obj->title ?? ''),
                'detail' => (string) ($obj->detail ?? ''),
                'file_original_name' => (string) ($obj->file_original_name ?? ''),
                'file_url' => $this->buildGoalObjectiveFileUrl((int) $obj->id, $obj->file_path ?? null),
                'key_results' => $krRows,
            ];
        }

        return $result;
    }

    private function loadNoticeLevelThreeEntriesByKeyResultIds(array $keyResultIds, string $lang): array
    {
        $keyResultIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $keyResultIds
        ), static fn (int $id): bool => $id > 0)));

        if (
            $keyResultIds === [] ||
            ! Schema::hasColumn('kpi_month_scores', 'okr_key_result_id') ||
            ! Schema::hasColumn('kpi_month_scores', 'okr_objective_id')
        ) {
            return [];
        }

        $cycle = Cycle::active() ?? Cycle::query()->orderByDesc('id')->first();
        $cycleId = (int) ($cycle?->id ?? 0);

        $rootRowsFilter = static function ($query): void {
            $query->where(function ($inner): void {
                $inner->where('month_no', 0)
                    ->whereNull('kpi_meta_id');
            })->orWhereColumn('kpi_meta_id', 'id');
        };

        $query = KpiMonthScore::query()
            ->with([
                'user:id,employee_code,full_name_th,full_name_en,dept_abbr_hr,position',
                'unit:id,code,name_th,name_en',
            ])
            ->whereIn('okr_key_result_id', $keyResultIds)
            ->where($rootRowsFilter)
            ->orderBy('okr_key_result_id')
            ->orderBy('id');

        if ($cycleId > 0) {
            $query->where('cycle_id', $cycleId);
        }

        if (Schema::hasColumn('kpi_month_scores', 'mode_type')) {
            $query->where('mode_type', 'target');
        }

        $scores = $query->get();
        if ($scores->isEmpty()) {
            return [];
        }

        $entries = [];
        /** @var KpiMonthScore $score */
        foreach ($scores as $score) {
            $keyResultId = (int) ($score->okr_key_result_id ?? 0);
            if ($keyResultId < 1) {
                continue;
            }

            $owner = [
                'id' => (int) ($score->app_user_id ?? 0),
                'dept_abbr_hr' => (string) ($score->user?->dept_abbr_hr ?? ''),
            ];
            $criteriaDisabled = $score->has_criteria === false;
            $criteriaOperator = $criteriaDisabled ? '' : trim((string) ($score->criteria_operator ?? ''));
            $targetValueNumber = ! $criteriaDisabled && $score->target_value !== null
                ? (float) $score->target_value
                : null;

            $entries[$keyResultId] ??= [];
            $entries[$keyResultId][] = [
                'id' => (int) $score->id,
                'app_user_id' => (int) ($score->app_user_id ?? 0),
                'employee_code' => trim((string) ($score->user?->employee_code ?? '')),
                'full_name_th' => trim((string) ($score->user?->full_name_th ?? '')),
                'full_name_en' => trim((string) ($score->user?->full_name_en ?? '')),
                'dept_abbr_hr' => $this->normalizeNoticeLevelThreeDepartmentCode((string) ($score->user?->dept_abbr_hr ?? '')),
                'target_departments' => $this->resolveNoticeLevelThreeTargetDepartments($score, $owner),
                'position' => trim((string) ($score->user?->position ?? '')),
                'objective' => trim((string) ($score->objective ?? '')),
                'detail' => trim((string) ($score->detail ?? '')),
                'target_value' => $targetValueNumber !== null
                    ? $this->formatNoticeTargetValue($targetValueNumber)
                    : '-',
                'target_goal' => $this->resolveNoticeCriteriaSymbol($criteriaOperator) ?: '-',
                'unit' => $criteriaDisabled ? '-' : $this->resolveNoticeUnitLabel($score->unit, $lang),
            ];
        }

        return $entries;
    }

    private function loadNoticeLevelThreeDepartmentRows(string $departmentCode, string $lang): array
    {
        $department = $this->normalizeNoticeLevelThreeDepartmentCode($departmentCode);
        if ($department === '') {
            return [
                'cycle_label' => '',
                'rows' => [],
            ];
        }

        $assignedTargetUserIds = $this->resolveAssignedTargetUserIdsByDepartment($department);
        if ($assignedTargetUserIds === []) {
            return [
                'cycle_label' => '',
                'rows' => [],
            ];
        }

        $targetUsers = AppUser::query()
            ->whereIn('id', $assignedTargetUserIds)
            ->get([
                'id',
                'employee_code',
                'full_name_th',
                'full_name_en',
                'dept_abbr_hr',
                'position',
            ]);

        if ($targetUsers->isEmpty()) {
            return [
                'cycle_label' => '',
                'rows' => [],
            ];
        }

        $cycle = Cycle::active() ?? Cycle::query()->orderByDesc('id')->first();
        $cycleId = (int) ($cycle?->id ?? 0);
        $cycleLabel = trim((string) ($cycle?->name ?? ''));
        if ($cycleLabel === '') {
            $cycleLabel = trim((string) ($cycle?->code ?? ''));
        }

        $userMap = [];
        foreach ($targetUsers as $targetUser) {
            $userId = (int) ($targetUser->id ?? 0);
            if ($userId < 1) {
                continue;
            }

            $userDeptCode = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($targetUser->dept_abbr_hr ?? ''));
            $userMap[$userId] = [
                'id' => $userId,
                'employee_code' => trim((string) ($targetUser->employee_code ?? '')),
                'full_name_th' => trim((string) ($targetUser->full_name_th ?? '')),
                'full_name_en' => trim((string) ($targetUser->full_name_en ?? '')),
                'position' => trim((string) ($targetUser->position ?? '')),
                'dept_abbr_hr' => $userDeptCode,
            ];
        }

        if (count($userMap) === 0) {
            return [
                'cycle_label' => $cycleLabel,
                'rows' => [],
            ];
        }

        $rootRowsFilter = static function ($query): void {
            $query->where(function ($inner): void {
                $inner->where('month_no', 0)
                    ->whereNull('kpi_meta_id');
            })->orWhereColumn('kpi_meta_id', 'id');
        };

        $scoresQuery = KpiMonthScore::query()
            ->with([
                'unit:id,code,name_th,name_en',
            ])
            ->whereIn('app_user_id', array_keys($userMap))
            ->where($rootRowsFilter)
            ->orderBy('id');

        if ($cycleId > 0) {
            $scoresQuery->where('cycle_id', $cycleId);
        }

        $scores = $scoresQuery->get();
        if ($scores->isEmpty() && $cycleId > 0) {
            $scores = KpiMonthScore::query()
                ->with([
                    'unit:id,code,name_th,name_en',
                ])
                ->whereIn('app_user_id', array_keys($userMap))
                ->where($rootRowsFilter)
                ->orderBy('id')
                ->get();
        }

        $targetOnlyRootIds = [];
        if ($scores->isNotEmpty()) {
            $rootIds = $scores
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values()
                ->all();

            if ($rootIds !== []) {
                $reportRootIds = KpiMonthScore::query()
                    ->whereIn('app_user_id', array_keys($userMap))
                    ->whereIn('kpi_meta_id', $rootIds)
                    ->whereBetween('month_no', [1, 12]);

                if ($cycleId > 0) {
                    $reportRootIds->where('cycle_id', $cycleId);
                }

                $reportRootIdSet = $reportRootIds
                    ->pluck('kpi_meta_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->filter(static fn (int $id): bool => $id > 0)
                    ->unique()
                    ->values()
                    ->all();

                if ($reportRootIdSet !== []) {
                    $targetOnlyRootIds = array_values(array_diff($rootIds, $reportRootIdSet));
                } else {
                    $targetOnlyRootIds = $rootIds;
                }
            }
        }
        $targetOnlyRootIdSet = [];
        foreach ($targetOnlyRootIds as $targetOnlyRootId) {
            $safeId = (int) $targetOnlyRootId;
            if ($safeId > 0) {
                $targetOnlyRootIdSet[$safeId] = true;
            }
        }

        $rows = [];
        foreach ($scores as $score) {
            $scoreId = (int) ($score->id ?? 0);
            if ($scoreId < 1 || ! isset($targetOnlyRootIdSet[$scoreId])) {
                continue;
            }

            $userId = (int) ($score->app_user_id ?? 0);
            $owner = $userMap[$userId] ?? null;
            if (! is_array($owner)) {
                continue;
            }

            $targetDepartments = $this->resolveNoticeLevelThreeTargetDepartments($score, $owner);
            if (! in_array($department, $targetDepartments, true)) {
                continue;
            }

            // Level 3 should list only target-type KPI cards (cards without monthly score rows).
            if ($score->result !== null) {
                continue;
            }

            $criteriaDisabled = $score->has_criteria === false;
            $targetValueNumber = ! $criteriaDisabled && $score->target_value !== null
                ? (float) $score->target_value
                : null;
            $targetValueText = $targetValueNumber !== null
                ? $this->formatNoticeTargetValue($targetValueNumber)
                : '-';
            $criteriaOperator = $criteriaDisabled ? '' : trim((string) ($score->criteria_operator ?? ''));
            $targetGoal = $this->resolveNoticeCriteriaSymbol($criteriaOperator);

            $rows[] = [
                'employee_code' => (string) ($owner['employee_code'] ?? ''),
                'full_name_th' => (string) ($owner['full_name_th'] ?? ''),
                'full_name_en' => (string) ($owner['full_name_en'] ?? ''),
                'position' => (string) ($owner['position'] ?? ''),
                'objective' => trim((string) ($score->objective ?? '')),
                'detail' => trim((string) ($score->detail ?? '')),
                'target_value' => $targetValueText,
                'target_goal' => $targetGoal !== '' ? $targetGoal : '-',
                'unit' => $criteriaDisabled ? '-' : $this->resolveNoticeUnitLabel($score->unit, $lang),
            ];
        }

        return [
            'cycle_label' => $cycleLabel,
            'rows' => array_values($rows),
        ];
    }

    private function formatNoticeTargetValue(float $value): string
    {
        $formatted = number_format($value, 10, '.', '');
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');

        return $formatted !== '' ? $formatted : '0';
    }

    private function resolveNoticeCriteriaSymbol(string $operator): string
    {
        return match ($operator) {
            '>=' => "\u{2265}",
            '<=' => "\u{2264}",
            '!=' => "\u{2260}",
            '>' => '>',
            '<' => '<',
            '=' => '=',
            default => '',
        };
    }

    private function normalizeNoticeLevelThreeDepartmentCode(?string $departmentCode): string
    {
        $text = strtoupper(trim((string) $departmentCode));
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return $text;
    }

    private function normalizeNoticeLevelThreePosition(?string $position): string
    {
        $text = strtolower(trim((string) $position));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    private function noticeLevelThreeManagerPositions(): array
    {
        return array_values(array_unique(array_map(
            fn (string $position): string => $this->normalizeNoticeLevelThreePosition($position),
            self::NOTICE_LEVEL_THREE_MANAGER_POSITIONS
        )));
    }

    private function normalizeNoticeLevelThreeDepartmentCodes(array $departmentCodes): array
    {
        $normalized = [];
        foreach ($departmentCodes as $departmentCode) {
            $code = $this->normalizeNoticeLevelThreeDepartmentCode((string) $departmentCode);
            if ($code !== '') {
                $normalized[] = $code;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function firstNoticeLevelThreeDepartmentCode(array $departmentCodes): string
    {
        $normalized = $this->normalizeNoticeLevelThreeDepartmentCodes($departmentCodes);

        return $normalized[0] ?? '';
    }

    private function normalizeUserIdList(array $rawUserIds): array
    {
        $userIds = [];
        foreach ($rawUserIds as $rawUserId) {
            $userId = (int) $rawUserId;
            if ($userId > 0) {
                $userIds[] = $userId;
            }
        }

        return array_values(array_unique($userIds));
    }

    private function ensureTargetAssignmentCaches(): void
    {
        if ($this->cachedTargetDepartmentsByUserId !== null && $this->cachedTargetUserIdsByDepartment !== null) {
            return;
        }

        $departmentsByUserId = [];
        $userIdsByDepartment = [];
        $hasTargetUserIdsJsonColumn = Schema::hasColumn('admin_department_assignments', 'target_user_ids_json');
        $columns = [
            'target_user_id',
            'dept_abbr_hr',
        ];
        if ($hasTargetUserIdsJsonColumn) {
            $columns[] = 'target_user_ids_json';
        }
        $rows = AdminDepartmentAssignment::query()
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->get($columns);

        foreach ($rows as $row) {
            $departmentCode = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($row->dept_abbr_hr ?? ''));
            if ($departmentCode === '') {
                continue;
            }

            $targetUserIds = [];
            if ($hasTargetUserIdsJsonColumn) {
                $targetUserIds = $this->normalizeUserIdList(
                    is_array($row->target_user_ids_json ?? null) ? $row->target_user_ids_json : []
                );
            }
            if ($targetUserIds === []) {
                $legacyTargetUserId = (int) ($row->target_user_id ?? 0);
                if ($legacyTargetUserId > 0) {
                    $targetUserIds[] = $legacyTargetUserId;
                }
            }
            if ($targetUserIds === []) {
                continue;
            }

            foreach ($targetUserIds as $targetUserId) {
                $departmentsByUserId[$targetUserId] ??= [];
                if (! in_array($departmentCode, $departmentsByUserId[$targetUserId], true)) {
                    $departmentsByUserId[$targetUserId][] = $departmentCode;
                }

                $userIdsByDepartment[$departmentCode] ??= [];
                if (! in_array($targetUserId, $userIdsByDepartment[$departmentCode], true)) {
                    $userIdsByDepartment[$departmentCode][] = $targetUserId;
                }
            }
        }

        foreach ($departmentsByUserId as $userId => $departmentCodes) {
            sort($departmentCodes, SORT_STRING);
            $departmentsByUserId[$userId] = array_values($departmentCodes);
        }

        foreach ($userIdsByDepartment as $departmentCode => $userIds) {
            sort($userIds, SORT_NUMERIC);
            $userIdsByDepartment[$departmentCode] = array_values($userIds);
        }

        $this->cachedTargetDepartmentsByUserId = $departmentsByUserId;
        $this->cachedTargetUserIdsByDepartment = $userIdsByDepartment;
    }

    private function resolveAssignedTargetDepartmentsByUser(?AppUser $user): array
    {
        if (! $user) {
            return [];
        }

        return $this->resolveAssignedTargetDepartmentsByUserId((int) $user->id);
    }

    private function resolveAssignedTargetDepartmentsByUserId(int $userId): array
    {
        if ($userId < 1) {
            return [];
        }

        $this->ensureTargetAssignmentCaches();

        return $this->cachedTargetDepartmentsByUserId[$userId] ?? [];
    }

    private function resolveAssignedTargetUserIdsByDepartment(string $departmentCode): array
    {
        $normalizedDepartmentCode = $this->normalizeNoticeLevelThreeDepartmentCode($departmentCode);
        if ($normalizedDepartmentCode === '') {
            return [];
        }

        $this->ensureTargetAssignmentCaches();

        return $this->cachedTargetUserIdsByDepartment[$normalizedDepartmentCode] ?? [];
    }

    private function canSelectNoticeLevelThreeTargetDepartments(?AppUser $user): bool
    {
        return $this->resolveAssignedTargetDepartmentsByUser($user) !== [];
    }

    private function resolveAllowedNoticeLevelThreeTargetDepartments(?AppUser $user): array
    {
        $assigned = $this->resolveAssignedTargetDepartmentsByUser($user);
        if ($assigned === []) {
            return [];
        }

        // เพิ่ม dept_abbr_hr ของ user เองเป็นตัวเลือกแรก (default) ถ้ายังไม่มีใน list
        $ownDept = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($user?->dept_abbr_hr ?? ''));
        if ($ownDept !== '' && ! in_array($ownDept, $assigned, true)) {
            return array_merge([$ownDept], $assigned);
        }

        return $assigned;
    }

    private function resolveDefaultNoticeLevelThreeTargetDepartments(?AppUser $user): array
    {
        $allowedDepartments = $this->resolveAllowedNoticeLevelThreeTargetDepartments($user);
        if ($allowedDepartments === []) {
            return [];
        }

        return [$allowedDepartments[0]];
    }

    private function resolveCardTargetDepartmentsForSave(?AppUser $user, mixed $rawDepartments): array
    {
        $allowedDepartments = $this->resolveAllowedNoticeLevelThreeTargetDepartments($user);
        if ($allowedDepartments === []) {
            return [];
        }

        $submittedDepartments = $this->normalizeNoticeLevelThreeDepartmentCodes(
            is_array($rawDepartments) ? $rawDepartments : []
        );

        if ($submittedDepartments !== []) {
            foreach ($allowedDepartments as $departmentCode) {
                if (in_array($departmentCode, $submittedDepartments, true)) {
                    return [$departmentCode];
                }
            }
        }

        return [$allowedDepartments[0]];
    }

    private function resolveOkrSelectionForSave(?AppUser $user, mixed $objectiveId, mixed $keyResultId): ?array
    {
        $objectiveId = (int) $objectiveId;
        $keyResultId = (int) $keyResultId;

        if ($objectiveId < 1 && $keyResultId < 1) {
            return [
                'objective_id' => null,
                'key_result_id' => null,
            ];
        }

        if ($objectiveId < 1 || $keyResultId < 1) {
            return null;
        }

        /** @var OkrKeyResult|null $keyResult */
        $keyResult = OkrKeyResult::query()
            ->whereKey($keyResultId)
            ->first([
                'id',
                'okr_objective_id',
                'dept_abbr_hr',
            ]);

        if (! $keyResult || (int) $keyResult->okr_objective_id !== $objectiveId) {
            return null;
        }

        return [
            'objective_id' => $objectiveId,
            'key_result_id' => $keyResultId,
        ];
    }

    private function selectableParentTargetKpiRows(?AppUser $user, int $cycleId): Collection
    {
        if ($cycleId < 1 || ! $this->supportsKpiMonthScoreOkrColumns()) {
            return collect();
        }

        $allowedDepartments = $this->resolveAllowedNoticeLevelThreeTargetDepartments($user);
        if ($allowedDepartments === []) {
            // fallback: user ที่ไม่มี assignment ให้ใช้ dept_abbr_hr ของตนเอง
            $ownDept = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($user?->dept_abbr_hr ?? ''));
            if ($ownDept === '') {
                return collect();
            }
            $allowedDepartments = [$ownDept];
        }

        $query = KpiMonthScore::query()
            ->where('cycle_id', $cycleId)
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->where('month_no', 0)
                        ->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            })
            ->whereNotNull('okr_objective_id')
            ->whereNotNull('okr_key_result_id')
            ->orderBy('id');

        if ($this->supportsKpiMonthScoreModeTypeColumn()) {
            $query->where('mode_type', 'target');
        }

        return $query->get([
            'id',
            'target_departments',
            'okr_objective_id',
            'okr_key_result_id',
        ])->filter(function (KpiMonthScore $score) use ($allowedDepartments): bool {
            $scoreDepartments = $this->normalizeNoticeLevelThreeDepartmentCodes(
                is_array($score->target_departments) ? $score->target_departments : []
            );

            if ($scoreDepartments === []) {
                return true;
            }

            return array_values(array_intersect($allowedDepartments, $scoreDepartments)) !== [];
        })->values();
    }

    /**
     * @return array{parent_target_kpi_id: int|null, objective_id: int|null, key_result_id: int|null}|null
     */
    private function resolveParentTargetKpiSelectionForSave(?AppUser $user, Cycle $cycle, mixed $parentTargetId, ?KpiMonthScore $root): ?array
    {
        $cycleId = (int) ($cycle->id ?? 0);
        $requestedParentTargetId = (int) $parentTargetId;

        if (
            $requestedParentTargetId < 1 &&
            $root &&
            $this->supportsKpiMonthScoreParentTargetColumn() &&
            (int) ($root->parent_target_kpi_id ?? 0) > 0
        ) {
            $requestedParentTargetId = (int) $root->parent_target_kpi_id;
        }

        $selectableRows = $this->selectableParentTargetKpiRows($user, $cycleId);
        if ($requestedParentTargetId < 1) {
            if ($selectableRows->isNotEmpty()) {
                return null;
            }

            return [
                'parent_target_kpi_id' => null,
                'objective_id' => null,
                'key_result_id' => null,
            ];
        }

        /** @var KpiMonthScore|null $target */
        $target = $selectableRows->first(
            static fn (KpiMonthScore $score): bool => (int) $score->id === $requestedParentTargetId
        );

        if (! $target) {
            return null;
        }

        if ($root && (int) $target->id === (int) $root->id) {
            return null;
        }

        $objectiveId = (int) ($target->okr_objective_id ?? 0);
        $keyResultId = (int) ($target->okr_key_result_id ?? 0);
        if ($objectiveId < 1 || $keyResultId < 1) {
            return null;
        }

        return [
            'parent_target_kpi_id' => (int) $target->id,
            'objective_id' => $objectiveId,
            'key_result_id' => $keyResultId,
        ];
    }

    private function hasSelectableOkrHierarchyForUser(?AppUser $user): bool
    {
        if (! $user) {
            return false;
        }

        return OkrKeyResult::query()->exists();
    }

    private function resolveCardTargetDepartmentsForDisplay(?AppUser $user, array $storedDepartments): array
    {
        $allowedDepartments = $this->resolveAllowedNoticeLevelThreeTargetDepartments($user);
        $normalizedDepartments = $this->normalizeNoticeLevelThreeDepartmentCodes($storedDepartments);

        if ($allowedDepartments !== []) {
            foreach ($allowedDepartments as $departmentCode) {
                if (in_array($departmentCode, $normalizedDepartments, true)) {
                    return [$departmentCode];
                }
            }

            return [$allowedDepartments[0]];
        }

        $firstDepartment = $this->firstNoticeLevelThreeDepartmentCode($normalizedDepartments);

        return $firstDepartment !== '' ? [$firstDepartment] : [];
    }

    private function resolveNoticeLevelThreeTargetDepartments(KpiMonthScore $score, array $owner): array
    {
        $scoreDepartments = $this->normalizeNoticeLevelThreeDepartmentCodes(
            is_array($score->target_departments) ? $score->target_departments : []
        );
        if ($scoreDepartments !== []) {
            return [$scoreDepartments[0]];
        }

        // fallback: ใช้แผนกของเจ้าของ entry โดยตรง
        $ownerDepartment = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($owner['dept_abbr_hr'] ?? ''));

        return $ownerDepartment !== '' ? [$ownerDepartment] : [];
    }

    private function buildKpiDepartmentTargetPolicy(?AppUser $user): array
    {
        $enabled = $this->canSelectNoticeLevelThreeTargetDepartments($user);
        $options = $enabled ? $this->resolveAllowedNoticeLevelThreeTargetDepartments($user) : [];
        $defaultDepartments = $enabled ? $this->resolveDefaultNoticeLevelThreeTargetDepartments($user) : [];
        $viewerDepartment = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($user?->dept_abbr_hr ?? ''));
        $hasReviewerAssignment = $enabled
            ? $this->hasAssignedReportReviewerForAnyDepartment($options)
            : false;

        return [
            'enabled' => $enabled,
            'has_target_assignment' => $enabled,
            'has_reviewer_assignment' => $hasReviewerAssignment,
            'viewer_department' => $viewerDepartment,
            'options' => $options,
            'default_departments' => $defaultDepartments,
        ];
    }

    private function hasAssignedReportReviewerForAnyDepartment(array $departmentCodes): bool
    {
        $normalizedDepartmentCodes = $this->normalizeNoticeLevelThreeDepartmentCodes($departmentCodes);
        if ($normalizedDepartmentCodes === []) {
            return false;
        }

        foreach ($normalizedDepartmentCodes as $departmentCode) {
            $reviewerUserIds = array_values(array_filter(array_map(
                'intval',
                DepartmentAssignmentResolver::resolveReviewerUserIdsByDepartment($departmentCode)
            ), static fn (int $userId): bool => $userId > 0));

            if ($reviewerUserIds !== []) {
                return true;
            }
        }

        return false;
    }

    private function resolveNoticeUnitLabel(mixed $unit, string $lang): string
    {
        if (! $unit) {
            return '-';
        }

        $name = $lang === 'th'
            ? trim((string) ($unit->name_th ?? ''))
            : trim((string) ($unit->name_en ?? ''));
        if ($name !== '') {
            return $name;
        }

        $code = trim((string) ($unit->code ?? ''));
        if ($code !== '') {
            return $code;
        }

        $fallback = trim((string) ($unit->name_en ?? ''));

        return $fallback !== '' ? $fallback : '-';
    }

    private function resolveOrCreateCustomUnitId(string $rawLabel): int
    {
        $label = trim($rawLabel);
        if ($label === '') {
            return 0;
        }

        /** @var KpiUnit|null $existing */
        $existing = KpiUnit::query()
            ->where(function ($query) use ($label) {
                $query->where('code', $label)
                    ->orWhere('name_th', $label)
                    ->orWhere('name_en', $label);
            })
            ->orderBy('id')
            ->first();

        if ($existing) {
            if (! (bool) $existing->is_active) {
                $existing->is_active = true;
                $existing->save();
            }

            return (int) $existing->id;
        }

        $maxSortOrder = (int) (KpiUnit::query()->max('sort_order') ?? 0);
        $created = KpiUnit::query()->firstOrCreate(
            ['code' => $label],
            [
                'name_th' => $label,
                'name_en' => $label,
                'sort_order' => $maxSortOrder + 1,
                'is_active' => true,
            ]
        );
        if (! (bool) $created->is_active) {
            $created->is_active = true;
            $created->save();
        }

        return (int) $created->id;
    }

    private function availableKpiUnits(): array
    {
        return KpiUnit::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name_th', 'name_en'])
            ->map(function (KpiUnit $unit) {
                return [
                    'id' => (int) $unit->id,
                    'code' => trim((string) $unit->code),
                    'name_th' => trim((string) $unit->name_th),
                    'name_en' => trim((string) $unit->name_en),
                ];
            })
            ->values()
            ->all();
    }

    private function resolveLangFromRequest(Request $request): string
    {
        $lang = strtolower((string) $request->input('lang', $request->query('lang', 'en')));

        return $lang === 'th' ? 'th' : 'en';
    }

    private function kpiInputMessage(string $lang, string $key): string
    {
        $messages = [
            'cannotSaveActivateCycle' => [
                'th' => 'ไม่สามารถบันทึกได้ กรุณาเปิดใช้งานรอบก่อน',
                'en' => 'Cannot save. Please activate cycle first.',
            ],
            'kpiItemNotFound' => [
                'th' => 'ไม่พบรายการ KPI',
                'en' => 'KPI item was not found.',
            ],
            'invalidOkrHierarchy' => [
                'th' => 'กรุณาเลือกลำดับชั้น 1 และ 2 ให้ถูกต้องก่อนบันทึก',
                'en' => 'Please select a valid Level 1 and 2 before saving.',
            ],
            'invalidOkrLevelThree' => [
                'th' => 'กรุณาเลือกลำดับชั้นที่ 3 ให้ถูกต้องก่อนบันทึก',
                'en' => 'Please select a valid Level 3 before saving.',
            ],
            'cannotSaveKpiActivateCycle' => [
                'th' => 'ไม่สามารถบันทึก KPI ได้ กรุณาเปิดใช้งานรอบก่อน',
                'en' => 'Cannot save KPI. Please activate cycle first.',
            ],
            'saveTargetAndCriteriaFirst' => [
                'th' => 'กรุณาบันทึกเป้าหมายและเกณฑ์ก่อน',
                'en' => 'Please save Target and Criteria first.',
            ],
            'saveTargetCriteriaAndUnitFirst' => [
                'th' => 'กรุณาบันทึกเป้าหมาย เกณฑ์ และหน่วยก่อน',
                'en' => 'Please save Target, Criteria, and Unit first.',
            ],
            'monthNotOpenedByAdmin' => [
                'th' => 'เดือนนี้ยังไม่เปิดรอบจากผู้ดูแลระบบ',
                'en' => 'This month is not opened by admin yet.',
            ],
            'attachEvidenceBeforeSaveMonth' => [
                'th' => 'กรุณาแนบไฟล์หลักฐานอย่างน้อย 1 ไฟล์ก่อนบันทึกเดือนนี้',
                'en' => 'Please attach at least one evidence file before saving this month.',
            ],
            'attachActionPlanBeforeSaveMonth' => [
                'th' => 'กรุณาแนบไฟล์ Action Plan อย่างน้อย 1 ไฟล์ก่อนบันทึกเดือนนี้',
                'en' => 'Please attach at least one Action Plan file before saving this month.',
            ],
            'attachActionPlanBeforeConfirmResult' => [
                'th' => 'กรุณาแนบ Action Plan ก่อนยืนยันผล KPI',
                'en' => 'Please attach Action Plan before confirming KPI result.',
            ],
            'saveAtLeastOneMonth' => [
                'th' => 'กรุณาบันทึกอย่างน้อย 1 เดือนก่อน',
                'en' => 'Please save at least 1 month first.',
            ],
        ];

        $langKey = $lang === 'th' ? 'th' : 'en';

        if (isset($messages[$key][$langKey])) {
            return $messages[$key][$langKey];
        }

        return $messages[$key]['en'] ?? $key;
    }

    private function normalizePosition(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function normalizeKpiModeType(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return $normalized === 'target' ? 'target' : 'report';
    }

    private function normalizePositionList(array $positions): array
    {
        $normalized = array_map(function ($position) {
            return $this->normalizePosition($position);
        }, $positions);

        $normalized = array_filter($normalized, function ($position) {
            return $position !== '';
        });

        return array_values(array_unique($normalized));
    }

    private function managerPositionsWithoutEvidence(): array
    {
        return $this->normalizePositionList(
            AppUserAuthController::POSITION_LEVEL_MAP[AppUserAuthController::LEVEL_4] ?? []
        );
    }

    private function actionPlanRequiredPositions(): array
    {
        $levelTwo = AppUserAuthController::POSITION_LEVEL_MAP[AppUserAuthController::LEVEL_2] ?? [];
        $levelThree = AppUserAuthController::POSITION_LEVEL_MAP[AppUserAuthController::LEVEL_3] ?? [];

        return $this->normalizePositionList(array_merge($levelTwo, $levelThree));
    }

    private function canSkipEvidenceByPosition(string $position): bool
    {
        return in_array($position, $this->managerPositionsWithoutEvidence(), true);
    }

    private function canConfirmWithoutSavedMonthByPosition(string $position): bool
    {
        return in_array(
            $position,
            $this->normalizePositionList(self::KPI_CONFIRM_WITHOUT_SCORE_POSITIONS),
            true
        );
    }

    private function showActionPlanOnFailByPosition(string $position): bool
    {
        if ($this->mustUploadActionPlanByPosition($position)) {
            return true;
        }

        return $this->canSkipEvidenceByPosition($position);
    }

    private function mustUploadActionPlanByPosition(string $position): bool
    {
        return in_array($position, $this->actionPlanRequiredPositions(), true);
    }

    private function rootRowsQuery(int $cycleId, int $authUserId)
    {
        return KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', $cycleId)
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('month_no', 0)
                        ->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            });
    }

    private function syncUserKpiAverageByCycle(int $authUserId, int $cycleId): ?float
    {
        $rootRows = $this->rootRowsQuery($cycleId, $authUserId)
            ->whereNotNull('result')
            ->get([
                'id',
                'app_user_id',
                'target_departments',
                'result',
            ]);

        $average = $rootRows->avg('result');

        if ($average === null) {
            KpiResult::query()
                ->where('app_user_id', $authUserId)
                ->where('cycle_id', $cycleId)
                ->delete();

            $this->syncDepartmentOkrAverage($cycleId);

            return null;
        }

        $roundedAverage = round((float) $average, 2);
        $hasResultDepartmentColumn = Schema::hasColumn('kpi_result', 'dept_abbr_hr');
        if (! $hasResultDepartmentColumn) {
            KpiResult::query()->updateOrCreate(
                [
                    'app_user_id' => $authUserId,
                    'cycle_id' => $cycleId,
                ],
                ['result' => $roundedAverage]
            );

            $this->syncDepartmentOkrAverage($cycleId);

            return $roundedAverage;
        }

        /** @var AppUser|null $owner */
        $owner = AppUser::query()
            ->whereKey($authUserId)
            ->first(['id', 'position', 'dept_abbr_hr']);

        $departmentScores = [];
        foreach ($rootRows as $rootRow) {
            $department = $this->resolveDepartmentForDepartmentKpiAverage($rootRow, $owner);
            if ($department === '') {
                continue;
            }

            $departmentScores[$department] ??= [];
            $departmentScores[$department][] = (float) $rootRow->result;
        }

        if ($departmentScores === [] && $owner) {
            $fallbackDepartment = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($owner->dept_abbr_hr ?? ''));
            if ($fallbackDepartment !== '') {
                $departmentScores[$fallbackDepartment] = $rootRows
                    ->map(fn (KpiMonthScore $row): float => (float) $row->result)
                    ->all();
            }
        }

        $departments = [];
        $payload = [];
        $now = now();
        foreach ($departmentScores as $department => $scores) {
            if (! is_array($scores) || $scores === []) {
                continue;
            }

            $departments[] = $department;
            $payload[] = [
                'app_user_id' => $authUserId,
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
                ->where('app_user_id', $authUserId)
                ->where('cycle_id', $cycleId)
                ->whereNotIn('dept_abbr_hr', $departments)
                ->delete();
        } else {
            KpiResult::query()
                ->where('app_user_id', $authUserId)
                ->where('cycle_id', $cycleId)
                ->delete();
        }

        $this->syncDepartmentOkrAverage($cycleId);

        return $roundedAverage;
    }

    private function resolveUserKpiAverageByDepartment(int $authUserId, int $cycleId, ?AppUser $user = null): array
    {
        $hasResultDepartmentColumn = Schema::hasColumn('kpi_result', 'dept_abbr_hr');
        $rows = KpiResult::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', $cycleId)
            ->get($hasResultDepartmentColumn ? ['dept_abbr_hr', 'result'] : ['result']);

        if ($rows->isEmpty()) {
            return [];
        }

        $result = [];
        if ($hasResultDepartmentColumn) {
            foreach ($rows as $row) {
                $department = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($row->dept_abbr_hr ?? ''));
                if ($department === '' || $row->result === null) {
                    continue;
                }

                $result[$department] = round((float) $row->result, 2);
            }
        } else {
            $ownDepartment = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($user?->dept_abbr_hr ?? ''));
            $first = $rows->first();
            if ($ownDepartment !== '' && $first && $first->result !== null) {
                $result[$ownDepartment] = round((float) $first->result, 2);
            }
        }

        if ($result === []) {
            return [];
        }

        ksort($result);

        return $result;
    }

    private function resolveDepartmentForDepartmentKpiAverage(KpiMonthScore $score, ?AppUser $owner): string
    {
        if (! $owner) {
            return '';
        }

        $ownerDepartment = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($owner->dept_abbr_hr ?? ''));
        $assignedDepartments = $this->resolveAssignedTargetDepartmentsByUser($owner);
        if ($assignedDepartments !== []) {
            $selectedDepartment = $this->firstNoticeLevelThreeDepartmentCode(
                is_array($score->target_departments) ? $score->target_departments : []
            );
            if ($selectedDepartment !== '') {
                return $selectedDepartment;
            }

            return $assignedDepartments[0];
        }

        return $ownerDepartment;
    }

    private function syncDepartmentOkrAverage(int $cycleId): void
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
            $this->syncAllDepartmentOkrAverage($cycleId);

            return;
        }

        $now = now();
        $payload = [];
        $departments = [];

        foreach ($rows as $row) {
            $department = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($row->dept_abbr_hr ?? ''));
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
            $this->syncAllDepartmentOkrAverage($cycleId);

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

        $this->syncAllDepartmentOkrAverage($cycleId);
    }

    private function syncAllDepartmentOkrAverage(int $cycleId): void
    {
        $average = OkrResult::query()
            ->where('cycle_id', $cycleId)
            ->whereNotNull('result')
            ->avg('result');

        if ($average === null) {
            OkrAllResult::query()
                ->where('cycle_id', $cycleId)
                ->delete();

            return;
        }

        OkrAllResult::query()->updateOrCreate(
            ['cycle_id' => $cycleId],
            ['result' => round((float) $average, 2)]
        );
    }

    private function buildSavedCards(?Cycle $activeCycle): array
    {
        if (! $activeCycle) {
            return [];
        }

        $authUserId = (int) auth()->id();
        /** @var AppUser|null $authUser */
        $authUser = auth()->user();
        $this->normalizeLegacyMonthRows((int) $activeCycle->id, $authUserId);

        $rootRows = $this->rootRowsQuery((int) $activeCycle->id, $authUserId)
            ->orderBy('id')
            ->get();

        if ($rootRows->isEmpty()) {
            return [];
        }

        $monthRows = KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', (int) $activeCycle->id)
            ->whereIn('kpi_meta_id', $rootRows->pluck('id')->all())
            ->whereBetween('month_no', [1, 12])
            ->orderBy('month_no')
            ->get();
        $monthRowsByRoot = $monthRows->groupBy('kpi_meta_id');
        $reviewRowsByScoreId = $monthRows->isEmpty()
            ? collect()
            : KpiMonthReview::query()
                ->whereIn('kpi_month_score_id', $monthRows->pluck('id')->all())
                ->get([
                    'kpi_month_score_id',
                    'status',
                    'reject_detail',
                    'reviewed_at',
                ])
                ->keyBy('kpi_month_score_id');

        $savedCards = [];

        /** @var KpiMonthScore $root */
        foreach ($rootRows as $root) {
            /** @var Collection<int, KpiMonthScore> $rows */
            $rows = $monthRowsByRoot->get((int) $root->id, collect());

            $cardMonths = [];
            /** @var KpiMonthScore $row */
            foreach ($rows as $row) {
                /** @var KpiMonthReview|null $review */
                $review = $reviewRowsByScoreId->get((int) $row->id);
                $evidenceFiles = $this->buildEvidenceFilePayload($row->evidence_files, (int) $row->id);
                $actionPlanFiles = $this->buildEvidenceFilePayload($row->action_plan_files, (int) $row->id, 'action_plan');
                $cardMonths[(int) $row->month_no] = [
                    'score_id' => (int) $row->id,
                    'score' => $row->score_value !== null ? (float) $row->score_value : null,
                    'is_pass' => (bool) $row->is_pass,
                    'evidence_count' => count($evidenceFiles),
                    'evidence_files' => $evidenceFiles,
                    'action_plan_count' => count($actionPlanFiles),
                    'action_plan_files' => $actionPlanFiles,
                    'submitted_at' => $row->submitted_at?->format('d/m/Y H:i'),
                    'review_status' => $this->normalizeReviewStatus($review?->status),
                    'review_reject_detail' => trim((string) ($review?->reject_detail ?? '')),
                    'reviewed_at' => $review?->reviewed_at?->format('d/m/Y H:i'),
                ];
            }

            $operator = trim((string) $root->criteria_operator);
            $allSavedMonthsSubmitted = $rows->isNotEmpty() && $rows->every(static function (KpiMonthScore $row): bool {
                return $row->submitted_at !== null;
            });
            $targetDepartments = $this->resolveCardTargetDepartmentsForDisplay(
                $authUser,
                is_array($root->target_departments) ? $root->target_departments : []
            );
            $savedCards[] = [
                'item_id' => (int) $root->id,
                'mode_type' => $this->normalizeKpiModeType($root->mode_type ?? 'report'),
                'objective' => (string) $root->objective,
                'detail' => (string) $root->detail,
                'target_departments' => $targetDepartments,
                'okr_objective_id' => $root->okr_objective_id !== null ? (int) $root->okr_objective_id : null,
                'okr_key_result_id' => $root->okr_key_result_id !== null ? (int) $root->okr_key_result_id : null,
                'parent_target_kpi_id' => $root->parent_target_kpi_id !== null ? (int) $root->parent_target_kpi_id : null,
                'target' => $operator === '' ? null : (float) $root->target_value,
                'kpi_unit_id' => $root->kpi_unit_id !== null ? (int) $root->kpi_unit_id : null,
                'criteria_operator' => $operator,
                'has_criteria' => $root->has_criteria !== null
                    ? (bool) $root->has_criteria
                    : ($operator !== '' && $root->kpi_unit_id !== null && $root->target_value !== null ? true : null),
                'result' => $root->result !== null ? (float) $root->result : null,
                'result_confirmed' => $allSavedMonthsSubmitted || $root->result !== null,
                'months' => $cardMonths,
            ];
        }

        return $savedCards;
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

    private function supportsKpiMonthScoreModeTypeColumn(): bool
    {
        if ($this->kpiMonthScoreHasModeTypeColumn === null) {
            $this->kpiMonthScoreHasModeTypeColumn = Schema::hasColumn('kpi_month_scores', 'mode_type');
        }

        return $this->kpiMonthScoreHasModeTypeColumn;
    }

    private function supportsKpiMonthScoreOkrColumns(): bool
    {
        if ($this->kpiMonthScoreHasOkrColumns === null) {
            $this->kpiMonthScoreHasOkrColumns = Schema::hasColumn('kpi_month_scores', 'okr_objective_id')
                && Schema::hasColumn('kpi_month_scores', 'okr_key_result_id');
        }

        return $this->kpiMonthScoreHasOkrColumns;
    }

    private function supportsKpiMonthScoreParentTargetColumn(): bool
    {
        if ($this->kpiMonthScoreHasParentTargetColumn === null) {
            $this->kpiMonthScoreHasParentTargetColumn = Schema::hasColumn('kpi_month_scores', 'parent_target_kpi_id');
        }

        return $this->kpiMonthScoreHasParentTargetColumn;
    }

    private function supportsKpiMonthScoreHasCriteriaFlagColumn(): bool
    {
        if ($this->kpiMonthScoreHasCriteriaFlagColumn === null) {
            $this->kpiMonthScoreHasCriteriaFlagColumn = Schema::hasColumn('kpi_month_scores', 'has_criteria');
        }

        return $this->kpiMonthScoreHasCriteriaFlagColumn;
    }

    private function filterKpiMonthScoreWriteAttributes(array $attributes): array
    {
        if (! $this->supportsKpiMonthScoreModeTypeColumn()) {
            unset($attributes['mode_type']);
        }

        if (! $this->supportsKpiMonthScoreOkrColumns()) {
            unset($attributes['okr_objective_id'], $attributes['okr_key_result_id']);
        }

        if (! $this->supportsKpiMonthScoreParentTargetColumn()) {
            unset($attributes['parent_target_kpi_id']);
        }

        if (! $this->supportsKpiMonthScoreHasCriteriaFlagColumn()) {
            unset($attributes['has_criteria']);
        }

        return $attributes;
    }

    /**
     * @return array{result: float|null, approved_count: int, point: int, max: int}
     */
    private function syncRootResultFromApprovedMonths(int $rootId, int $ownerId, int $cycleId): array
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

        if ($monthRows->isEmpty()) {
            KpiMonthScore::query()
                ->where('app_user_id', $ownerId)
                ->where('cycle_id', $cycleId)
                ->where(function ($query) use ($rootId): void {
                    $query->where('id', $rootId)
                        ->orWhere('kpi_meta_id', $rootId);
                })
                ->update([
                    'result' => null,
                ]);

            return [
                'result' => null,
                'approved_count' => 0,
                'point' => 0,
                'max' => 0,
            ];
        }

        $reviewRows = KpiMonthReview::query()
            ->whereIn('kpi_month_score_id', $monthRows->pluck('id')->all())
            ->get([
                'kpi_month_score_id',
                'status',
            ])
            ->keyBy('kpi_month_score_id');

        $approvedCount = 0;
        $point = 0;
        /** @var KpiMonthScore $monthRow */
        foreach ($monthRows as $monthRow) {
            /** @var KpiMonthReview|null $review */
            $review = $reviewRows->get((int) $monthRow->id);
            if ($this->normalizeReviewStatus($review?->status) !== KpiMonthReview::STATUS_APPROVED) {
                continue;
            }

            $approvedCount++;
            $point += (bool) $monthRow->is_pass ? 100 : 0;
        }

        $max = $approvedCount * 100;
        $result = $max > 0 ? round(($point / $max) * 100, 2) : null;

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

        return [
            'result' => $result,
            'approved_count' => $approvedCount,
            'point' => $point,
            'max' => $max,
        ];
    }

    private function upsertPendingReviewForSavedMonth(
        KpiMonthScore $score,
        int $rootId,
        ?AppUser $owner,
        string $lang,
        bool $notifyReviewers = false
    ): ?KpiMonthReview {
        $monthNo = (int) ($score->month_no ?? 0);
        if ($monthNo < 1 || $monthNo > 12) {
            return null;
        }

        /** @var KpiMonthScore|null $root */
        $root = KpiMonthScore::query()
            ->whereKey($rootId)
            ->first([
                'id',
                'objective',
                'target_departments',
            ]);

        $departmentCode = '';
        if ($root) {
            $departmentCode = $this->firstNoticeLevelThreeDepartmentCode(
                is_array($root->target_departments) ? $root->target_departments : []
            );
        }
        if ($departmentCode === '') {
            $departmentCode = $this->normalizeNoticeLevelThreeDepartmentCode((string) ($owner?->dept_abbr_hr ?? ''));
        }

        $reviewerUserIds = $departmentCode !== ''
            ? DepartmentAssignmentResolver::resolveReviewerUserIdsByDepartment($departmentCode)
            : [];
        $reviewerUserIds = array_values(array_unique(array_filter(array_map('intval', $reviewerUserIds))));

        $review = KpiMonthReview::query()->firstOrNew([
            'kpi_month_score_id' => (int) $score->id,
        ]);

        $currentStatus = $this->normalizeReviewStatus((string) ($review->status ?? ''));
        if ($currentStatus === KpiMonthReview::STATUS_APPROVED) {
            return $review->exists ? $review->fresh() : $review;
        }

        $review->status = KpiMonthReview::STATUS_PENDING;
        $review->reviewed_by_user_id = null;
        $review->reviewed_at = null;
        $review->reject_detail = null;
        $review->save();

        if ($notifyReviewers && $reviewerUserIds !== []) {
            $ownerName = $this->resolveOwnerDisplayName($owner, $lang);
            $ownerPosition = trim((string) ($owner?->position ?? ''));
            $ownerPositionDisplay = $ownerPosition !== '' ? ucwords(strtolower($ownerPosition)) : '-';
            $ownerEmployeeCode = trim((string) ($owner?->employee_code ?? ''));
            $objective = trim((string) ($root?->objective ?? ''));
            if ($objective === '') {
                $objective = $lang === 'th' ? 'ไม่ระบุหัวข้อ' : 'Untitled KPI';
            }
            $reviewLink = route('kpi.review.index', ['lang' => $lang]);
            $title = $lang === 'th' ? 'มีรายงาน KPI ใหม่รอตรวจ' : 'New KPI report submitted';
            $message = $lang === 'th'
                ? "คุณได้รับรายงานจาก {$ownerName} ({$ownerEmployeeCode}) แผนก {$departmentCode} ตำแหน่ง {$ownerPositionDisplay}"
                : "You received a KPI report from {$ownerName} ({$ownerEmployeeCode}) in {$departmentCode}.";

            foreach ($reviewerUserIds as $reviewerUserId) {
                if ($reviewerUserId < 1) {
                    continue;
                }

                AppNotification::query()->create([
                    'recipient_user_id' => $reviewerUserId,
                    'actor_user_id' => (int) ($owner?->id ?? 0) ?: null,
                    'type' => 'kpi_review_requested',
                    'title' => $title,
                    'message' => $message,
                    'link_url' => $reviewLink,
                    'payload_json' => [
                        'score_id' => (int) $score->id,
                        'root_id' => $rootId,
                        'month_no' => $monthNo,
                        'cycle_id' => (int) ($score->cycle_id ?? 0),
                        'is_pass' => (bool) ($score->is_pass ?? false),
                        'objective' => $objective,
                        'owner_name' => $ownerName,
                        'owner_employee_code' => $ownerEmployeeCode,
                        'department' => $departmentCode,
                    ],
                    'is_read' => false,
                ]);
            }
        }

        return $review->fresh();
    }

    private function resolveOwnerDisplayName(?AppUser $owner, string $lang): string
    {
        if (! $owner) {
            return '-';
        }

        $primary = $lang === 'th'
            ? trim((string) ($owner->full_name_th ?? ''))
            : trim((string) ($owner->full_name_en ?? ''));
        if ($primary !== '') {
            return $primary;
        }

        $fallback = trim((string) ($owner->full_name_en ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        $fallback = trim((string) ($owner->full_name_th ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        $employeeCode = trim((string) ($owner->employee_code ?? ''));

        return $employeeCode !== '' ? $employeeCode : '-';
    }

    private function normalizeLegacyMonthRows(int $cycleId, int $authUserId): void
    {
        $draftRoots = KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', $cycleId)
            ->where('month_no', 0)
            ->whereNull('kpi_meta_id')
            ->orderBy('id')
            ->get();

        /** @var KpiMonthScore $draft */
        foreach ($draftRoots as $draft) {
            $children = KpiMonthScore::query()
                ->where('app_user_id', $authUserId)
                ->where('cycle_id', $cycleId)
                ->where('kpi_meta_id', (int) $draft->id)
                ->whereBetween('month_no', [1, 12])
                ->orderBy('id')
                ->get();

            if ($children->isEmpty()) {
                continue;
            }

            /** @var KpiMonthScore $base */
            $base = $children->first();
            $targetValue = (float) $draft->target_value;
            $operator = trim((string) $draft->criteria_operator);
            $draftModeType = $this->normalizeKpiModeType($draft->mode_type ?? 'report');

            $base->update($this->filterKpiMonthScoreWriteAttributes([
                'kpi_meta_id' => (int) $base->id,
                'objective' => trim((string) $draft->objective),
                'detail' => trim((string) $draft->detail),
                'target_departments' => $draft->target_departments,
                'okr_objective_id' => $draft->okr_objective_id,
                'okr_key_result_id' => $draft->okr_key_result_id,
                'parent_target_kpi_id' => $draft->parent_target_kpi_id,
                'target_value' => $targetValue,
                'kpi_unit_id' => $draft->kpi_unit_id !== null ? (int) $draft->kpi_unit_id : null,
                'criteria_operator' => $operator,
                'mode_type' => $draftModeType,
                'is_pass' => $this->evaluateScore((float) $base->score_value, $targetValue, $operator),
            ]));

            /** @var KpiMonthScore $child */
            foreach ($children as $child) {
                if ((int) $child->id === (int) $base->id) {
                    continue;
                }

                $child->update($this->filterKpiMonthScoreWriteAttributes([
                    'kpi_meta_id' => (int) $base->id,
                    'objective' => trim((string) $draft->objective),
                    'detail' => trim((string) $draft->detail),
                    'target_departments' => $draft->target_departments,
                    'okr_objective_id' => $draft->okr_objective_id,
                    'okr_key_result_id' => $draft->okr_key_result_id,
                    'parent_target_kpi_id' => $draft->parent_target_kpi_id,
                    'target_value' => $targetValue,
                    'kpi_unit_id' => $draft->kpi_unit_id !== null ? (int) $draft->kpi_unit_id : null,
                    'criteria_operator' => $operator,
                    'mode_type' => $draftModeType,
                    'is_pass' => $this->evaluateScore((float) $child->score_value, $targetValue, $operator),
                ]));
            }

            $draft->delete();
        }

        $legacyRows = KpiMonthScore::query()
            ->where('app_user_id', $authUserId)
            ->where('cycle_id', $cycleId)
            ->whereNull('kpi_meta_id')
            ->whereBetween('month_no', [1, 12])
            ->orderBy('id')
            ->get();

        if ($legacyRows->isEmpty()) {
            return;
        }

        $groupRoots = [];
        /** @var KpiMonthScore $legacy */
        foreach ($legacyRows as $legacy) {
            $key = implode('|', [
                trim((string) $legacy->objective),
                trim((string) $legacy->detail),
                json_encode(
                    $this->normalizeNoticeLevelThreeDepartmentCodes(
                        is_array($legacy->target_departments) ? $legacy->target_departments : []
                    )
                ),
                (string) ((float) $legacy->target_value),
                (string) ($legacy->kpi_unit_id !== null ? (int) $legacy->kpi_unit_id : 0),
                trim((string) $legacy->criteria_operator),
            ]);

            if (! isset($groupRoots[$key])) {
                $groupRoots[$key] = (int) $legacy->id;
                $legacy->update([
                    'kpi_meta_id' => (int) $legacy->id,
                ]);

                continue;
            }

            $legacy->update([
                'kpi_meta_id' => (int) $groupRoots[$key],
            ]);
        }
    }

    private function findCardRoot(int $itemId, int $authUserId): ?KpiMonthScore
    {
        return KpiMonthScore::query()
            ->whereKey($itemId)
            ->where('app_user_id', $authUserId)
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('month_no', 0)
                        ->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            })
            ->first();
    }

    private function collectUploadedFiles(
        Request $request,
        string $inputName,
        ?KpiMonthScore $existingScore,
        string $storageDir,
        string $scoreColumn
    ): ?array {
        $newFiles = [];
        $uploadedFiles = $request->file($inputName, []);
        if (! is_array($uploadedFiles)) {
            $uploadedFiles = $uploadedFiles ? [$uploadedFiles] : [];
        }

        foreach (($uploadedFiles ?: []) as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store($storageDir, 'public');
            $newFiles[] = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
            ];
        }

        if (count($newFiles) > 0) {
            // New upload replaces old file list for this month.
            $this->deleteStoredFiles($existingScore?->getAttribute($scoreColumn));

            return $newFiles;
        }

        $existing = $existingScore?->getAttribute($scoreColumn);
        $existing = is_array($existing) ? $existing : [];

        return count($existing) > 0 ? $existing : null;
    }

    private function deleteStoredFiles(mixed $rawFiles): void
    {
        if (! is_array($rawFiles)) {
            return;
        }

        foreach ($rawFiles as $file) {
            $path = is_array($file) ? ($file['path'] ?? null) : null;
            if (is_string($path) && trim($path) !== '') {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function buildEvidenceFilePayload(mixed $rawFiles, ?int $rowId = null, string $type = 'evidence'): array
    {
        if (! is_array($rawFiles)) {
            return [];
        }

        $items = [];
        foreach (array_values($rawFiles) as $idx => $file) {
            $path = is_array($file) ? trim((string) ($file['path'] ?? '')) : '';
            if ($path === '') {
                continue;
            }

            $name = is_array($file) ? trim((string) ($file['name'] ?? '')) : '';
            if ($name === '') {
                $name = basename($path);
            }

            $url = Storage::disk('public')->url($path);
            if ($rowId !== null && \Illuminate\Support\Facades\Route::has('kpi.input.evidence.show')) {
                $url = route('kpi.input.evidence.show', [
                    'score' => $rowId,
                    'index' => $idx,
                ], false);
                if ($type !== 'evidence') {
                    $url .= (str_contains($url, '?') ? '&' : '?').'type='.urlencode($type);
                }
            }

            $items[] = [
                'name' => $name,
                'path' => $path,
                'url' => $url,
            ];
        }

        return $items;
    }

    private function evaluateScore(float $score, float $target, string $operator): bool
    {
        return match ($operator) {
            '>' => $score > $target,
            '>=' => $score >= $target,
            '<=' => $score <= $target,
            '<' => $score < $target,
            '=' => $score === $target,
            '!=' => $score !== $target,
            default => false,
        };
    }
}
