<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppUserAuthController;
use App\Http\Controllers\Controller;
use App\Models\AdminHomeAnnouncement;
use App\Models\AdminHomeHierarchyConfig;
use App\Models\AppUser;
use App\Support\PlainTextNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeHierarchyController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $adminId = (int) ($user?->id ?? 0);

        if ($adminId <= 0 || ! AppUserAuthController::isAdminRole($user?->role)) {
            return response()->json([
                'ok' => false,
                'message' => 'You are not allowed to delete hierarchy.',
            ], 403);
        }

        $deleted = AdminHomeHierarchyConfig::query()
            ->where('admin_user_id', $adminId)
            ->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Hierarchy deleted successfully.',
            'deleted' => $deleted > 0,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $user = $request->user();
        $adminId = (int) ($user?->id ?? 0);

        if ($adminId <= 0 || ! AppUserAuthController::isAdminRole($user?->role)) {
            return response()->json([
                'ok' => false,
                'message' => 'You are not allowed to save hierarchy.',
            ], 403);
        }

        $validated = $request->validate([
            'levels_count' => ['required', 'integer', 'min:1', 'max:20'],
            'levels' => ['required', 'array', 'max:20'],
            'levels.*.level' => ['required', 'integer', 'min:1', 'max:20'],
            'levels.*.positions' => ['nullable', 'array', 'max:200'],
            'levels.*.positions.*' => ['nullable', 'string', 'max:255'],
        ]);

        $levelsCount = (int) $validated['levels_count'];
        $inputLevels = is_array($validated['levels'] ?? null) ? $validated['levels'] : [];

        $layout = [];
        foreach ($inputLevels as $row) {
            $level = (int) ($row['level'] ?? 0);
            if ($level < 1 || $level > $levelsCount) {
                continue;
            }

            $positions = collect($row['positions'] ?? [])
                ->map(fn ($value): string => $this->normalizePositionText((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $layout[(string) $level] = $positions;
        }
        for ($level = 1; $level <= $levelsCount; $level++) {
            if (! array_key_exists((string) $level, $layout)) {
                $layout[(string) $level] = [];
            }
        }
        ksort($layout);

        $config = AdminHomeHierarchyConfig::query()->firstOrNew([
            'admin_user_id' => $adminId,
        ]);
        $config->levels_count = $levelsCount;
        $config->layout_json = $layout;
        $config->last_saved_at = now();
        $config->save();

        $syncedPosts = $this->syncStaffAnnouncementLevels($adminId, $layout);

        return response()->json([
            'ok' => true,
            'message' => 'Positions saved successfully.',
            'config' => [
                'levels_count' => $config->levels_count,
                'layout' => $config->layout_json,
                'last_saved_at' => optional($config->last_saved_at)->toIso8601String(),
            ],
            'synced_posts' => $syncedPosts,
        ]);
    }

    private function normalizePositionText(?string $value): string
    {
        $text = PlainTextNormalizer::normalize($value);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return strtoupper($text);
    }

    private function syncStaffAnnouncementLevels(int $adminId, array $layout): int
    {
        if ($adminId < 1) {
            return 0;
        }

        $positionLevelMap = [];
        foreach ($layout as $rawLevel => $positions) {
            $level = (int) $rawLevel;
            if ($level < 1 || ! is_array($positions)) {
                continue;
            }

            foreach ($positions as $position) {
                $normalized = $this->normalizePositionText((string) $position);
                if ($normalized === '' || isset($positionLevelMap[$normalized])) {
                    continue;
                }

                $positionLevelMap[$normalized] = $level;
            }
        }

        if (count($positionLevelMap) === 0) {
            return 0;
        }

        $announcements = AdminHomeAnnouncement::query()
            ->where('admin_user_id', $adminId)
            ->where('posted_by_user_id', '<>', $adminId)
            ->get(['id', 'posted_by_user_id', 'level_no']);

        if ($announcements->isEmpty()) {
            return 0;
        }

        $authorIds = $announcements
            ->pluck('posted_by_user_id')
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();

        if (count($authorIds) === 0) {
            return 0;
        }

        $authorLevelMap = AppUser::query()
            ->whereIn('id', $authorIds)
            ->get(['id', 'position'])
            ->mapWithKeys(function (AppUser $user) use ($positionLevelMap): array {
                $normalizedPosition = $this->normalizePositionText((string) ($user->position ?? ''));
                if ($normalizedPosition === '' || ! isset($positionLevelMap[$normalizedPosition])) {
                    return [];
                }

                return [(int) $user->id => (int) $positionLevelMap[$normalizedPosition]];
            })
            ->all();

        if (count($authorLevelMap) === 0) {
            return 0;
        }

        $updates = [];
        foreach ($announcements as $announcement) {
            $authorId = (int) ($announcement->posted_by_user_id ?? 0);
            $currentLevel = (int) ($announcement->level_no ?? 0);
            $targetLevel = (int) ($authorLevelMap[$authorId] ?? 0);
            if ($authorId < 1 || $targetLevel < 1 || $targetLevel === $currentLevel) {
                continue;
            }

            $updates[(int) $announcement->id] = $targetLevel;
        }

        if (count($updates) === 0) {
            return 0;
        }

        DB::transaction(function () use ($updates): void {
            foreach ($updates as $announcementId => $levelNo) {
                AdminHomeAnnouncement::query()
                    ->where('id', (int) $announcementId)
                    ->update(['level_no' => (int) $levelNo]);
            }
        });

        return count($updates);
    }
}
