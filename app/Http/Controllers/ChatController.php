<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.'
            ], 401);
        }

        $conversations = DB::table('conversations as c')
            ->join(
                'conversation_members as cm',
                'cm.conversation_id',
                '=',
                'c.id'
            )
            ->where('cm.user_id', $user->id)
            ->select(
                'c.id',
                'c.type',
                'c.name',
                'c.photo_path',
                'c.created_at'
            )
            ->orderByDesc('c.created_at')
            ->get();

        $data = $conversations->map(function ($conversation) use ($user) {

            $members = DB::table('conversation_members as cm')
                ->join('users as u', 'u.id', '=', 'cm.user_id')
                ->where('cm.conversation_id', $conversation->id)
                ->select(
                    'u.id',
                    'u.name',
                    'cm.role'
                )
                ->get();

            $isGroup = $conversation->type === 'group';

            $otherMember = $members->first(
                fn ($member) => $member->id != $user->id
            );

            return [
                'id' => $conversation->id,
                'dbConversationId' => $conversation->id,
                'name' => $isGroup
                    ? ($conversation->name ?: 'Grup Tanpa Nama')
                    : ($otherMember->name ?? 'Pengguna FINS'),
                'isGroup' => $isGroup,
                'members' => $members->pluck('name')->values(),
                'unread' => 0,
                'messages' => [],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

public function messages(Request $request, int $conversationId)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Silakan login terlebih dahulu.'
        ], 401);
    }

    // Pastikan pengguna merupakan anggota percakapan
    $isMember = DB::table('conversation_members')
        ->where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->exists();

    if (!$isMember) {
        return response()->json([
            'success' => false,
            'message' => 'Kamu tidak memiliki akses ke percakapan ini.'
        ], 403);
    }

    // Ambil pesan yang belum dihapus
    $messages = DB::table('messages as m')
        ->leftJoin(
            'message_attachments as a',
            'a.message_id',
            '=',
            'm.id'
        )
        ->where('m.conversation_id', $conversationId)
        ->whereNull('m.deleted_at')
        ->select(
            'm.id',
            'm.sender_id',
            'm.message',
            'm.type',
            'm.created_at',
            'a.id as attachment_id',
            'a.file_name',
            'a.file_size'
        )
        ->orderBy('m.created_at')
        ->orderBy('m.id')
        ->get();

    $data = $messages->map(function ($message) use ($user) {
        return [
            'id' => $message->id,
            'from' => $message->type === 'system'
                ? 'system'
                : (
                    $message->sender_id == $user->id
                        ? 'me'
                        : 'them'
                ),
            'type' => $message->type,
            'text' => $message->message,
            'fileName' => $message->file_name,
            'fileSize' => $message->file_size,
            'attachmentId' => $message->attachment_id,
            'at' => $message->created_at,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}

public function sendMessage(Request $request, int $conversationId)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Silakan login terlebih dahulu.'
        ], 401);
    }

    // Validasi pesan
    $validated = $request->validate([
        'message' => [
            'required',
            'string',
            'max:2000'
        ]
    ]);

    // Pastikan pengguna adalah anggota percakapan
    $isMember = DB::table('conversation_members')
        ->where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->exists();

    if (!$isMember) {
        return response()->json([
            'success' => false,
            'message' => 'Kamu bukan anggota percakapan ini.'
        ], 403);
    }

    // Simpan pesan ke database Supabase
    $messageId = DB::table('messages')->insertGetId([
        'conversation_id' => $conversationId,
        'sender_id' => $user->id,
        'message' => $validated['message'],
        'type' => 'text',
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Ambil pesan yang baru disimpan
    $message = DB::table('messages')
        ->where('id', $messageId)
        ->first();

    return response()->json([
        'success' => true,
        'message' => 'Pesan berhasil dikirim.',
        'data' => [
            'id' => $message->id,
            'from' => 'me',
            'type' => 'text',
            'text' => $message->message,
            'at' => $message->created_at,
        ]
    ], 201);
}

}
