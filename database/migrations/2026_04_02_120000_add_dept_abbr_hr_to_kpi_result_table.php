<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kpi_result')) {
            return;
        }

        if (! Schema::hasColumn('kpi_result', 'dept_abbr_hr')) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->string('dept_abbr_hr', 100)
                    ->default('')
                    ->after('cycle_id');
            });
        }

        DB::statement("\n            UPDATE kpi_result kr\n            INNER JOIN app_users u ON u.id = kr.app_user_id\n            SET kr.dept_abbr_hr = UPPER(REPLACE(TRIM(COALESCE(u.dept_abbr_hr, '')), ' ', ''))\n            WHERE TRIM(COALESCE(kr.dept_abbr_hr, '')) = ''\n        ");

        $newUniqueExists = DB::table('information_schema.statistics')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'kpi_result')
            ->where('INDEX_NAME', 'kpi_result_user_cycle_dept_unique')
            ->exists();

        if (! $newUniqueExists) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->unique(['app_user_id', 'cycle_id', 'dept_abbr_hr'], 'kpi_result_user_cycle_dept_unique');
            });
        }

        $oldUniqueExists = DB::table('information_schema.statistics')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'kpi_result')
            ->where('INDEX_NAME', 'kpi_result_app_user_cycle_unique')
            ->exists();

        if ($oldUniqueExists) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->dropUnique('kpi_result_app_user_cycle_unique');
            });
        }

        $legacyUniqueExists = DB::table('information_schema.statistics')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'kpi_result')
            ->where('INDEX_NAME', 'kpi_result_app_user_id_unique')
            ->exists();

        if ($legacyUniqueExists) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->dropUnique('kpi_result_app_user_id_unique');
            });
        }

        $managerPositions = [
            'assist manager',
            'assistant manager',
            'manager',
        ];

        $normalizePosition = static function (mixed $value): string {
            $text = strtolower(trim((string) $value));
            $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
            return $text;
        };
        $normalizeDepartment = static function (mixed $value): string {
            $text = strtoupper(trim((string) $value));
            $text = preg_replace('/\s+/u', '', $text) ?? $text;
            return $text;
        };
        $firstTargetDepartment = static function (mixed $raw) use ($normalizeDepartment): string {
            $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
            if (! is_array($decoded)) {
                return '';
            }

            foreach ($decoded as $departmentCode) {
                $normalized = $normalizeDepartment($departmentCode);
                if ($normalized !== '') {
                    return $normalized;
                }
            }

            return '';
        };

        $rootRows = DB::table('kpi_month_scores as s')
            ->join('app_users as u', 'u.id', '=', 's.app_user_id')
            ->whereNotNull('s.result')
            ->where(function ($query) {
                $query->where(function ($inner) {
                    $inner->where('s.month_no', 0)
                        ->whereNull('s.kpi_meta_id');
                })->orWhereColumn('s.kpi_meta_id', 's.id');
            })
            ->get([
                's.app_user_id',
                's.cycle_id',
                's.result',
                's.target_departments',
                'u.position',
                'u.dept_abbr_hr',
            ]);

        $scoreBuckets = [];
        foreach ($rootRows as $row) {
            $userId = (int) ($row->app_user_id ?? 0);
            $cycleId = (int) ($row->cycle_id ?? 0);
            if ($userId < 1 || $cycleId < 1) {
                continue;
            }

            $position = $normalizePosition($row->position ?? '');
            $ownerDepartment = $normalizeDepartment($row->dept_abbr_hr ?? '');
            $department = $ownerDepartment;
            if (in_array($position, $managerPositions, true)) {
                $selectedDepartment = $firstTargetDepartment($row->target_departments);
                if ($selectedDepartment !== '') {
                    $department = $selectedDepartment;
                }
            }
            if ($department === '') {
                continue;
            }

            $resultValue = (float) ($row->result ?? 0);
            $bucketKey = $userId.'|'.$cycleId.'|'.$department;
            if (! isset($scoreBuckets[$bucketKey])) {
                $scoreBuckets[$bucketKey] = [
                    'app_user_id' => $userId,
                    'cycle_id' => $cycleId,
                    'dept_abbr_hr' => $department,
                    'sum' => 0.0,
                    'count' => 0,
                ];
            }

            $scoreBuckets[$bucketKey]['sum'] += $resultValue;
            $scoreBuckets[$bucketKey]['count']++;
        }

        $now = now();
        $kpiResultPayload = [];
        foreach ($scoreBuckets as $bucket) {
            $count = (int) ($bucket['count'] ?? 0);
            if ($count < 1) {
                continue;
            }

            $kpiResultPayload[] = [
                'app_user_id' => (int) $bucket['app_user_id'],
                'cycle_id' => (int) $bucket['cycle_id'],
                'dept_abbr_hr' => (string) $bucket['dept_abbr_hr'],
                'result' => round(((float) $bucket['sum']) / $count, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('kpi_result')->delete();
        if ($kpiResultPayload !== []) {
            DB::table('kpi_result')->insert($kpiResultPayload);
        }

        $okrBuckets = [];
        foreach ($kpiResultPayload as $row) {
            $cycleId = (int) ($row['cycle_id'] ?? 0);
            $department = $normalizeDepartment($row['dept_abbr_hr'] ?? '');
            if ($cycleId < 1 || $department === '') {
                continue;
            }

            $key = $cycleId.'|'.$department;
            if (! isset($okrBuckets[$key])) {
                $okrBuckets[$key] = [
                    'cycle_id' => $cycleId,
                    'dept_abbr_hr' => $department,
                    'sum' => 0.0,
                    'count' => 0,
                ];
            }

            $okrBuckets[$key]['sum'] += (float) ($row['result'] ?? 0);
            $okrBuckets[$key]['count']++;
        }

        $okrPayload = [];
        foreach ($okrBuckets as $bucket) {
            $count = (int) ($bucket['count'] ?? 0);
            if ($count < 1) {
                continue;
            }

            $okrPayload[] = [
                'dept_abbr_hr' => (string) $bucket['dept_abbr_hr'],
                'cycle_id' => (int) $bucket['cycle_id'],
                'result' => round(((float) $bucket['sum']) / $count, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('okr_result')->delete();
        if ($okrPayload !== []) {
            DB::table('okr_result')->insert($okrPayload);
        }

        $overallBuckets = [];
        foreach ($okrPayload as $row) {
            $cycleId = (int) ($row['cycle_id'] ?? 0);
            if ($cycleId < 1) {
                continue;
            }

            $overallBuckets[$cycleId] ??= ['sum' => 0.0, 'count' => 0];
            $overallBuckets[$cycleId]['sum'] += (float) ($row['result'] ?? 0);
            $overallBuckets[$cycleId]['count']++;
        }

        $overallPayload = [];
        foreach ($overallBuckets as $cycleId => $bucket) {
            $count = (int) ($bucket['count'] ?? 0);
            if ($count < 1) {
                continue;
            }

            $overallPayload[] = [
                'cycle_id' => (int) $cycleId,
                'result' => round(((float) $bucket['sum']) / $count, 2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('okr_all_result')->delete();
        if ($overallPayload !== []) {
            DB::table('okr_all_result')->insert($overallPayload);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_result')) {
            return;
        }

        $newUniqueExists = DB::table('information_schema.statistics')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'kpi_result')
            ->where('INDEX_NAME', 'kpi_result_user_cycle_dept_unique')
            ->exists();

        if ($newUniqueExists) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->dropUnique('kpi_result_user_cycle_dept_unique');
            });
        }

        if (Schema::hasColumn('kpi_result', 'dept_abbr_hr')) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->dropColumn('dept_abbr_hr');
            });
        }

        $oldUniqueExists = DB::table('information_schema.statistics')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'kpi_result')
            ->where('INDEX_NAME', 'kpi_result_app_user_cycle_unique')
            ->exists();

        if (! $oldUniqueExists) {
            Schema::table('kpi_result', function (Blueprint $table) {
                $table->unique(['app_user_id', 'cycle_id'], 'kpi_result_app_user_cycle_unique');
            });
        }
    }
};
