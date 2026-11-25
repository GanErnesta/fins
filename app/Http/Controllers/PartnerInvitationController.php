<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PartnerInvitationController extends Controller
{
    // ==========================================
    // 1. KIRIM AJAKAN KOLABORASI
    // ==========================================

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'receiver_id' => [
                'required',
                'integer',
                'exists:users,id'
            ],
            'competition_name' => [
                'required',
                'string',
                'max:150'
            ],
            'role' => [
                'nullable',
                'string',
                'max:100'
            ],
            'message' => [
                'nullable',
                'string',
                'max:2000'
            ]
        ]);

        if ((int) $validated['receiver_id'] === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu tidak bisa mengajak diri sendiri.'
            ], 422);
        }

        $existing = DB::table('partner_invitations')
            ->where('sender_id', $user->id)
            ->where('receiver_id', $validated['receiver_id'])
            ->where('competition_name', $validated['competition_name'])
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Ajakan untuk lomba ini masih menunggu respons.'
            ], 409);
        }

        $id = DB::table('partner_invitations')->insertGetId([
            'sender_id' => $user->id,
            'receiver_id' => $validated['receiver_id'],
            'competition_name' => $validated['competition_name'],
            'role' => $validated['role'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ajakan kolaborasi berhasil dikirim.',
            'data' => [
                'id' => $id,
                'status' => 'pending'
            ]
        ], 201);
    }

    // ==========================================
    // 2. DAFTAR AJAKAN MASUK
    // ==========================================

    public function received(Request $request)
    {
        $invitations = DB::table('partner_invitations as pi')
            ->join('users as u', 'u.id', '=', 'pi.sender_id')
            ->where('pi.receiver_id', $request->user()->id)
            ->select(
                'pi.id',
                'pi.sender_id',
                'u.name as sender_name',
                'pi.competition_name',
                'pi.role',
                'pi.message',
                'pi.status',
                'pi.conversation_id',
                'pi.created_at'
            )
            ->orderByDesc('pi.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invitations
        ]);
    }

    // ==========================================
    // 3. DAFTAR AJAKAN TERKIRIM
    // ==========================================

    public function sent(Request $request)
    {
        $invitations = DB::table('partner_invitations as pi')
            ->join('users as u', 'u.id', '=', 'pi.receiver_id')
            ->where('pi.sender_id', $request->user()->id)
            ->select(
                'pi.id',
                'pi.receiver_id',
                'u.name as receiver_name',
                'pi.competition_name',
                'pi.role',
                'pi.message',
                'pi.status',
                'pi.conversation_id',
                'pi.created_at'
            )
            ->orderByDesc('pi.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invitations
        ]);
    }

    // ==========================================
    // 4. TERIMA ATAU TOLAK AJAKAN
    // ==========================================

    public function respond(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['accepted', 'rejected'])
            ]
        ]);

        $userId = $request->user()->id;

        return DB::transaction(function () use (
            $id,
            $userId,
            $validated
        ) {

            // Kunci data agar tidak direspons dua kali
            $invitation = DB::table('partner_invitations')
                ->where('id', $id)
                ->where('receiver_id', $userId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$invitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ajakan tidak ditemukan atau sudah direspons.'
                ], 409);
            }

            $conversationId = null;

            // Buat chat hanya jika ajakan diterima
            if ($validated['status'] === 'accepted') {

                $conversationId = $this->getOrCreatePrivateConversation(
                    (int) $invitation->sender_id,
                    (int) $invitation->receiver_id
                );
            }

            // Update status dan hubungkan dengan chat
            DB::table('partner_invitations')
                ->where('id', $id)
                ->update([
                    'status' => $validated['status'],
                    'conversation_id' => $conversationId,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => $validated['status'] === 'accepted'
                    ? 'Ajakan kolaborasi diterima.'
                    : 'Ajakan kolaborasi ditolak.',
                'data' => [
                    'id' => $id,
                    'status' => $validated['status'],
                    'conversation_id' => $conversationId,
                    'chat_url' => $conversationId
                        ? '/chat?conversation=' . $conversationId
                        : null
                ]
            ]);

        }, 3);
    }

    // ==========================================
    // 5. BUAT ATAU GUNAKAN CHAT PRIBADI
    // ==========================================

    private function getOrCreatePrivateConversation(
        int $senderId,
        int $receiverId
    ): int {

        $userOne = min($senderId, $receiverId);
        $userTwo = max($senderId, $receiverId);

        // Cegah pembuatan chat bersamaan untuk pasangan sama
        DB::select(
            'SELECT pg_advisory_xact_lock(
                hashtextextended(CAST(? AS text), 0)
            )',
            ["fins-private-chat:{$userOne}:{$userTwo}"]
        );

        // Periksa apakah ruang chat sudah tersedia
        $existing = DB::table('conversations')
            ->where('type', 'private')
            ->where('private_user_one', $userOne)
            ->where('private_user_two', $userTwo)
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $now = now();

        // Buat ruang chat baru
        $conversationId = DB::table('conversations')
            ->insertGetId([
                'type' => 'private',
                'name' => null,
                'created_by' => $senderId,
                'private_user_one' => $userOne,
                'private_user_two' => $userTwo,
                'photo_path' => null,
                'created_at' => $now,
                'updated_at' => $now
            ]);

        // Masukkan kedua pengguna sebagai anggota
        DB::table('conversation_members')->insert([
            [
                'conversation_id' => $conversationId,
                'user_id' => $senderId,
                'role' => 'member',
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'conversation_id' => $conversationId,
                'user_id' => $receiverId,
                'role' => 'member',
                'joined_at' => $now,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ]);

        // Pesan sistem sebagai pembuka percakapan
        DB::table('messages')->insert([
            'conversation_id' => $conversationId,
            'sender_id' => null,
            'message' => 'Kalian telah terhubung melalui ajakan kolaborasi. Yuk, mulai berdiskusi!',
            'type' => 'system',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        return (int) $conversationId;
    }
}