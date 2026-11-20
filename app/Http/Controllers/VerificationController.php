<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VerificationController extends Controller
{
    // Cek data registrasi sementara
    private function hasSignup(Request $request): bool
    {
        return $request->session()->has('signup_form.email')
            && $request->session()->has('signup_password_hash');
    }

    // Menampilkan halaman OTP
    public function show(Request $request)
    {
        if (!$this->hasSignup($request)) {
            return redirect()->route('signup');
        }

        return view('verification');
    }

    // Memproses OTP dan menyimpan akun
    public function verify(Request $request)
    {
        if (!$this->hasSignup($request)) {
            return redirect()->route('signup');
        }

        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus 6 angka.',
        ]);

        $session = $request->session();

        $key = 'otp-verify:' . $session->getId();

        $attempts = (int) $session->get(
            'verification_attempts',
            0
        );

        if (
            $attempts >= 5 ||
            RateLimiter::tooManyAttempts($key, 5)
        ) {
            return redirect()->route('verification-failed');
        }

        $expiresAt = (int) $session->get(
            'verification_expires',
            0
        );

        if (now()->timestamp >= $expiresAt) {
            return back()->withErrors([
                'otp' => 'Kode OTP sudah kedaluwarsa. Kirim ulang kode.',
            ]);
        }

        // OTP hanya berasal dari email
        $otpHash = $session->get('verification_code_hash');

        if (!is_string($otpHash) || $otpHash === '') {
            return back()->withErrors([
                'otp' => 'Kode OTP belum tersedia. Kirim ulang kode.',
            ]);
        }

        // Periksa OTP
        if (!Hash::check($validated['otp'], $otpHash)) {
            $attempts++;

            $session->put('verification_attempts', $attempts);

            RateLimiter::hit($key, 300);

            if ($attempts >= 5) {
                return redirect()->route('verification-failed');
            }

            return back()->withErrors([
                'otp' => 'OTP salah. Sisa percobaan: '
                    . (5 - $attempts),
            ]);
        }

        // OTP BENAR
        $signup = $session->get('signup_form', []);
        $passwordHash = $session->get('signup_password_hash');

        // Validasi ulang data sebelum disimpan
        $validator = Validator::make([
            'nama' => $signup['nama'] ?? null,
            'nim' => $signup['nim'] ?? null,
            'fakultas' => $signup['fakultas'] ?? null,
            'prodi' => $signup['prodi'] ?? null,
            'email' => $signup['email'] ?? null,
        ], [
            'nama' => ['required', 'string', 'max:255'],
            'nim' => [
                'required',
                'regex:/^[0-9]{10,15}$/',
                Rule::unique('users', 'nim'),
            ],
            'fakultas' => ['required', 'string', 'max:255'],
            'prodi' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'ends_with:@student.ub.ac.id',
                Rule::unique('users', 'email'),
            ],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('signup')
                ->withErrors($validator);
        }

        if (!is_string($passwordHash) || $passwordHash === '') {
            return redirect()->route('signup')->withErrors([
                'password' => 'Data password tidak tersedia.',
            ]);
        }

        // SIMPAN USER KE SUPABASE POSTGRESQL
        User::create([
            'name' => $signup['nama'],
            'nim' => $signup['nim'],
            'fakultas' => $signup['fakultas'],
            'prodi' => $signup['prodi'],
            'email' => $signup['email'],
            'password' => $passwordHash,
            'email_verified_at' => now(),
        ]);

        // Bersihkan OTP dan data sementara
        RateLimiter::clear($key);

        $session->forget([
            'signup_form',
            'signup_password_hash',
            'verification_code',
            'verification_code_hash',
            'verification_expires',
            'verification_attempts',
        ]);

        $session->put('account_verified', true);

        return redirect()->route('verification-success');
    }

    // Kirim ulang kode OTP ke email
    public function resend(Request $request)
    {
        if (!$this->hasSignup($request)) {
            return redirect()->route('signup');
        }

        $session = $request->session();

        $verifyKey = 'otp-verify:' . $session->getId();
        $attempts = (int) $session->get(
            'verification_attempts',
            0
        );

        if (
            $attempts >= 5 ||
            RateLimiter::tooManyAttempts($verifyKey, 5)
        ) {
            return redirect()->route('verification-failed');
        }

        $email = $session->get('signup_form.email');

        $resendKey = 'otp-resend:' .
            hash('sha256', strtolower($email));

        if (RateLimiter::tooManyAttempts($resendKey, 1)) {
            return back()->withErrors([
                'otp' => 'Tunggu 60 detik sebelum kirim ulang.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        try {
            Mail::raw(
                "Halo!\n\n" .
                "Kode verifikasi FINS terbaru kamu adalah:\n\n" .
                "{$otp}\n\n" .
                "Kode berlaku selama 5 menit.\n\n" .
                "Salam,\nTim FINS",
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Kirim Ulang OTP FINS');
                }
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'otp' => 'Gagal mengirim ulang OTP. Coba lagi.',
            ]);
        }

        $session->put([
            'verification_code_hash' => Hash::make($otp),
            'verification_expires' => now()->addMinutes(5)->timestamp,
        ]);

        // Hapus kode demo lama
        $session->forget('verification_code');

        RateLimiter::hit($resendKey, 60);

        return redirect()
            ->route('verification')
            ->with('resent', true);
    }
}
