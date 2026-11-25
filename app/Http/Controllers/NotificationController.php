<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    // ================= DAFTAR NOTIFIKASI =================

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = DB::table('notifications as n')
            ->leftJoin('users as u', 'u.id', '=', 'n.actor_id')
            ->leftJoin(
                'partner_invitations as pi',
                'pi.id',
                '=',
                'n.invitation_id'
            )
            ->where('n.user_id', $userId)
            ->select([
                'n.id',
                'n.user_id',
                'n.actor_id',
                'n.invitation_id',
                'pi.conversation_id as conversation_id',
                'n.type',
                'n.title',
                'n.message',
                'n.is_read',
                'n.read_at',
                'n.created_at',
                'u.name as actor_name',
                'u.profile_fullname as actor_fullname',
                'u.profile_photo_path as actor_photo_path',
            ])
            ->orderByDesc('n.created_at')
            ->orderByDesc('n.id')
            ->limit(100)
            ->get();

        $unreadCount = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ])->header('Cache-Control', 'no-store, private');
    }

    // ================= JUMLAH BELUM DIBACA =================

    public function unreadCount(Request $request): JsonResponse
    {
        $count = DB::table('notifications')
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count,
        ])->header('Cache-Control', 'no-store, private');
    }

    // ================= TANDAI SUDAH DIBACA =================

    public function markAsRead(
        Request $request,
        int $id
    ): JsonResponse {

        $notification = DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $request->user()->id);

        if (!$notification->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        $notification
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca.',
        ]);
    }

    // ================= BACA SEMUA NOTIFIKASI =================

    public function markAllAsRead(
        Request $request
    ): JsonResponse {

        $updated = DB::table('notifications')
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca.',
            'updated_count' => $updated,
        ]);
    }
}