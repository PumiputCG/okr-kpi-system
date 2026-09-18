<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AppUserAuthController extends Controller
{
    public const LEVEL_1 = 1;
    public const LEVEL_2 = 2;
    public const LEVEL_3 = 3;
    public const LEVEL_4 = 4;
    public const LEVEL_5 = 5;

    public const POSITION_LEVEL_MAP = [
        self::LEVEL_1 => [
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
        ],
        self::LEVEL_2 => [
            'senior staff',
            'staff',
            'engineer',
            'senior engineer',
        ],
        self::LEVEL_3 => [
            'supervisor',
            
        ],
        self::LEVEL_4 => [
            'assist manager',
            'assistant manager',
            'manager',
            'deputy general manager',
            'general manager',
        ],
        self::LEVEL_5 => [
            'chief financial officer',
            'cfo',
            'chief executive officer',
            'ceo',
            'president',
        ],
    ];

    // Future access plan (not enforced yet).
    public const FUTURE_LEVEL_ACCESS_POLICY = [
        self::LEVEL_1 => 'No login access',
        self::LEVEL_2 => 'Can submit OKR',
        self::LEVEL_3 => 'Can view KPI of Level 2',
        self::LEVEL_4 => 'Can view department OKR (calculated from KPI)',
        self::LEVEL_5 => 'Can view all departments OKR (calculated from KPI)',
    ];

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'employee_id' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
        ]);
        $lang = $this->resolveLangFromRequest($request);

        $employeeCode = trim($credentials['employee_id']);
        $inputPassword = (string) $credentials['password'];

        $user = AppUser::where('employee_code', $employeeCode)->first();

        if (! $user) {
            return back()
                ->withErrors([
                    'employee_id' => $lang === 'th'
                        ? "\u{0E44}\u{0E21}\u{0E48}\u{0E21}\u{0E35}\u{0E02}\u{0E49}\u{0E2D}\u{0E21}\u{0E39}\u{0E25}\u{0E1E}\u{0E19}\u{0E31}\u{0E01}\u{0E07}\u{0E32}\u{0E19}"
                        : 'Employee record not found.',
                ])
                ->withInput($request->only('employee_id'));
        }

        if (! $this->isPasswordValid($user->password, $inputPassword)) {
            return back()
                ->withErrors([
                    'employee_id' => $lang === 'th'
                        ? 'รหัสพนักงานหรือรหัสผ่านไม่ถูกต้อง'
                        : 'Employee ID or password is incorrect.',
                ])
                ->withInput($request->only('employee_id'));
        }

        // Login rule by position level (future-ready for main HR database sync).
        if (! $this->canLoginByPosition($user)) {
            return back()
                ->withErrors([
                    'employee_id' => $lang === 'th'
                        ? 'ระบบนี้ตั้งแต่ตำแหน่ง Staff ขึ้นไป'
                        : 'This system is available for Staff level and above.',
                ])
                ->withInput($request->only('employee_id'));
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $fallbackRoute = self::isAdminRole($user->role)
            ? route('admin.goal.targets', ['lang' => $lang])
            : route('profile.edit', ['lang' => $lang]);

        return redirect()->intended($fallbackRoute);
    }

    public function logout(Request $request): RedirectResponse
    {
        $lang = $this->resolveLangFromRequest($request);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('welcome', ['lang' => $lang]);
    }

    public function verifyResetIdentity(Request $request): JsonResponse
    {
        $lang = $this->resolveLangFromRequest($request);

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:100'],
            'identity_code' => ['required', 'string', 'max:255'],
        ]);

        $employeeCode = trim($validated['employee_id']);
        $identityCode = trim($validated['identity_code']);

        $user = AppUser::where('employee_code', $employeeCode)->first();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th' ? 'ไม่พบรหัสพนักงานนี้' : 'Employee ID not found.',
            ], 422);
        }

        if (! $this->isIdentityMatched($user, $identityCode)) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th'
                    ? 'รหัสบัตรประชาชน / บัตรประกันสังคมไม่ถูกต้อง'
                    : 'National ID / Social Security No. is incorrect.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => $lang === 'th' ? 'ตรวจสอบข้อมูลสำเร็จ' : 'Identity verification successful.',
        ]);
    }

    public function resetForgotPassword(Request $request): JsonResponse
    {
        $lang = $this->resolveLangFromRequest($request);

        $validated = $request->validate([
            'employee_id' => ['required', 'string', 'max:100'],
            'identity_code' => ['required', 'string', 'max:255'],
            'new_password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $employeeCode = trim($validated['employee_id']);
        $identityCode = trim($validated['identity_code']);

        $user = AppUser::where('employee_code', $employeeCode)->first();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th' ? 'ไม่พบรหัสพนักงานนี้' : 'Employee ID not found.',
            ], 422);
        }

        if (! $this->isIdentityMatched($user, $identityCode)) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th'
                    ? 'รหัสบัตรประชาชน / บัตรประกันสังคมไม่ถูกต้อง'
                    : 'National ID / Social Security No. is incorrect.',
            ], 422);
        }

        $user->password = $validated['new_password'];
        $user->save();

        return response()->json([
            'ok' => true,
            'message' => $lang === 'th' ? 'เปลี่ยนรหัสผ่านสำเร็จ' : 'Password changed successfully.',
        ]);
    }

    protected function isPasswordValid(?string $storedPassword, string $inputPassword): bool
    {
        if ($storedPassword === null || $storedPassword === '') {
            return false;
        }

        if (hash_equals($storedPassword, $inputPassword)) {
            return true;
        }

        try {
            return Hash::check($inputPassword, $storedPassword);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isIdentityMatched(AppUser $user, string $identityCode): bool
    {
        $storedIdentity = trim((string) ($user->id_thai_hash ?? ''));
        $inputIdentity = trim($identityCode);

        if ($storedIdentity === '' || $inputIdentity === '') {
            return false;
        }

        return hash_equals($storedIdentity, $inputIdentity);
    }

    protected function canLoginByPosition(AppUser $user): bool
    {
        // Keep admin accessible even if position is missing.
        if (self::isAdminRole($user->role)) {
            return true;
        }

        foreach (self::resolveLevelsByPosition($user->position) as $level) {
            if ($level !== self::LEVEL_1) {
                return true;
            }
        }

        return false;
    }

    public static function isAdminRole(?string $role): bool
    {
        return strtolower(trim((string) $role)) === 'admin';
    }

    public static function resolveLevelByPosition(?string $position): int
    {
        $levels = self::resolveLevelsByPosition($position);

        // Keep backward compatibility for callers that still expect one level.
        return (int) max($levels);
    }

    public static function resolveLevelsByPosition(?string $position): array
    {
        $positionKey = self::normalizePosition($position);
        if ($positionKey === '') {
            return [self::LEVEL_1];
        }

        $exactLevels = [];
        foreach (self::POSITION_LEVEL_MAP as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if ($positionKey === self::normalizePosition($keyword)) {
                    $exactLevels[(int) $level] = true;
                    break;
                }
            }
        }

        if ($exactLevels !== []) {
            $levels = array_map('intval', array_keys($exactLevels));
            sort($levels);

            return $levels;
        }

        $containsLevels = [];
        foreach (self::POSITION_LEVEL_MAP as $level => $keywords) {
            foreach ($keywords as $keyword) {
                $normalizedKeyword = self::normalizePosition($keyword);
                if ($normalizedKeyword !== '' && str_contains($positionKey, $normalizedKeyword)) {
                    $containsLevels[(int) $level] = true;
                    break;
                }
            }
        }

        if ($containsLevels !== []) {
            $levels = array_map('intval', array_keys($containsLevels));
            sort($levels);

            return $levels;
        }

        // Unknown position: block by default for safety.
        return [self::LEVEL_1];
    }

    public static function normalizePosition(?string $position): string
    {
        $normalized = strtolower(trim((string) $position));

        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }

    protected function resolveLangFromRequest(Request $request): string
    {
        $lang = strtolower((string) $request->input('lang', $request->query('lang', 'en')));

        return $lang === 'th' ? 'th' : 'en';
    }
}
