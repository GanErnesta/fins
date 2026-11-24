<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(
        Request $request,
        SupabaseAuthService $supabase
    ) {
        $data = $request->validate([
            'nim' => ['required', 'digits_between:1,20'],
            'password' => ['required', 'string'],
        ]);

        $failure = fn () => back()->withErrors([
            'nim' => 'NIM atau password salah. Jika akun lama belum dimigrasikan, hubungi pengelola FINS.',
        ])->onlyInput('nim');

        $user = User::query()
            ->where('nim', $data['nim'])
            ->first();

        if (!$user || !$user->auth_user_id) {
            return $failure();
        }

        try {
            // Supabase Auth memvalidasi email dan password.
            $result = $supabase->signIn(
                $user->email,
                $data['password']
            );
        } catch (Throwable $e) {
            Log::warning('Supabase login gagal', [
                'reason' => class_basename($e),
                'http_status' => $e->getCode(),
            ]);

            return $failure();
        }

        // Pastikan akun Supabase sesuai dengan akun FINS.
        if (
            ($result['user']['id'] ?? null)
            !== $user->auth_user_id
        ) {
            Log::warning(
                'Supabase Auth UUID tidak sama dengan akun FINS.',
                ['user_id' => $user->id]
            );

            return $failure();
        }

        // Pastikan token tersedia.
        $accessToken = $result['access_token'] ?? null;
        $refreshToken = $result['refresh_token'] ?? null;
        $expiresIn = (int) ($result['expires_in'] ?? 3600);

        if (
            !is_string($accessToken) ||
            $accessToken === '' ||
            !is_string($refreshToken) ||
            $refreshToken === ''
        ) {
            Log::warning(
                'Supabase Auth tidak mengembalikan token login yang lengkap.',
                ['user_id' => $user->id]
            );

            return $failure();
        }

        // Login Laravel untuk sesi website.
        Auth::login($user);

        // Regenerasi session untuk mencegah session fixation.
        $request->session()->regenerate();

        // Simpan token Supabase di session Laravel (server-side).
        $request->session()->put([
            'supabase_access_token' => $accessToken,
            'supabase_refresh_token' => $refreshToken,
            'supabase_token_expires_at' => now()
                ->addSeconds(max(1, $expiresIn - 60))
                ->timestamp,
        ]);

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        // Hapus seluruh session, termasuk token Supabase.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
