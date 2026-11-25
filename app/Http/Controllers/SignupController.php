<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Throwable;

class SignupController extends Controller
{
    private function facultyMap(): array
    {
        return config('fins_academics.faculties', []);
    }

    private function faculties(): array
    {
        return array_keys($this->facultyMap());
    }

    private function programs(): array
    {
        return array_values(
            array_unique(
                array_merge([], ...array_values($this->facultyMap()))
            )
        );
    }

    public function showSignup(Request $request)
    {
        $old = $request->session()->get('signup_form', [
            'nama' => '',
            'nim' => '',
            'fakultas' => '',
            'prodi' => '',
            'email' => '',
        ]);

        $step = (
            $request->query('step') == 2 &&
            !empty($old['nama']) &&
            !empty($old['nim'])
        ) ? 2 : 1;

        return view('signup', [
            'step' => $step,
            'old' => $old,
            'faculties' => $this->faculties(),
            'programs' => $this->programs(),
        ]);
    }

    public function step1(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nim' => [
                'required',
                'regex:/^[0-9]{10,15}$/',
                Rule::unique('users', 'nim')
            ],
            'fakultas' => [
                'required',
                Rule::in($this->faculties())
            ],
            'prodi' => [
                'required',
                Rule::in(
                    $this->facultyMap()[$request->input('fakultas')] ?? []
                )
            ],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.regex' => 'NIM harus terdiri dari 10–15 angka.',
            'nim.unique' => 'NIM sudah terdaftar.',
            'fakultas.required' => 'Silakan pilih fakultas.',
            'fakultas.in' => 'Fakultas tidak valid.',
            'prodi.required' => 'Silakan pilih program studi.',
            'prodi.in' => 'Program studi tidak sesuai dengan fakultas yang dipilih.',
        ]);

        $request->session()->forget([
            'signup_password_hash',
            'verification_code',
            'verification_code_hash',
            'verification_expires',
            'verification_attempts',
            'account_verified',
        ]);

        $request->session()->put('signup_form', $validated);

        return redirect()->route('signup', ['step' => 2]);
    }

    public function step2(Request $request)
    {
        $signup = $request->session()->get('signup_form');

        if (
            !is_array($signup) ||
            empty($signup['nama']) ||
            empty($signup['nim'])
        ) {
            return redirect()->route('signup')->withErrors([
                'signup' => 'Lengkapi data diri terlebih dahulu.'
            ]);
        }

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                'ends_with:@student.ub.ac.id',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.ends_with' => 'Gunakan email mahasiswa UB (@student.ub.ac.id).',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
        ]);

        $email = strtolower(trim($validated['email']));
        $rateKey = 'signup-otp:' . hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return back()->withErrors([
                'email' => 'Terlalu banyak permintaan OTP. Coba lagi dalam beberapa menit.'
            ])->onlyInput('email');
        }

        $otp = (string) random_int(100000, 999999);

        try {
            Mail::raw(
                "Halo {$signup['nama']},\n\nSelamat datang di FINS!\n\n" .
                "Kode verifikasi akun kamu: {$otp}\n\nKode berlaku selama 5 menit. " .
                "Jangan bagikan kode ini kepada siapa pun.\n\nTim FINS Universitas Brawijaya",
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Kode Verifikasi Akun FINS');
                }
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'email' => 'Gagal mengirim OTP. Periksa konfigurasi SMTP.',
            ])->onlyInput('email');
        }

        $request->session()->put('signup_form', [
            ...$signup,
            'email' => $email
        ]);

        // Hash bcrypt Laravel juga dapat diimpor ke Supabase Auth setelah OTP valid.
        // Tidak menyimpan plaintext password di session atau log.
        $request->session()->put(
            'signup_password_hash',
            Hash::make($validated['password'])
        );

        $request->session()->put([
            'verification_code_hash' => Hash::make($otp),
            'verification_expires' => now()->addMinutes(5)->timestamp,
            'verification_attempts' => 0,
        ]);

        $request->session()->forget([
            'verification_code',
            'account_verified'
        ]);

        RateLimiter::hit($rateKey, 900);

        return redirect()->route('verification');
    }
}