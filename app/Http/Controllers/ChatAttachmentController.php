<?php

namespace App\Http\Controllers;

use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChatAttachmentController extends Controller
{
    public function upload(
        Request $request,
        SupabaseStorageService $storage
    ) {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        $validated = $request->validate([
            'conversation_id' => [
                'required',
                'integer',
                'exists:conversations,id',
            ],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ]);

        $conversationId = $validated['conversation_id'];

        // Hanya anggota percakapan yang boleh mengirim file
        $isMember = DB::table('conversation_members')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();

        abort_unless(
            $isMember,
            403,
            'Kamu bukan anggota percakapan ini.'
        );

        $uploaded = $storage->upload(
            $request->file('file'),
            $conversationId
        );

        try {
            $result = DB::transaction(function () use (
                $conversationId,
                $user,
                $uploaded
            ) {
                $messageId = DB::table('messages')
                    ->insertGetId([
                        'conversation_id' => $conversationId,
                        'sender_id' => $user->id,
                        'message' => $uploaded['file_name'],
                        'type' => str_starts_with(
                            $uploaded['file_type'] ?? '',
                            'image/'
                        ) ? 'image' : 'file',
                        'created_at' => now(),
                        'updated_at' => now(),
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
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                return [
                    'message_id' => $messageId,
                    'attachment_id' => $attachmentId,
                ];
            });
        } catch (Throwable $exception) {
            // Bersihkan file jika penyimpanan database gagal
            try {
                $storage->delete($uploaded['file_path']);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dikirim.',
            'data' => $result,
        ], 201);
    }

    public function download(
        Request $request,
        int $attachmentId,
        SupabaseStorageService $storage
    ) {
        $user = $request->user();

        abort_unless($user, 401);

        $attachment = DB::table('message_attachments as a')
            ->join('messages as m', 'm.id', '=', 'a.message_id')
            ->join(
                'conversation_members as cm',
                'cm.conversation_id',
                '=',
                'm.conversation_id'
            )
            ->where('a.id', $attachmentId)
            ->where('cm.user_id', $user->id)
            ->whereNull('m.deleted_at')
            ->select('a.*')
            ->first();

        abort_unless(
            $attachment,
            404,
            'Lampiran tidak ditemukan.'
        );

        abort_unless(
            $attachment->disk === 'supabase' &&
            $attachment->bucket === 'chat-attachments',
            404
        );

        return redirect()->away(
            $storage->signedUrl($attachment->file_path, 60)
        );
    }
}
