<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\OkrAllResult;
use App\Models\OkrResult;
use Illuminate\Http\Request;

class OkrSummaryAllController extends Controller
{
    public function index(Request $request)
    {
        $lang = strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';

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
        $overallResult = '-';
        if ($selectedCycle) {
            [$rows, $overallResult] = $this->buildRows((int) $selectedCycle->id);
        }

        return view('okr-summary-all', [
            'lang' => $lang,
            'cycles' => $cycles,
            'selectedCycle' => $selectedCycle,
            'rows' => $rows,
            'overallResult' => $overallResult,
        ]);
    }

    private function buildRows(int $cycleId): array
    {
        $departmentsFromUsers = AppUser::query()
            ->whereNotNull('dept_abbr_hr')
            ->whereRaw("TRIM(dept_abbr_hr) <> ''")
            ->pluck('dept_abbr_hr')
            ->map(fn ($value) => strtoupper(trim((string) $value)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $okrRows = OkrResult::query()
            ->where('cycle_id', $cycleId)
            ->get(['dept_abbr_hr', 'result']);

        $departmentsFromOkrResult = $okrRows->pluck('dept_abbr_hr')
            ->map(fn ($value) => strtoupper(trim((string) $value)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $departments = array_values(array_unique(array_merge($departmentsFromUsers, $departmentsFromOkrResult)));
        sort($departments);

        $resultByDepartment = $okrRows
            ->mapWithKeys(function ($row) {
                $key = strtoupper(trim((string) $row->dept_abbr_hr));
                return [$key => $row->result];
            });

        $rows = [];
        foreach ($departments as $index => $department) {
            $rows[] = [
                'no' => $index + 1,
                'department' => $department,
                'dept_result' => $this->formatPercent($resultByDepartment->get($department)),
            ];
        }

        $overall = OkrAllResult::query()
            ->where('cycle_id', $cycleId)
            ->value('result');

        return [$rows, $this->formatPercent($overall)];
    }

    private function formatPercent(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, 2).'%';
    }
}
