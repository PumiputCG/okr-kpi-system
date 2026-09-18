<?php

namespace App\Support;

use App\Models\AdminDepartmentAssignment;
use Illuminate\Support\Facades\Schema;

class DepartmentAssignmentResolver
{
    /**
     * @return array<int, string>
     */
    public static function resolveTargetDepartmentsByUserId(int $userId): array
    {
        return self::resolveDepartmentsByUserId(
            $userId,
            'target_user_id',
            'target_user_ids_json'
        );
    }

    /**
     * @return array<int, string>
     */
    public static function resolveReviewerDepartmentsByUserId(int $userId): array
    {
        return self::resolveDepartmentsByUserId(
            $userId,
            'reviewer_user_id',
            'reviewer_user_ids_json'
        );
    }

    /**
     * @return array<int, int>
     */
    public static function resolveTargetUserIdsByDepartment(string $departmentCode): array
    {
        return self::resolveUserIdsByDepartment(
            $departmentCode,
            'target_user_id',
            'target_user_ids_json'
        );
    }

    /**
     * @return array<int, int>
     */
    public static function resolveReviewerUserIdsByDepartment(string $departmentCode): array
    {
        return self::resolveUserIdsByDepartment(
            $departmentCode,
            'reviewer_user_id',
            'reviewer_user_ids_json'
        );
    }

    /**
     * @return array<int, string>
     */
    private static function resolveDepartmentsByUserId(int $userId, string $legacyColumn, string $jsonColumn): array
    {
        if ($userId < 1) {
            return [];
        }

        $rows = AdminDepartmentAssignment::query()
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->get(self::assignmentColumns($legacyColumn, $jsonColumn));

        $departments = [];
        foreach ($rows as $row) {
            $departmentCode = self::normalizeDepartmentCode((string) ($row->dept_abbr_hr ?? ''));
            if ($departmentCode === '') {
                continue;
            }

            $userIds = self::resolveUserIdsFromRow($row, $legacyColumn, $jsonColumn);
            if (in_array($userId, $userIds, true)) {
                $departments[] = $departmentCode;
            }
        }

        $departments = array_values(array_unique($departments));
        sort($departments, SORT_STRING);
        return $departments;
    }

    /**
     * @return array<int, int>
     */
    private static function resolveUserIdsByDepartment(string $departmentCode, string $legacyColumn, string $jsonColumn): array
    {
        $normalizedDepartment = self::normalizeDepartmentCode($departmentCode);
        if ($normalizedDepartment === '') {
            return [];
        }

        $rows = AdminDepartmentAssignment::query()
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(COALESCE(dept_abbr_hr, '')) <> ''")
            ->get(self::assignmentColumns($legacyColumn, $jsonColumn));

        $userIds = [];
        foreach ($rows as $row) {
            $rowDepartment = self::normalizeDepartmentCode((string) ($row->dept_abbr_hr ?? ''));
            if ($rowDepartment !== $normalizedDepartment) {
                continue;
            }

            $userIds = array_merge($userIds, self::resolveUserIdsFromRow($row, $legacyColumn, $jsonColumn));
        }

        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        sort($userIds, SORT_NUMERIC);
        return $userIds;
    }

    /**
     * @return array<int, int>
     */
    private static function resolveUserIdsFromRow(
        AdminDepartmentAssignment $row,
        string $legacyColumn,
        string $jsonColumn
    ): array {
        $userIds = [];

        if (Schema::hasColumn('admin_department_assignments', $jsonColumn)) {
            $jsonList = $row->getAttribute($jsonColumn);
            if (is_array($jsonList)) {
                foreach ($jsonList as $value) {
                    $id = (int) $value;
                    if ($id > 0) {
                        $userIds[] = $id;
                    }
                }
            }
        }

        if ($userIds === []) {
            $legacyId = (int) ($row->getAttribute($legacyColumn) ?? 0);
            if ($legacyId > 0) {
                $userIds[] = $legacyId;
            }
        }

        return array_values(array_unique($userIds));
    }

    /**
     * @return array<int, string>
     */
    private static function assignmentColumns(string $legacyColumn, string $jsonColumn): array
    {
        $columns = ['dept_abbr_hr', $legacyColumn];
        if (Schema::hasColumn('admin_department_assignments', $jsonColumn)) {
            $columns[] = $jsonColumn;
        }

        return $columns;
    }

    private static function normalizeDepartmentCode(string $value): string
    {
        $text = strtoupper(trim($value));
        $text = preg_replace('/\s+/u', '', $text) ?? $text;
        return $text;
    }
}

