<?php

namespace App\Http\Controllers;

use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class RealtimeController extends Controller
{
    public function token(
        Request $request,
        SupabaseAuthService $supabase
    ): JsonResponse {

        $user = $request->user();

        // Pastikan pengguna sudah login.
        if (!$user || !$user->auth_user_id) {
            return $this->response([
                'message' => 'Pengguna belum terautentikasi.',
            ], 401);
        }

        $session = $request->session();

        $accessToken = $session->get('supabase_access_token');
        $refreshToken = $session->get('supabase_refresh_token');
        $expiresAt = (int) $session->get(
            'supabase_token_expires_at',
            0
        );

        // Pastikan session Supabase tersedia.
        if (!$accessToken || !$refreshToken) {
            return $this->response([
                'message' => 'Session Supabase tidak tersedia. Silakan login ulang.',
            ], 401);
        }

        // Refresh token jika hampir kedaluwarsa.
        if (now()->timestamp >= $expiresAt) {

            try {
                $result = $supabase->refreshSession(
                    $refreshToken
                );

                // Pastikan token tetap milik akun yang sama.
                if (
                    ($result['user']['id'] ?? null)
                    !== $user->auth_user_id
                ) {
                    return $this->response([
                        'message' => 'Identitas Supabase tidak sesuai.',
                    ], 401);
                }

                $newAccessToken = $result['access_token'] ?? null;
                $newRefreshToken = $result['refresh_token'] ?? null;
                $expiresIn = (int) ($result['expires_in'] ?? 3600);

                if (
                    !is_string($newAccessToken) ||
                    $newAccessToken === '' ||
                    !is_string($newRefreshToken) ||
                    $newRefreshToken === ''
                ) {
                    return $this->response([
                        'message' => 'Token Supabase tidak lengkap.',
                    ], 401);
                }

                $accessToken = $newAccessToken;
                $refreshToken = $newRefreshToken;

                $expiresAt = now()
                    ->addSeconds(max(1, $expiresIn - 60))
                    ->timestamp;

                // Simpan token terbaru di session Laravel.
                $session->put([
                    'supabase_access_token' => $accessToken,
                    'supabase_refresh_token' => $refreshToken,
                    'supabase_token_expires_at' => $expiresAt,
                ]);

            } catch (Throwable $e) {

                Log::warning('Refresh token Supabase gagal.', [
                    'user_id' => $user->id,
                    'reason' => class_basename($e),
                    'http_status' => $e->getCode(),
                ]);

                return $this->response([
                    'message' => 'Session Realtime tidak dapat diperbarui.',
                ], 503);
            }
        }

        // Hanya kirim konfigurasi publik dan access token.
        return $this->response([
            'user_id' => $user->id,
            'supabase_url' => rtrim(
                (string) config('fins_supabase_auth.url'),
                '/'
            ),
            'supabase_public_key' => config(
                'fins_supabase_auth.public_key'
            ),
            'access_token' => $accessToken,
            'expires_at' => $expiresAt,
        ]);
    }

    private function response(
        array $data,
        int $status = 200
    ): JsonResponse {
        return response()
            ->json($data, $status)
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }
}