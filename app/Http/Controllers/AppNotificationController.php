<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $items = AppNotification::query()
            ->where('recipient_user_id', (int) $user->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get([
                'id',
                'type',
                'title',
                'message',
                'link_url',
                'payload_json',
                'is_read',
                'read_at',
                'created_at',
            ]);

        $unreadCount = AppNotification::query()
            ->where('recipient_user_id', (int) $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'ok' => true,
            'unread_count' => $unreadCount,
            'items' => $items->map(function (AppNotification $item): array {
                return [
                    'id' => (int) $item->id,
                    'type' => (string) $item->type,
                    'title' => (string) $item->title,
                    'message' => (string) $item->message,
                    'link_url' => trim((string) ($item->link_url ?? '')),
                    'payload' => is_array($item->payload_json) ? $item->payload_json : [],
                    'is_read' => (bool) $item->is_read,
                    'read_at' => optional($item->read_at)->toIso8601String(),
                    'created_at' => optional($item->created_at)->toIso8601String(),
                ];
            })->values()->all(),
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ((int) $notification->recipient_user_id !== (int) $user->id) {
            return response()->json([
                'ok' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        if (! (bool) $notification->is_read) {
            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();
        }

        $unreadCount = AppNotification::query()
            ->where('recipient_user_id', (int) $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'ok' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'ok' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        AppNotification::query()
            ->where('recipient_user_id', (int) $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'unread_count' => 0,
        ]);
    }
}
