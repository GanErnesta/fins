<?php

namespace App\Http\Controllers;

use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatAttachmentController extends Controller
{
    public function upload(
        Request $request,
        SupabaseStorageService $storage
    ) {
        $startTime = microtime(true);

        $user = $request->user();

        abort_unless(
            $user,
            401,
            'Silakan login terlebih dahulu.'
        );

        $validated = $request->validate([
            'conversation_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ]);

        $conversationId = (int) $validated['conversation_id'];

        $file = $request->file('file');

        $isMember = DB::table('conversation_members')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless(
            $isMember,
            403,
            'Kamu bukan anggota percakapan ini.'
        );

        Log::info('[FINS Chat] Upload dimulai', [
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'file_size' => $file->getSize(),
            'elapsed_ms' => round(
                (microtime(true) - $startTime) * 1000
            ),
        ]);

        $storageStartTime = microtime(true);

        try {
            $uploaded = $storage->upload(
                $file,
                $conversationId
            );
        } catch (Throwable $exception) {
            Log::error('[FINS Chat] Storage upload gagal', [
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
                'exception' => get_class($exception),
                'duration_ms' => round(
                    (microtime(true) - $storageStartTime) * 1000
                ),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah file ke penyimpanan.',
            ], 502);
        }

        Log::info('[FINS Chat] Storage upload selesai', [
            'conversation_id' => $conversationId,
            'duration_ms' => round(
                (microtime(true) - $storageStartTime) * 1000
            ),
        ]);

        $messageType = str_starts_with(
            $uploaded['file_type'] ?? '',
            'image/'
        ) ? 'image' : 'file';

        $timestamp = now();

        $databaseStartTime = microtime(true);

        try {
            $result = DB::transaction(function () use (
                $conversationId,
                $user,
                $uploaded,
                $messageType,
                $timestamp
            ) {
                $isStillMember = DB::table('conversation_members')
                    ->where('conversation_id', $conversationId)
                    ->where('user_id', $user->id)
                    ->exists();

                abort_unless(
                    $isStillMember,
                    403,
                    'Akses percakapan tidak tersedia.'
                );

                $messageId = DB::table('messages')
                    ->insertGetId([
                        'conversation_id' => $conversationId,
                        'sender_id' => $user->id,
                        'message' => $uploaded['file_name'],
                        'type' => $messageType,
                        'edited_at' => null,
                        'deleted_at' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                $attachmentId = DB::table('message_attachments')
                    ->insertGetId([
                        'message_id' => $messageId,
                        'file_name' => $uploaded['file_name'],
                        'file_path' => $uploaded['file_path'],
                        'file_type' => $uploaded['file_type'],
                        'file_size' => $uploaded['file_size'],
                        'disk' => $uploaded['disk'],
                        'bucket' => $uploaded['bucket'],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                return [
                    'message_id' => (int) $messageId,
                    'attachment_id' => (int) $attachmentId,
                    'conversation_id' => $conversationId,

                    'message' => [
                        'id' => (int) $messageId,
                        'conversationId' => $conversationId,
                        'senderId' => (int) $user->id,
                        'from' => 'me',
                        'type' => $messageType,
                        'text' => $uploaded['file_name'],
                        'fileName' => $uploaded['file_name'],
                        'fileSize' => (int) $uploaded['file_size'],
                        'fileType' => $uploaded['file_type'],
                        'attachmentId' => (int) $attachmentId,

                        'downloadUrl' =>
                            '/chat/attachments/' .
                            $attachmentId .
                            '/download',

                        'at' => $timestamp->toIso8601String(),
                        'updatedAt' => $timestamp->toIso8601String(),
                        'editedAt' => null,
                        'deletedAt' => null,
                        'isEdited' => false,
                        'isOwnMessage' => true,
                        'canEdit' => false,
                        'canDeleteForMe' => true,
                        'canDeleteForEveryone' => true,
                    ],
                ];
            }, 3);

        } catch (Throwable $exception) {
            Log::error('[FINS Chat] Database upload gagal', [
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
                'exception' => get_class($exception),
                'duration_ms' => round(
                    (microtime(true) - $databaseStartTime) * 1000
                ),
            ]);

            try {
                $storage->delete(
                    $uploaded['file_path']
                );
            } catch (Throwable $cleanupException) {
                Log::warning('[FINS Chat] Cleanup file gagal', [
                    'conversation_id' => $conversationId,
                    'exception' => get_class($cleanupException),
                ]);
            }

            throw $exception;
        }

        Log::info('[FINS Chat] Database upload selesai', [
            'message_id' => $result['message_id'],
            'attachment_id' => $result['attachment_id'],
            'duration_ms' => round(
                (microtime(true) - $databaseStartTime) * 1000
            ),
        ]);

        $totalDuration = round(
            (microtime(true) - $startTime) * 1000
        );

        Log::info('[FINS Chat] Upload berhasil', [
            'conversation_id' => $conversationId,
            'message_id' => $result['message_id'],
            'attachment_id' => $result['attachment_id'],
            'total_duration_ms' => $totalDuration,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dikirim.',
            'data' => $result,
        ], 201)->header(
            'Cache-Control',
            'no-store, private'
        );
    }

    public function download(
        Request $request,
        int $attachmentId,
        SupabaseStorageService $storage
    ) {
        $user = $request->user();

        abort_unless(
            $user,
            401,
            'Silakan login terlebih dahulu.'
        );

        $attachment = DB::table('message_attachments as a')
            ->join(
                'messages as m',
                'm.id',
                '=',
                'a.message_id'
            )
            ->join(
                'conversation_members as cm',
                'cm.conversation_id',
                '=',
                'm.conversation_id'
            )
            ->where('a.id', $attachmentId)
            ->where('cm.user_id', $user->id)
            ->whereNull('m.deleted_at')
            ->whereNotExists(function ($query) use ($user) {
                $query->selectRaw('1')
                    ->from('message_deletions as md')
                    ->whereColumn('md.message_id', 'm.id')
                    ->where('md.user_id', $user->id);
            })
            ->select('a.*')
            ->first();

        abort_unless(
            $attachment,
            404,
            'Lampiran tidak ditemukan atau sudah dihapus.'
        );

        abort_unless(
            $attachment->disk === 'supabase' &&
            $attachment->bucket === 'chat-attachments',
            404,
            'Penyimpanan lampiran tidak tersedia.'
        );

        try {
            $signedUrl = $storage->signedUrl(
                $attachment->file_path,
                60
            );
        } catch (Throwable $exception) {
            Log::error('[FINS Chat] Signed URL gagal', [
                'attachment_id' => $attachmentId,
                'user_id' => $user->id,
                'exception' => get_class($exception),
            ]);

            abort(
                502,
                'Gagal membuka lampiran. Silakan coba lagi.'
            );
        }

        return redirect()
            ->away($signedUrl)
            ->header(
                'Cache-Control',
                'no-store, private'
            );
    }
}