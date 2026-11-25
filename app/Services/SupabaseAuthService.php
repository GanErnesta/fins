<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SupabaseAuthService
{
    private function baseUrl(): string
    {
        $base = rtrim(
            (string) config('fins_supabase_auth.url'),
            '/'
        );

        if ($base === '' || !str_starts_with($base, 'https://')) {
            throw new RuntimeException(
                'SUPABASE_PROJECT_URL belum disetel menjadi URL HTTPS root proyek.'
            );
        }

        return $base . '/auth/v1';
    }

    private function key(bool $admin): string
    {
        $value = trim((string) config(
            $admin
                ? 'fins_supabase_auth.admin_key'
                : 'fins_supabase_auth.public_key'
        ));

        if ($value === '') {
            throw new RuntimeException(
                $admin
                    ? 'Kunci admin Supabase belum diatur.'
                    : 'Kunci publik Supabase belum diatur.'
            );
        }

        return $value;
    }

    private function headers(
        bool $admin = false,
        ?string $accessToken = null
    ): array {
        $key = $this->key($admin);

        $headers = [
            'apikey' => $key,
            'Accept' => 'application/json',
        ];

        if ($accessToken) {
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        } elseif ($admin && substr_count($key, '.') === 2) {
            $headers['Authorization'] = 'Bearer ' . $key;
        }

        return $headers;
    }

    private function send(
        string $method,
        string $endpoint,
        array $payload = [],
        bool $admin = false,
        ?string $token = null,
        array $query = []
    ): array {
        $url = $this->baseUrl() . $endpoint;

        $client = Http::acceptJson()
            ->withHeaders($this->headers($admin, $token))
            ->timeout(
                (int) config('fins_supabase_auth.timeout', 15)
            );

        if ($query) {
            $client = $client->withQueryParameters($query);
        }

        $options = $payload
            ? ['json' => $payload]
            : [];

        $response = $client->send(
            $method,
            $url,
            $options
        );

        $result = $response->json();

        if (!$response->successful()) {

            $code = is_array($result)
                ? ($result['code'] ?? $result['error_code'] ?? 'unknown')
                : 'unknown';

            Log::warning('Supabase Auth request gagal.', [
                'endpoint' => $endpoint,
                'http_status' => $response->status(),
                'error_code' => $code,
            ]);

            throw new RuntimeException(
                'Supabase Auth gagal memproses permintaan (HTTP '
                . $response->status() . ').',
                $response->status()
            );
        }

        return is_array($result) ? $result : [];
    }

    // ================= CREATE USER =================

    public function createVerifiedUserFromHash(
        string $email,
        string $hash,
        array $metadata = []
    ): string {
        if (!preg_match('/^\$(2[aby]\$|argon2(?:i|id)\$)/', $hash)) {
            throw new RuntimeException(
                'Hash password tidak kompatibel untuk impor Supabase Auth.'
            );
        }

        $result = $this->send(
            'POST',
            '/admin/users',
            [
                'email' => $email,
                'password_hash' => $hash,
                'email_confirm' => true,
                'user_metadata' => $metadata,
            ],
            true
        );

        $id = $result['id']
            ?? $result['user']['id']
            ?? null;

        if (
            !is_string($id) ||
            !preg_match('/^[0-9a-fA-F-]{36}$/', $id)
        ) {
            throw new RuntimeException(
                'Supabase Auth tidak mengembalikan ID pengguna yang valid.'
            );
        }

        return $id;
    }

    // ================= DELETE USER =================

    public function deleteUser(string $authUserId): void
    {
        $this->send(
            'DELETE',
            '/admin/users/' . rawurlencode($authUserId),
            [],
            true
        );
    }

    // ================= LOGIN =================

    public function signIn(
        string $email,
        string $password
    ): array {
        return $this->send(
            'POST',
            '/token',
            [
                'email' => $email,
                'password' => $password,
            ],
            false,
            null,
            [
                'grant_type' => 'password',
            ]
        );
    }

    // ================= REFRESH SESSION =================
    // Digunakan untuk menjaga autentikasi Supabase Realtime.

    public function refreshSession(string $refreshToken): array
    {
        if (trim($refreshToken) === '') {
            throw new RuntimeException(
                'Refresh token Supabase tidak tersedia.'
            );
        }

        return $this->send(
            'POST',
            '/token',
            [
                'refresh_token' => $refreshToken,
            ],
            false,
            null,
            [
                'grant_type' => 'refresh_token',
            ]
        );
    }

    // ================= FORGOT PASSWORD =================

    public function sendRecovery(
        string $email,
        string $redirectUrl
    ): void {
        $this->send(
            'POST',
            '/recover',
            [
                'email' => $email,
            ],
            false,
            null,
            [
                'redirect_to' => $redirectUrl,
            ]
        );
    }

    // ================= GET USER FROM TOKEN =================

    public function userFromToken(string $accessToken): array
    {
        return $this->send(
            'GET',
            '/user',
            [],
            false,
            $accessToken
        );
    }

    // ================= RESET PASSWORD =================

    public function changePassword(
        string $accessToken,
        string $newPassword
    ): void {
        $this->send(
            'PUT',
            '/user',
            [
                'password' => $newPassword,
            ],
            false,
            $accessToken
        );
    }
}