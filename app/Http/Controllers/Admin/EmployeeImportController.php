<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppUserAuthController;
use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Support\PlainTextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class EmployeeImportController extends Controller
{
    private const IMPORT_MODE_APPEND = 'append';
    private const IMPORT_MODE_REPLACE = 'replace';
    private const UPSERT_CHUNK_SIZE = 500;

    /**
     * @var array<int, string>
     */
    private const ORDERED_FIELDS = [
        'employee_code',
        'full_name_th',
        'full_name_en',
        'employee_type',
        'position',
        'department',
        'dept_abbr_hr',
        'dept_abbr_qms',
        'id_thai_hash',
    ];

    /**
     * @var array<string, array{th:string,en:string}>
     */
    private const FIELD_LABELS = [
        'employee_code' => [
            'th' => "\u{E23}\u{E2B}\u{E31}\u{E2A}\u{E1E}\u{E19}\u{E31}\u{E01}\u{E07}\u{E32}\u{E19}",
            'en' => 'Employee Code',
        ],
        'full_name_th' => [
            'th' => "\u{E0A}\u{E37}\u{E48}\u{E2D}-\u{E2A}\u{E01}\u{E38}\u{E25}(\u{E44}\u{E17}\u{E22})",
            'en' => 'Full Name (TH)',
        ],
        'full_name_en' => [
            'th' => "\u{E0A}\u{E37}\u{E48}\u{E2D}-\u{E2A}\u{E01}\u{E38}\u{E25}(Eng)",
            'en' => 'Full Name (EN)',
        ],
        'employee_type' => [
            'th' => "\u{E1B}\u{E23}\u{E30}\u{E40}\u{E20}\u{E17}",
            'en' => 'Employee Type',
        ],
        'position' => [
            'th' => "\u{E15}\u{E33}\u{E41}\u{E2B}\u{E19}\u{E48}\u{E07}",
            'en' => 'Position',
        ],
        'department' => [
            'th' => "\u{E41}\u{E1C}\u{E19}\u{E01}/\u{E1D}\u{E48}\u{E32}\u{E22}",
            'en' => 'Department/Division',
        ],
        'dept_abbr_hr' => [
            'th' => "\u{E15}\u{E31}\u{E27}\u{E22}\u{E48}\u{E2D}\u{E2A}\u{E48}\u{E27}\u{E19}\u{E07}\u{E32}\u{E19} (\u{E43}\u{E0A}\u{E49}\u{E43}\u{E19} HR)",
            'en' => 'Work Unit Abbreviation (HR)',
        ],
        'dept_abbr_qms' => [
            'th' => "\u{E15}\u{E31}\u{E27}\u{E22}\u{E48}\u{E2D}\u{E41}\u{E1C}\u{E19}\u{E01} (\u{E43}\u{E0A}\u{E49}\u{E43}\u{E19} QMS)",
            'en' => 'Department Abbreviation (QMS)',
        ],
        'id_thai_hash' => [
            'th' => "\u{E40}\u{E25}\u{E02}\u{E17}\u{E35}\u{E48}\u{E1B}\u{E23}\u{E30}\u{E01}\u{E31}\u{E19}\u{E2A}\u{E31}\u{E07}\u{E04}\u{E21}",
            'en' => 'Social Security Number',
        ],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const HEADER_ALIASES = [
        'employee_code' => [
            "\u{E23}\u{E2B}\u{E31}\u{E2A}\u{E1E}\u{E19}\u{E31}\u{E01}\u{E07}\u{E32}\u{E19}",
            'employee code',
            'employeecode',
            'employee id',
            'employeeid',
        ],
        'full_name_th' => [
            "\u{E0A}\u{E37}\u{E48}\u{E2D}-\u{E2A}\u{E01}\u{E38}\u{E25}(\u{E44}\u{E17}\u{E22})",
            'full name th',
            'fullname(th)',
            'fullnameth',
        ],
        'full_name_en' => [
            "\u{E0A}\u{E37}\u{E48}\u{E2D}-\u{E2A}\u{E01}\u{E38}\u{E25}(eng)",
            'full name en',
            'fullname(en)',
            'full name eng',
            'fullnameeng',
        ],
        'employee_type' => [
            "\u{E1B}\u{E23}\u{E30}\u{E40}\u{E20}\u{E17}",
            'employee type',
            'employeetype',
            'type',
        ],
        'position' => [
            "\u{E15}\u{E33}\u{E41}\u{E2B}\u{E19}\u{E48}\u{E07}",
            'position',
            'job title',
            'jobtitle',
        ],
        'department' => [
            "\u{E41}\u{E1C}\u{E19}\u{E01}/\u{E1D}\u{E48}\u{E32}\u{E22}",
            'department',
            'division',
            'dept',
        ],
        'dept_abbr_hr' => [
            "\u{E15}\u{E31}\u{E27}\u{E22}\u{E48}\u{E2D}\u{E2A}\u{E48}\u{E27}\u{E19}\u{E07}\u{E32}\u{E19} (\u{E43}\u{E0A}\u{E49}\u{E43}\u{E19} hr)",
            "\u{E15}\u{E31}\u{E27}\u{E22}\u{E48}\u{E2D}\u{E2A}\u{E48}\u{E27}\u{E19}\u{E07}\u{E32}\u{E19}(\u{E43}\u{E0A}\u{E49}\u{E43}\u{E19} hr)",
            "\u{E15}\u{E31}\u{E27}\u{E22}\u{E48}\u{E2D}\u{E2A}\u{E48}\u{E27}\u{E19}\u{E07}\u{E32}\u{E19}",
            'abbr hr',
            'work unit abbr hr',
            'hr abbr',
        ],
        'dept_abbr_qms' => [
            "\u{E15}\u{E31}\u{E27}\u{E22}\u{E48}\u{E2D}\u{E41}\u{E1C}\u{E19}\u{E01} (\u{E43}\u{E0A}\u{E49}\u{E43}\u{E19} qms)",
            'abbr qms',
            'qms abbr',
        ],
        'id_thai_hash' => [
            "\u{E40}\u{E25}\u{E02}\u{E17}\u{E35}\u{E48}\u{E1B}\u{E23}\u{E30}\u{E01}\u{E31}\u{E19}\u{E2A}\u{E31}\u{E07}\u{E04}\u{E21}",
            'social security number',
            'social security no',
            'id thai hash',
        ],
    ];

    private const TH_FILE_REQUIRED = "\u{E01}\u{E23}\u{E38}\u{E13}\u{E32}\u{E40}\u{E25}\u{E37}\u{E2D}\u{E01}\u{E44}\u{E1F}\u{E25}\u{E4C} Excel \u{E01}\u{E48}\u{E2D}\u{E19}\u{E19}\u{E33}\u{E40}\u{E02}\u{E49}\u{E32}";
    private const TH_FILE_INVALID = "\u{E44}\u{E1F}\u{E25}\u{E4C}\u{E17}\u{E35}\u{E48}\u{E2D}\u{E31}\u{E1B}\u{E42}\u{E2B}\u{E25}\u{E14}\u{E44}\u{E21}\u{E48}\u{E16}\u{E39}\u{E01}\u{E15}\u{E49}\u{E2D}\u{E07}";
    private const TH_FILE_MIME = "\u{E23}\u{E2D}\u{E07}\u{E23}\u{E31}\u{E1A}\u{E40}\u{E09}\u{E1E}\u{E32}\u{E30}\u{E44}\u{E1F}\u{E25}\u{E4C} .xlsx, .xls, .csv";
    private const TH_MODE_INVALID = "\u{E42}\u{E2B}\u{E21}\u{E14}\u{E19}\u{E33}\u{E40}\u{E02}\u{E49}\u{E32}\u{E44}\u{E21}\u{E48}\u{E16}\u{E39}\u{E01}\u{E15}\u{E49}\u{E2D}\u{E07}";
    private const TH_FILE_READ_FAIL = "\u{E44}\u{E21}\u{E48}\u{E2A}\u{E32}\u{E21}\u{E32}\u{E23}\u{E16}\u{E2D}\u{E48}\u{E32}\u{E19}\u{E44}\u{E1F}\u{E25}\u{E4C}\u{E17}\u{E35}\u{E48}\u{E2D}\u{E31}\u{E1B}\u{E42}\u{E2B}\u{E25}\u{E14}\u{E44}\u{E14}\u{E49}";
    private const TH_IMPORT_ERROR = "\u{E40}\u{E01}\u{E34}\u{E14}\u{E02}\u{E49}\u{E2D}\u{E1C}\u{E34}\u{E14}\u{E1E}\u{E25}\u{E32}\u{E14}\u{E02}\u{E13}\u{E30}\u{E19}\u{E33}\u{E40}\u{E02}\u{E49}\u{E32}\u{E44}\u{E1F}\u{E25}\u{E4C} \u{E01}\u{E23}\u{E38}\u{E13}\u{E32}\u{E25}\u{E2D}\u{E07}\u{E43}\u{E2B}\u{E21}\u{E48}\u{E2D}\u{E35}\u{E01}\u{E04}\u{E23}\u{E31}\u{E49}\u{E07}";
    private const TH_NO_ROWS = "\u{E44}\u{E21}\u{E48}\u{E1E}\u{E1A}\u{E02}\u{E49}\u{E2D}\u{E21}\u{E39}\u{E25}\u{E1E}\u{E19}\u{E31}\u{E01}\u{E07}\u{E32}\u{E19}\u{E17}\u{E35}\u{E48}\u{E19}\u{E33}\u{E40}\u{E02}\u{E49}\u{E32}\u{E44}\u{E14}\u{E49}\u{E43}\u{E19}\u{E44}\u{E1F}\u{E25}\u{E4C}";
    private const TH_SAVE_FAIL = "\u{E44}\u{E21}\u{E48}\u{E2A}\u{E32}\u{E21}\u{E32}\u{E23}\u{E16}\u{E1A}\u{E31}\u{E19}\u{E17}\u{E36}\u{E01}\u{E02}\u{E49}\u{E2D}\u{E21}\u{E39}\u{E25}\u{E19}\u{E33}\u{E40}\u{E02}\u{E49}\u{E32}\u{E44}\u{E14}\u{E49} \u{E01}\u{E23}\u{E38}\u{E13}\u{E32}\u{E25}\u{E2D}\u{E07}\u{E43}\u{E2B}\u{E21}\u{E48}\u{E2D}\u{E35}\u{E01}\u{E04}\u{E23}\u{E31}\u{E49}\u{E07}";
    private const TH_OPEN_EXCEL_FAIL = "\u{E44}\u{E21}\u{E48}\u{E2A}\u{E32}\u{E21}\u{E32}\u{E23}\u{E16}\u{E40}\u{E1B}\u{E34}\u{E14}\u{E44}\u{E1F}\u{E25}\u{E4C} Excel \u{E44}\u{E14}\u{E49} \u{E01}\u{E23}\u{E38}\u{E13}\u{E32}\u{E15}\u{E23}\u{E27}\u{E08}\u{E2A}\u{E2D}\u{E1A}\u{E44}\u{E1F}\u{E25}\u{E4C}\u{E2D}\u{E35}\u{E01}\u{E04}\u{E23}\u{E31}\u{E49}\u{E07}";
    private const TH_NO_DATA_IN_FILE = "\u{E44}\u{E21}\u{E48}\u{E1E}\u{E1A}\u{E02}\u{E49}\u{E2D}\u{E21}\u{E39}\u{E25}\u{E43}\u{E19}\u{E44}\u{E1F}\u{E25}\u{E4C} Excel";
    private const TH_MISSING_CODE_PREFIX = "\u{E1E}\u{E1A}\u{E41}\u{E16}\u{E27}\u{E17}\u{E35}\u{E48}\u{E44}\u{E21}\u{E48}\u{E21}\u{E35}\u{E23}\u{E2B}\u{E31}\u{E2A}\u{E1E}\u{E19}\u{E31}\u{E01}\u{E07}\u{E32}\u{E19}\u{E43}\u{E19}\u{E44}\u{E1F}\u{E25}\u{E4C} Excel (\u{E41}\u{E16}\u{E27} ";
    private const TH_MISSING_COLUMN_PREFIX = "\u{E44}\u{E21}\u{E48}\u{E1E}\u{E1A}\u{E04}\u{E2D}\u{E25}\u{E31}\u{E21}\u{E19}\u{E4C}\u{E17}\u{E35}\u{E48}\u{E08}\u{E33}\u{E40}\u{E1B}\u{E47}\u{E19}\u{E43}\u{E19}\u{E44}\u{E1F}\u{E25}\u{E4C} Excel: ";
    private const TH_SUCCESS = "\u{E19}\u{E33}\u{E40}\u{E02}\u{E49}\u{E32}\u{E23}\u{E32}\u{E22}\u{E0A}\u{E37}\u{E48}\u{E2D}\u{E1E}\u{E19}\u{E31}\u{E01}\u{E07}\u{E32}\u{E19}\u{E2A}\u{E33}\u{E40}\u{E23}\u{E47}\u{E08}";
    private const TH_ADD_PREFIX = "\u{E40}\u{E1E}\u{E34}\u{E48}\u{E21}\u{E43}\u{E2B}\u{E21}\u{E48} ";
    private const TH_UPDATE_PREFIX = "\u{E2D}\u{E31}\u{E1B}\u{E40}\u{E14}\u{E15} ";
    private const TH_RESTORE_PREFIX = "\u{E01}\u{E39}\u{E49}\u{E04}\u{E37}\u{E19}\u{E1C}\u{E39}\u{E49}\u{E43}\u{E0A}\u{E49}\u{E40}\u{E14}\u{E34}\u{E21} ";
    private const TH_DEACTIVATE_PREFIX = "\u{E1B}\u{E34}\u{E14}\u{E01}\u{E32}\u{E23}\u{E43}\u{E0A}\u{E49}\u{E07}\u{E32}\u{E19}\u{E02}\u{E49}\u{E2D}\u{E21}\u{E39}\u{E25}\u{E40}\u{E14}\u{E34}\u{E21} ";
    private const TH_SKIP_ADMIN_PREFIX = "\u{E02}\u{E49}\u{E32}\u{E21}\u{E1A}\u{E31}\u{E0D}\u{E0A}\u{E35}\u{E41}\u{E2D}\u{E14}\u{E21}\u{E34}\u{E19} ";
    private const TH_ITEM_SUFFIX = " \u{E23}\u{E32}\u{E22}\u{E01}\u{E32}\u{E23}";

    public function index(Request $request)
    {
        $lang = $this->resolveLangFromRequest($request);

        return view('admin-employee-import', [
            'lang' => $lang,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $lang = $this->resolveLangFromRequest($request);

        $request->validate(
            [
                'employee_excel' => ['required', 'file', 'mimes:xlsx,xls,csv'],
                'import_mode' => ['nullable', 'in:'.self::IMPORT_MODE_APPEND.','.self::IMPORT_MODE_REPLACE],
            ],
            [
                'employee_excel.required' => $lang === 'th'
                    ? self::TH_FILE_REQUIRED
                    : 'Please choose an Excel file before importing.',
                'employee_excel.file' => $lang === 'th'
                    ? self::TH_FILE_INVALID
                    : 'The uploaded file is invalid.',
                'employee_excel.mimes' => $lang === 'th'
                    ? self::TH_FILE_MIME
                    : 'Only .xlsx, .xls, and .csv files are supported.',
                'import_mode.in' => $lang === 'th'
                    ? self::TH_MODE_INVALID
                    : 'Invalid import mode.',
            ]
        );

        $mode = (string) $request->input('import_mode', self::IMPORT_MODE_APPEND);
        if (! in_array($mode, [self::IMPORT_MODE_APPEND, self::IMPORT_MODE_REPLACE], true)) {
            $mode = self::IMPORT_MODE_APPEND;
        }

        $file = $request->file('employee_excel');
        $filePath = $file ? $file->getRealPath() : null;
        if (! is_string($filePath) || $filePath === '') {
            return back()->withInput()->withErrors([
                'employee_excel' => $lang === 'th'
                    ? self::TH_FILE_READ_FAIL
                    : 'Unable to read the uploaded file.',
            ]);
        }

        try {
            $rows = $this->parseRowsFromExcel($filePath, $lang);
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['employee_excel' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'employee_excel' => $lang === 'th'
                    ? self::TH_IMPORT_ERROR
                    : 'An unexpected error occurred while importing. Please try again.',
            ]);
        }

        if ($rows === []) {
            return back()->withInput()->withErrors([
                'employee_excel' => $lang === 'th'
                    ? self::TH_NO_ROWS
                    : 'No importable employee rows were found in the file.',
            ]);
        }

        try {
            $summary = DB::transaction(
                fn (): array => $this->syncRowsToAppUsers($rows, $mode),
                3
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'employee_excel' => $lang === 'th'
                    ? self::TH_SAVE_FAIL
                    : 'Unable to save imported records. Please try again.',
            ]);
        }

        return redirect()
            ->route('admin.employees.import', ['lang' => $lang])
            ->with('status', $this->buildStatusMessage($summary, $mode, $lang));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseRowsFromExcel(string $filePath, string $lang): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                $lang === 'th'
                    ? self::TH_OPEN_EXCEL_FAIL
                    : 'Unable to open the Excel file. Please verify the file and try again.',
                0,
                $exception
            );
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if ($rows === [] || ! isset($rows[0]) || ! is_array($rows[0])) {
            throw new RuntimeException(
                $lang === 'th' ? self::TH_NO_DATA_IN_FILE : 'No data found in the Excel file.'
            );
        }

        $headerRow = array_map(
            fn ($value): string => $this->stringifyCellValue($value),
            $rows[0]
        );

        $fieldIndexMap = $this->resolveFieldIndexMap($headerRow, $lang);

        $normalizedRows = [];
        $missingCodeRows = [];

        for ($rowIndex = 1; $rowIndex < count($rows); $rowIndex++) {
            $rawRow = isset($rows[$rowIndex]) && is_array($rows[$rowIndex]) ? $rows[$rowIndex] : [];
            $record = [];

            foreach (self::ORDERED_FIELDS as $field) {
                $columnIndex = $fieldIndexMap[$field] ?? -1;
                $record[$field] = $columnIndex >= 0
                    ? $this->stringifyCellValue($rawRow[$columnIndex] ?? null)
                    : '';
            }

            if ($this->isRowEmpty($record)) {
                continue;
            }

            $record['employee_code'] = $this->normalizeEmployeeCode($record['employee_code']);
            if ($record['employee_code'] === '') {
                $missingCodeRows[] = $rowIndex + 1; // include Excel header row
                continue;
            }

            $normalizedRows[] = $record;
        }

        if ($missingCodeRows !== []) {
            $previewRows = implode(', ', array_slice($missingCodeRows, 0, 10));
            $suffix = count($missingCodeRows) > 10 ? ' ...' : '';

            throw new RuntimeException(
                $lang === 'th'
                    ? self::TH_MISSING_CODE_PREFIX.$previewRows.$suffix.')'
                    : 'Some rows have no employee code in Excel (rows '.$previewRows.$suffix.').'
            );
        }

        $deduplicated = [];
        foreach ($normalizedRows as $row) {
            $deduplicated[strtolower((string) $row['employee_code'])] = $row;
        }

        return array_values($deduplicated);
    }

    /**
     * @param array<int, string> $headerRow
     * @return array<string, int>
     */
    private function resolveFieldIndexMap(array $headerRow, string $lang): array
    {
        $normalizedHeaders = [];
        foreach ($headerRow as $index => $headerText) {
            $headerKey = $this->normalizeHeaderKey($headerText);
            if ($headerKey !== '') {
                $normalizedHeaders[(int) $index] = $headerKey;
            }
        }

        $fieldIndexMap = [];
        $missingColumns = [];

        foreach (self::ORDERED_FIELDS as $field) {
            $headerIndex = $this->findHeaderIndex($normalizedHeaders, self::HEADER_ALIASES[$field] ?? []);
            if ($headerIndex === null) {
                $missingColumns[] = self::FIELD_LABELS[$field][$lang === 'th' ? 'th' : 'en'];
                continue;
            }

            $fieldIndexMap[$field] = $headerIndex;
        }

        // Fallback for mojibake CSV/Excel headers: if no field can be matched at all,
        // assume file follows the fixed column order from HR template.
        if ($fieldIndexMap === [] && count($headerRow) >= count(self::ORDERED_FIELDS)) {
            return array_combine(
                self::ORDERED_FIELDS,
                range(0, count(self::ORDERED_FIELDS) - 1)
            ) ?: [];
        }

        if ($missingColumns !== []) {
            $missingList = implode(', ', $missingColumns);
            throw new RuntimeException(
                $lang === 'th'
                    ? self::TH_MISSING_COLUMN_PREFIX.$missingList
                    : 'Required columns are missing in the Excel file: '.$missingList
            );
        }

        return $fieldIndexMap;
    }

    /**
     * @param array<int, string> $normalizedHeaders
     * @param array<int, string> $aliases
     */
    private function findHeaderIndex(array $normalizedHeaders, array $aliases): ?int
    {
        $normalizedAliases = [];
        foreach ($aliases as $alias) {
            $normalizedAlias = $this->normalizeHeaderKey($alias);
            if ($normalizedAlias !== '') {
                $normalizedAliases[] = $normalizedAlias;
            }
        }

        foreach ($normalizedHeaders as $index => $headerKey) {
            if (in_array($headerKey, $normalizedAliases, true)) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @return array{created:int,updated:int,restored:int,deactivated:int,skipped_admin:int}
     */
    private function syncRowsToAppUsers(array $rows, string $mode): array
    {
        $now = Carbon::now();
        $codesInFile = array_values(array_unique(array_map(
            fn (array $row): string => (string) $row['employee_code'],
            $rows
        )));

        $existingUsers = AppUser::withTrashed()
            ->whereIn('employee_code', $codesInFile)
            ->get()
            ->keyBy(fn (AppUser $user): string => strtolower(trim((string) $user->employee_code)));

        $summary = [
            'created' => 0,
            'updated' => 0,
            'restored' => 0,
            'deactivated' => 0,
            'skipped_admin' => 0,
        ];

        $keptCodes = [];
        $upsertRows = [];

        foreach ($rows as $row) {
            $employeeCode = (string) $row['employee_code'];
            $employeeCodeKey = strtolower($employeeCode);
            $idThaiHash = $this->nullIfBlank($row['id_thai_hash'] ?? null);

            /** @var AppUser|null $user */
            $user = $existingUsers->get($employeeCodeKey);
            if ($employeeCodeKey === 'admin' || ($user && $this->isAdminUser($user))) {
                $summary['skipped_admin']++;
                continue;
            }

            $isNewUser = $user === null;
            $registeredAt = $isNewUser
                ? $now->copy()
                : ($user->registered_at ?? $now->copy());
            $isRegistered = $isNewUser
                ? true
                : ($user->is_registered === null ? true : (bool) $user->is_registered);
            $role = $isNewUser
                ? 'user'
                : (AppUserAuthController::isAdminRole($user->role) ? 'admin' : ((string) ($user->role ?: 'user')));

            if (! $user) {
                $summary['created']++;
            } else {
                $summary['updated']++;
                if ($user->trashed()) {
                    $summary['restored']++;
                }
            }

            $upsertRows[] = [
                'employee_code' => $employeeCode,
                // Intentionally store plain text by request.
                'password' => $idThaiHash,
                'full_name_th' => $this->nullIfBlank($row['full_name_th'] ?? null),
                'full_name_en' => $this->nullIfBlank($row['full_name_en'] ?? null),
                'employee_type' => $this->nullIfBlank($row['employee_type'] ?? null),
                'position' => $this->nullIfBlank($row['position'] ?? null),
                'department' => $this->nullIfBlank($row['department'] ?? null),
                'dept_abbr_hr' => $this->nullIfBlank($row['dept_abbr_hr'] ?? null),
                'dept_abbr_qms' => $this->nullIfBlank($row['dept_abbr_qms'] ?? null),
                // Excel currently does not provide email column; keep null for future use.
                'email' => null,
                'id_thai_hash' => $idThaiHash,
                'role' => $role,
                'is_registered' => $isRegistered,
                'registered_at' => $registeredAt,
                'deleted_at' => null,
                'created_at' => $user?->created_at ?? $now,
                'updated_at' => $now,
            ];
            $keptCodes[] = $employeeCode;
        }

        if ($upsertRows !== []) {
            foreach (array_chunk($upsertRows, self::UPSERT_CHUNK_SIZE) as $chunkRows) {
                DB::table('app_users')->upsert(
                    $chunkRows,
                    ['employee_code'],
                    [
                        'password',
                        'full_name_th',
                        'full_name_en',
                        'employee_type',
                        'position',
                        'department',
                        'dept_abbr_hr',
                        'dept_abbr_qms',
                        'email',
                        'id_thai_hash',
                        'role',
                        'is_registered',
                        'registered_at',
                        'deleted_at',
                        'updated_at',
                    ]
                );
            }
        }

        if ($mode === self::IMPORT_MODE_REPLACE && $keptCodes !== []) {
            $summary['deactivated'] = AppUser::query()
                ->where(function ($builder): void {
                    $builder->whereNull('role')
                        ->orWhereRaw("LOWER(TRIM(COALESCE(role, ''))) <> 'admin'");
                })
                ->whereRaw("LOWER(TRIM(COALESCE(employee_code, ''))) <> 'admin'")
                ->whereNotIn('employee_code', array_values(array_unique($keptCodes)))
                ->delete();
        }

        return $summary;
    }

    /**
     * @param array<string, string> $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function stringifyCellValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_float($value) || is_int($value)) {
            $text = PlainTextNormalizer::normalize($value);
            return str_ends_with($text, '.0') ? substr($text, 0, -2) : $text;
        }

        return PlainTextNormalizer::normalize($value);
    }

    private function normalizeEmployeeCode(string $code): string
    {
        return trim($code);
    }

    private function normalizeHeaderKey(string $header): string
    {
        $normalized = str_replace("\u{FEFF}", '', $header);
        $normalized = mb_strtolower(trim($normalized), 'UTF-8');
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? '';
        $normalized = preg_replace('/[\-\_\(\)\[\]\{\}\/\\:\.\,]+/u', '', $normalized) ?? '';

        return trim($normalized);
    }

    private function nullIfBlank(?string $value): ?string
    {
        $clean = trim((string) $value);
        return $clean === '' ? null : $clean;
    }

    /**
     * @param array{created:int,updated:int,restored:int,deactivated:int,skipped_admin:int} $summary
     */
    private function buildStatusMessage(array $summary, string $mode, string $lang): string
    {
        if ($lang === 'th') {
            $message = self::TH_SUCCESS;
            $message .= ' | '.self::TH_ADD_PREFIX.$summary['created'].self::TH_ITEM_SUFFIX;
            $message .= ' | '.self::TH_UPDATE_PREFIX.$summary['updated'].self::TH_ITEM_SUFFIX;

            if ($summary['restored'] > 0) {
                $message .= ' | '.self::TH_RESTORE_PREFIX.$summary['restored'].self::TH_ITEM_SUFFIX;
            }

            if ($mode === self::IMPORT_MODE_REPLACE) {
                $message .= ' | '.self::TH_DEACTIVATE_PREFIX.$summary['deactivated'].self::TH_ITEM_SUFFIX;
            }

            if ($summary['skipped_admin'] > 0) {
                $message .= ' | '.self::TH_SKIP_ADMIN_PREFIX.$summary['skipped_admin'].self::TH_ITEM_SUFFIX;
            }

            return $message;
        }

        $message = 'Employee import completed';
        $message .= ' | Created '.$summary['created'];
        $message .= ' | Updated '.$summary['updated'];

        if ($summary['restored'] > 0) {
            $message .= ' | Restored '.$summary['restored'];
        }

        if ($mode === self::IMPORT_MODE_REPLACE) {
            $message .= ' | Deactivated '.$summary['deactivated'];
        }

        if ($summary['skipped_admin'] > 0) {
            $message .= ' | Skipped admin '.$summary['skipped_admin'];
        }

        return $message;
    }

    private function isAdminUser(AppUser $user): bool
    {
        if (strtolower(trim((string) $user->employee_code)) === 'admin') {
            return true;
        }

        return AppUserAuthController::isAdminRole($user->role);
    }

    private function resolveLangFromRequest(Request $request): string
    {
        return strtolower((string) $request->input('lang', $request->query('lang', 'en'))) === 'th' ? 'th' : 'en';
    }
}
