<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppUserAuthController;
use App\Http\Controllers\Controller;
use App\Models\AdminHomeAnnouncement;
use App\Models\AdminHomeAnnouncementFile;
use App\Models\AppUser;
use App\Support\PlainTextNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class HomeAnnouncementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $adminId = (int) ($user?->id ?? 0);
        $lang = $this->resolveLang($request);

        if ($adminId <= 0 || ! AppUserAuthController::isAdminRole($user?->role)) {
            return response()->json([
                'ok' => false,
                'message' => 'You are not allowed to save this post.',
            ], 403);
        }

        $validated = $request->validate([
            'level_no' => ['required', 'integer', 'in:1,2'],
            'parent_announcement_id' => ['nullable', 'integer'],
            'dept_abbr_hr' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:60000'],
            'detail' => ['nullable', 'string', 'max:60000'],
            'files' => ['nullable', 'array', 'max:100'],
            'files.*' => ['file', 'mimes:png,jpg,jpeg,xlsx,xls,doc,docx,ppt,pptx,pdf', 'max:204800'],
        ]);

        $levelNo = (int) $validated['level_no'];
        $parentAnnouncementIdInput = isset($validated['parent_announcement_id'])
            ? (int) $validated['parent_announcement_id']
            : 0;
        $deptAbbrHr = $this->normalizeDeptAbbrHr($validated['dept_abbr_hr'] ?? null);
        $title = PlainTextNormalizer::normalize($validated['title'] ?? '');
        $detail = PlainTextNormalizer::normalize($validated['detail'] ?? '', false);
        $detailHasContent = trim($detail) !== '';
        $uploadedFiles = collect($request->file('files', []))
            ->filter(static fn ($file): bool => $file instanceof UploadedFile)
            ->values();

        $parentResolution = $this->resolveParentAnnouncementForWrite(
            $adminId,
            $levelNo,
            $parentAnnouncementIdInput,
            null,
            $lang
        );
        if (! (bool) ($parentResolution['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($parentResolution['message'] ?? 'Invalid parent announcement.'),
            ], 422);
        }
        $parentAnnouncementId = (int) ($parentResolution['parent_id'] ?? 0);

        if ($deptAbbrHr !== null && ! $this->isKnownDeptAbbrHr($deptAbbrHr)) {
            return response()->json([
                'ok' => false,
                'message' => 'Selected HR department abbreviation was not found.',
            ], 422);
        }

        if ($title === '' && ! $detailHasContent && $uploadedFiles->count() === 0) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th'
                    ? 'Please provide at least one item before saving this post.'
                    : 'Please provide at least one item before saving this post.',
            ], 422);
        }

        /** @var AdminHomeAnnouncement|null $announcement */
        $announcement = null;
        DB::transaction(function () use (&$announcement, $adminId, $levelNo, $parentAnnouncementId, $deptAbbrHr, $title, $detail, $detailHasContent, $uploadedFiles): void {
            $announcement = AdminHomeAnnouncement::query()->create([
                'admin_user_id' => $adminId,
                'posted_by_user_id' => $adminId,
                'level_no' => $levelNo,
                'parent_announcement_id' => $parentAnnouncementId > 0 ? $parentAnnouncementId : null,
                'dept_abbr_hr' => $deptAbbrHr,
                'title' => $title !== '' ? $title : null,
                'detail' => $detailHasContent ? $detail : null,
                'posted_at' => now(),
            ]);

            foreach ($uploadedFiles as $file) {
                /** @var UploadedFile $file */
                if (! $file->isValid()) {
                    continue;
                }

                $storedPath = $file->store('admin-home-announcements/'.$announcement->id, 'public');
                AdminHomeAnnouncementFile::query()->create([
                    'announcement_id' => $announcement->id,
                    'uploaded_by' => $adminId,
                    'original_name' => $file->getClientOriginalName(),
                    'storage_path' => $storedPath,
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => (int) ($file->getSize() ?? 0),
                ]);
            }
        });

        if (! $announcement) {
            return response()->json([
                'ok' => false,
                'message' => 'Unable to save this post.',
            ], 500);
        }

        $announcement->load([
            'files' => fn ($query) => $query->orderByDesc('id'),
            'author',
        ]);

        return response()->json([
            'ok' => true,
            'message' => $lang === 'th'
                ? 'Post saved successfully.'
                : 'Post saved successfully.',
            'announcement' => $this->toAnnouncementPayload($announcement, $request),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $adminId = (int) ($user?->id ?? 0);
        $lang = $this->resolveLang($request);

        if ($adminId <= 0 || ! AppUserAuthController::isAdminRole($user?->role)) {
            return response()->json([
                'ok' => false,
                'message' => 'You are not allowed to edit this post.',
            ], 403);
        }

        $validated = $request->validate([
            'announcement_id' => ['required', 'integer'],
            'level_no' => ['required', 'integer', 'in:1,2'],
            'parent_announcement_id' => ['nullable', 'integer'],
            'dept_abbr_hr' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:60000'],
            'detail' => ['nullable', 'string', 'max:60000'],
            'files' => ['nullable', 'array', 'max:100'],
            'files.*' => ['file', 'mimes:png,jpg,jpeg,xlsx,xls,doc,docx,ppt,pptx,pdf', 'max:204800'],
        ]);

        /** @var AdminHomeAnnouncement|null $announcement */
        $announcement = AdminHomeAnnouncement::query()
            ->with([
                'files' => fn ($query) => $query->orderByDesc('id'),
                'author',
            ])
            ->where('id', (int) $validated['announcement_id'])
            ->where('admin_user_id', $adminId)
            ->first();

        if (! $announcement) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th'
                    ? 'Requested post not found.'
                    : 'Requested post not found.',
            ], 404);
        }

        $levelNo = (int) $validated['level_no'];
        $parentAnnouncementIdInput = isset($validated['parent_announcement_id'])
            ? (int) $validated['parent_announcement_id']
            : 0;
        $deptAbbrHr = $this->normalizeDeptAbbrHr($validated['dept_abbr_hr'] ?? null);
        $title = PlainTextNormalizer::normalize($validated['title'] ?? '');
        $detail = PlainTextNormalizer::normalize($validated['detail'] ?? '', false);
        $detailHasContent = trim($detail) !== '';
        $uploadedFiles = collect($request->file('files', []))
            ->filter(static fn ($file): bool => $file instanceof UploadedFile)
            ->values();
        $replaceFiles = $uploadedFiles->count() > 0;

        if ((int) $announcement->level_no === 1 && $levelNo !== 1 && $announcement->children()->exists()) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th'
                    ? 'ไม่สามารถเปลี่ยนลำดับชั้นนี้ได้ เนื่องจากยังมีข้อมูลลำดับชั้น 2 ผูกอยู่'
                    : 'Cannot change this level while child level-2 records still exist.',
            ], 422);
        }

        $parentResolution = $this->resolveParentAnnouncementForWrite(
            $adminId,
            $levelNo,
            $parentAnnouncementIdInput,
            $announcement,
            $lang
        );
        if (! (bool) ($parentResolution['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'message' => (string) ($parentResolution['message'] ?? 'Invalid parent announcement.'),
            ], 422);
        }
        $parentAnnouncementId = (int) ($parentResolution['parent_id'] ?? 0);

        if ($deptAbbrHr !== null && ! $this->isKnownDeptAbbrHr($deptAbbrHr)) {
            return response()->json([
                'ok' => false,
                'message' => 'Selected HR department abbreviation was not found.',
            ], 422);
        }

        $existingFileCount = $announcement->files->count();
        $resultingFileCount = $replaceFiles ? $uploadedFiles->count() : $existingFileCount;
        if ($title === '' && ! $detailHasContent && $resultingFileCount === 0) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th'
                    ? 'Please provide at least one item before saving this post.'
                    : 'Please provide at least one item before saving this post.',
            ], 422);
        }

        $oldFilePaths = [];
        DB::transaction(function () use ($announcement, $adminId, $levelNo, $parentAnnouncementId, $deptAbbrHr, $title, $detail, $detailHasContent, $replaceFiles, $uploadedFiles, &$oldFilePaths): void {
            $announcement->level_no = $levelNo;
            $announcement->parent_announcement_id = $parentAnnouncementId > 0 ? $parentAnnouncementId : null;
            $announcement->dept_abbr_hr = $deptAbbrHr;
            $announcement->title = $title !== '' ? $title : null;
            $announcement->detail = $detailHasContent ? $detail : null;
            $announcement->save();

            if (! $replaceFiles) {
                return;
            }

            $oldFilePaths = $announcement->files()
                ->pluck('storage_path')
                ->filter(static fn ($path): bool => trim((string) $path) !== '')
                ->values()
                ->all();

            $announcement->files()->delete();
            foreach ($uploadedFiles as $file) {
                /** @var UploadedFile $file */
                if (! $file->isValid()) {
                    continue;
                }

                $storedPath = $file->store('admin-home-announcements/'.$announcement->id, 'public');
                AdminHomeAnnouncementFile::query()->create([
                    'announcement_id' => $announcement->id,
                    'uploaded_by' => $adminId,
                    'original_name' => $file->getClientOriginalName(),
                    'storage_path' => $storedPath,
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => (int) ($file->getSize() ?? 0),
                ]);
            }
        });

        if ($replaceFiles && count($oldFilePaths) > 0) {
            Storage::disk('public')->delete($oldFilePaths);
        }

        $announcement->load([
            'files' => fn ($query) => $query->orderByDesc('id'),
            'author',
        ]);

        return response()->json([
            'ok' => true,
            'message' => $lang === 'th'
                ? 'Post updated successfully.'
                : 'Post updated successfully.',
            'announcement' => $this->toAnnouncementPayload($announcement, $request),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $adminId = (int) ($user?->id ?? 0);
        $lang = $this->resolveLang($request);

        if ($adminId <= 0 || ! AppUserAuthController::isAdminRole($user?->role)) {
            return response()->json([
                'ok' => false,
                'message' => 'You are not allowed to delete this post.',
            ], 403);
        }

        $validated = $request->validate([
            'announcement_id' => ['required', 'integer'],
        ]);

        $announcement = AdminHomeAnnouncement::query()
            ->where('id', (int) $validated['announcement_id'])
            ->where('admin_user_id', $adminId)
            ->first();

        if (! $announcement) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th'
                    ? 'Requested post not found.'
                    : 'Requested post not found.',
            ], 404);
        }

        if ((int) $announcement->level_no === 1 && $announcement->children()->exists()) {
            return response()->json([
                'ok' => false,
                'message' => $lang === 'th'
                    ? 'ไม่สามารถลบลำดับชั้นที่ 1 ได้ เนื่องจากยังมีลำดับชั้นที่ 2 ผูกอยู่'
                    : 'Unable to delete level 1 because linked level-2 records still exist.',
            ], 422);
        }

        $filePaths = $announcement->files()
            ->pluck('storage_path')
            ->filter(static fn ($path): bool => trim((string) $path) !== '')
            ->values()
            ->all();

        DB::transaction(function () use ($announcement): void {
            $announcement->delete();
        });

        if (count($filePaths) > 0) {
            Storage::disk('public')->delete($filePaths);
        }

        return response()->json([
            'ok' => true,
            'message' => $lang === 'th'
                ? 'Post deleted successfully.'
                : 'Post deleted successfully.',
        ]);
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
            'posted_by_user_id' => (int) ($announcement->posted_by_user_id ?? 0),
            'can_manage' => $canManage,
            'level_no' => (int) $announcement->level_no,
            'parent_announcement_id' => (int) ($announcement->parent_announcement_id ?? 0),
            'dept_abbr_hr' => strtoupper(trim((string) ($announcement->dept_abbr_hr ?? ''))),
            'title' => trim((string) ($announcement->title ?? '')),
            'detail' => (string) ($announcement->detail ?? ''),
            'posted_at' => optional($announcement->posted_at)->toIso8601String(),
            'author_name' => $this->resolveAuthorName($announcement, $request),
            'author_profile' => $this->resolveAuthorProfile($announcement->author, $request),
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

    private function resolveAuthorName(AdminHomeAnnouncement $announcement, Request $request): string
    {
        $author = $announcement->author;
        if (! $author) {
            return '';
        }

        $lang = $this->resolveLang($request);
        $preferredName = $lang === 'th'
            ? trim((string) ($author->full_name_th ?? ''))
            : trim((string) ($author->full_name_en ?? ''));

        if ($preferredName !== '') {
            return $preferredName;
        }

        $fallbackName = trim((string) ($author->full_name_en ?? ''));
        if ($fallbackName !== '') {
            return $fallbackName;
        }

        $fallbackThaiName = trim((string) ($author->full_name_th ?? ''));
        if ($fallbackThaiName !== '') {
            return $fallbackThaiName;
        }

        return trim((string) ($author->employee_code ?? ''));
    }

    private function resolveAuthorProfile(?AppUser $author, Request $request): string
    {
        if (! $author) {
            return '';
        }

        $lang = $this->resolveLang($request);
        $fullName = $lang === 'th'
            ? trim((string) ($author->full_name_th ?? ''))
            : trim((string) ($author->full_name_en ?? ''));
        if ($fullName === '') {
            $fullName = trim((string) ($author->full_name_en ?? ''));
        }
        if ($fullName === '') {
            $fullName = trim((string) ($author->full_name_th ?? ''));
        }

        $employeeCode = trim((string) ($author->employee_code ?? ''));
        $department = trim((string) ($author->department ?? ''));
        $position = trim((string) ($author->position ?? ''));

        $parts = [
            $fullName !== '' ? $fullName : '-',
            $employeeCode !== '' ? $employeeCode : '-',
            $department !== '' ? $department : '-',
            $position !== '' ? $position : '-',
        ];

        return implode(' , ', $parts);
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

    /**
     * @return array{ok: bool, parent_id: int, message: string}
     */
    private function resolveParentAnnouncementForWrite(
        int $adminId,
        int $levelNo,
        int $parentAnnouncementIdInput,
        ?AdminHomeAnnouncement $currentAnnouncement,
        string $lang
    ): array {
        if ($levelNo === 1) {
            return [
                'ok' => true,
                'parent_id' => 0,
                'message' => '',
            ];
        }

        if ($levelNo !== 2) {
            return [
                'ok' => false,
                'parent_id' => 0,
                'message' => $lang === 'th'
                    ? 'ลำดับชั้นประกาศไม่ถูกต้อง'
                    : 'Invalid announcement level.',
            ];
        }

        if ($parentAnnouncementIdInput < 1) {
            return [
                'ok' => false,
                'parent_id' => 0,
                'message' => $lang === 'th'
                    ? 'กรุณาเลือกลำดับชั้นที่ 1 สำหรับข้อมูลลำดับชั้นที่ 2'
                    : 'Please select a level-1 parent for level-2 records.',
            ];
        }

        $parentAnnouncement = AdminHomeAnnouncement::query()
            ->where('id', $parentAnnouncementIdInput)
            ->where('admin_user_id', $adminId)
            ->where('level_no', 1)
            ->first();

        if (! $parentAnnouncement) {
            return [
                'ok' => false,
                'parent_id' => 0,
                'message' => $lang === 'th'
                    ? 'ไม่พบข้อมูลลำดับชั้นที่ 1 ที่เลือก'
                    : 'Selected level-1 parent was not found.',
            ];
        }

        $currentId = (int) ($currentAnnouncement?->id ?? 0);
        if ($currentId > 0 && (int) $parentAnnouncement->id === $currentId) {
            return [
                'ok' => false,
                'parent_id' => 0,
                'message' => $lang === 'th'
                    ? 'ลำดับชั้นที่ 2 ไม่สามารถอ้างอิงตัวเองเป็นลำดับชั้นที่ 1 ได้'
                    : 'Level-2 record cannot reference itself as its level-1 parent.',
            ];
        }

        return [
            'ok' => true,
            'parent_id' => (int) $parentAnnouncement->id,
            'message' => '',
        ];
    }

    private function resolveLang(Request $request): string
    {
        $lang = strtolower((string) $request->input('lang', $request->query('lang', 'en')));

        return $lang === 'th' ? 'th' : 'en';
    }

    private function normalizeDeptAbbrHr(?string $value): ?string
    {
        $text = strtoupper(trim((string) $value));

        return $text !== '' ? $text : null;
    }

    private function isKnownDeptAbbrHr(string $deptAbbrHr): bool
    {
        return AppUser::query()
            ->whereRaw("UPPER(TRIM(COALESCE(dept_abbr_hr, ''))) = ?", [$deptAbbrHr])
            ->exists();
    }
}
