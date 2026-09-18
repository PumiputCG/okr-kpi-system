<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('Profie', [
            'user' => $request->user(),
        ]);
    }

    public function verifyIdentity(Request $request): JsonResponse
    {
        /** @var AppUser $user */
        $user = $request->user();
        $lang = $this->resolveLangFromRequest($request);

        $validated = $request->validate([
            'identity_code' => ['required', 'string', 'max:255'],
        ]);

        if (! $this->isIdentityMatched($user, $validated['identity_code'])) {
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

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var AppUser $user */
        $user = $request->user();
        $lang = $this->resolveLangFromRequest($request);

        $validated = $request->validate([
            'identity_code' => ['required', 'string', 'max:255'],
            'new_password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        if (! $this->isIdentityMatched($user, $validated['identity_code'])) {
            return back()->withErrors([
                'identity_code' => $lang === 'th'
                    ? 'รหัสบัตรประชาชน / บัตรประกันสังคมไม่ถูกต้อง'
                    : 'National ID / Social Security No. is incorrect.',
            ])->withInput();
        }

        $user->password = $validated['new_password'];
        $user->save();

        return back()->with('status', $lang === 'th' ? 'เปลี่ยนรหัสผ่านสำเร็จ' : 'Password changed successfully.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        /** @var AppUser $user */
        $user = $request->user();
        $lang = $this->resolveLangFromRequest($request);

        $validated = $request->validate([
            'profile_picture' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:51200'],
        ]);

        $file = $validated['profile_picture'];
        $filename = 'profile_' . $user->id . '_' . Str::uuid() . '.' . strtolower((string) $file->getClientOriginalExtension());
        $destination = public_path('uploads/profile');

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);
        $user->profile_picture = 'uploads/profile/' . $filename;
        $user->save();

        return back()->with('status', $lang === 'th' ? 'อัปเดตรูปโปรไฟล์สำเร็จ' : 'Profile photo updated successfully.');
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

    protected function resolveLangFromRequest(Request $request): string
    {
        $lang = strtolower((string) $request->input('lang', $request->query('lang', 'en')));

        return $lang === 'th' ? 'th' : 'en';
    }
}
