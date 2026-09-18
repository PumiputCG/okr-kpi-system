<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppUserAuthController;
use App\Http\Controllers\Controller;
use App\Models\AdminDepartmentAssignment;
use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\KpiMonthReview;
use App\Models\KpiMonthScore;
use App\Models\KpiUnit;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use App\Support\KpiResultSynchronizer;
use App\Support\PlainTextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoalTargetController extends Controller
{
    public function index(Request $request)
    {
        $lang = $this->resolveLang($request);
        $deptFilter = $this->normalizeDepartmentCode((string) $request->get('dept_filter', ''));
        $levelThreeDeptFilter = $this->normalizeDepartmentCode((string) $request->get('l3_dept_filter', ''));
        if ($deptFilter !== '') {
            $levelThreeDeptFilter = '';
        }

        $rows = $this->buildRows($lang, $deptFilter, $levelThreeDeptFilter);
        $deptOptions = $this->getDepartmentOptions();
        $krDeptOptions = $this->getKrDepartmentOptions();
        $levelThreeDeptOptions = $this->getLevelThreeDepartmentOptions();
        $kpiUnits = KpiUnit::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name_th', 'name_en']);

        return view('admin-goal-targets', [
            'lang'                      => $lang,
            'rows'                      => $rows,
            'deptOptions'               => $deptOptions,
            'krDeptOptions'             => $krDeptOptions,
            'levelThreeDeptOptions'     => $levelThreeDeptOptions,
            'kpiUnits'                  => $kpiUnits,
            'deptFilter'                => $deptFilter,
            'levelThreeDeptFilter'      => $levelThreeDeptFilter,
            'hasHierarchyFilter'        => $deptFilter !== '' || $levelThreeDeptFilter !== '',
            'monthlySummaryUrlTemplate' => route('kpi.summary.dept.monthly', ['root' => '__ROOT__']),
        ]);
    }

    public function storeObjective(Request $request): RedirectResponse
    {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'detail' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        $maxSortNo = OkrObjective::query()->max('sort_no') ?? 0;

        $obj = OkrObjective::query()->create([
            'sort_no' => (int) $maxSortNo + 1,
            'title' => PlainTextNormalizer::normalize($validated['title']),
            'detail' => PlainTextNormalizer::nullable($validated['detail'] ?? null),
            'created_by_admin_id' => (int) ($request->user()->id ?? 0),
        ]);

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $stored = $file->store('goal-targets/objectives/'.$obj->id, 'public');
            $obj->file_path = $stored;
            $obj->file_original_name = $file->getClientOriginalName();
            $obj->save();
        }

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th' ? 'เพิ่มลำดับชั้น 1 เรียบร้อยแล้ว' : 'Level 1 objective added.');
    }

    public function updateObjective(Request $request, int $id): RedirectResponse
    {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);

        $obj = OkrObjective::query()->findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'detail' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:20480'],
            'remove_file' => ['nullable', 'string'],
        ]);

        $obj->title = PlainTextNormalizer::normalize($validated['title']);
        $obj->detail = PlainTextNormalizer::nullable($validated['detail'] ?? null);

        if ($request->input('remove_file') === '1') {
            $this->deleteStoredFile($obj->file_path);
            $obj->file_path = null;
            $obj->file_original_name = null;
        }

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $this->deleteStoredFile($obj->file_path);
            $file = $request->file('file');
            $stored = $file->store('goal-targets/objectives/'.$obj->id, 'public');
            $obj->file_path = $stored;
            $obj->file_original_name = $file->getClientOriginalName();
        }

        $obj->save();

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th' ? 'แก้ไขลำดับชั้น 1 เรียบร้อยแล้ว' : 'Level 1 objective updated.');
    }

    public function destroyObjective(Request $request, int $id): RedirectResponse
    {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);

        $obj = OkrObjective::query()->with('keyResults')->findOrFail($id);

        foreach ($obj->keyResults as $kr) {
            $this->deleteStoredFile($kr->file_path);
        }
        $this->deleteStoredFile($obj->file_path);

        $obj->delete();

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th' ? 'ลบลำดับชั้น 1 เรียบร้อยแล้ว' : 'Level 1 objective deleted.');
    }

    public function downloadObjectiveFile(Request $request, int $id): StreamedResponse
    {
        $obj = OkrObjective::query()->findOrFail($id);

        return $this->streamFile($obj->file_path, $obj->file_original_name);
    }

    public function storeKeyResult(Request $request): RedirectResponse
    {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);

        $validated = $request->validate([
            'okr_objective_id' => ['required', 'integer', 'exists:okr_objectives,id'],
            'dept_abbr_hr' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:500'],
            'detail' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:20480'],
        ]);

        $objId = (int) $validated['okr_objective_id'];
        $maxSortNo = OkrKeyResult::query()->where('okr_objective_id', $objId)->max('sort_no') ?? 0;

        $kr = OkrKeyResult::query()->create([
            'okr_objective_id' => $objId,
            'dept_abbr_hr' => strtoupper(trim($validated['dept_abbr_hr'])),
            'sort_no' => (int) $maxSortNo + 1,
            'title' => PlainTextNormalizer::normalize($validated['title']),
            'detail' => PlainTextNormalizer::nullable($validated['detail'] ?? null),
        ]);

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $stored = $file->store('goal-targets/key-results/'.$kr->id, 'public');
            $kr->file_path = $stored;
            $kr->file_original_name = $file->getClientOriginalName();
            $kr->save();
        }

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th' ? 'เพิ่มลำดับชั้น 2 เรียบร้อยแล้ว' : 'Level 2 key result added.');
    }

    public function updateKeyResult(Request $request, int $id): RedirectResponse
    {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);

        $kr = OkrKeyResult::query()->findOrFail($id);

        $validated = $request->validate([
            'dept_abbr_hr' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:500'],
            'detail' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:20480'],
            'remove_file' => ['nullable', 'string'],
        ]);

        $kr->dept_abbr_hr = strtoupper(trim($validated['dept_abbr_hr']));
        $kr->title = PlainTextNormalizer::normalize($validated['title']);
        $kr->detail = PlainTextNormalizer::nullable($validated['detail'] ?? null);

        if ($request->input('remove_file') === '1') {
            $this->deleteStoredFile($kr->file_path);
            $kr->file_path = null;
            $kr->file_original_name = null;
        }

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $this->deleteStoredFile($kr->file_path);
            $file = $request->file('file');
            $stored = $file->store('goal-targets/key-results/'.$kr->id, 'public');
            $kr->file_path = $stored;
            $kr->file_original_name = $file->getClientOriginalName();
        }

        $kr->save();

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th' ? 'แก้ไขลำดับชั้น 2 เรียบร้อยแล้ว' : 'Level 2 key result updated.');
    }

    public function destroyKeyResult(Request $request, int $id): RedirectResponse
    {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);

        $kr = OkrKeyResult::query()->findOrFail($id);
        $this->deleteStoredFile($kr->file_path);
        $kr->delete();

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th' ? 'ลบลำดับชั้น 2 เรียบร้อยแล้ว' : 'Level 2 key result deleted.');
    }

    public function destroyKpiReport(
        Request $request,
        int $root,
        KpiResultSynchronizer $resultSynchronizer
    ): RedirectResponse {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);
        $storedFilePaths = [];

        DB::transaction(function () use ($root, $resultSynchronizer, &$storedFilePaths): void {
            $reportQuery = KpiMonthScore::query()
                ->whereKey($root)
                ->whereNotNull('parent_target_kpi_id')
                ->where(function ($query): void {
                    $query->where(function ($inner): void {
                        $inner->where('month_no', 0)
                            ->whereNull('kpi_meta_id');
                    })->orWhereColumn('kpi_meta_id', 'id');
                });

            if (Schema::hasColumn('kpi_month_scores', 'mode_type')) {
                $reportQuery->where('mode_type', 'report');
            }

            /** @var KpiMonthScore $report */
            $report = $reportQuery->lockForUpdate()->firstOrFail();
            $ownerId = (int) $report->app_user_id;
            $cycleId = (int) $report->cycle_id;

            $reportRows = KpiMonthScore::query()
                ->where(function ($query) use ($root): void {
                    $query->whereKey($root)
                        ->orWhere('kpi_meta_id', $root);
                })
                ->get(['id', 'evidence_files', 'action_plan_files']);

            foreach ($reportRows as $reportRow) {
                foreach (['evidence_files', 'action_plan_files'] as $fileColumn) {
                    foreach ((array) $reportRow->{$fileColumn} as $file) {
                        $path = is_array($file) ? trim((string) ($file['path'] ?? '')) : '';
                        if ($path !== '') {
                            $storedFilePaths[] = $path;
                        }
                    }
                }
            }

            $reportRowIds = $reportRows->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($reportRowIds !== []) {
                KpiMonthReview::query()
                    ->whereIn('kpi_month_score_id', $reportRowIds)
                    ->delete();
                KpiMonthScore::query()
                    ->whereIn('id', $reportRowIds)
                    ->delete();
            }

            $resultSynchronizer->syncUserAndDepartmentResults($ownerId, $cycleId);
        }, 3);

        foreach (array_unique($storedFilePaths) as $storedFilePath) {
            $this->deleteStoredFile($storedFilePath);
        }

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th'
                ? 'ลบรายงาน KPI และคำนวณผลใหม่เรียบร้อยแล้ว'
                : 'KPI report deleted and results recalculated.');
    }

    public function destroyKpiReportMonth(
        Request $request,
        int $root,
        int $month,
        KpiResultSynchronizer $resultSynchronizer
    ): RedirectResponse {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);
        $storedFilePaths = [];

        DB::transaction(function () use ($root, $month, $resultSynchronizer, &$storedFilePaths): void {
            $reportQuery = KpiMonthScore::query()
                ->whereKey($root)
                ->whereNotNull('parent_target_kpi_id')
                ->where(function ($query): void {
                    $query->where(function ($inner): void {
                        $inner->where('month_no', 0)
                            ->whereNull('kpi_meta_id');
                    })->orWhereColumn('kpi_meta_id', 'id');
                });

            if (Schema::hasColumn('kpi_month_scores', 'mode_type')) {
                $reportQuery->where('mode_type', 'report');
            }

            /** @var KpiMonthScore $report */
            $report = $reportQuery->lockForUpdate()->firstOrFail();

            /** @var KpiMonthScore $monthRow */
            $monthRow = KpiMonthScore::query()
                ->whereKey($month)
                ->where('kpi_meta_id', $root)
                ->whereBetween('month_no', [1, 12])
                ->lockForUpdate()
                ->firstOrFail();

            foreach (['evidence_files', 'action_plan_files'] as $fileColumn) {
                foreach ((array) $monthRow->{$fileColumn} as $file) {
                    $path = is_array($file) ? trim((string) ($file['path'] ?? '')) : '';
                    if ($path !== '') {
                        $storedFilePaths[] = $path;
                    }
                }
            }

            KpiMonthReview::query()
                ->where('kpi_month_score_id', $monthRow->id)
                ->delete();
            $monthRow->delete();

            $resultSynchronizer->syncRootAndUserResults(
                (int) $report->id,
                (int) $report->app_user_id,
                (int) $report->cycle_id
            );
        }, 3);

        foreach (array_unique($storedFilePaths) as $storedFilePath) {
            $this->deleteStoredFile($storedFilePath);
        }

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th'
                ? 'ลบข้อมูล KPI รายเดือนและคำนวณผลใหม่เรียบร้อยแล้ว'
                : 'Monthly KPI data deleted and results recalculated.');
    }

    public function updateLevelThreeTarget(Request $request, int $target): RedirectResponse
    {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);
        $validated = $request->validate([
            'objective' => ['required', 'string', 'max:60000'],
            'detail' => ['required', 'string', 'max:60000'],
            'target_value' => ['required', 'numeric'],
            'kpi_unit_id' => ['required', 'integer', 'exists:kpi_units,id'],
            'criteria_operator' => ['required', 'in:>,>=,<=,<,=,!='],
        ]);

        $targetRow = $this->findLevelThreeTargetRoot($target);

        $targetRow->update([
            'objective' => PlainTextNormalizer::normalize($validated['objective']),
            'detail' => PlainTextNormalizer::normalize($validated['detail']),
            'target_value' => (float) $validated['target_value'],
            'kpi_unit_id' => (int) $validated['kpi_unit_id'],
            'criteria_operator' => (string) $validated['criteria_operator'],
        ]);

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th'
                ? 'แก้ไขเป้าหมายลำดับชั้น 3 เรียบร้อยแล้ว'
                : 'Level 3 target updated.');
    }

    public function destroyLevelThreeTarget(
        Request $request,
        int $target,
        KpiResultSynchronizer $resultSynchronizer
    ): RedirectResponse {
        $this->requireAdmin($request);
        $lang = $this->resolveLang($request);
        $storedFilePaths = [];

        DB::transaction(function () use ($target, $resultSynchronizer, &$storedFilePaths): void {
            $targetRow = $this->findLevelThreeTargetRoot($target, true);

            $linkedReportRoots = KpiMonthScore::query()
                ->where('parent_target_kpi_id', $targetRow->id)
                ->where(function ($query): void {
                    $query->where(function ($inner): void {
                        $inner->where('month_no', 0)
                            ->whereNull('kpi_meta_id');
                    })->orWhereColumn('kpi_meta_id', 'id');
                })
                ->get(['id', 'app_user_id', 'cycle_id']);

            $reportRootIds = $linkedReportRoots
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $rowsToDelete = KpiMonthScore::query()
                ->where(function ($query) use ($target, $reportRootIds): void {
                    $query->whereKey($target)
                        ->orWhere('kpi_meta_id', $target);
                    if ($reportRootIds !== []) {
                        $query->orWhereIn('id', $reportRootIds)
                            ->orWhereIn('kpi_meta_id', $reportRootIds);
                    }
                })
                ->get(['id', 'evidence_files', 'action_plan_files']);

            foreach ($rowsToDelete as $row) {
                foreach (['evidence_files', 'action_plan_files'] as $fileColumn) {
                    foreach ((array) $row->{$fileColumn} as $file) {
                        $path = is_array($file) ? trim((string) ($file['path'] ?? '')) : '';
                        if ($path !== '') {
                            $storedFilePaths[] = $path;
                        }
                    }
                }
            }

            $rowIds = $rowsToDelete->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($rowIds !== []) {
                KpiMonthReview::query()->whereIn('kpi_month_score_id', $rowIds)->delete();
                KpiMonthScore::query()->whereIn('id', $rowIds)->delete();
            }

            $linkedReportRoots
                ->map(fn (KpiMonthScore $root): string => ((int) $root->app_user_id).'|'.((int) $root->cycle_id))
                ->unique()
                ->each(function (string $ownerCycle) use ($resultSynchronizer): void {
                    [$ownerId, $cycleId] = array_map('intval', explode('|', $ownerCycle, 2));
                    $resultSynchronizer->syncUserAndDepartmentResults($ownerId, $cycleId);
                });
        }, 3);

        foreach (array_unique($storedFilePaths) as $storedFilePath) {
            $this->deleteStoredFile($storedFilePath);
        }

        return redirect()
            ->route('admin.goal.targets', ['lang' => $lang])
            ->with('status', $lang === 'th'
                ? 'ลบเป้าหมายลำดับชั้น 3 และรายงานที่เกี่ยวข้องเรียบร้อยแล้ว'
                : 'Level 3 target and related reports deleted.');
    }

    public function downloadKeyResultFile(Request $request, int $id): StreamedResponse
    {
        $kr = OkrKeyResult::query()->findOrFail($id);

        return $this->streamFile($kr->file_path, $kr->file_original_name);
    }

    private function buildRows(string $lang, string $deptFilter = '', string $levelThreeDeptFilter = ''): array
    {
        $deptFilter = $this->normalizeDepartmentCode($deptFilter);
        $levelThreeDeptFilter = $this->normalizeDepartmentCode($levelThreeDeptFilter);
        $hasActiveFilter = $deptFilter !== '' || $levelThreeDeptFilter !== '';

        $objectives = OkrObjective::query()
            ->orderBy('sort_no')
            ->orderBy('id')
            ->with(['keyResults' => fn ($q) => $q->orderBy('sort_no')->orderBy('id')])
            ->get();

        $keyResultIds = $objectives
            ->flatMap(fn ($objective) => $objective->keyResults->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $levelThreeEntriesByKeyResultId = $this->loadLevelThreeEntriesByKeyResultIds($keyResultIds, $lang);

        $rows = [];
        foreach ($objectives as $obj) {
            $krRows = [];
            $objRowspan = 0;

            foreach ($obj->keyResults as $kr) {
                $dept = $this->normalizeDepartmentCode((string) ($kr->dept_abbr_hr ?? ''));

                if ($deptFilter !== '' && $dept !== $deptFilter) {
                    continue;
                }
                $levelThreeEntries = $levelThreeEntriesByKeyResultId[(int) $kr->id] ?? [];

                if ($levelThreeDeptFilter !== '') {
                    $levelThreeEntries = array_values(array_filter(
                        $levelThreeEntries,
                        fn (array $entry): bool => $this->levelThreeEntryTargetsDepartment($entry, $levelThreeDeptFilter)
                    ));

                    if ($levelThreeEntries === []) {
                        continue;
                    }
                }

                // Each L3 entry may span multiple L4 rows; sum those rowspans.
                $rowspan = (int) array_sum(array_map(
                    fn (array $l3): int => (int) ($l3['rowspan'] ?? 1),
                    $levelThreeEntries
                ));
                $rowspan = max(1, $rowspan);
                $objRowspan += $rowspan;

                $krRows[] = [
                    'id' => (int) $kr->id,
                    'dept_abbr_hr' => $dept,
                    'sort_no' => (int) $kr->sort_no,
                    'title' => (string) ($kr->title ?? ''),
                    'detail' => (string) ($kr->detail ?? ''),
                    'file_path' => (string) ($kr->file_path ?? ''),
                    'file_original_name' => (string) ($kr->file_original_name ?? ''),
                    'level_three' => $levelThreeEntries,
                    'rowspan' => $rowspan,
                ];
            }

            if ($hasActiveFilter && $krRows === []) {
                continue;
            }

            if ($objRowspan === 0) {
                $objRowspan = 1;
            }

            $rows[] = [
                'id' => (int) $obj->id,
                'sort_no' => (int) $obj->sort_no,
                'title' => (string) ($obj->title ?? ''),
                'detail' => (string) ($obj->detail ?? ''),
                'file_path' => (string) ($obj->file_path ?? ''),
                'file_original_name' => (string) ($obj->file_original_name ?? ''),
                'key_results' => $krRows,
                'rowspan' => $objRowspan,
            ];
        }

        return $rows;
    }

    private function loadLevelThreeEntriesByKeyResultIds(array $keyResultIds, string $lang): array
    {
        $keyResultIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $keyResultIds
        ), fn (int $id) => $id > 0)));

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
                'user:id,employee_code,full_name_th,full_name_en,position,dept_abbr_hr',
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

        $rootScoreIds = $scores
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->all();

        $l4EntriesByL3ScoreId = $this->loadLevelFourEntriesByL3ScoreIds($rootScoreIds, $lang);

        $entries = [];
        /** @var KpiMonthScore $score */
        foreach ($scores as $score) {
            $keyResultId = (int) ($score->okr_key_result_id ?? 0);
            if ($keyResultId < 1) {
                continue;
            }

            $scoreId = (int) $score->id;
            $l4Entries = $l4EntriesByL3ScoreId[$scoreId] ?? [];
            $entries[$keyResultId] ??= [];
            $entries[$keyResultId][] = [
                'score_id'   => $scoreId,
                'dept_abbr_hr' => $this->normalizeDepartmentCode((string) ($score->user?->dept_abbr_hr ?? '')),
                'target_departments' => $this->resolveLevelThreeTargetDepartments($score),
                'from'       => $this->resolveLevelThreeSourceLabel($score->user, $lang),
                'title'      => trim((string) ($score->objective ?? '')),
                'detail'     => trim((string) ($score->detail ?? '')),
                'criteria'   => $this->resolveCriteriaSymbol(trim((string) ($score->criteria_operator ?? ''))),
                'target'     => $this->formatTargetValue($score->target_value),
                'unit'       => $this->resolveUnitLabel($score->unit, $lang),
                'criteria_operator' => trim((string) ($score->criteria_operator ?? '')),
                'target_value' => $score->target_value !== null ? (float) $score->target_value : null,
                'kpi_unit_id' => $score->kpi_unit_id !== null ? (int) $score->kpi_unit_id : null,
                'level_four' => $l4Entries,
                'rowspan'    => max(1, count($l4Entries)),
            ];
        }

        return $entries;
    }

    private function loadMonthDataByRootIds(array $rootIds, string $lang): array
    {
        if ($rootIds === []) {
            return [];
        }

        $monthScores = KpiMonthScore::query()
            ->whereIn('kpi_meta_id', $rootIds)
            ->whereBetween('month_no', [1, 12])
            ->whereNotNull('submitted_at')
            ->orderBy('kpi_meta_id')
            ->orderBy('month_no')
            ->get(['id', 'kpi_meta_id', 'month_no', 'score_value', 'is_pass', 'submitted_at']);

        if ($monthScores->isEmpty()) {
            return [];
        }

        $monthScoreIds = $monthScores
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $reviews = KpiMonthReview::query()
            ->whereIn('kpi_month_score_id', $monthScoreIds)
            ->get(['kpi_month_score_id', 'status'])
            ->keyBy('kpi_month_score_id');

        $thaiShort = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
        ];
        $engShort = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        $result = [];
        foreach ($monthScores as $ms) {
            $rootId = (int) ($ms->kpi_meta_id ?? 0);
            if ($rootId < 1) {
                continue;
            }

            $monthNo = (int) $ms->month_no;
            $review = $reviews->get((int) $ms->id);
            $status = $review ? (string) $review->status : '';
            $monthName = $lang === 'th'
                ? ($thaiShort[$monthNo] ?? "เดือน {$monthNo}")
                : ($engShort[$monthNo] ?? "Month {$monthNo}");

            $result[$rootId][] = [
                'month_no'      => $monthNo,
                'month_name'    => $monthName,
                'score_value'   => $ms->score_value !== null ? (float) $ms->score_value : null,
                'is_pass'       => (bool) $ms->is_pass,
                'review_status' => $status,
            ];
        }

        return $result;
    }

    private function resolveLevelThreeSourceLabel(mixed $user, string $lang): string
    {
        if (! $user) {
            return '-';
        }

        $employeeCode = trim((string) ($user->employee_code ?? ''));
        $name = $lang === 'th'
            ? trim((string) ($user->full_name_th ?? ''))
            : trim((string) ($user->full_name_en ?? ''));
        if ($name === '') {
            $name = trim((string) ($user->full_name_th ?? ''));
        }
        if ($name === '') {
            $name = trim((string) ($user->full_name_en ?? ''));
        }
        $position = trim((string) ($user->position ?? ''));

        $parts = array_values(array_filter([
            $employeeCode,
            $name,
            $position,
        ], fn (string $part) => $part !== ''));

        return $parts !== [] ? implode(' ', $parts) : '-';
    }

    private function resolveCriteriaSymbol(string $operator): string
    {
        return match ($operator) {
            '>=' => "\u{2265}",
            '<=' => "\u{2264}",
            '!=' => "\u{2260}",
            '>' => '>',
            '<' => '<',
            '=' => '=',
            default => '-',
        };
    }

    private function formatTargetValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $number = (float) $value;
        $formatted = number_format($number, 10, '.', '');
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');

        return $formatted !== '' ? $formatted : '0';
    }

    private function resolveUnitLabel(mixed $unit, string $lang): string
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

        $fallback = trim((string) ($unit->name_en ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        $code = trim((string) ($unit->code ?? ''));

        return $code !== '' ? $code : '-';
    }

    private function getDepartmentOptions(): array
    {
        return AppUser::query()
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->distinct()
            ->orderByRaw('UPPER(TRIM(dept_abbr_hr))')
            ->pluck('dept_abbr_hr')
            ->map(fn ($d) => strtoupper(trim((string) $d)))
            ->filter(fn ($d) => $d !== '')
            ->unique()
            ->values()
            ->toArray();
    }

    private function getKrDepartmentOptions(): array
    {
        return OkrKeyResult::query()
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->distinct()
            ->orderByRaw('UPPER(TRIM(dept_abbr_hr))')
            ->pluck('dept_abbr_hr')
            ->map(fn ($d) => strtoupper(trim((string) $d)))
            ->filter(fn ($d) => $d !== '')
            ->unique()
            ->values()
            ->toArray();
    }

    private function getLevelThreeDepartmentOptions(): array
    {
        if (
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
            ->with(['user:id,dept_abbr_hr'])
            ->whereNotNull('okr_key_result_id')
            ->where($rootRowsFilter)
            ->orderBy('id');

        if ($cycleId > 0) {
            $query->where('cycle_id', $cycleId);
        }

        if (Schema::hasColumn('kpi_month_scores', 'mode_type')) {
            $query->where('mode_type', 'target');
        }

        $departmentSet = [];
        foreach ($query->get() as $score) {
            foreach ($this->resolveLevelThreeTargetDepartments($score) as $departmentCode) {
                $departmentSet[$departmentCode] = true;
            }
        }

        $departments = array_keys($departmentSet);
        sort($departments, SORT_STRING);

        return $departments;
    }

    private function levelThreeEntryTargetsDepartment(array $entry, string $departmentCode): bool
    {
        $targetDepartment = $this->normalizeDepartmentCode($departmentCode);
        if ($targetDepartment === '') {
            return true;
        }

        $targetDepartments = is_array($entry['target_departments'] ?? null)
            ? $this->normalizeDepartmentCodes($entry['target_departments'])
            : [];
        if ($targetDepartments !== []) {
            return in_array($targetDepartment, $targetDepartments, true);
        }

        $fallbackDepartment = $this->normalizeDepartmentCode((string) ($entry['dept_abbr_hr'] ?? ''));

        return $fallbackDepartment === $targetDepartment;
    }

    private function resolveLevelThreeTargetDepartments(KpiMonthScore $score): array
    {
        $targetDepartments = is_array($score->target_departments ?? null)
            ? $this->normalizeDepartmentCodes($score->target_departments)
            : [];

        if ($targetDepartments !== []) {
            return $targetDepartments;
        }

        return $this->normalizeDepartmentCodes([
            (string) ($score->user?->dept_abbr_hr ?? ''),
        ]);
    }

    private function normalizeDepartmentCodes(array $departmentCodes): array
    {
        $normalized = [];
        foreach ($departmentCodes as $departmentCode) {
            $code = $this->normalizeDepartmentCode((string) $departmentCode);
            if ($code !== '') {
                $normalized[] = $code;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeDepartmentCode(?string $departmentCode): string
    {
        $text = strtoupper(trim((string) $departmentCode));
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return $text;
    }

    private function streamFile(?string $path, ?string $originalName): StreamedResponse
    {
        $path = (string) ($path ?? '');
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $name = ($originalName !== null && $originalName !== '') ? $originalName : basename($path);
        $mime = (string) (Storage::disk('public')->mimeType($path) ?: 'application/octet-stream');

        return Storage::disk('public')->download($path, $name, ['Content-Type' => $mime]);
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path !== null && $path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function findLevelThreeTargetRoot(int $target, bool $lockForUpdate = false): KpiMonthScore
    {
        $query = KpiMonthScore::query()
            ->whereKey($target)
            ->where(function ($rootQuery): void {
                $rootQuery->where(function ($inner): void {
                    $inner->where('month_no', 0)
                        ->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            });

        if (Schema::hasColumn('kpi_month_scores', 'mode_type')) {
            $query->where('mode_type', 'target');
        }

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    private function requireAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! AppUserAuthController::isAdminRole($user->role)) {
            abort(403);
        }
    }

    private function resolveLang(Request $request): string
    {
        return strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';
    }

    private function loadLevelFourEntriesByL3ScoreIds(array $l3ScoreIds, string $lang): array
    {
        if ($l3ScoreIds === []) {
            return [];
        }

        if (! Schema::hasColumn('kpi_month_scores', 'parent_target_kpi_id')) {
            return [];
        }

        $cycle = Cycle::active() ?? Cycle::query()->orderByDesc('id')->first();
        $cycleId = (int) ($cycle?->id ?? 0);

        $rootRowsFilter = static function ($query): void {
            $query->where(function ($inner): void {
                $inner->where('month_no', 0)->whereNull('kpi_meta_id');
            })->orWhereColumn('kpi_meta_id', 'id');
        };

        $query = KpiMonthScore::query()
            ->where($rootRowsFilter)
            ->whereIn('parent_target_kpi_id', $l3ScoreIds)
            ->whereNotNull('result')
            ->orderBy('app_user_id')
            ->orderBy('id');

        if ($cycleId > 0) {
            $query->where('cycle_id', $cycleId);
        }

        $roots = $query->get();
        if ($roots->isEmpty()) {
            return [];
        }

        // ── Reviewer name per dept ────────────────────────────────────────────
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
            $deptKey = strtoupper(trim((string) ($assignment->dept_abbr_hr ?? '')));
            if ($deptKey === '') {
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
            $reviewerNameByDept[$deptKey] = $rName ?: '-';
        }

        // ── Per-root approved / pending flags ────────────────────────────────
        $rootIds = $roots->pluck('id')->map(fn ($id) => (int) $id)->all();

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
                $status = strtolower(trim((string) ($reviewsByMonthId->get($monthId)?->status ?? '')));
                if ($status === KpiMonthReview::STATUS_APPROVED) {
                    $hasApproved = true;
                } else {
                    $hasPending = true;
                }
            }
            $hasApprovedByRootId[$rootId] = $hasApproved;
            $hasPendingByRootId[$rootId]  = $hasPending;
        }

        $userIds = $roots->pluck('app_user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()->values()->all();

        $usersById = AppUser::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'employee_code', 'full_name_th', 'full_name_en', 'position', 'dept_abbr_hr'])
            ->keyBy('id');

        $unitLabelMap = $this->buildUnitLabelMapForL4($lang);

        $result = [];
        foreach ($roots as $root) {
            $l3Id = (int) ($root->parent_target_kpi_id ?? 0);
            if ($l3Id < 1) {
                continue;
            }

            $user = $usersById->get((int) ($root->app_user_id ?? 0));

            $dept = $user ? strtoupper(trim((string) ($user->dept_abbr_hr ?? ''))) : '';
            $selectedDept = '-';
            if ($user) {
                $posKey = strtolower(preg_replace('/\s+/', ' ', trim((string) ($user->position ?? ''))) ?? '');
                if (in_array($posKey, ['assist manager', 'assistant manager', 'manager'], true)) {
                    foreach ((array) ($root->target_departments ?? []) as $deptCode) {
                        $nd = strtoupper(trim((string) $deptCode));
                        if ($nd !== '') {
                            $selectedDept = $nd;
                            break;
                        }
                    }
                }
            }

            $operator = trim((string) ($root->criteria_operator ?? ''));
            $targetValue = $root->target_value !== null ? (float) $root->target_value : null;
            $unitText = $this->resolveUnitLabelById($root->kpi_unit_id ?? null, $unitLabelMap);
            $resultValue = $root->result !== null ? (float) $root->result : null;

            $rootIntId      = (int) $root->id;
            $reviewerDeptKey = ($selectedDept !== '-' && $selectedDept !== '') ? $selectedDept : $dept;

            $result[$l3Id][] = [
                'root_id'       => $rootIntId,
                'dept'          => $dept ?: '-',
                'selected_dept' => $selectedDept,
                'code'          => $user ? trim((string) ($user->employee_code ?? '')) : '-',
                'name'          => $user ? $this->resolveUserNameForL4($user, $lang) : '-',
                'objective'     => trim((string) ($root->objective ?? '')) ?: '-',
                'detail'        => trim((string) ($root->detail ?? '')) ?: '-',
                'target'        => $this->formatL4TargetText($targetValue, $operator, $unitText, $lang),
                'result'        => $resultValue !== null ? number_format($resultValue, 2).'%' : '-',
                'has_approved'  => $hasApprovedByRootId[$rootIntId] ?? false,
                'has_pending'   => $hasPendingByRootId[$rootIntId] ?? false,
                'reviewer_name' => $reviewerNameByDept[$reviewerDeptKey] ?? '-',
            ];
        }

        return $result;
    }

    private function buildUnitLabelMapForL4(string $lang): array
    {
        return KpiUnit::query()
            ->orderBy('id')
            ->get(['id', 'code', 'name_th', 'name_en'])
            ->mapWithKeys(function (KpiUnit $unit) use ($lang): array {
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

    private function resolveUnitLabelById(mixed $unitId, array $unitLabelMap): string
    {
        $id = (int) ($unitId ?? 0);
        if ($id < 1) {
            return '';
        }

        return trim((string) ($unitLabelMap[$id] ?? ''));
    }

    private function resolveUserNameForL4(AppUser $user, string $lang): string
    {
        $name = $lang === 'th'
            ? trim((string) ($user->full_name_th ?? ''))
            : trim((string) ($user->full_name_en ?? ''));
        if ($name === '') {
            $name = trim((string) ($user->full_name_th ?? '')) ?: trim((string) ($user->full_name_en ?? ''));
        }

        return $name !== '' ? $name : '-';
    }

    private function formatL4PositionText(mixed $value): string
    {
        $position = trim((string) ($value ?? ''));
        if ($position === '') {
            return '-';
        }
        $normalized = preg_replace('/\s+/', ' ', strtolower($position)) ?? strtolower($position);

        return ucwords($normalized);
    }

    private function formatL4TargetText(?float $target, string $operator, string $unitText, string $lang): string
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
            '>' => 'มากกว่า', '>=' => 'มากกว่าหรือเท่ากับ',
            '<' => 'น้อยกว่า', '<=' => 'น้อยกว่าหรือเท่ากับ',
            '=' => 'เท่ากับ', '!=' => 'ไม่เท่ากับ',
        ];
        $labelsEn = [
            '>' => 'Greater than', '>=' => 'Greater than or equal to',
            '<' => 'Less than', '<=' => 'Less than or equal to',
            '=' => 'Equal to', '!=' => 'Not equal to',
        ];
        $symbol = match ($operator) {
            '>=' => '≥', '<=' => '≤', '!=' => '≠', default => $operator,
        };
        $text = $lang === 'th'
            ? ($labelsTh[$operator] ?? 'เงื่อนไข')
            : ($labelsEn[$operator] ?? 'Criteria');

        return $text.' '.$targetWithUnit.' ('.$symbol.')';
    }
}
