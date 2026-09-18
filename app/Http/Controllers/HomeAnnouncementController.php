<?php

namespace App\Http\Controllers;

use App\Models\AdminHomeAnnouncement;
use App\Models\AdminHomeAnnouncementFile;
use App\Models\AdminHomeHierarchyConfig;
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
        $lang = $this->resolveLang($request);

        if (! $user || AppUserAuthController::isAdminRole($user->role)) {
            return response()->json([
                'ok' => false,
                'message' => 'You are not allowed to save this post.',
            ], 403);
        }

        $validated = $request->validate([
            'admin_user_id' => ['nullable', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:60000'],
            'detail' => ['nullable', 'string', 'max:60000'],
            'files' => ['nullable', 'array', 'max:100'],
            'files.*' => ['file', 'mimes:png,jpg,jpeg,xlsx,xls,doc,docx,pdf', 'max:204800'],
        ]);

        $targetContext = $this->resolveUserTargetContext($user, $validated['admin_user_id'] ?? null);
        if (! $targetContext) {
            return response()->json([
                'ok' => false,
                'message' => 'You cannot post in this hierarchy.',
            ], 422);
        }

        $title = PlainTextNormalizer::normalize($validated['title'] ?? '');
        $detail = PlainTextNormalizer::normalize($validated['detail'] ?? '', false);
        $detailHasContent = trim($detail) !== '';
        $uploadedFiles = collect($request->file('files', []))
            ->filter(static fn ($file): bool => $file instanceof UploadedFile)
            ->values();

        if ($title === '' && ! $detailHasContent && $uploadedFiles->count() === 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Please provide at least one item before saving this post.',
            ], 422);
        }

        /** @var AdminHomeAnnouncement|null $announcement */
        $announcement = null;
        DB::transaction(function () use (&$announcement, $user, $targetContext, $title, $detail, $detailHasContent, $uploadedFiles): void {
            $announcement = AdminHomeAnnouncement::query()->create([
                'admin_user_id' => (int) $targetContext['admin_user_id'],
                'posted_by_user_id' => (int) ($user->id ?? 0),
                'level_no' => (int) $targetContext['level_no'],
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
                    'uploaded_by' => (int) ($user->id ?? 0),
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
            'message' => $lang === 'th' ? 'Post saved successfully.' : 'Post saved successfully.',
            'announcement' => $this->toAnnouncementPayload($announcement, $request),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $lang = $this->resolveLang($request);

        if (! $user || AppUserAuthController::isAdminRole($user->role)) {
            return response()->json([
                'ok' => false,
                'message' => 'You are not allowed to edit this post.',
            ], 403);
        }

        $validated = $request->validate([
            'announcement_id' => ['required', 'integer'],
            'title' => ['nullable', 'string', 'max:60000'],
            'detail' => ['nullable', 'string', 'max:60000'],
            'files' => ['nullable', 'array', 'max:100'],
            'files.*' => ['file', 'mimes:png,jpg,jpeg,xlsx,xls,doc,docx,pdf', 'max:204800'],
        ]);

        /** @var AdminHomeAnnouncement|null $announcement */
        $announcement = AdminHomeAnnouncement::query()
            ->with([
                'files' => fn ($query) => $query->orderByDesc('id'),
                'author',
            ])
            ->where('id', (int) $validated['announcement_id'])
            ->where('posted_by_user_id', (int) ($user->id ?? 0))
            ->first();

        if (! $announcement) {
            return response()->json([
                'ok' => false,
                'message' => 'Requested post not found.',
            ], 404);
        }

        $title = PlainTextNormalizer::normalize($validated['title'] ?? '');
        $detail = PlainTextNormalizer::normalize($validated['detail'] ?? '', false);
        $detailHasContent = trim($detail) !== '';
        $uploadedFiles = collect($request->file('files', []))
            ->filter(static fn ($file): bool => $file instanceof UploadedFile)
            ->values();
        $replaceFiles = $uploadedFiles->count() > 0;

        $existingFileCount = $announcement->files->count();
        $resultingFileCount = $replaceFiles ? $uploadedFiles->count() : $existingFileCount;
        if ($title === '' && ! $detailHasContent && $resultingFileCount === 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Please provide at least one item before saving this post.',
            ], 422);
        }

        $oldFilePaths = [];
        DB::transaction(function () use ($announcement, $user, $title, $detail, $detailHasContent, $replaceFiles, $uploadedFiles, &$oldFilePaths): void {
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
                    'uploaded_by' => (int) ($user->id ?? 0),
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
            'message' => $lang === 'th' ? 'Post updated successfully.' : 'Post updated successfully.',
            'announcement' => $this->toAnnouncementPayload($announcement, $request),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $lang = $this->resolveLang($request);

        if (! $user || AppUserAuthController::isAdminRole($user->role)) {
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
            ->where('posted_by_user_id', (int) ($user->id ?? 0))
            ->first();

        if (! $announcement) {
            return response()->json([
                'ok' => false,
                'message' => 'Requested post not found.',
            ], 404);
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
            'message' => $lang === 'th' ? 'Post deleted successfully.' : 'Post deleted successfully.',
        ]);
    }

    private function resolveUserTargetContext(AppUser $user, $requestedAdminId): ?array
    {
        $normalizedUserPosition = $this->normalizePositionText($user->position);
        if ($normalizedUserPosition === '') {
            return null;
        }

        $contexts = [];
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
            if ($matchedLevel < 1) {
                continue;
            }

            $contexts[$adminId] = [
                'admin_user_id' => $adminId,
                'level_no' => $matchedLevel,
            ];
        }

        if ($contexts === []) {
            return null;
        }

        $targetAdminId = (int) $requestedAdminId;
        if ($targetAdminId > 0) {
            return $contexts[$targetAdminId] ?? null;
        }

        ksort($contexts, SORT_NUMERIC);

        return array_values($contexts)[0] ?? null;
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

    private function normalizePositionText(?string $position): string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $position)) ?? trim((string) $position);

        return strtoupper($text);
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

    private function resolveLang(Request $request): string
    {
        $lang = strtolower((string) $request->input('lang', $request->query('lang', 'en')));

        return $lang === 'th' ? 'th' : 'en';
    }
}
