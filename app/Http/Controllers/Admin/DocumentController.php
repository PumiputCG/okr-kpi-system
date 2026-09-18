<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Cycle;
use App\Models\KpiMonthScore;
use App\Models\KpiUnit;
use App\Models\OkrAllResult;
use App\Models\OkrKeyResult;
use App\Models\OkrObjective;
use App\Models\OkrResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class DocumentController extends Controller
{
    private const STYLE_HEADER = 1;
    private const STYLE_CENTER = 3;
    private const STYLE_DEFAULT = 4;
    private const KPI_TARGET_DEPARTMENT_POSITIONS = [
        'assist manager',
        'assistant manager',
        'manager',
    ];

    public function index(Request $request)
    {
        $lang = strtolower((string) $request->query('lang', 'en')) === 'th' ? 'th' : 'en';

        $cycles = Cycle::query()
            ->orderBy('id')
            ->get(['id', 'name', 'start_date', 'end_date', 'opened_at', 'closed_at']);

        return view('admin-documents', [
            'lang' => $lang,
            'cycles' => $cycles,
        ]);
    }

    public function downloadCycleExcel(Cycle $cycle)
    {
        $payload = $this->buildExcelPayload($cycle);
        $filePath = $this->createXlsxFile($payload['rows'], $payload['merge_ranges']);

        $rawCycleName = trim((string) ($cycle->name ?? ''));
        $safeCycleName = preg_replace('/[<>:"\/\\\\|?*]+/', '_', $rawCycleName ?? '') ?? '';
        $safeCycleName = trim($safeCycleName);
        if ($safeCycleName === '') {
            $safeCycleName = 'cycle-'.$cycle->id;
        }
        $fileName = 'okr-kpi_'.$safeCycleName.'.xlsx';

        return response()
            ->download(
                $filePath,
                $fileName,
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
    }

    public function downloadRangeExcel(Request $request)
    {
        $validated = $request->validate([
            'cycle_id' => ['required', 'integer', 'exists:cycles,id'],
            'months' => ['required', 'array', 'min:1'],
            'months.*' => ['required', 'integer', 'between:1,12'],
        ]);

        $cycle = Cycle::query()->findOrFail((int) $validated['cycle_id']);
        $selectedMonths = collect($validated['months'])
            ->map(fn ($monthNo) => (int) $monthNo)
            ->filter(fn (int $monthNo) => $monthNo >= 1 && $monthNo <= 12)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (count($selectedMonths) < 1) {
            return back()->withErrors([
                'months' => 'Please select at least one month.',
            ]);
        }

        $payload = $this->buildExcelPayload($cycle, $selectedMonths);
        $filePath = $this->createXlsxFile($payload['rows'], $payload['merge_ranges']);

        $rawCycleName = trim((string) ($cycle->name ?? ''));
        $safeCycleName = preg_replace('/[<>:"\/\\\\|?*]+/', '_', $rawCycleName ?? '') ?? '';
        $safeCycleName = trim($safeCycleName);
        if ($safeCycleName === '') {
            $safeCycleName = 'cycle-'.$cycle->id;
        }

        $monthPart = implode('-', $selectedMonths);
        $fileName = 'okr-kpi_'.$safeCycleName.'_months-'.$monthPart.'.xlsx';

        return response()
            ->download(
                $filePath,
                $fileName,
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
    }

    /**
     * @return array{
     *     rows: array<int, array<int, array{value: string, style: int, type: string}>>,
     *     merge_ranges: array<int, string>
     * }
     */
    private function buildExcelPayload(Cycle $cycle, ?array $selectedMonths = null): array
    {
        $isMonthScopedExport = is_array($selectedMonths);
        $selectedMonths = $this->normalizeMonthSelection($selectedMonths);
        $headerColumns = [
            'รหัสพนักงาน',
            'ชื่อ-สกุล(ไทย)',
            'ชื่อ-สกุล(Eng)',
            'ประเภท',
            'ตำแหน่ง',
            'แผนก/ฝ่าย',
            'ตัวย่อส่วนงาน (ใช้ใน HR)',
            'ตัวย่อแผนก (ใช้ใน QMS)',
            'เลขที่ประกันสังคม',
            'แผนกที่เลือก',
            'หัวข้อเป้าหมาย',
            'วัตถุประสงค์',
            'รายละเอียด',
            'เป้าหมาย',
            'หน่วย',
            $isMonthScopedExport ? 'คะแนน (เดือนที่เลือก)' : 'คะแนน(12 เดือน)',
            'ค่าเฉลี่ยผลลัพธ์ KPI',
            'ผลลัพธ์ KPIs เฉลี่ยทั้งหมดรายบุคคล',
            'ค่าเฉลี่ยผลลัพธ์ของแผนก (OKR)',
            'ค่าเฉลี่ยผลลัพธ์ OKRs ทุกแผนก',
        ];

        $rows = [
            array_map(
                fn (string $text) => $this->stringCell($text, self::STYLE_HEADER),
                $headerColumns
            ),
        ];

        $users = AppUser::query()
            ->where(function ($query) {
                $query->whereNull('role')
                    ->orWhereRaw('LOWER(role) != ?', ['admin']);
            })
            ->orderByRaw("COALESCE(dept_abbr_hr, '')")
            ->orderByRaw("COALESCE(employee_code, '')")
            ->get([
                'id',
                'employee_code',
                'full_name_th',
                'full_name_en',
                'employee_type',
                'position',
                'department',
                'dept_abbr_hr',
                'dept_abbr_qms',
                'id_thai_hash',
                'role',
            ]);

        if ($users->isEmpty()) {
            return ['rows' => $rows, 'merge_ranges' => []];
        }

        $userIds = $users->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $reportRowsData = $this->buildReportRowsForExport((int) $cycle->id, $userIds);
        $rootRows = $reportRowsData['root_rows'];
        $rootRowsByUser = $reportRowsData['root_rows_by_user'];
        $monthRowsByRoot = $reportRowsData['month_rows_by_root'];
        $goalHeadingMap = $this->buildGoalHeadingMap($rootRows);

        $scopedMetrics = $this->calculateScopedMetrics($users, $rootRows, $monthRowsByRoot, $selectedMonths);
        $scopedRootResultById = $scopedMetrics['root_result_by_id'];

        $usersById = $users->keyBy(fn (AppUser $user) => (int) $user->id);
        if ($isMonthScopedExport) {
            $kpiResultByUserAndDepartment = $this->calculateUserDepartmentKpiAverageFromRootRows(
                $rootRowsByUser,
                $usersById,
                $scopedRootResultById
            );
            $okrResultByDepartment = collect(
                $this->calculateDepartmentAverageFromUserDepartmentKpi($kpiResultByUserAndDepartment)
            );
            $okrAllValue = $this->calculateOverallOkrFromDepartment($okrResultByDepartment->all());
        } else {
            $kpiResultByUserAndDepartment = $this->calculateUserDepartmentKpiAverageFromRootRows(
                $rootRowsByUser,
                $usersById,
                $scopedRootResultById
            );

            $okrResultByDepartment = OkrResult::query()
                ->where('cycle_id', (int) $cycle->id)
                ->get(['dept_abbr_hr', 'result'])
                ->mapWithKeys(function ($row) {
                    $departmentCode = strtoupper(trim((string) $row->dept_abbr_hr));
                    return [$departmentCode => $row->result];
                });

            $okrAllValue = OkrAllResult::query()
                ->where('cycle_id', (int) $cycle->id)
                ->value('result');
        }

        $unitById = KpiUnit::query()
            ->get(['id', 'code', 'name_th'])
            ->mapWithKeys(function (KpiUnit $unit) {
                $nameTh = trim((string) $unit->name_th);
                $code = trim((string) $unit->code);
                $label = $nameTh !== '' ? $nameTh : $code;
                return [(int) $unit->id => $label];
            })
            ->all();

        $records = [];
        foreach ($users as $user) {
            $departmentCode = $this->normalizeDepartmentCode((string) ($user->dept_abbr_hr ?? ''));
            $baseRecord = [
                'sort_employee_code' => strtolower(trim((string) ($user->employee_code ?? ''))),
                'sort_employee_id' => (int) $user->id,
                'employee_id' => (int) $user->id,
                'department_code' => $departmentCode,
            ];

            $rootsForUser = $rootRowsByUser->get((int) $user->id, collect());
            if ($rootsForUser->isEmpty()) {
                $groupDepartmentCode = $departmentCode;
                $departmentResult = $this->resolveDepartmentResultForExport(
                    $okrResultByDepartment,
                    $groupDepartmentCode,
                    $departmentCode
                );
                $records[] = array_merge($baseRecord, [
                    'sort_department' => $groupDepartmentCode,
                    'sort_score_group' => 2,
                    'group_department_code' => $groupDepartmentCode,
                    'department_result' => $departmentResult,
                    'has_report' => false,
                    'sort_root_id' => 0,
                    'row_values' => [
                        $this->safeText($user->employee_code),
                        $this->safeText($user->full_name_th),
                        $this->safeText($user->full_name_en),
                        $this->safeText($user->employee_type),
                        $this->formatPositionText($user->position),
                        $this->safeText($user->department),
                        $this->safeText($user->dept_abbr_hr),
                        $this->safeText($user->dept_abbr_qms),
                        $this->safeText($user->id_thai_hash),
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '-',
                        '',
                        '',
                    ],
                    'personal_result_text' => '-',
                ]);
                continue;
            }

            foreach ($rootsForUser as $rootRow) {
                $monthRows = $monthRowsByRoot->get((int) $rootRow->id, collect());
                $operator = trim((string) $rootRow->criteria_operator);
                $targetValue = $rootRow->target_value !== null ? (float) $rootRow->target_value : null;
                $unitText = $unitById[(int) ($rootRow->kpi_unit_id ?? 0)] ?? '-';
                $selectedDepartmentDisplay = $this->resolveSelectedDepartmentDisplayForExport($user, $rootRow);
                $groupDepartmentCode = $this->resolveGroupDepartmentCodeForExport($user, $rootRow);
                if ($groupDepartmentCode === '') {
                    $groupDepartmentCode = $departmentCode;
                }
                $kpiAverageMapKey = ((int) $user->id).'|'.$this->normalizeDepartmentCode($groupDepartmentCode);
                $kpiAverageValue = $kpiResultByUserAndDepartment[$kpiAverageMapKey] ?? null;
                $departmentResult = $this->resolveDepartmentResultForExport(
                    $okrResultByDepartment,
                    $groupDepartmentCode,
                    $departmentCode
                );
                $rootResult = $scopedRootResultById[(int) $rootRow->id] ?? null;
                $monthlyScoreText = $isMonthScopedExport
                    ? $this->formatSelectedMonthScoreLines($monthRows, $selectedMonths, $targetValue, $operator)
                    : $this->formatMonthlyScoreLines($monthRows, $targetValue, $operator);
                $hasRootScore = $rootResult !== null;
                $kpiAverageText = $this->formatPercent($kpiAverageValue);
                $rootResultText = $hasRootScore
                    ? $this->formatPercent($rootResult)
                    : '-';
                $goalHeadingText = $goalHeadingMap[(int) $rootRow->id] ?? '-';

                $records[] = array_merge($baseRecord, [
                    'sort_department' => $groupDepartmentCode,
                    'sort_score_group' => $hasRootScore ? 1 : 0,
                    'group_department_code' => $groupDepartmentCode,
                    'department_result' => $departmentResult,
                    'has_report' => true,
                    'sort_root_id' => (int) $rootRow->id,
                    'row_values' => [
                        $this->safeText($user->employee_code),
                        $this->safeText($user->full_name_th),
                        $this->safeText($user->full_name_en),
                        $this->safeText($user->employee_type),
                        $this->formatPositionText($user->position),
                        $this->safeText($user->department),
                        $this->safeText($user->dept_abbr_hr),
                        $this->safeText($user->dept_abbr_qms),
                        $this->safeText($user->id_thai_hash),
                        $selectedDepartmentDisplay,
                        $goalHeadingText,
                        $this->safeText($rootRow->objective),
                        $this->safeText($rootRow->detail),
                        $this->formatTargetForExport($targetValue, $operator),
                        $this->safeText($unitText),
                        $monthlyScoreText,
                        $rootResultText,
                        $kpiAverageText,
                        '',
                        '',
                    ],
                    'personal_result_text' => $kpiAverageText,
                ]);
            }
        }

        usort($records, function (array $a, array $b): int {
            $cmpDepartment = strcmp((string) $a['sort_department'], (string) $b['sort_department']);
            if ($cmpDepartment !== 0) {
                return $cmpDepartment;
            }

            $cmpEmployeeCode = strcmp((string) $a['sort_employee_code'], (string) $b['sort_employee_code']);
            if ($cmpEmployeeCode !== 0) {
                return $cmpEmployeeCode;
            }

            $cmpEmployeeId = ((int) ($a['sort_employee_id'] ?? 0)) <=> ((int) ($b['sort_employee_id'] ?? 0));
            if ($cmpEmployeeId !== 0) {
                return $cmpEmployeeId;
            }

            $cmpScoreGroup = ((int) ($a['sort_score_group'] ?? 9)) <=> ((int) ($b['sort_score_group'] ?? 9));
            if ($cmpScoreGroup !== 0) {
                return $cmpScoreGroup;
            }

            return ((int) $a['sort_root_id']) <=> ((int) $b['sort_root_id']);
        });

        $recordCount = count($records);
        if ($recordCount === 0) {
            return ['rows' => $rows, 'merge_ranges' => []];
        }

        foreach ($records as $record) {
            $rows[] = $this->buildStyledDataRow($record['row_values']);
        }

        $mergeRanges = [];
        $firstDataRow = 2;

        for ($start = 0; $start < $recordCount; ) {
            $groupEmployeeId = (int) ($records[$start]['employee_id'] ?? 0);
            $groupEmployeeDepartmentCode = (string) ($records[$start]['group_department_code'] ?? '');
            $end = $start;

            while (
                ($end + 1) < $recordCount
                && (int) ($records[$end + 1]['employee_id'] ?? 0) === $groupEmployeeId
                && (string) ($records[$end + 1]['group_department_code'] ?? '') === $groupEmployeeDepartmentCode
            ) {
                $end++;
            }

            if ($end > $start) {
                $groupStartExcelRow = $firstDataRow + $start;
                $groupEndExcelRow = $firstDataRow + $end;
                $firstPersonalResultText = trim((string) ($records[$start]['personal_result_text'] ?? ''));
                $canMergePersonalResult = $firstPersonalResultText !== '' && $firstPersonalResultText !== '-';
                if ($canMergePersonalResult) {
                    for ($recordIndex = $start + 1; $recordIndex <= $end; $recordIndex++) {
                        $nextPersonalResultText = trim((string) ($records[$recordIndex]['personal_result_text'] ?? ''));
                        if ($nextPersonalResultText !== $firstPersonalResultText) {
                            $canMergePersonalResult = false;
                            break;
                        }
                    }
                }
                if ($canMergePersonalResult) {
                    $mergeRanges[] = 'R'.$groupStartExcelRow.':R'.$groupEndExcelRow;
                }

                for ($recordIndex = $start + 1; $recordIndex <= $end; $recordIndex++) {
                    $excelRow = $firstDataRow + $recordIndex;
                    for ($columnIndex = 0; $columnIndex <= 8; $columnIndex++) {
                        $rows[$excelRow - 1][$columnIndex] = $this->stringCell('', $this->styleForColumn($columnIndex));
                    }

                    if ($canMergePersonalResult) {
                        $rows[$excelRow - 1][17] = $this->stringCell('', $this->styleForColumn(17));
                    }
                }
            }

            $start = $end + 1;
        }

        for ($start = 0; $start < $recordCount; ) {
            $groupDepartmentCode = (string) ($records[$start]['group_department_code'] ?? ($records[$start]['department_code'] ?? ''));
            $end = $start;

            while (
                ($end + 1) < $recordCount
                && (string) ($records[$end + 1]['group_department_code'] ?? ($records[$end + 1]['department_code'] ?? '')) === $groupDepartmentCode
            ) {
                $end++;
            }

            $groupStartExcelRow = $firstDataRow + $start;
            $groupEndExcelRow = $firstDataRow + $end;
            $departmentResultText = $this->formatPercent($records[$start]['department_result'] ?? null);
            $rows[$groupStartExcelRow - 1][18] = $this->stringCell($departmentResultText, self::STYLE_CENTER);

            if ($groupEndExcelRow > $groupStartExcelRow) {
                $mergeRanges[] = 'S'.$groupStartExcelRow.':S'.$groupEndExcelRow;
            }

            $start = $end + 1;
        }

        $rows[$firstDataRow - 1][19] = $this->stringCell($this->formatPercent($okrAllValue), self::STYLE_CENTER);
        $lastDataRow = $firstDataRow + $recordCount - 1;
        if ($lastDataRow > $firstDataRow) {
            $mergeRanges[] = 'T'.$firstDataRow.':T'.$lastDataRow;
        }

        return [
            'rows' => $rows,
            'merge_ranges' => $mergeRanges,
        ];
    }

    /**
     * @param array<int, array<int, array{value: string, style: int, type: string}>> $rows
     * @param array<int, string> $mergeRanges
     */
    private function createXlsxFile(array $rows, array $mergeRanges = []): string
    {
        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir) && ! mkdir($tmpDir, 0777, true) && ! is_dir($tmpDir)) {
            throw new RuntimeException('Unable to prepare temporary directory for Excel export.');
        }

        $path = $tmpDir.DIRECTORY_SEPARATOR.'documents-export-'.Str::uuid().'.xlsx';
        $zip = new ZipArchive();
        $opened = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            throw new RuntimeException('Unable to create Excel export file.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($rows, $mergeRanges));
        $zip->close();

        return $path;
    }

    /**
     * @param array<int, array<int, array{value: string, style: int, type: string}>> $rows
     * @param array<int, string> $mergeRanges
     */
    private function worksheetXml(array $rows, array $mergeRanges = []): string
    {
        $rowXml = [];

        foreach ($rows as $rowIndex => $rowValues) {
            $excelRow = $rowIndex + 1;
            $cellXml = [];

            foreach ($rowValues as $columnIndex => $cell) {
                $column = $this->columnName($columnIndex + 1);
                $cellRef = $column.$excelRow;
                $styleId = (int) ($cell['style'] ?? self::STYLE_DEFAULT);
                $text = $this->escapeXml((string) ($cell['value'] ?? ''));
                $cellXml[] = '<c r="'.$cellRef.'" s="'.$styleId.'" t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>';
            }

            $rowXml[] = '<row r="'.$excelRow.'">'.implode('', $cellXml).'</row>';
        }

        $mergeCellsXml = '';
        if (count($mergeRanges) > 0) {
            $mergeItems = array_map(
                static fn (string $range): string => '<mergeCell ref="'.$range.'"/>',
                $mergeRanges
            );
            $mergeCellsXml = '<mergeCells count="'.count($mergeItems).'">'.implode('', $mergeItems).'</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .$this->columnsXml()
            .'<sheetData>'.implode('', $rowXml).'</sheetData>'
            .$mergeCellsXml
            .'</worksheet>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" '
            .'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" '
            .'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" '
            .'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" '
            .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" '
            .'Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" '
            .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
            .'Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" '
            .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" '
            .'Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="2">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color auto="1"/></left>'
            .'<right style="thin"><color auto="1"/></right>'
            .'<top style="thin"><color auto="1"/></top>'
            .'<bottom style="thin"><color auto="1"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            .'</cellStyleXfs>'
            .'<cellXfs count="5">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            .'<alignment vertical="top"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            .'<alignment vertical="top"/>'
            .'</xf>'
            .'</cellXfs>'
            .'<cellStyles count="1">'
            .'<cellStyle name="Normal" xfId="0" builtinId="0"/>'
            .'</cellStyles>'
            .'</styleSheet>';
    }

    /**
     * @param array<int, string> $values
     * @return array<int, array{value: string, style: int, type: string}>
     */
    private function buildStyledDataRow(array $values): array
    {
        $styled = [];

        foreach ($values as $columnIndex => $value) {
            $styled[] = $this->stringCell($value, $this->styleForColumn((int) $columnIndex));
        }

        return $styled;
    }

    private function buildGoalHeadingMap(\Illuminate\Support\Collection $rootRows): array
    {
        if ($rootRows->isEmpty()) {
            return [];
        }

        $parentIds = $rootRows
            ->pluck('parent_target_kpi_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $l3TargetMap = [];
        if ($parentIds !== []) {
            KpiMonthScore::query()
                ->whereIn('id', $parentIds)
                ->get(['id', 'objective', 'okr_objective_id', 'okr_key_result_id'])
                ->each(function (KpiMonthScore $t) use (&$l3TargetMap): void {
                    $l3TargetMap[(int) $t->id] = $t;
                });
        }

        $objIds = [];
        $krIds = [];
        foreach ($rootRows as $row) {
            $parentId = (int) ($row->parent_target_kpi_id ?? 0);
            $l3 = $parentId > 0 ? ($l3TargetMap[$parentId] ?? null) : null;
            $objId = (int) ($l3?->okr_objective_id ?? $row->okr_objective_id ?? 0);
            $krId  = (int) ($l3?->okr_key_result_id ?? $row->okr_key_result_id ?? 0);
            if ($objId > 0) {
                $objIds[] = $objId;
            }
            if ($krId > 0) {
                $krIds[] = $krId;
            }
        }

        $objectivesById = OkrObjective::query()
            ->whereIn('id', array_unique($objIds))
            ->get(['id', 'title'])
            ->keyBy('id');

        $keyResultsById = OkrKeyResult::query()
            ->whereIn('id', array_unique($krIds))
            ->get(['id', 'title'])
            ->keyBy('id');

        $headingMap = [];
        foreach ($rootRows as $row) {
            $rootId = (int) ($row->id ?? 0);
            if ($rootId < 1) {
                continue;
            }

            $parentId = (int) ($row->parent_target_kpi_id ?? 0);
            $l3Target = $parentId > 0 ? ($l3TargetMap[$parentId] ?? null) : null;

            $objId      = (int) ($l3Target?->okr_objective_id ?? $row->okr_objective_id ?? 0);
            $krId       = (int) ($l3Target?->okr_key_result_id ?? $row->okr_key_result_id ?? 0);
            $l3Title    = trim((string) ($l3Target?->objective ?? ''));

            $l1 = $objId > 0 ? trim((string) ($objectivesById->get($objId)?->title ?? '')) : '';
            $l2 = $krId  > 0 ? trim((string) ($keyResultsById->get($krId)?->title  ?? '')) : '';

            $parts = array_filter([$l1, $l2, $l3Title], fn (string $s) => $s !== '');
            $headingMap[$rootId] = $parts !== []
                ? implode("\n", array_map(fn (string $s) => '- '.$s, array_values($parts)))
                : '-';
        }

        return $headingMap;
    }

    private function styleForColumn(int $columnIndex): int
    {
        $centerColumns = [
            0,
            3,
            4,
            5,
            6,
            7,
            8,
            9,
            13,
            14,
            16,
            17,
            18,
            19,
        ];

        return in_array($columnIndex, $centerColumns, true)
            ? self::STYLE_CENTER
            : self::STYLE_DEFAULT;
    }

    /**
     * @return array{value: string, style: int, type: string}
     */
    private function stringCell(string $value, int $style): array
    {
        return [
            'value' => $value,
            'style' => $style,
            'type' => 's',
        ];
    }

    private function safeText(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : '-';
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

    private function normalizeDepartmentCode(mixed $value): string
    {
        $text = strtoupper(trim((string) ($value ?? '')));
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return $text;
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

    private function canUseSelectedDepartmentByPosition(string $position): bool
    {
        $normalizedPosition = $this->normalizePositionKey($position);
        if ($normalizedPosition === '') {
            return false;
        }

        return in_array($normalizedPosition, $this->targetDepartmentPositions(), true);
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

    private function resolveGroupDepartmentCodeForExport(AppUser $user, KpiMonthScore $rootRow): string
    {
        $ownerDepartment = $this->normalizeDepartmentCode((string) ($user->dept_abbr_hr ?? ''));
        if (! $this->canUseSelectedDepartmentByPosition((string) ($user->position ?? ''))) {
            return $ownerDepartment;
        }

        $selectedDepartment = $this->firstDepartmentCodeFromTargetDepartments($rootRow->target_departments);
        return $selectedDepartment !== '' ? $selectedDepartment : $ownerDepartment;
    }

    private function resolveSelectedDepartmentDisplayForExport(AppUser $user, KpiMonthScore $rootRow): string
    {
        if (! $this->canUseSelectedDepartmentByPosition((string) ($user->position ?? ''))) {
            return '-';
        }

        $selectedDepartment = $this->firstDepartmentCodeFromTargetDepartments($rootRow->target_departments);
        return $selectedDepartment !== '' ? $selectedDepartment : '-';
    }

    private function resolveDepartmentResultForExport(
        mixed $okrResultByDepartment,
        string $groupDepartmentCode,
        string $ownerDepartmentCode
    ): mixed {
        $groupDepartmentCode = $this->normalizeDepartmentCode($groupDepartmentCode);
        $ownerDepartmentCode = $this->normalizeDepartmentCode($ownerDepartmentCode);

        if ($groupDepartmentCode !== '') {
            $value = is_object($okrResultByDepartment) && method_exists($okrResultByDepartment, 'get')
                ? $okrResultByDepartment->get($groupDepartmentCode)
                : null;
            if ($value !== null) {
                return $value;
            }
        }

        if ($ownerDepartmentCode !== '') {
            return is_object($okrResultByDepartment) && method_exists($okrResultByDepartment, 'get')
                ? $okrResultByDepartment->get($ownerDepartmentCode)
                : null;
        }

        return null;
    }

    private function formatTargetForExport(?float $targetValue, string $operator): string
    {
        if ($targetValue === null) {
            return '-';
        }

        $targetText = $this->formatNumber($targetValue);
        if ($operator === '') {
            return $targetText;
        }

        return $this->displayOperator($operator).' '.$targetText;
    }

    /**
     * @param array<int, int>|null $selectedMonths
     * @return array<int, int>
     */
    private function normalizeMonthSelection(?array $selectedMonths): array
    {
        if (! is_array($selectedMonths)) {
            return range(1, 12);
        }

        $months = collect($selectedMonths)
            ->map(fn ($monthNo) => (int) $monthNo)
            ->filter(fn (int $monthNo) => $monthNo >= 1 && $monthNo <= 12)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return count($months) > 0 ? $months : range(1, 12);
    }

    /**
     * @param array<int, int> $userIds
     * @return array{
     *     root_rows: \Illuminate\Support\Collection<int, KpiMonthScore>,
     *     root_rows_by_user: \Illuminate\Support\Collection<int|string, \Illuminate\Support\Collection<int, KpiMonthScore>>,
     *     month_rows_by_root: \Illuminate\Support\Collection<int|string, \Illuminate\Support\Collection<int, KpiMonthScore>>
     * }
     */
    private function buildReportRowsForExport(int $cycleId, array $userIds): array
    {
        if (count($userIds) < 1) {
            return [
                'root_rows' => collect(),
                'root_rows_by_user' => collect(),
                'month_rows_by_root' => collect(),
            ];
        }

        $allRows = KpiMonthScore::query()
            ->where('cycle_id', $cycleId)
            ->whereIn('app_user_id', $userIds)
            ->whereBetween('month_no', [0, 12])
            ->orderBy('id')
            ->get([
                'id',
                'app_user_id',
                'kpi_meta_id',
                'month_no',
                'objective',
                'detail',
                'target_value',
                'kpi_unit_id',
                'criteria_operator',
                'target_departments',
                'score_value',
                'is_pass',
                'result',
                'parent_target_kpi_id',
                'okr_objective_id',
                'okr_key_result_id',
            ]);

        if ($allRows->isEmpty()) {
            return [
                'root_rows' => collect(),
                'root_rows_by_user' => collect(),
                'month_rows_by_root' => collect(),
            ];
        }

        $reportRowsByUser = [];
        foreach ($allRows as $row) {
            $userId = (int) ($row->app_user_id ?? 0);
            if ($userId < 1) {
                continue;
            }

            $reportKey = $this->resolveReportKeyForExport($row);
            $reportRowsByUser[$userId] ??= [];
            $reportRowsByUser[$userId][$reportKey] ??= [];
            $reportRowsByUser[$userId][$reportKey][] = $row;
        }

        $rootRows = collect();
        $monthRowsByRoot = collect();

        foreach ($reportRowsByUser as $userReports) {
            foreach ($userReports as $reportRows) {
                $rootRow = $this->resolveReportRootRowForExport($reportRows);
                if (! $rootRow) {
                    continue;
                }

                $rootRows->push($rootRow);
                $monthRowsByRoot->put(
                    (int) $rootRow->id,
                    collect($reportRows)
                        ->filter(function (KpiMonthScore $row): bool {
                            $monthNo = (int) ($row->month_no ?? 0);
                            return $monthNo >= 1 && $monthNo <= 12;
                        })
                        ->sortByDesc(fn (KpiMonthScore $row): int => (int) ($row->id ?? 0))
                        ->values()
                );
            }
        }

        $rootRows = $rootRows
            ->sortBy(function (KpiMonthScore $row): string {
                return sprintf(
                    '%010d-%010d',
                    (int) ($row->app_user_id ?? 0),
                    (int) ($row->id ?? 0)
                );
            })
            ->values();

        return [
            'root_rows' => $rootRows,
            'root_rows_by_user' => $rootRows->groupBy('app_user_id'),
            'month_rows_by_root' => $monthRowsByRoot,
        ];
    }

    private function resolveReportKeyForExport(KpiMonthScore $row): int
    {
        $metaId = (int) ($row->kpi_meta_id ?? 0);
        if ($metaId > 0) {
            return $metaId;
        }

        return (int) ($row->id ?? 0);
    }

    /**
     * @param array<int, KpiMonthScore> $reportRows
     */
    private function resolveReportRootRowForExport(array $reportRows): ?KpiMonthScore
    {
        if (count($reportRows) < 1) {
            return null;
        }

        $rows = collect($reportRows);

        $selfRoot = $rows->first(function (KpiMonthScore $row): bool {
            return (int) ($row->kpi_meta_id ?? 0) > 0
                && (int) ($row->kpi_meta_id ?? 0) === (int) ($row->id ?? 0);
        });
        if ($selfRoot instanceof KpiMonthScore) {
            return $selfRoot;
        }

        $draftRoot = $rows->first(function (KpiMonthScore $row): bool {
            return (int) ($row->month_no ?? 0) === 0
                && ($row->kpi_meta_id === null || (int) ($row->kpi_meta_id ?? 0) === 0);
        });
        if ($draftRoot instanceof KpiMonthScore) {
            return $draftRoot;
        }

        $fallback = $rows
            ->sortBy(fn (KpiMonthScore $row): int => (int) ($row->id ?? 0))
            ->first();

        return $fallback instanceof KpiMonthScore ? $fallback : null;
    }

    /**
     * @param iterable<int, mixed> $users
     * @param iterable<int, mixed> $rootRows
     * @param mixed $monthRowsByRoot
     * @param array<int, int> $selectedMonths
     * @return array{
     *     root_result_by_id: array<int, float|null>,
     *     kpi_result_by_user: array<int, float>,
     *     okr_result_by_department: array<string, float>,
     *     okr_all_result: float|null
     * }
     */
    private function calculateScopedMetrics(
        iterable $users,
        iterable $rootRows,
        mixed $monthRowsByRoot,
        array $selectedMonths
    ): array {
        $rootResultById = [];
        $itemResultsByUser = [];

        foreach ($rootRows as $rootRow) {
            $rootId = (int) ($rootRow->id ?? 0);
            $userId = (int) ($rootRow->app_user_id ?? 0);
            if ($rootId < 1 || $userId < 1) {
                continue;
            }

            $rows = $monthRowsByRoot->get($rootId, collect());
            $monthByNo = $this->indexMonthRowsByNo($rows);
            $pointTotal = 0.0;
            $scoredMonthCount = 0;
            $targetValue = $rootRow->target_value !== null ? (float) $rootRow->target_value : null;
            $operator = trim((string) ($rootRow->criteria_operator ?? ''));

            foreach ($selectedMonths as $monthNo) {
                $monthRow = $monthByNo[$monthNo] ?? null;
                if (! $monthRow || $monthRow->score_value === null) {
                    continue;
                }

                $scoredMonthCount++;
                $isPass = $monthRow->is_pass !== null
                    ? (bool) $monthRow->is_pass
                    : (
                        $targetValue !== null
                        && $operator !== ''
                        && $this->evaluateScore((float) $monthRow->score_value, $targetValue, $operator)
                    );

                if ($isPass) {
                    $pointTotal += 100.0;
                }
            }

            // Formula required by business:
            // item KPI average = (sum of selected-month points where is_pass=1 => 100, else 0) / selected months that have score
            $result = $scoredMonthCount > 0 ? round($pointTotal / $scoredMonthCount, 2) : null;
            $rootResultById[$rootId] = $result;
            if ($result !== null) {
                $itemResultsByUser[$userId] ??= [];
                $itemResultsByUser[$userId][] = $result;
            }
        }

        $kpiResultByUser = [];
        foreach ($itemResultsByUser as $userId => $values) {
            $reportCount = count($values);
            if ($reportCount < 1) {
                continue;
            }

            $total = 0.0;
            foreach ($values as $value) {
                $total += $value !== null ? (float) $value : 0.0;
            }

            // Personal KPI average = total KPI average result values / number of user reports.
            $kpiResultByUser[(int) $userId] = round($total / $reportCount, 2);
        }

        $okrResultByDepartment = $this->calculateDepartmentAverageFromUserKpi($users, $kpiResultByUser);
        $okrAllResult = $this->calculateOverallOkrFromDepartment($okrResultByDepartment);

        return [
            'root_result_by_id' => $rootResultById,
            'kpi_result_by_user' => $kpiResultByUser,
            'okr_result_by_department' => $okrResultByDepartment,
            'okr_all_result' => $okrAllResult,
        ];
    }

    /**
     * @param mixed $rootRowsByUser
     * @param array<int, float|null>|null $rootResultById
     * @return array<int, float>
     */
    private function calculateUserKpiAverageFromRootRows(
        mixed $rootRowsByUser,
        ?array $rootResultById = null
    ): array
    {
        $resultByUser = [];

        foreach ($rootRowsByUser as $userId => $rows) {
            $total = 0.0;
            $reportCount = 0;
            foreach ($rows as $row) {
                $rootId = (int) ($row->id ?? 0);
                if ($rootId < 1) {
                    continue;
                }

                $resolvedRootResult = is_array($rootResultById)
                    ? ($rootResultById[$rootId] ?? null)
                    : $row->result;
                if ($resolvedRootResult === null) {
                    continue;
                }

                $total += (float) $resolvedRootResult;
                $reportCount++;
            }

            if ($reportCount < 1) {
                continue;
            }

            // Personal KPI average = total KPI average result values / number of user reports.
            $resultByUser[(int) $userId] = round($total / $reportCount, 2);
        }

        return $resultByUser;
    }

    /**
     * @param mixed $rootRowsByUser
     * @param mixed $usersById
     * @param array<int, float|null>|null $rootResultById
     * @return array<string, float>
     */
    private function calculateUserDepartmentKpiAverageFromRootRows(
        mixed $rootRowsByUser,
        mixed $usersById,
        ?array $rootResultById = null
    ): array {
        $scoresByUserDepartment = [];

        foreach ($rootRowsByUser as $userId => $rows) {
            $resolvedUserId = (int) $userId;
            if ($resolvedUserId < 1) {
                continue;
            }

            $user = is_object($usersById) && method_exists($usersById, 'get')
                ? $usersById->get($resolvedUserId)
                : null;
            if (! $user instanceof AppUser) {
                continue;
            }

            foreach ($rows as $row) {
                $rootId = (int) ($row->id ?? 0);
                if ($rootId < 1) {
                    continue;
                }

                $resolvedRootResult = is_array($rootResultById)
                    ? ($rootResultById[$rootId] ?? null)
                    : $row->result;
                if ($resolvedRootResult === null) {
                    continue;
                }

                $groupDepartmentCode = $this->resolveGroupDepartmentCodeForExport($user, $row);
                if ($groupDepartmentCode === '') {
                    $groupDepartmentCode = $this->normalizeDepartmentCode((string) ($user->dept_abbr_hr ?? ''));
                }
                if ($groupDepartmentCode === '') {
                    continue;
                }

                $mapKey = $resolvedUserId.'|'.$groupDepartmentCode;
                $scoresByUserDepartment[$mapKey] ??= [];
                $scoresByUserDepartment[$mapKey][] = (float) $resolvedRootResult;
            }
        }

        $resultByUserDepartment = [];
        foreach ($scoresByUserDepartment as $mapKey => $scores) {
            if ($scores === []) {
                continue;
            }

            $resultByUserDepartment[$mapKey] = round(array_sum($scores) / count($scores), 2);
        }

        return $resultByUserDepartment;
    }

    /**
     * @param array<string, float> $kpiResultByUserAndDepartment
     * @return array<string, float>
     */
    private function calculateDepartmentAverageFromUserDepartmentKpi(array $kpiResultByUserAndDepartment): array
    {
        $departmentScores = [];

        foreach ($kpiResultByUserAndDepartment as $mapKey => $score) {
            if (! is_numeric($score)) {
                continue;
            }

            $parts = explode('|', (string) $mapKey, 2);
            $departmentCode = $this->normalizeDepartmentCode($parts[1] ?? '');
            if ($departmentCode === '') {
                continue;
            }

            $departmentScores[$departmentCode] ??= [];
            $departmentScores[$departmentCode][] = (float) $score;
        }

        $resultByDepartment = [];
        foreach ($departmentScores as $departmentCode => $scores) {
            if ($scores === []) {
                continue;
            }

            $resultByDepartment[$departmentCode] = round(array_sum($scores) / count($scores), 2);
        }

        return $resultByDepartment;
    }

    /**
     * @param iterable<int, mixed> $users
     * @param array<int, float> $kpiResultByUser
     * @return array<string, float>
     */
    private function calculateDepartmentAverageFromUserKpi(iterable $users, array $kpiResultByUser): array
    {
        $departmentScores = [];

        foreach ($users as $user) {
            $userId = (int) ($user->id ?? 0);
            if (! array_key_exists($userId, $kpiResultByUser)) {
                continue;
            }

            $departmentCode = strtoupper(trim((string) ($user->dept_abbr_hr ?? '')));
            if ($departmentCode === '') {
                continue;
            }

            $departmentScores[$departmentCode] ??= [];
            $departmentScores[$departmentCode][] = (float) $kpiResultByUser[$userId];
        }

        $resultByDepartment = [];
        foreach ($departmentScores as $departmentCode => $scores) {
            if ($scores === []) {
                continue;
            }

            // OKR department = average of personal KPI averages in the same department.
            $resultByDepartment[$departmentCode] = round(array_sum($scores) / count($scores), 2);
        }

        return $resultByDepartment;
    }

    /**
     * @param array<string, float> $okrResultByDepartment
     */
    private function calculateOverallOkrFromDepartment(array $okrResultByDepartment): ?float
    {
        if ($okrResultByDepartment === []) {
            return null;
        }

        return round(array_sum($okrResultByDepartment) / count($okrResultByDepartment), 2);
    }

    /**
     * @param iterable<int, mixed> $monthRows
     * @return array<int, mixed>
     */
    private function indexMonthRowsByNo(iterable $monthRows): array
    {
        $monthByNo = [];

        foreach ($monthRows as $monthRow) {
            $monthNo = (int) ($monthRow->month_no ?? 0);
            if ($monthNo < 1 || $monthNo > 12 || array_key_exists($monthNo, $monthByNo)) {
                continue;
            }

            $monthByNo[$monthNo] = $monthRow;
        }

        return $monthByNo;
    }

    /**
     * @param iterable<int, mixed> $monthRows
     * @param array<int, int> $selectedMonths
     */
    private function formatSelectedMonthScoreLines(
        iterable $monthRows,
        array $selectedMonths,
        ?float $targetValue,
        string $operator
    ): string {
        $monthByNo = $this->indexMonthRowsByNo($monthRows);
        $lines = [];
        $enteredScores = [];

        foreach ($selectedMonths as $monthNo) {
            $monthLabel = $this->shortMonthLabel($monthNo);
            $monthRow = $monthByNo[$monthNo] ?? null;

            if (! $monthRow || $monthRow->score_value === null) {
                $lines[] = $monthLabel.': -';
                continue;
            }

            $scoreValue = (float) $monthRow->score_value;
            $scoreText = $this->formatNumber($scoreValue);
            $enteredScores[] = $scoreValue;

            if ($targetValue !== null && $operator !== '') {
                $targetText = $this->formatNumber($targetValue);
                $pass = $monthRow->is_pass !== null
                    ? (bool) $monthRow->is_pass
                    : $this->evaluateScore($scoreValue, $targetValue, $operator);
                $passText = $pass ? '100%' : '0%';
                $lines[] = $monthLabel.': '.$scoreText.' '.$this->displayOperator($operator).' '.$targetText.' = '.$passText;
                continue;
            }

            $lines[] = $monthLabel.': '.$scoreText;
        }

        $averageScoreText = '-';
        if ($enteredScores !== []) {
            $averageScoreText = $this->formatNumber(array_sum($enteredScores) / count($enteredScores));
        }
        $lines[] = 'Average result score = '.$averageScoreText;

        return $lines === [] ? '-' : implode(' | ', $lines);
    }

    private function formatMonthlyScoreLines(iterable $monthRows, ?float $targetValue, string $operator): string
    {
        $monthByNo = $this->indexMonthRowsByNo($monthRows);
        ksort($monthByNo);

        if ($monthByNo === []) {
            return '-';
        }

        $lines = [];
        $enteredScores = [];
        foreach ($monthByNo as $monthNo => $monthRow) {
            $monthLabel = $this->shortMonthLabel($monthNo);
            if ($monthRow->score_value === null) {
                $lines[] = $monthLabel.': -';
                continue;
            }

            $scoreValue = (float) $monthRow->score_value;
            $scoreText = $this->formatNumber($scoreValue);
            $enteredScores[] = $scoreValue;

            if ($targetValue !== null && $operator !== '') {
                $targetText = $this->formatNumber($targetValue);
                $pass = $monthRow->is_pass !== null
                    ? (bool) $monthRow->is_pass
                    : $this->evaluateScore($scoreValue, $targetValue, $operator);
                $passText = $pass ? '100%' : '0%';
                $lines[] = $monthLabel.': '.$scoreText.' '.$this->displayOperator($operator).' '.$targetText.' = '.$passText;
                continue;
            }

            $lines[] = $monthLabel.': '.$scoreText;
        }

        $averageScoreText = '-';
        if ($enteredScores !== []) {
            $averageScoreText = $this->formatNumber(array_sum($enteredScores) / count($enteredScores));
        }
        $lines[] = 'Average result score = '.$averageScoreText;

        return implode(' | ', $lines);
    }

    private function formatPercent(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, 2).'%';
    }

    private function formatNumber(float $value): string
    {
        $formatted = number_format($value, 10, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');
        return $formatted === '' ? '0' : $formatted;
    }

    private function shortMonthLabel(int $monthNo): string
    {
        $labels = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];

        return $labels[$monthNo] ?? ('M'.$monthNo);
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

    private function displayOperator(string $operator): string
    {
        return match ($operator) {
            '>=' => "\u{2265}",
            default => $operator,
        };
    }

    private function columnsXml(): string
    {
        $widthByColumn = [
            1  => 16,
            2  => 24,
            3  => 24,
            4  => 14,
            5  => 18,
            6  => 20,
            7  => 20,
            8  => 20,
            9  => 22,
            10 => 18,
            11 => 30,
            12 => 26,
            13 => 28,
            14 => 18,
            15 => 16,
            16 => 64,
            17 => 22,
            18 => 22,
            19 => 22,
            20 => 24,
        ];

        $columns = [];
        foreach ($widthByColumn as $columnNo => $width) {
            $columns[] = '<col min="'.$columnNo.'" max="'.$columnNo.'" width="'.$width.'" customWidth="1"/>';
        }

        return '<cols>'.implode('', $columns).'</cols>';
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function escapeXml(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $value);
        return htmlspecialchars($normalized, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}

