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

    public function login(Request $request, SupabaseAuthService $supabase)
    {
        $data = $request->validate([
            'nim' => ['required', 'digits_between:1,20'],
            'password' => ['required', 'string'],
        ]);

        $failure = fn () => back()->withErrors([
            'nim' => 'NIM atau password salah. Jika akun lama belum dimigrasikan, hubungi pengelola FINS.',
        ])->onlyInput('nim');

        $user = User::query()->where('nim', $data['nim'])->first();
        if (!$user || !$user->auth_user_id) {
            return $failure();
        }

        try {
            // Supabase Auth menilai kredensial; Auth Laravel menjaga sesi halaman web.
            $result = $supabase->signIn($user->email, $data['password']);
        } catch (Throwable $e) {
            Log::warning('Supabase login gagal', [
                'reason' => class_basename($e),
                'http_status' => $e->getCode(),
            ]);
            return $failure();
        }

        if (($result['user']['id'] ?? null) !== $user->auth_user_id) {
            Log::warning('Supabase Auth UUID tidak sama dengan akun FINS.', ['user_id' => $user->id]);
            return $failure();
        }

        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
