<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppUserAuthController;
use App\Http\Controllers\Controller;
use App\Models\AdminDepartmentAssignment;
use App\Models\AppUser;
use App\Support\PlainTextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepartmentAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $lang = $this->resolveLang($request);
        $staffOptions = $this->buildStaffOptions($lang);
        $rows = $this->buildDepartmentRows($lang);

        return view('admin-department-assignments', [
            'lang' => $lang,
            'rows' => $rows,
            'staffOptions' => $staffOptions,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! AppUserAuthController::isAdminRole($user->role)) {
            abort(403);
        }

        $lang = $this->resolveLang($request);
        $validated = $request->validate([
            'dept_abbr_hr' => ['required', 'string', 'max:100'],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['nullable', 'integer', 'exists:app_users,id'],
            'target_user_id' => ['nullable', 'integer', 'exists:app_users,id'],
            'reviewer_user_ids' => ['nullable', 'array'],
            'reviewer_user_ids.*' => ['nullable', 'integer', 'exists:app_users,id'],
            'reviewer_user_id' => ['nullable', 'integer', 'exists:app_users,id'],
        ]);

        $departmentCode = $this->normalizeDepartmentCode((string) $validated['dept_abbr_hr']);
        if ($departmentCode === '') {
            return redirect()
                ->route('admin.employee.assignments.index', ['lang' => $lang])
                ->withErrors([
                    'dept_abbr_hr' => $lang === 'th'
                        ? 'ไม่พบรหัสแผนกที่ใช้งานได้'
                        : 'Department code is required.',
                ]);
        }

        $targetUserIds = $this->normalizeUserIdList($validated['target_user_ids'] ?? []);
        if ($targetUserIds === []) {
            $targetUserIds = $this->normalizeUserIdList([$validated['target_user_id'] ?? null]);
        }
        $reviewerUserIds = $this->normalizeUserIdList($validated['reviewer_user_ids'] ?? []);
        if ($reviewerUserIds === []) {
            $reviewerUserIds = $this->normalizeUserIdList([$validated['reviewer_user_id'] ?? null]);
        }

        $targetUserId = $targetUserIds[0] ?? 0;
        $reviewerUserId = $reviewerUserIds[0] ?? 0;

        DB::transaction(function () use (
            $departmentCode,
            $targetUserIds,
            $reviewerUserIds,
            $targetUserId,
            $reviewerUserId,
            $user
        ): void {
            if ($targetUserId < 1 && $reviewerUserId < 1) {
                AdminDepartmentAssignment::query()
                    ->where('dept_abbr_hr', $departmentCode)
                    ->delete();

                return;
            }

            $assignment = AdminDepartmentAssignment::query()->firstOrNew([
                'dept_abbr_hr' => $departmentCode,
            ]);
            $assignment->target_user_id = $targetUserId > 0 ? $targetUserId : null;
            $assignment->reviewer_user_id = $reviewerUserId > 0 ? $reviewerUserId : null;
            if (Schema::hasColumn('admin_department_assignments', 'target_user_ids_json')) {
                $assignment->target_user_ids_json = $targetUserIds !== [] ? $targetUserIds : null;
            }
            if (Schema::hasColumn('admin_department_assignments', 'reviewer_user_ids_json')) {
                $assignment->reviewer_user_ids_json = $reviewerUserIds !== [] ? $reviewerUserIds : null;
            }
            $assignment->assigned_by_admin_user_id = (int) $user->id;
            $assignment->save();
        });

        return redirect()
            ->route('admin.employee.assignments.index', ['lang' => $lang])
            ->with('status', $lang === 'th' ? 'บันทึกการกำหนดพนักงานเรียบร้อยแล้ว' : 'Employee assignment saved successfully.');
    }

    private function buildStaffOptions(string $lang): array
    {
        $users = AppUser::query()
            ->where(function ($query): void {
                $query->whereNull('role')
                    ->orWhereRaw("LOWER(TRIM(COALESCE(role, ''))) <> 'admin'");
            })
            ->orderByRaw("TRIM(COALESCE(employee_code, ''))")
            ->orderByRaw("TRIM(COALESCE(full_name_th, ''))")
            ->orderByRaw("TRIM(COALESCE(full_name_en, ''))")
            ->get([
                'id',
                'employee_code',
                'full_name_th',
                'full_name_en',
                'position',
                'dept_abbr_hr',
            ]);

        $options = [];
        foreach ($users as $user) {
            $id = (int) ($user->id ?? 0);
            if ($id < 1) {
                continue;
            }

            $employeeCode = trim((string) ($user->employee_code ?? ''));
            $name = $this->resolveUserName($user, $lang);
            $position = $this->normalizeDisplayText((string) ($user->position ?? ''), '-');
            $department = $this->normalizeDisplayText((string) ($user->dept_abbr_hr ?? ''), '-');

            $label = sprintf(
                '%-8s %s [%s, %s]',
                $employeeCode !== '' ? $employeeCode : '-',
                $name !== '' ? $name : '-',
                $position,
                $department
            );

            $searchKey = strtolower(trim(implode(' ', [
                $employeeCode,
                trim((string) ($user->full_name_th ?? '')),
                trim((string) ($user->full_name_en ?? '')),
                $position !== '-' ? $position : '',
                $department !== '-' ? $department : '',
            ])));

            $options[] = [
                'id' => $id,
                'label' => $label,
                'display_name' => $name !== '' ? $name : '-',
                'search_key' => $searchKey,
            ];
        }

        return $options;
    }

    private function buildDepartmentRows(string $lang): array
    {
        $members = AppUser::query()
            ->where(function ($query): void {
                $query->whereNull('role')
                    ->orWhereRaw("LOWER(TRIM(COALESCE(role, ''))) <> 'admin'");
            })
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->orderByRaw("UPPER(TRIM(COALESCE(dept_abbr_hr, '')))")
            ->orderByRaw("TRIM(COALESCE(full_name_th, ''))")
            ->orderByRaw("TRIM(COALESCE(full_name_en, ''))")
            ->get([
                'id',
                'employee_code',
                'full_name_th',
                'full_name_en',
                'position',
                'dept_abbr_hr',
            ]);

        $rowsByDepartment = [];
        foreach ($members as $member) {
            $departmentCode = $this->normalizeDepartmentCode((string) ($member->dept_abbr_hr ?? ''));
            if ($departmentCode === '') {
                continue;
            }

            if (! array_key_exists($departmentCode, $rowsByDepartment)) {
                $rowsByDepartment[$departmentCode] = [
                    'dept_abbr_hr' => $departmentCode,
                    'target_user_ids' => [],
                    'reviewer_user_ids' => [],
                    'target_names' => [],
                    'reviewer_names' => [],
                    'target_user_id' => null,
                    'reviewer_user_id' => null,
                    'target_name' => '-',
                    'reviewer_name' => '-',
                    'members' => [],
                ];
            }

            $rowsByDepartment[$departmentCode]['members'][] = [
                'id' => (int) $member->id,
                'employee_code' => trim((string) ($member->employee_code ?? '')),
                'name' => $this->resolveUserName($member, $lang),
                'position' => trim((string) ($member->position ?? '')),
            ];
        }

        if ($rowsByDepartment === []) {
            return [];
        }

        $assignments = AdminDepartmentAssignment::query()
            ->with([
                'targetUser:id,employee_code,full_name_th,full_name_en',
                'reviewerUser:id,employee_code,full_name_th,full_name_en',
            ])
            ->whereIn('dept_abbr_hr', array_keys($rowsByDepartment))
            ->get();

        $allAssignedUserIds = [];
        foreach ($assignments as $assignment) {
            $targetIds = $this->normalizeUserIdList(
                is_array($assignment->target_user_ids_json ?? null) ? $assignment->target_user_ids_json : []
            );
            if ($targetIds === []) {
                $legacyTargetId = (int) ($assignment->target_user_id ?? 0);
                if ($legacyTargetId > 0) {
                    $targetIds[] = $legacyTargetId;
                }
            }
            foreach ($targetIds as $targetId) {
                $allAssignedUserIds[] = $targetId;
            }

            $reviewerIds = $this->normalizeUserIdList(
                is_array($assignment->reviewer_user_ids_json ?? null) ? $assignment->reviewer_user_ids_json : []
            );
            if ($reviewerIds === []) {
                $legacyReviewerId = (int) ($assignment->reviewer_user_id ?? 0);
                if ($legacyReviewerId > 0) {
                    $reviewerIds[] = $legacyReviewerId;
                }
            }
            foreach ($reviewerIds as $reviewerId) {
                $allAssignedUserIds[] = $reviewerId;
            }
        }

        $allAssignedUserIds = array_values(array_unique(array_filter(array_map('intval', $allAssignedUserIds))));
        $assignedUsers = $allAssignedUserIds === []
            ? collect()
            : AppUser::query()
                ->whereIn('id', $allAssignedUserIds)
                ->get(['id', 'employee_code', 'full_name_th', 'full_name_en'])
                ->keyBy('id');

        foreach ($assignments as $assignment) {
            $departmentCode = $this->normalizeDepartmentCode((string) ($assignment->dept_abbr_hr ?? ''));
            if ($departmentCode === '' || ! isset($rowsByDepartment[$departmentCode])) {
                continue;
            }

            $targetUserIds = $this->normalizeUserIdList(
                is_array($assignment->target_user_ids_json ?? null) ? $assignment->target_user_ids_json : []
            );
            if ($targetUserIds === []) {
                $legacyTargetId = (int) ($assignment->target_user_id ?? 0);
                if ($legacyTargetId > 0) {
                    $targetUserIds[] = $legacyTargetId;
                }
            }
            $reviewerUserIds = $this->normalizeUserIdList(
                is_array($assignment->reviewer_user_ids_json ?? null) ? $assignment->reviewer_user_ids_json : []
            );
            if ($reviewerUserIds === []) {
                $legacyReviewerId = (int) ($assignment->reviewer_user_id ?? 0);
                if ($legacyReviewerId > 0) {
                    $reviewerUserIds[] = $legacyReviewerId;
                }
            }

            $targetNames = [];
            foreach ($targetUserIds as $targetUserId) {
                $targetUser = $assignedUsers->get($targetUserId);
                $targetName = $targetUser
                    ? $this->resolveUserName($targetUser, $lang)
                    : '';
                if ($targetName !== '') {
                    $targetNames[] = $targetName;
                }
            }
            $reviewerNames = [];
            foreach ($reviewerUserIds as $reviewerUserId) {
                $reviewerUser = $assignedUsers->get($reviewerUserId);
                $reviewerName = $reviewerUser
                    ? $this->resolveUserName($reviewerUser, $lang)
                    : '';
                if ($reviewerName !== '') {
                    $reviewerNames[] = $reviewerName;
                }
            }

            $rowsByDepartment[$departmentCode]['target_user_ids'] = $targetUserIds;
            $rowsByDepartment[$departmentCode]['reviewer_user_ids'] = $reviewerUserIds;
            $rowsByDepartment[$departmentCode]['target_names'] = $targetNames;
            $rowsByDepartment[$departmentCode]['reviewer_names'] = $reviewerNames;
            $rowsByDepartment[$departmentCode]['target_user_id'] = $targetUserIds[0] ?? null;
            $rowsByDepartment[$departmentCode]['reviewer_user_id'] = $reviewerUserIds[0] ?? null;
            $rowsByDepartment[$departmentCode]['target_name'] = $targetNames !== [] ? implode(', ', $targetNames) : '-';
            $rowsByDepartment[$departmentCode]['reviewer_name'] = $reviewerNames !== [] ? implode(', ', $reviewerNames) : '-';
        }

        ksort($rowsByDepartment, SORT_STRING);
        return array_values($rowsByDepartment);
    }

    private function resolveUserName(?AppUser $user, string $lang): string
    {
        if (! $user) {
            return '';
        }

        $primary = $lang === 'th'
            ? trim((string) ($user->full_name_th ?? ''))
            : trim((string) ($user->full_name_en ?? ''));
        if ($primary !== '') {
            return $primary;
        }

        $fallbackEn = trim((string) ($user->full_name_en ?? ''));
        if ($fallbackEn !== '') {
            return $fallbackEn;
        }

        $fallbackTh = trim((string) ($user->full_name_th ?? ''));
        if ($fallbackTh !== '') {
            return $fallbackTh;
        }

        return trim((string) ($user->employee_code ?? ''));
    }

    private function normalizeDepartmentCode(?string $value): string
    {
        $text = PlainTextNormalizer::normalize($value);
        $text = preg_replace('/\s+/u', '', $text) ?? $text;
        return strtoupper($text);
    }

    private function normalizeDisplayText(?string $value, string $fallback = '-'): string
    {
        $text = PlainTextNormalizer::normalize($value);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        if ($text === '') {
            return $fallback;
        }

        return $text;
    }

    private function normalizeUserIdList(mixed $raw): array
    {
        $values = [];
        if (is_array($raw)) {
            $values = $raw;
        } elseif ($raw !== null && $raw !== '') {
            $values = [$raw];
        }

        $normalized = [];
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $normalized[] = $id;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function resolveLang(Request $request): string
    {
        return strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';
    }
}
