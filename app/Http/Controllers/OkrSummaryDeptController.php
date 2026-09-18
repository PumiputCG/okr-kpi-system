<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\KpiMonthScore;
use App\Models\KpiResult;
use App\Models\OkrResult;
use App\Support\DepartmentAssignmentResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OkrSummaryDeptController extends Controller
{
    private const KPI_TARGET_DEPARTMENT_POSITIONS = [
        'assist manager',
        'assistant manager',
        'manager',
        'deputy general manager',
        'general manager',
    ];

    public function index(Request $request)
    {
        $lang = strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';
        $q = trim((string) $request->query('q', ''));

        /** @var AppUser|null $authUser */
        $authUser = $request->user();
        $isAdmin = $this->isAdminRole($authUser?->role);
        $accessibleDepartments = $isAdmin ? null : $this->resolveAccessibleDepartments($authUser);
        $departmentOptions = $this->resolveDepartmentOptions($accessibleDepartments);
        $requestedDepartment = $this->normalizeDepartmentCode((string) $request->query('dept', ''));
        $selectedDepartment = $requestedDepartment !== '' && in_array($requestedDepartment, $departmentOptions, true)
            ? $requestedDepartment
            : '';
        $departmentLabel = $isAdmin
            ? ($lang === 'th' ? 'ทุกแผนก' : 'All Departments')
            : ($accessibleDepartments !== [] ? implode(', ', $accessibleDepartments) : '-');
        if ($selectedDepartment !== '') {
            $departmentLabel = $selectedDepartment;
        } elseif (! $isAdmin && $departmentOptions !== []) {
            $departmentLabel = implode(', ', $departmentOptions);
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
        if (($isAdmin || $accessibleDepartments !== []) && $selectedCycle) {
            $rows = $this->buildRows(
                (int) $selectedCycle->id,
                $accessibleDepartments,
                $lang,
                $q,
                $selectedDepartment
            );
        }

        return view('okr-summary-dept', [
            'lang' => $lang,
            'q' => $q,
            'departmentLabel' => $departmentLabel,
            'departmentOptions' => $departmentOptions,
            'selectedDepartment' => $selectedDepartment,
            'isAdmin' => $isAdmin,
            'cycles' => $cycles,
            'selectedCycle' => $selectedCycle,
            'rows' => $rows,
        ]);
    }

    private function buildRows(
        int $cycleId,
        ?array $accessibleDepartments,
        string $lang,
        string $q = '',
        string $selectedDepartment = ''
    ): array
    {
        $allowedPositions = $this->allowedPositions();
        $positionBindings = implode(',', array_fill(0, count($allowedPositions), '?'));

        $usersQuery = AppUser::query()
            ->whereNotNull('position')
            ->where('position', '!=', '')
            ->whereRaw('LOWER(position) IN ('.$positionBindings.')', $allowedPositions);

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

        $normalizedAccessibleDepartments = $accessibleDepartments === null
            ? null
            : array_values(array_unique(array_filter(array_map(
                fn ($departmentCode) => $this->normalizeDepartmentCode((string) $departmentCode),
                $accessibleDepartments
            ))));
        $normalizedSelectedDepartment = $this->normalizeDepartmentCode($selectedDepartment);

        $usersById = $users->keyBy(fn (AppUser $user) => (int) $user->id);
        $userIds = $usersById->keys()->map(fn ($id) => (int) $id)->all();
        if ($userIds === []) {
            return [];
        }

        $hasResultDepartmentColumn = Schema::hasColumn('kpi_result', 'dept_abbr_hr');
        $kpiResultRows = KpiResult::query()
            ->where('cycle_id', $cycleId)
            ->whereIn('app_user_id', $userIds)
            ->whereNotNull('result')
            ->get($hasResultDepartmentColumn
                ? ['app_user_id', 'dept_abbr_hr', 'result']
                : ['app_user_id', 'result']);
        if ($kpiResultRows->isEmpty()) {
            return [];
        }

        $explicitSelectedDepartmentMap = [];
        $firstSelectedDepartmentByUser = [];
        $rootRows = KpiMonthScore::query()
            ->where('cycle_id', $cycleId)
            ->whereIn('app_user_id', $userIds)
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('month_no', 0)
                        ->whereNull('kpi_meta_id');
                })->orWhereColumn('kpi_meta_id', 'id');
            })
            ->get(['app_user_id', 'target_departments']);
        foreach ($rootRows as $rootRow) {
            $userId = (int) ($rootRow->app_user_id ?? 0);
            if ($userId < 1) {
                continue;
            }

            $selectedDepartment = $this->firstDepartmentCodeFromTargetDepartments($rootRow->target_departments);
            if ($selectedDepartment === '') {
                continue;
            }

            $explicitSelectedDepartmentMap[$userId.'|'.$selectedDepartment] = true;
            if (! isset($firstSelectedDepartmentByUser[$userId])) {
                $firstSelectedDepartmentByUser[$userId] = $selectedDepartment;
            }
        }

        $rowBuckets = [];
        foreach ($kpiResultRows as $kpiResultRow) {
            $userId = (int) ($kpiResultRow->app_user_id ?? 0);
            /** @var AppUser|null $user */
            $user = $usersById->get($userId);
            if (! $user instanceof AppUser) {
                continue;
            }

            $ownerDepartment = $this->normalizeDepartmentCode((string) ($user->dept_abbr_hr ?? ''));
            $selectedDepartment = $hasResultDepartmentColumn
                ? $this->normalizeDepartmentCode((string) ($kpiResultRow->dept_abbr_hr ?? ''))
                : '';
            if ($selectedDepartment === '') {
                $selectedDepartment = $ownerDepartment;
            }
            $positionKey = $this->normalizePositionKey((string) ($user->position ?? ''));
            $canShowSelectedDepartment = $this->canUseSelectedDepartmentByPosition($positionKey);
            $hasExplicitSelectedDepartment = isset($explicitSelectedDepartmentMap[$userId.'|'.$selectedDepartment]);
            if ($canShowSelectedDepartment && ! $hasExplicitSelectedDepartment) {
                $mappedDepartment = $firstSelectedDepartmentByUser[$userId] ?? '';
                if ($mappedDepartment !== '') {
                    $selectedDepartment = $mappedDepartment;
                    $hasExplicitSelectedDepartment = isset($explicitSelectedDepartmentMap[$userId.'|'.$selectedDepartment]);
                }
            }
            if ($selectedDepartment === '') {
                continue;
            }
            $groupDepartment = $selectedDepartment !== ''
                ? $selectedDepartment
                : ($ownerDepartment !== '' ? $ownerDepartment : '-');
            $hasExplicitSelectedDepartment = isset($explicitSelectedDepartmentMap[$userId.'|'.$selectedDepartment]);
            $selectedDepartmentDisplay = $canShowSelectedDepartment
                && $hasExplicitSelectedDepartment
                ? $selectedDepartment
                : '-';

            if (
                $normalizedAccessibleDepartments !== null
                && ! in_array($selectedDepartment, $normalizedAccessibleDepartments, true)
            ) {
                continue;
            }
            if ($normalizedSelectedDepartment !== '' && $selectedDepartment !== $normalizedSelectedDepartment) {
                continue;
            }

            $resultValue = $kpiResultRow->result !== null ? (float) $kpiResultRow->result : null;
            if ($resultValue === null) {
                continue;
            }

            $bucketKey = $userId.'|'.$selectedDepartment;
            $rowBuckets[$bucketKey] = [
                'user_id' => $userId,
                'department' => $ownerDepartment !== '' ? $ownerDepartment : '-',
                'group_department' => $groupDepartment,
                'selected_department' => $selectedDepartmentDisplay,
                'employee_parts' => $this->formatEmployeeParts($user, $lang),
                'kpi_result_value' => $resultValue,
            ];
        }

        if ($rowBuckets === []) {
            return [];
        }

        $okrLookupDepartments = array_values(array_unique(array_filter(array_map(
            fn (array $bucket) => $this->normalizeDepartmentCode((string) ($bucket['group_department'] ?? ($bucket['department'] ?? ''))),
            $rowBuckets
        ))));

        $okrResultByDepartment = [];
        if ($okrLookupDepartments !== []) {
            $lookupDepartmentSet = array_fill_keys($okrLookupDepartments, true);
            $okrResultByDepartment = OkrResult::query()
                ->where('cycle_id', $cycleId)
                ->whereNotNull('result')
                ->get(['dept_abbr_hr', 'result'])
                ->mapWithKeys(function (OkrResult $row) use ($lookupDepartmentSet) {
                    $department = $this->normalizeDepartmentCode((string) ($row->dept_abbr_hr ?? ''));
                    if ($department === '' || ! isset($lookupDepartmentSet[$department])) {
                        return [];
                    }

                    return [$department => (float) $row->result];
                })
                ->all();

            $missingLookupDepartments = array_values(array_diff($okrLookupDepartments, array_keys($okrResultByDepartment)));
            if ($missingLookupDepartments !== []) {
                $fallbackResultByDepartment = $this->resolveOkrResultFallbackByDepartment($cycleId, $missingLookupDepartments);
                if ($fallbackResultByDepartment !== []) {
                    $okrResultByDepartment += $fallbackResultByDepartment;
                }
            }
        }

        $rows = array_values(array_map(function (array $bucket): array {
            return [
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
                'kpi_result_value' => isset($bucket['kpi_result_value']) ? (float) $bucket['kpi_result_value'] : null,
                '_user_id' => (int) ($bucket['user_id'] ?? 0),
            ];
        }, $rowBuckets));
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
            $department = trim((string) ($row['department'] ?? ''));
            $departmentKey = $groupDepartment !== '' ? strtoupper($groupDepartment) : '__EMPTY__';
            $selectedDepartment = $this->normalizeDepartmentCode((string) ($row['selected_department'] ?? ''));
            $groupDepartmentNormalized = $this->normalizeDepartmentCode($groupDepartment);
            $okrLookupDepartment = $groupDepartmentNormalized !== '' ? $groupDepartmentNormalized : $selectedDepartment;

            $rows[$index]['no'] = $index + 1;
            $rows[$index]['employee_count'] = isset($departmentEmployeeCounts[$departmentKey])
                ? count($departmentEmployeeCounts[$departmentKey])
                : 0;
            $rows[$index]['selected_department'] = $selectedDepartment !== '' ? $selectedDepartment : ($department !== '' ? $department : '-');
            $rows[$index]['group_department'] = $groupDepartment !== '' ? strtoupper($groupDepartment) : '-';
            $rows[$index]['kpi_result'] = $this->formatPercent($row['kpi_result_value'] ?? null);
            $rows[$index]['okr_result'] = $this->formatPercent(
                $okrLookupDepartment !== '' && isset($okrResultByDepartment[$okrLookupDepartment])
                    ? $okrResultByDepartment[$okrLookupDepartment]
                    : null
            );
            unset($rows[$index]['kpi_result_value'], $rows[$index]['_user_id']);
        }

        return array_values($rows);
    }

    private function resolveOkrResultFallbackByDepartment(int $cycleId, array $lookupDepartments): array
    {
        $targetDepartments = array_values(array_unique(array_filter(array_map(
            fn ($departmentCode) => $this->normalizeDepartmentCode((string) $departmentCode),
            $lookupDepartments
        ))));
        if ($targetDepartments === []) {
            return [];
        }

        $targetDepartmentSet = array_fill_keys($targetDepartments, true);
        $hasResultDepartmentColumn = Schema::hasColumn('kpi_result', 'dept_abbr_hr');

        if ($hasResultDepartmentColumn) {
            $sourceRows = KpiResult::query()
                ->where('cycle_id', $cycleId)
                ->whereNotNull('result')
                ->whereNotNull('dept_abbr_hr')
                ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
                ->get(['dept_abbr_hr', 'result']);
        } else {
            $sourceRows = KpiResult::query()
                ->join('app_users', 'app_users.id', '=', 'kpi_result.app_user_id')
                ->where('kpi_result.cycle_id', $cycleId)
                ->whereNotNull('kpi_result.result')
                ->whereNotNull('app_users.dept_abbr_hr')
                ->whereRaw("TRIM(app_users.dept_abbr_hr) <> ''")
                ->get([
                    'app_users.dept_abbr_hr as dept_abbr_hr',
                    'kpi_result.result as result',
                ]);
        }

        if ($sourceRows->isEmpty()) {
            return [];
        }

        $scoresByDepartment = [];
        foreach ($sourceRows as $sourceRow) {
            $department = $this->normalizeDepartmentCode((string) ($sourceRow->dept_abbr_hr ?? ''));
            if ($department === '' || ! isset($targetDepartmentSet[$department])) {
                continue;
            }

            if ($sourceRow->result === null) {
                continue;
            }

            $scoresByDepartment[$department] ??= [];
            $scoresByDepartment[$department][] = (float) $sourceRow->result;
        }

        if ($scoresByDepartment === []) {
            return [];
        }

        $resultByDepartment = [];
        foreach ($scoresByDepartment as $department => $scores) {
            if (! is_array($scores) || $scores === []) {
                continue;
            }

            $resultByDepartment[$department] = round(array_sum($scores) / count($scores), 2);
        }

        return $resultByDepartment;
    }

    private function formatEmployeeParts(AppUser $user, string $lang): array
    {
        $name = $lang === 'th'
            ? trim((string) $user->full_name_th)
            : trim((string) $user->full_name_en);

        if ($name === '') {
            $name = trim((string) ($user->full_name_en ?: $user->full_name_th));
        }

        $position = $this->formatPositionText($user->position);

        return [
            'code' => trim((string) ($user->employee_code ?? '')),
            'name' => $name !== '' ? $name : '-',
            'position' => $position,
        ];
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

    private function formatPercent(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, 2).'%';
    }

    private function normalizeDepartmentCode(mixed $value): string
    {
        $text = strtoupper(trim((string) ($value ?? '')));
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return $text;
    }

    private function firstDepartmentCodeFromTargetDepartments(mixed $value): string
    {
        $items = is_array($value) ? $value : [];
        foreach ($items as $item) {
            $department = $this->normalizeDepartmentCode((string) $item);
            if ($department !== '') {
                return $department;
            }
        }

        return '';
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

    private function isAdminRole(?string $role): bool
    {
        return strtolower(trim((string) $role)) === 'admin';
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

    private function allowedPositions(): array
    {
        return [
            'supervisor',
            'senior staff',
            'staff',
            'engineer',
            'senior engineer',
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
            'assist manager',
            'assistant manager',
            'manager',
            'deputy general manager',
            'general manager',
        ];
    }
}
