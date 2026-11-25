<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PartnerInvitationController extends Controller
{
    // User A mengirim ajakan kolaborasi
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

        // Cegah ajakan pending yang sama dikirim berulang
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

    // User B melihat ajakan yang diterima
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
                'pi.created_at'
            )
            ->orderByDesc('pi.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invitations
        ]);
    }

    // User A melihat status ajakan yang dikirim
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
                'pi.created_at'
            )
            ->orderByDesc('pi.created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invitations
        ]);
    }

    // User B menerima atau menolak ajakan
    public function respond(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['accepted', 'rejected'])
            ]
        ]);

        $updated = DB::table('partner_invitations')
            ->where('id', $id)
            ->where('receiver_id', $request->user()->id)
            ->where('status', 'pending')
            ->update([
                'status' => $validated['status'],
                'updated_at' => now()
            ]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Ajakan tidak ditemukan atau sudah direspons.'
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => $validated['status'] === 'accepted'
                ? 'Ajakan kolaborasi diterima.'
                : 'Ajakan kolaborasi ditolak.',
            'data' => [
                'id' => $id,
                'status' => $validated['status']
            ]
        ]);
    }
}
