<?php

namespace App\Http\Controllers;

use App\Models\AdminHomeAnnouncement;
use App\Models\AdminHomeAnnouncementFile;
use App\Models\AdminHomeHierarchyConfig;
use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\KpiMonthScore;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    private const LEVEL_THREE_MANAGER_POSITIONS = [
        'assist manager',
        'assistant manager',
        'manager',
    ];

    private const NOTICE_LEVEL_TWO_OVERRIDE_POSITIONS = [
        'DEPUTY GENERAL MANAGER',
        'GENERAL MANAGER',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $lang = $this->resolveLang($request);
        $isAdminHome = AppUserAuthController::isAdminRole($user?->role);

        $targetPositions = [];
        $hierarchySaveUrl = '';
        $hierarchyDeleteUrl = '';
        $hierarchyLevelsCount = 3;
        $hierarchyAssignments = [];
        $announcementSaveUrl = '';
        $announcementUpdateUrl = '';
        $announcementDeleteUrl = '';
        $adminAnnouncements = [];
        $deptAbbrHrOptions = [];
        $levelThreeDeptCards = [];
        $levelThreeRowsByDept = [];
        $levelThreeCycleLabel = '';
        $okrRows = [];

        $userAnnouncementSaveUrl = '';
        $userAnnouncementUpdateUrl = '';
        $userAnnouncementDeleteUrl = '';
        $userPostPrimaryAdminId = 0;
        $userPostContexts = [];
        $userVisibleAnnouncements = [];

        if ($isAdminHome) {
            $targetPositions = $this->loadTargetPositions();
            $deptAbbrHrOptions = $this->loadDeptAbbrHrOptions();
            $adminId = (int) ($user?->id ?? 0);
            $levelThreePositions = [];

            $hierarchySaveUrl = Route::has('admin.home.hierarchy.save')
                ? route('admin.home.hierarchy.save')
                : '';
            $hierarchyDeleteUrl = Route::has('admin.home.hierarchy.delete')
                ? route('admin.home.hierarchy.delete')
                : '';
            $announcementSaveUrl = Route::has('admin.home.announcements.save')
                ? route('admin.home.announcements.save')
                : '';
            $announcementUpdateUrl = Route::has('admin.home.announcements.update')
                ? route('admin.home.announcements.update')
                : '';
            $announcementDeleteUrl = Route::has('admin.home.announcements.delete')
                ? route('admin.home.announcements.delete')
                : '';

            if ($adminId > 0) {
                $hierarchyConfig = AdminHomeHierarchyConfig::query()
                    ->where('admin_user_id', $adminId)
                    ->first();

                if ($hierarchyConfig) {
                    $hierarchyLevelsCount = max(1, (int) ($hierarchyConfig->levels_count ?? 3));
                    $hierarchyAssignments = $this->normalizeHierarchyLayout(
                        is_array($hierarchyConfig->layout_json) ? $hierarchyConfig->layout_json : [],
                        $hierarchyLevelsCount
                    );
                    $levelThreePositions = is_array($hierarchyAssignments['3'] ?? null)
                        ? $hierarchyAssignments['3']
                        : [];
                }

                $adminAnnouncements = AdminHomeAnnouncement::query()
                    ->with([
                        'files' => fn ($query) => $query->orderByDesc('id'),
                        'author',
                    ])
                    ->where('admin_user_id', $adminId)
                    ->orderByDesc('posted_at')
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn (AdminHomeAnnouncement $announcement): array => $this->toAnnouncementPayload($announcement, $request))
                    ->values()
                    ->all();
            }

            $levelThreeData = $this->loadLevelThreeDepartmentKpiData($lang, $levelThreePositions);
            $levelThreeDeptCards = $levelThreeData['cards'];
            $levelThreeRowsByDept = $levelThreeData['rows_by_department'];
            $levelThreeCycleLabel = $levelThreeData['cycle_label'];

            $okrRows = $this->buildOkrRows();
        } else {
            $normalizedUserPosition = $this->normalizePositionText($user?->position);
            if ($normalizedUserPosition !== '') {
                $userPostContexts = $this->resolveUserPostContextsForPosition($normalizedUserPosition, $lang);
                if (count($userPostContexts) > 0) {
                    $userAnnouncementSaveUrl = Route::has('home.announcements.save')
                        ? route('home.announcements.save')
                        : '';
                    $userAnnouncementUpdateUrl = Route::has('home.announcements.update')
                        ? route('home.announcements.update')
                        : '';
                    $userAnnouncementDeleteUrl = Route::has('home.announcements.delete')
                        ? route('home.announcements.delete')
                        : '';
                    $userPostPrimaryAdminId = (int) ($userPostContexts[0]['admin_user_id'] ?? 0);
                }

                $visibleAdminLevels = $this->resolveVisibleAdminLevelsForPosition($normalizedUserPosition);
                if (count($visibleAdminLevels) > 0) {
                    $announcementQuery = AdminHomeAnnouncement::query()
                        ->with([
                            'files' => fn ($query) => $query->orderByDesc('id'),
                            'author',
                        ])
                        ->where(function ($query) use ($visibleAdminLevels): void {
                            foreach ($visibleAdminLevels as $adminId => $maxVisibleLevel) {
                                $query->orWhere(function ($subQuery) use ($adminId, $maxVisibleLevel): void {
                                    $subQuery->where('admin_user_id', (int) $adminId)
                                        ->where('level_no', '<=', (int) $maxVisibleLevel);
                                });
                            }
                        })
                        ->orderByDesc('posted_at')
                        ->orderByDesc('id');

                    $userVisibleAnnouncements = $announcementQuery
                        ->get()
                        ->map(fn (AdminHomeAnnouncement $announcement): array => $this->toAnnouncementPayload($announcement, $request))
                        ->values()
                        ->all();
                }
            }
        }

        return view('home', [
            'isAdminHome' => $isAdminHome,
            'targetPositions' => $targetPositions,
            'hierarchySaveUrl' => $hierarchySaveUrl,
            'hierarchyDeleteUrl' => $hierarchyDeleteUrl,
            'hierarchyLevelsCount' => $hierarchyLevelsCount,
            'hierarchyAssignments' => $hierarchyAssignments,
            'announcementSaveUrl' => $announcementSaveUrl,
            'announcementUpdateUrl' => $announcementUpdateUrl,
            'announcementDeleteUrl' => $announcementDeleteUrl,
            'adminAnnouncements' => $adminAnnouncements,
            'deptAbbrHrOptions' => $deptAbbrHrOptions,
            'levelThreeDeptCards' => $levelThreeDeptCards,
            'levelThreeRowsByDept' => $levelThreeRowsByDept,
            'levelThreeCycleLabel' => $levelThreeCycleLabel,
            'okrRows' => $okrRows,
            'userAnnouncementSaveUrl' => $userAnnouncementSaveUrl,
            'userAnnouncementUpdateUrl' => $userAnnouncementUpdateUrl,
            'userAnnouncementDeleteUrl' => $userAnnouncementDeleteUrl,
            'userPostPrimaryAdminId' => $userPostPrimaryAdminId,
            'userPostContexts' => $userPostContexts,
            'userVisibleAnnouncements' => $userVisibleAnnouncements,
            'homeViewerUserId' => (int) ($user?->id ?? 0),
        ]);
    }

    public function downloadAnnouncementFile(Request $request, AdminHomeAnnouncementFile $file)
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $announcement = $file->announcement()->first();
        if (! $announcement) {
            abort(404);
        }

        $isAdmin = AppUserAuthController::isAdminRole($user->role);
        if ($isAdmin) {
            if ((int) $announcement->admin_user_id !== (int) $user->id) {
                abort(403);
            }
        } else {
            $normalizedUserPosition = $this->normalizePositionText($user->position);
            if ($normalizedUserPosition === '') {
                abort(403);
            }

            $visibleAdminLevels = $this->resolveVisibleAdminLevelsForPosition($normalizedUserPosition);
            $maxVisibleLevel = (int) ($visibleAdminLevels[(int) $announcement->admin_user_id] ?? 0);
            if ($maxVisibleLevel < 1 || (int) $announcement->level_no > $maxVisibleLevel) {
                abort(403);
            }
        }

        $path = trim((string) ($file->storage_path ?? ''));
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $downloadName = trim((string) ($file->original_name ?? '')) !== ''
            ? trim((string) $file->original_name)
            : basename($path);

        return Storage::disk('public')->download($path, $downloadName);
    }

    private function buildOkrRows(): array
    {
        $objectives = OkrObjective::query()
            ->orderBy('sort_no')
            ->orderBy('id')
            ->with(['keyResults' => fn ($q) => $q->orderBy('sort_no')->orderBy('id')])
            ->get();

        $rows = [];
        foreach ($objectives as $obj) {
            $krRows = [];
            foreach ($obj->keyResults as $kr) {
                $krRows[] = [
                    'id' => (int) $kr->id,
                    'dept_abbr_hr' => strtoupper(trim((string) ($kr->dept_abbr_hr ?? ''))),
                    'sort_no' => (int) $kr->sort_no,
                    'title' => (string) ($kr->title ?? ''),
                    'detail' => (string) ($kr->detail ?? ''),
                ];
            }
            $rows[] = [
                'id' => (int) $obj->id,
                'sort_no' => (int) $obj->sort_no,
                'title' => (string) ($obj->title ?? ''),
                'detail' => (string) ($obj->detail ?? ''),
                'key_results' => $krRows,
            ];
        }

        return $rows;
    }

    private function loadTargetPositions(): array
    {
        return AppUser::query()
            ->where(function ($query) {
                $query->whereNull('role')
                    ->orWhereRaw("LOWER(TRIM(COALESCE(role, ''))) <> 'admin'");
            })
            ->whereNotNull('position')
            ->whereRaw("TRIM(COALESCE(position, '')) <> ''")
            ->pluck('position')
            ->map(function ($position) {
                return preg_replace('/\s+/', ' ', trim((string) $position)) ?? trim((string) $position);
            })
            ->filter()
            ->unique(static fn (string $position): string => strtolower($position))
            ->sort(static fn (string $a, string $b): int => strcasecmp($a, $b))
            ->values()
            ->all();
    }

    private function normalizeHierarchyLayout(array $layout, int $levelsCount): array
    {
        $normalizedLayout = [];
        for ($level = 1; $level <= $levelsCount; $level++) {
            $key = (string) $level;
            $rawPositions = $layout[$key] ?? [];
            if (! is_array($rawPositions)) {
                $rawPositions = [];
            }

            $positions = collect($rawPositions)
                ->map(fn ($value): string => $this->normalizePositionText((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $normalizedLayout[$key] = $positions;
        }

        return $normalizedLayout;
    }

    private function loadDeptAbbrHrOptions(): array
    {
        return AppUser::query()
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->pluck('dept_abbr_hr')
            ->map(function ($value): string {
                return strtoupper(trim((string) $value));
            })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function loadLevelThreeDepartmentKpiData(string $lang, array $levelThreePositions = []): array
    {
        $allowedPositions = collect($levelThreePositions)
            ->map(fn ($position): string => $this->normalizePositionText((string) $position))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($allowedPositions) < 1) {
            $allowedPositions = collect(self::LEVEL_THREE_MANAGER_POSITIONS)
                ->map(fn (string $position): string => $this->normalizePositionText($position))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $managerUsers = AppUser::query()
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->whereNotNull('position')
            ->whereRaw("TRIM(COALESCE(position, '')) <> ''")
            ->get([
                'id',
                'employee_code',
                'full_name_th',
                'full_name_en',
                'position',
                'dept_abbr_hr',
            ]);

        if ($managerUsers->isEmpty()) {
            return [
                'cards' => [],
                'rows_by_department' => [],
                'cycle_label' => '',
            ];
        }

        $cycle = Cycle::active() ?? Cycle::query()->orderByDesc('id')->first();
        $cycleId = (int) ($cycle?->id ?? 0);
        $cycleLabel = trim((string) ($cycle?->name ?? ''));
        if ($cycleLabel === '') {
            $cycleLabel = trim((string) ($cycle?->code ?? ''));
        }

        $userMap = [];
        foreach ($managerUsers as $managerUser) {
            $departmentCode = $this->normalizeDepartmentCode((string) ($managerUser->dept_abbr_hr ?? ''));
            $positionCode = $this->normalizePositionText((string) ($managerUser->position ?? ''));
            if ($departmentCode === '') {
                continue;
            }
            if (! in_array($positionCode, $allowedPositions, true)) {
                continue;
            }

            $userMap[(int) $managerUser->id] = [
                'employee_code' => trim((string) ($managerUser->employee_code ?? '')),
                'full_name_th' => trim((string) ($managerUser->full_name_th ?? '')),
                'full_name_en' => trim((string) ($managerUser->full_name_en ?? '')),
                'position' => trim((string) ($managerUser->position ?? '')),
                'department' => $departmentCode,
            ];
        }

        if (count($userMap) === 0) {
            return [
                'cards' => [],
                'rows_by_department' => [],
                'cycle_label' => $cycleLabel,
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
            ->orderByDesc('id');

        $hasModeTypeColumn = Schema::hasColumn('kpi_month_scores', 'mode_type');
        if ($hasModeTypeColumn) {
            $scoresQuery->where(function ($query): void {
                $query->where('mode_type', 'target')
                    ->orWhere(function ($legacyQuery): void {
                        $legacyQuery->whereNull('mode_type')
                            ->where('month_no', 0)
                            ->whereNull('kpi_meta_id');
                    });
            });
        } else {
            // Legacy fallback: rows without month details were used as "target-style" cards.
            $scoresQuery->where('month_no', 0)
                ->whereNull('kpi_meta_id');
        }

        if ($cycleId > 0) {
            $scoresQuery->where('cycle_id', $cycleId);
        }

        $scores = $scoresQuery->get();
        if ($scores->isEmpty() && $cycleId > 0) {
            $fallbackScoresQuery = KpiMonthScore::query()
                ->with([
                    'unit:id,code,name_th,name_en',
                ])
                ->whereIn('app_user_id', array_keys($userMap))
                ->where($rootRowsFilter)
                ->orderByDesc('id');

            if ($hasModeTypeColumn) {
                $fallbackScoresQuery->where(function ($query): void {
                    $query->where('mode_type', 'target')
                        ->orWhere(function ($legacyQuery): void {
                            $legacyQuery->whereNull('mode_type')
                                ->where('month_no', 0)
                                ->whereNull('kpi_meta_id');
                        });
                });
            } else {
                $fallbackScoresQuery->where('month_no', 0)
                    ->whereNull('kpi_meta_id');
            }

            $scores = $fallbackScoresQuery->get();
        }

        $rowsByDepartment = [];
        foreach ($scores as $score) {
            $userId = (int) ($score->app_user_id ?? 0);
            $owner = $userMap[$userId] ?? null;
            if (! is_array($owner)) {
                continue;
            }

            $department = (string) ($owner['department'] ?? '');
            if ($department === '') {
                continue;
            }

            $targetValueNumber = $score->target_value !== null
                ? (float) $score->target_value
                : null;
            $targetValueText = $targetValueNumber !== null
                ? $this->formatTargetValue($targetValueNumber)
                : '-';
            $criteriaOperator = trim((string) ($score->criteria_operator ?? ''));
            $targetGoal = $this->resolveCriteriaSymbol($criteriaOperator);

            $rowsByDepartment[$department][] = [
                'employee_code' => (string) ($owner['employee_code'] ?? ''),
                'full_name_th' => (string) ($owner['full_name_th'] ?? ''),
                'full_name_en' => (string) ($owner['full_name_en'] ?? ''),
                'position' => (string) ($owner['position'] ?? ''),
                'objective' => trim((string) ($score->objective ?? '')),
                'detail' => trim((string) ($score->detail ?? '')),
                'target_value' => $targetValueText,
                'target_goal' => $targetGoal !== '' ? $targetGoal : '-',
                'unit' => $this->resolveUnitLabel($score->unit, $lang),
                'unit_th' => $this->resolveUnitLabel($score->unit, 'th'),
                'unit_en' => $this->resolveUnitLabel($score->unit, 'en'),
            ];
        }

        foreach ($rowsByDepartment as $department => $rows) {
            usort($rows, static function (array $left, array $right): int {
                $positionComparison = strcasecmp((string) ($left['position'] ?? ''), (string) ($right['position'] ?? ''));
                if ($positionComparison !== 0) {
                    return $positionComparison;
                }

                return strcasecmp((string) ($left['employee_code'] ?? ''), (string) ($right['employee_code'] ?? ''));
            });
            $rowsByDepartment[$department] = array_values($rows);
        }

        $cards = array_values(array_unique(array_keys($rowsByDepartment)));
        sort($cards, SORT_STRING);

        return [
            'cards' => $cards,
            'rows_by_department' => $rowsByDepartment,
            'cycle_label' => $cycleLabel,
        ];
    }

    private function formatTargetValue(float $value): string
    {
        $formatted = number_format($value, 10, '.', '');
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');

        return $formatted !== '' ? $formatted : '0';
    }

    private function resolveUnitLabel($unit, string $lang): string
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

    private function resolveCriteriaSymbol(string $operator): string
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

    private function normalizeDepartmentCode(?string $departmentCode): string
    {
        $text = strtoupper(trim((string) $departmentCode));
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return $text;
    }

    private function resolveVisibleAdminLevelsForPosition(string $normalizedUserPosition): array
    {
        if ($normalizedUserPosition === '') {
            return [];
        }

        $visibleAdminLevels = [];
        $isOverridePosition = $this->isNoticeLevelTwoOverridePosition($normalizedUserPosition);
        $configs = AdminHomeHierarchyConfig::query()->get([
            'admin_user_id',
            'levels_count',
            'layout_json',
        ]);

        foreach ($configs as $config) {
            $adminId = (int) ($config->admin_user_id ?? 0);
            if ($adminId < 1) {
                continue;
            }

            $levelsCount = max(1, (int) ($config->levels_count ?? 1));
            $layout = is_array($config->layout_json) ? $config->layout_json : [];
            $matchedLevel = $this->resolveMatchedLevel($layout, $levelsCount, $normalizedUserPosition);

            if ($matchedLevel > 0) {
                $visibleAdminLevels[$adminId] = max((int) ($visibleAdminLevels[$adminId] ?? 0), $matchedLevel);
            }

            if ($isOverridePosition) {
                $visibleAdminLevels[$adminId] = max((int) ($visibleAdminLevels[$adminId] ?? 0), 2);
            }
        }

        return $visibleAdminLevels;
    }

    private function resolveUserPostContextsForPosition(string $normalizedUserPosition, string $lang): array
    {
        if ($normalizedUserPosition === '') {
            return [];
        }

        $contexts = [];
        $configs = AdminHomeHierarchyConfig::query()
            ->with([
                'admin:id,employee_code,full_name_th,full_name_en',
            ])
            ->get([
                'admin_user_id',
                'levels_count',
                'layout_json',
            ]);

        foreach ($configs as $config) {
            $adminId = (int) ($config->admin_user_id ?? 0);
            if ($adminId < 1) {
                continue;
            }

            $levelsCount = max(1, (int) ($config->levels_count ?? 1));
            $layout = is_array($config->layout_json) ? $config->layout_json : [];
            $matchedLevel = $this->resolveMatchedLevel($layout, $levelsCount, $normalizedUserPosition);
            if ($matchedLevel < 1) {
                continue;
            }

            $contexts[] = [
                'admin_user_id' => $adminId,
                'admin_label' => $this->resolveUserName($config->admin, $lang),
                'level_no' => $matchedLevel,
                'levels_count' => $levelsCount,
                'visible_positions' => $this->resolveVisiblePositionsBelowLevel($layout, $levelsCount, $matchedLevel),
            ];
        }

        usort($contexts, static function (array $left, array $right): int {
            $levelComparison = ((int) ($left['level_no'] ?? 0)) <=> ((int) ($right['level_no'] ?? 0));
            if ($levelComparison !== 0) {
                return $levelComparison;
            }

            return strcasecmp((string) ($left['admin_label'] ?? ''), (string) ($right['admin_label'] ?? ''));
        });

        return $contexts;
    }

    private function resolveVisiblePositionsBelowLevel(array $layout, int $levelsCount, int $matchedLevel): array
    {
        $positions = [];
        for ($level = $matchedLevel + 1; $level <= $levelsCount; $level++) {
            $rawPositions = $layout[(string) $level] ?? [];
            if (! is_array($rawPositions)) {
                continue;
            }

            foreach ($rawPositions as $value) {
                $normalized = $this->normalizePositionText((string) $value);
                if ($normalized === '') {
                    continue;
                }

                $positions[$normalized] = true;
            }
        }

        $list = array_keys($positions);
        sort($list, SORT_STRING);

        return $list;
    }

    private function resolveMatchedLevel(array $layout, int $levelsCount, string $normalizedUserPosition): int
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
                ->map(fn ($value): string => $this->normalizePositionText((string) $value))
                ->filter()
                ->values()
                ->all();

            if (in_array($normalizedUserPosition, $normalizedPositions, true)) {
                $matchedLevel = max($matchedLevel, $level);
            }
        }

        return $matchedLevel;
    }

    private function toAnnouncementPayload(AdminHomeAnnouncement $announcement, Request $request): array
    {
        $viewer = $request->user();
        $viewerId = (int) ($viewer?->id ?? 0);
        $isViewerAdmin = AppUserAuthController::isAdminRole($viewer?->role);
        $canManage = false;
        if ($viewerId > 0) {
            if ($isViewerAdmin) {
                $canManage = (int) $announcement->admin_user_id === $viewerId;
            } else {
                $canManage = (int) ($announcement->posted_by_user_id ?? 0) === $viewerId;
            }
        }

        return [
            'id' => (int) $announcement->id,
            'admin_user_id' => (int) $announcement->admin_user_id,
            'posted_by_user_id' => (int) ($announcement->posted_by_user_id ?? 0),
            'can_manage' => $canManage,
            'level_no' => (int) $announcement->level_no,
            'parent_announcement_id' => (int) ($announcement->parent_announcement_id ?? 0),
            'dept_abbr_hr' => strtoupper(trim((string) ($announcement->dept_abbr_hr ?? ''))),
            'title' => trim((string) ($announcement->title ?? '')),
            'detail' => (string) ($announcement->detail ?? ''),
            'posted_at' => optional($announcement->posted_at)->toIso8601String(),
            'author_name' => $this->resolveUserName($announcement->author, $this->resolveLang($request)),
            'author_profile' => $this->resolveAuthorProfile($announcement->author, $this->resolveLang($request)),
            'author_role' => strtolower(trim((string) ($announcement->author?->role ?? ''))),
            'files' => $announcement->files
                ->map(function (AdminHomeAnnouncementFile $file) use ($request): array {
                    return [
                        'id' => (int) $file->id,
                        'name' => trim((string) ($file->original_name ?? '')),
                        'size_text' => $this->formatFileSize($file->size_bytes),
                        'url' => Route::has('home.announcements.files.download')
                            ? route('home.announcements.files.download', [
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

    private function normalizePositionText(?string $position): string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $position)) ?? trim((string) $position);

        return strtoupper($text);
    }

    private function isNoticeLevelTwoOverridePosition(string $normalizedUserPosition): bool
    {
        return in_array($normalizedUserPosition, self::NOTICE_LEVEL_TWO_OVERRIDE_POSITIONS, true);
    }

    private function formatFileSize($sizeBytes): string
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

    private function resolveUserName(?AppUser $user, string $lang): string
    {
        if (! $user) {
            return '';
        }

        $primaryName = $lang === 'th'
            ? trim((string) ($user->full_name_th ?? ''))
            : trim((string) ($user->full_name_en ?? ''));

        if ($primaryName !== '') {
            return $primaryName;
        }

        $fallbackName = trim((string) ($user->full_name_en ?? ''));
        if ($fallbackName !== '') {
            return $fallbackName;
        }

        $fallbackThaiName = trim((string) ($user->full_name_th ?? ''));
        if ($fallbackThaiName !== '') {
            return $fallbackThaiName;
        }

        return trim((string) ($user->employee_code ?? ''));
    }

    private function resolveAuthorProfile(?AppUser $user, string $lang): string
    {
        if (! $user) {
            return '';
        }

        $fullName = $this->resolveUserName($user, $lang);
        $employeeCode = trim((string) ($user->employee_code ?? ''));
        $department = trim((string) ($user->department ?? ''));
        $position = trim((string) ($user->position ?? ''));

        $parts = [
            $fullName !== '' ? $fullName : '-',
            $employeeCode !== '' ? $employeeCode : '-',
            $department !== '' ? $department : '-',
            $position !== '' ? $position : '-',
        ];

        return implode(' , ', $parts);
    }

    private function resolveLang(Request $request): string
    {
        $lang = strtolower((string) $request->input('lang', $request->query('lang', 'en')));

        return $lang === 'th' ? 'th' : 'en';
    }
}
