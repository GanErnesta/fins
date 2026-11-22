<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class VerificationController extends Controller
{
    private function hasSignup(Request $request): bool
    {
        return $request->session()->has('signup_form.email')
            && $request->session()->has('signup_password_hash');
    }

    public function show(Request $request)
    {
        if (!$this->hasSignup($request)) return redirect()->route('signup');
        return view('verification');
    }

    public function verify(Request $request, SupabaseAuthService $supabase)
    {
        if (!$this->hasSignup($request)) return redirect()->route('signup');
        $validated = $request->validate(['otp' => ['required', 'digits:6']], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus 6 angka.',
        ]);
        $session = $request->session();
        $key = 'otp-verify:' . $session->getId();
        $attempts = (int) $session->get('verification_attempts', 0);
        if ($attempts >= 5 || RateLimiter::tooManyAttempts($key, 5)) {
            return redirect()->route('verification-failed');
        }
        if (now()->timestamp >= (int) $session->get('verification_expires', 0)) {
            return back()->withErrors(['otp' => 'Kode OTP sudah kedaluwarsa. Kirim ulang kode.']);
        }
        $otpHash = $session->get('verification_code_hash');
        if (!is_string($otpHash) || $otpHash === '') {
            return back()->withErrors(['otp' => 'Kode OTP belum tersedia. Kirim ulang kode.']);
        }
        if (!Hash::check($validated['otp'], $otpHash)) {
            $attempts++;
            $session->put('verification_attempts', $attempts);
            RateLimiter::hit($key, 300);
            if ($attempts >= 5) return redirect()->route('verification-failed');
            return back()->withErrors(['otp' => 'OTP salah. Sisa percobaan: ' . (5 - $attempts)]);
        }

        $signup = $session->get('signup_form', []);
        $passwordHash = $session->get('signup_password_hash');
        $facultyMap = config('fins_academics.faculties', []);
        $validator = Validator::make([
            'nama' => $signup['nama'] ?? null,
            'nim' => $signup['nim'] ?? null,
            'fakultas' => $signup['fakultas'] ?? null,
            'prodi' => $signup['prodi'] ?? null,
            'email' => $signup['email'] ?? null,
        ], [
            'nama' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'regex:/^[0-9]{10,15}$/', Rule::unique('users', 'nim')],
            'fakultas' => ['required', Rule::in(array_keys($facultyMap))],
            'prodi' => ['required', Rule::in($facultyMap[$signup['fakultas'] ?? ''] ?? [])],
            'email' => [
                'required', 'email', 'ends_with:@student.ub.ac.id', Rule::unique('users', 'email'),
            ],
        ]);
        if ($validator->fails()) {
            return redirect()->route('signup')->withErrors($validator);
        }
        if (!is_string($passwordHash) || $passwordHash === '') {
            return redirect()->route('signup')->withErrors(['password' => 'Data password tidak tersedia.']);
        }

        $authUserId = null;
        try {
            // Hanya buat user berstatus email_confirm=true SETELAH OTP email benar.
            $authUserId = $supabase->createVerifiedUserFromHash(
                strtolower($signup['email']), $passwordHash,
                ['nim' => $signup['nim'], 'name' => $signup['nama']]
            );

            // ID integer di public.users tetap dipakai oleh profil, chat, lomba, dll.
            User::create([
                'name' => $signup['nama'],
                'nim' => $signup['nim'],
                'fakultas' => $signup['fakultas'],
                'prodi' => $signup['prodi'],
                'email' => strtolower($signup['email']),
                'password' => $passwordHash,
                'email_verified_at' => now(),
                'auth_user_id' => $authUserId,
            ]);
        } catch (Throwable $exception) {
            // Kompensasi: jika penyimpanan ke users gagal, jangan tinggalkan
            // akun Auth baru tanpa pasangan. Jangan hapus akun yang sudah ada.
            if ($authUserId) {
                try {
                    $supabase->deleteUser($authUserId);
                } catch (Throwable $cleanup) {
                    Log::error('FINS: gagal membersihkan akun Auth baru', [
                        'auth_user_id' => $authUserId,
                        'error_type' => class_basename($cleanup),
                    ]);
                }
            }
            Log::error('FINS: pendaftaran Supabase Auth gagal', [
                'error_type' => class_basename($exception),
                'http_status' => $exception->getCode(),
            ]);
            return back()->withErrors([
                'otp' => 'Verifikasi berhasil, tetapi akun belum bisa disimpan. Periksa konfigurasi Supabase Auth atau hubungi pengelola.',
            ]);
        }

        RateLimiter::clear($key);
        $session->forget([
            'signup_form', 'signup_password_hash', 'verification_code',
            'verification_code_hash', 'verification_expires', 'verification_attempts',
        ]);
        $session->put('account_verified', true);
        return redirect()->route('verification-success');
    }

    public function resend(Request $request)
    {
        if (!$this->hasSignup($request)) return redirect()->route('signup');
        $session = $request->session();
        $verifyKey = 'otp-verify:' . $session->getId();
        if ((int) $session->get('verification_attempts', 0) >= 5
            || RateLimiter::tooManyAttempts($verifyKey, 5)) {
            return redirect()->route('verification-failed');
        }
        $email = $session->get('signup_form.email');
        $resendKey = 'otp-resend:' . hash('sha256', strtolower($email));
        if (RateLimiter::tooManyAttempts($resendKey, 1)) {
            return back()->withErrors(['otp' => 'Tunggu 60 detik sebelum kirim ulang.']);
        }
        $otp = (string) random_int(100000, 999999);
        try {
            Mail::raw(
                "Halo!\n\nKode verifikasi FINS terbaru kamu: {$otp}\n\n" .
                "Kode berlaku selama 5 menit.\n\nSalam, Tim FINS",
                fn ($message) => $message->to($email)->subject('Kirim Ulang OTP FINS')
            );
        } catch (Throwable $exception) {
            report($exception);
            return back()->withErrors(['otp' => 'Gagal mengirim ulang OTP. Coba lagi.']);
        }
        $session->put([
            'verification_code_hash' => Hash::make($otp),
            'verification_expires' => now()->addMinutes(5)->timestamp,
        ]);
        $session->forget('verification_code');
        RateLimiter::hit($resendKey, 60);
        return redirect()->route('verification')->with('resent', true);
    }
}
