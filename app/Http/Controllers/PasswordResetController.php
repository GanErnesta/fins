<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('forgot-password');
    }

    public function sendLink(Request $request, SupabaseAuthService $supabase): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));
        $message = 'Jika email terdaftar di FINS, link reset password akan dikirim. Periksa inbox dan spam.';

        // Jangan membocorkan apakah akun ada. Akun lama yang belum migrasi
        // memang belum bisa menggunakan fitur pemulihan Supabase Auth.
        $user = User::query()->where('email', $email)->first();
        if (!$user || !$user->auth_user_id) {
            return back()->with('status', $message);
        }

        try {
            $redirectTo = rtrim(config('app.url'), '/') . '/reset-password';
            // Supabase membuat token + mengirim email recovery.
            $supabase->sendRecovery($email, $redirectTo);
        } catch (Throwable $exception) {
            Log::error('FINS: email recovery Supabase Auth gagal', [
                'reason' => class_basename($exception),
                'http_status' => $exception->getCode(),
            ]);
            return back()->withErrors([
                'email' => 'Layanan email reset password sedang bermasalah. Coba lagi nanti.',
            ])->onlyInput('email');
        }
        return back()->with('status', $message);
    }

    public function resetForm(): View
    {
        // Supabase redirect membawa access_token di URL fragment (#...),
        // bukan token Laravel di /reset-password/{token}.
        return view('reset-password');
    }

    public function resetPassword(Request $request, SupabaseAuthService $supabase): RedirectResponse
    {
        $data = $request->validate([
            'access_token' => ['required', 'string', 'max:8192'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        try {
            // Verifikasi token lewat Supabase; jangan percaya email/id dari form.
            $identity = $supabase->userFromToken($data['access_token']);
            $authId = $identity['id'] ?? null;
            $email = strtolower((string) ($identity['email'] ?? ''));
            $user = ($authId && $email) ? User::query()
                ->where('auth_user_id', $authId)->where('email', $email)->first() : null;
            if (!$user) {
                return redirect()->route('password.request')->withErrors([
                    'email' => 'Akun FINS tidak cocok. Silakan hubungi pengelola.',
                ]);
            }

            // Hanya token Supabase sah yang diizinkan mengubah password.
            $supabase->changePassword($data['access_token'], $data['password']);

            // Samakan hash lokal karena kolom password lama tetap ada di users.
            // Login FINS tetap mengautentikasi password lewat Supabase Auth.
            $user->forceFill([
                'password' => Hash::make($data['password']),
                'remember_token' => Str::random(60),
            ])->save();
        } catch (Throwable $exception) {
            Log::warning('FINS: perubahan password Supabase gagal', [
                'reason' => class_basename($exception),
                'http_status' => $exception->getCode(),
            ]);
            return redirect()->route('password.request')->withErrors([
                'email' => 'Link reset mungkin kedaluwarsa atau tidak valid. Minta tautan reset baru.',
            ]);
        }
        return redirect()->route('login')->with('status',
            'Password berhasil diperbarui. Silakan login memakai password baru.');
    }
}
