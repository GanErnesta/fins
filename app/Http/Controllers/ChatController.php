<?php

namespace App\Http\Controllers;

use App\Support\ProfilePhotoUrl;
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

        $membersByConversation = DB::table('conversation_members as cm')
            ->join('users as u', 'u.id', '=', 'cm.user_id')
            ->whereIn('cm.conversation_id', $conversations->pluck('id'))
            ->select(
                'cm.conversation_id',
                'u.id',
                'u.name',
                'u.profile_fullname',
                'u.profile_username',
                'u.profile_photo_path',
                'cm.role'
            )
            ->get()
            ->groupBy('conversation_id');

        $data = $conversations->map(function ($conversation) use (
            $user,
            $membersByConversation
        ) {
            $members = $membersByConversation->get(
                $conversation->id,
                collect()
            );

            $isGroup = $conversation->type === 'group';

            $otherMember = $members->first(
                fn ($member) => $member->id != $user->id
            );

            $name = $isGroup
                ? ($conversation->name ?: 'Grup Tanpa Nama')
                : (
                    $otherMember->profile_fullname
                    ?: $otherMember->name
                    ?? 'Pengguna FINS'
                );

            $photoPath = $isGroup
                ? $conversation->photo_path
                : ($otherMember->profile_photo_path ?? null);

            $photoUrl = !$isGroup && $photoPath
                ? ProfilePhotoUrl::publicUrl($photoPath)
                : null;

            $memberDetails = $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->profile_fullname
                        ?: $member->name
                        ?: 'Pengguna FINS',
                    'username' => $member->profile_username,
                    'role' => $member->role,
                    'photoPath' => $member->profile_photo_path,
                    'photoUrl' => $member->profile_photo_path
                        ? ProfilePhotoUrl::publicUrl(
                            $member->profile_photo_path
                        )
                        : null,
                ];
            })->values();

            return [
                'id' => $conversation->id,
                'dbConversationId' => $conversation->id,
                'name' => $name,
                'username' => $isGroup
                    ? null
                    : ($otherMember->profile_username ?? null),
                'otherUserId' => $isGroup
                    ? null
                    : ($otherMember->id ?? null),
                'photoPath' => $photoPath,
                'photoUrl' => $photoUrl,
                'isGroup' => $isGroup,
                'members' => $memberDetails->pluck('name')->values(),
                'memberDetails' => $memberDetails,
                'created_at' => $conversation->created_at,
                'unread' => 0,
                'messages' => [],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ])->header('Cache-Control', 'no-store, private');
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

        if (!$this->isMember($conversationId, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak memiliki akses ke percakapan ini.'
            ], 403);
        }

        $messages = DB::table('messages as m')
            ->leftJoin(
                'message_attachments as a',
                'a.message_id',
                '=',
                'm.id'
            )
            ->where('m.conversation_id', $conversationId)
            ->whereNull('m.deleted_at')
            ->whereNotExists(function ($query) use ($user) {
                $query->selectRaw('1')
                    ->from('message_deletions as md')
                    ->whereColumn('md.message_id', 'm.id')
                    ->where('md.user_id', $user->id);
            })
            ->select(
                'm.id',
                'm.conversation_id',
                'm.sender_id',
                'm.message',
                'm.type',
                'm.created_at',
                'm.updated_at',
                'm.edited_at',
                'a.id as attachment_id',
                'a.file_name',
                'a.file_size',
                'a.file_type'
            )
            ->orderBy('m.created_at')
            ->orderBy('m.id')
            ->get();

        $data = $messages->map(function ($message) use ($user) {
            return $this->formatMessage($message, $user->id);
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

        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:2000'
            ]
        ]);

        $text = trim($validated['message']);

        if ($text === '') {
            return response()->json([
                'success' => false,
                'message' => 'Pesan tidak boleh kosong.'
            ], 422);
        }

        if (!$this->isMember($conversationId, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu bukan anggota percakapan ini.'
            ], 403);
        }

        $now = now();

        $messageId = DB::table('messages')->insertGetId([
            'conversation_id' => $conversationId,
            'sender_id' => $user->id,
            'message' => $text,
            'type' => 'text',
            'edited_at' => null,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        $message = DB::table('messages')
            ->where('id', $messageId)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim.',
            'data' => $this->formatMessage($message, $user->id)
        ], 201);
    }

    public function updateMessage(
        Request $request,
        int $conversationId,
        int $messageId
    ) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.'
            ], 401);
        }

        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:2000'
            ]
        ]);

        $newMessage = trim($validated['message']);

        if ($newMessage === '') {
            return response()->json([
                'success' => false,
                'message' => 'Pesan tidak boleh kosong.'
            ], 422);
        }

        if (!$this->isMember($conversationId, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak memiliki akses ke percakapan ini.'
            ], 403);
        }

        return DB::transaction(function () use (
            $user,
            $conversationId,
            $messageId,
            $newMessage
        ) {
            $message = DB::table('messages')
                ->where('id', $messageId)
                ->where('conversation_id', $conversationId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan tidak ditemukan atau sudah dihapus.'
                ], 404);
            }

            if ($message->sender_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu hanya dapat mengedit pesan milikmu sendiri.'
                ], 403);
            }

            if ($message->type !== 'text') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pesan teks yang dapat diedit.'
                ], 422);
            }

            $isDeletedForMe = DB::table('message_deletions')
                ->where('message_id', $messageId)
                ->where('user_id', $user->id)
                ->exists();

            if ($isDeletedForMe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan sudah dihapus dari akunmu.'
                ], 404);
            }

            if ($message->message === $newMessage) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada perubahan pada pesan.',
                    'data' => $this->formatMessage($message, $user->id)
                ]);
            }

            $editedAt = now();

            $affected = DB::table('messages')
                ->where('id', $messageId)
                ->where('conversation_id', $conversationId)
                ->where('sender_id', $user->id)
                ->where('type', 'text')
                ->whereNull('deleted_at')
                ->whereExists(function ($query) use (
                    $conversationId,
                    $user
                ) {
                    $query->selectRaw('1')
                        ->from('conversation_members')
                        ->where('conversation_id', $conversationId)
                        ->where('user_id', $user->id);
                })
                ->update([
                    'message' => $newMessage,
                    'edited_at' => $editedAt,
                    'updated_at' => $editedAt,
                ]);

            if ($affected === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan tidak dapat diperbarui.'
                ], 409);
            }

            $message->message = $newMessage;
            $message->edited_at = $editedAt;
            $message->updated_at = $editedAt;

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil diedit.',
                'data' => $this->formatMessage($message, $user->id)
            ]);
        }, 3);
    }

    public function deleteMessageForMe(
        Request $request,
        int $conversationId,
        int $messageId
    ) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.'
            ], 401);
        }

        if (!$this->isMember($conversationId, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak memiliki akses ke percakapan ini.'
            ], 403);
        }

        return DB::transaction(function () use (
            $user,
            $conversationId,
            $messageId
        ) {
            $message = DB::table('messages')
                ->where('id', $messageId)
                ->where('conversation_id', $conversationId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan tidak ditemukan atau sudah dihapus.'
                ], 404);
            }

            if ($message->type === 'system') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan sistem tidak dapat dihapus.'
                ], 422);
            }

            if (!$this->isMember($conversationId, $user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu tidak memiliki akses ke percakapan ini.'
                ], 403);
            }

            DB::table('message_deletions')->insertOrIgnore([
                'message_id' => $messageId,
                'user_id' => $user->id,
                'deleted_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dihapus untuk kamu.',
                'data' => [
                    'id' => $messageId,
                    'messageId' => $messageId,
                    'conversationId' => $conversationId,
                    'userId' => $user->id,
                    'scope' => 'me',
                    'deleted' => true,
                ]
            ]);
        }, 3);
    }

    public function deleteMessageForEveryone(
        Request $request,
        int $conversationId,
        int $messageId
    ) {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu.'
            ], 401);
        }

        if (!$this->isMember($conversationId, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak memiliki akses ke percakapan ini.'
            ], 403);
        }

        return DB::transaction(function () use (
            $user,
            $conversationId,
            $messageId
        ) {
            $message = DB::table('messages')
                ->where('id', $messageId)
                ->where('conversation_id', $conversationId)
                ->lockForUpdate()
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan tidak ditemukan.'
                ], 404);
            }

            if ($message->type === 'system') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan sistem tidak dapat dihapus.'
                ], 422);
            }

            if ($message->sender_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu hanya dapat menghapus pesan milikmu sendiri untuk semua orang.'
                ], 403);
            }

            if ($message->deleted_at !== null) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesan sudah dihapus untuk semua orang.',
                    'data' => [
                        'id' => $messageId,
                        'messageId' => $messageId,
                        'conversationId' => $conversationId,
                        'scope' => 'everyone',
                        'deleted' => true,
                        'deletedAt' => $message->deleted_at,
                    ]
                ]);
            }

            $isDeletedForMe = DB::table('message_deletions')
                ->where('message_id', $messageId)
                ->where('user_id', $user->id)
                ->exists();

            if ($isDeletedForMe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan sudah dihapus dari akunmu.'
                ], 404);
            }

            $deletedAt = now();

            $affected = DB::table('messages')
                ->where('id', $messageId)
                ->where('conversation_id', $conversationId)
                ->where('sender_id', $user->id)
                ->where('type', '!=', 'system')
                ->whereNull('deleted_at')
                ->whereExists(function ($query) use (
                    $conversationId,
                    $user
                ) {
                    $query->selectRaw('1')
                        ->from('conversation_members')
                        ->where('conversation_id', $conversationId)
                        ->where('user_id', $user->id);
                })
                ->update([
                    'deleted_at' => $deletedAt,
                    'updated_at' => $deletedAt,
                ]);

            if ($affected === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesan gagal dihapus.'
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dihapus untuk semua orang.',
                'data' => [
                    'id' => $messageId,
                    'messageId' => $messageId,
                    'conversationId' => $conversationId,
                    'senderId' => $user->id,
                    'scope' => 'everyone',
                    'deleted' => true,
                    'deletedAt' => $deletedAt->toISOString(),
                ]
            ]);
        }, 3);
    }

    private function isMember(
        int $conversationId,
        int $userId
    ): bool {
        return DB::table('conversation_members')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function formatMessage(
        object $message,
        int $userId
    ): array {
        $isSystem = $message->type === 'system';

        $isOwnMessage = !$isSystem &&
            $message->sender_id == $userId;

        $editedAt = $message->edited_at ?? null;

        return [
            'id' => $message->id,
            'conversationId' => $message->conversation_id,
            'senderId' => $message->sender_id,

            'from' => $isSystem
                ? 'system'
                : ($isOwnMessage ? 'me' : 'them'),

            'type' => $message->type,
            'text' => $message->message,

            'fileName' => $message->file_name ?? null,
            'fileSize' => $message->file_size ?? null,
            'fileType' => $message->file_type ?? null,
            'attachmentId' => $message->attachment_id ?? null,

            'at' => $message->created_at,
            'updatedAt' => $message->updated_at ?? null,
            'editedAt' => $editedAt,
            'deletedAt' => $message->deleted_at ?? null,

            'isEdited' => $editedAt !== null,
            'isOwnMessage' => $isOwnMessage,

            'canEdit' => $isOwnMessage &&
                $message->type === 'text',

            'canDeleteForMe' => !$isSystem,

            'canDeleteForEveryone' => $isOwnMessage,
        ];
    }
}