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
    // ==========================================
    // DATA FAKULTAS
    // ==========================================

   private function faculties(): array
    {
        return [
            'FH',
            'FEB',
            'FIA',
            'FBiPK',
            'FAST',
            'FT',
            'FK',
            'FPIK',
            'FSTeM',
            'FTAB',
            'FISIP',
            'FIB',
            'FKH',
            'FILKOM',
            'FKG',
            'FIKES',
        ];
    }

    // ==========================================
    // DATA PROGRAM STUDI
    // ==========================================

    private function programs(): array
    {
        return [
            // Fakultas Hukum
            'Ilmu Hukum',

            // Fakultas Ekonomi dan Bisnis
            'Akuntansi',
            'Ekonomi Pembangunan',
            'Manajemen',
            'Ekonomi Islam',
            'Kewirausahaan',
            'Ekonomi, Keuangan dan Perbankan',

            // Fakultas Ilmu Administrasi
            'Ilmu Administrasi Negara/Publik',
            'Ilmu Administrasi Niaga/Bisnis',
            'Perpajakan',
            'Ilmu Perpustakaan',
            'Pariwisata',
            'Administrasi Pendidikan',

            // Fakultas Bio-Industri Pertanian dan Kehutanan
            'Agribisnis',
            'Agroekoteknologi',
            'Agribisnis (PSDKU Kediri)',
            'Agroekoteknologi (PSDKU Kediri)',
            'Kehutanan',

            // Fakultas Sains dan Teknologi Peternakan
            'Peternakan',
            'Peternakan (PSDKU Kediri)',

            // Fakultas Teknik
            'Teknik Sipil',
            'Teknik Mesin',
            'Teknik Pengairan',
            'Teknik Elektro',
            'Teknik Industri',
            'Teknik Kimia',
            'Perencanaan Wilayah dan Kota',
            'Arsitektur',

            // Fakultas Kedokteran
            'Kedokteran',
            'Farmasi',
            'Kebidanan',

            // Fakultas Perikanan dan Ilmu Kelautan
            'Agrobisnis Perikanan',
            'Budidaya Perairan',
            'Ilmu Kelautan',
            'Manajemen Sumberdaya Perairan',
            'Pemanfaatan Sumberdaya Perikanan',
            'Teknologi Hasil Perikanan',
            'Akuakultur (PSDKU Kediri)',
            'Sosial Ekonomi Perikanan (PSDKU Kediri)',

            // Fakultas Sains, Teknologi, dan Matematika
            'Biologi',
            'Fisika',
            'Kimia',
            'Matematika',
            'Statistika',
            'Teknik Geofisika',
            'Instrumentasi',
            'Ilmu Aktuaria',

            // Fakultas Teknologi Agroindustri dan Biosistem
            'Ilmu dan Teknologi Pangan',
            'Teknologi Industri Pertanian',
            'Teknik Lingkungan',
            'Bioteknologi',
            'Teknologi Bioproses',
            'Teknik Pertanian',

            // Fakultas Ilmu Sosial dan Ilmu Politik
            'Hubungan Internasional',
            'Ilmu Komunikasi',
            'Ilmu Politik',
            'Psikologi',
            'Sosiologi',
            'Ilmu Pemerintahan',

            // Fakultas Ilmu Budaya
            'Sastra Inggris',
            'Sastra Jepang',
            'Sastra Cina',
            'Bahasa dan Sastra Perancis',
            'Pendidikan Bahasa dan Sastra Indonesia',
            'Pendidikan Bahasa Inggris',
            'Pendidikan Bahasa Jepang',
            'Antropologi (Sosial)',
            'Seni Rupa Murni',

            // Fakultas Kedokteran Hewan
            'Pendidikan Dokter Hewan',

            // Fakultas Ilmu Komputer
            'Teknik Informatika',
            'Teknik Komputer',
            'Sistem Informasi',
            'Teknologi Informasi',
            'Pendidikan Teknologi Informasi',

            // Fakultas Kedokteran Gigi
            'Kedokteran Gigi',

            // Fakultas Ilmu Kesehatan
            'Keperawatan',
            'Ilmu Gizi',
        ];
    }

    // ==========================================
    // MENAMPILKAN HALAMAN SIGNUP
    // ==========================================

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

    // ==========================================
    // STEP 1: DATA DIRI MAHASISWA
    // ==========================================

    public function step1(Request $request)
    {
        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',
            ],

            'nim' => [
                'required',
                'regex:/^[0-9]{10,15}$/',
                Rule::unique('users', 'nim'),
            ],

            'fakultas' => [
                'required',
                Rule::in($this->faculties()),
            ],

            'prodi' => [
                'required',
                Rule::in($this->programs()),
            ],
        ], [
            'nama.required' =>
                'Nama lengkap wajib diisi.',

            'nim.required' =>
                'NIM wajib diisi.',

            'nim.regex' =>
                'NIM harus terdiri dari 10–15 angka.',

            'nim.unique' =>
                'NIM sudah terdaftar.',

            'fakultas.required' =>
                'Silakan pilih fakultas.',

            'fakultas.in' =>
                'Fakultas tidak valid.',

            'prodi.required' =>
                'Silakan pilih program studi.',

            'prodi.in' =>
                'Program studi tidak valid.',
        ]);

        // Menghapus data verifikasi dari proses lama
        $request->session()->forget([
            'signup_password_hash',
            'verification_code',
            'verification_code_hash',
            'verification_expires',
            'verification_attempts',
            'account_verified',
        ]);

        // Menyimpan sementara data tahap pertama
        $request->session()->put(
            'signup_form',
            $validated
        );

        // Pindah ke tahap kedua
        return redirect()->route('signup', [
            'step' => 2,
        ]);
    }

    // ==========================================
    // STEP 2: EMAIL DAN PASSWORD
    // ==========================================

    public function step2(Request $request)
    {
        // Pastikan tahap pertama sudah selesai
        $signup = $request->session()->get('signup_form');

        if (
            !is_array($signup) ||
            empty($signup['nama']) ||
            empty($signup['nim'])
        ) {
            return redirect()->route('signup')
                ->withErrors([
                    'signup' =>
                        'Lengkapi data diri terlebih dahulu.',
                ]);
        }

        // Validasi email dan password
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
                'confirmed',
            ],
        ], [
            'email.required' =>
                'Email wajib diisi.',

            'email.email' =>
                'Masukkan alamat email yang valid.',

            'email.ends_with' =>
                'Gunakan email mahasiswa UB (@student.ub.ac.id).',

            'email.unique' =>
                'Email sudah terdaftar.',

            'password.required' =>
                'Password wajib diisi.',

            'password.min' =>
                'Password minimal 8 karakter.',

            'password.confirmed' =>
                'Konfirmasi password tidak sesuai.',
        ]);

        // Normalisasi email
        $email = strtolower(trim($validated['email']));

        // Batasi jumlah permintaan OTP
        $rateKey = 'signup-otp:' . hash(
            'sha256',
            $email
        );

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return back()
                ->withErrors([
                    'email' =>
                        'Terlalu banyak permintaan OTP. ' .
                        'Silakan coba lagi dalam beberapa menit.',
                ])
                ->onlyInput('email');
        }

        // ======================================
        // MEMBUAT OTP ACAK
        // ======================================

        $otp = (string) random_int(100000, 999999);

        // ======================================
        // MENGIRIM OTP MELALUI SMTP
        // ======================================

        try {
            Mail::raw(
                "Halo {$signup['nama']},\n\n" .
                "Selamat datang di FINS!\n\n" .
                "Terima kasih telah melakukan pendaftaran.\n\n" .
                "Kode verifikasi akun kamu adalah:\n\n" .
                "{$otp}\n\n" .
                "Kode ini berlaku selama 5 menit.\n" .
                "Jangan bagikan kode ini kepada siapa pun.\n\n" .
                "Jika kamu tidak melakukan pendaftaran, " .
                "abaikan email ini.\n\n" .
                "Salam,\n" .
                "Tim FINS Universitas Brawijaya",
                function ($message) use ($email) {
                    $message
                        ->to($email)
                        ->subject(
                            'Kode Verifikasi Akun FINS'
                        );
                }
            );

        } catch (Throwable $exception) {

            // Catat penyebab error pada log Laravel
            report($exception);

            return back()
                ->withErrors([
                    'email' =>
                        'Gagal mengirim OTP ke email. ' .
                        'Periksa email atau konfigurasi SMTP.',
                ])
                ->onlyInput('email');
        }

        // ======================================
        // MENYIMPAN DATA SEMENTARA
        // ======================================

        // Simpan email bersama data tahap pertama
        $request->session()->put('signup_form', [
            ...$signup,
            'email' => $email,
        ]);

        // Password disimpan dalam bentuk hash
        $request->session()->put(
            'signup_password_hash',
            Hash::make($validated['password'])
        );

        // Simpan HASH OTP, bukan kode aslinya
        $request->session()->put([
            'verification_code_hash' =>
                Hash::make($otp),

            'verification_expires' =>
                now()->addMinutes(5)->timestamp,

            'verification_attempts' => 0,
        ]);

        // Hapus OTP demo dan status verifikasi lama
        $request->session()->forget([
            'verification_code',
            'account_verified',
        ]);

        // Maksimal 3 permintaan dalam 15 menit
        RateLimiter::hit($rateKey, 900);

        // Arahkan ke halaman verifikasi
        return redirect()->route('verification');
    }
}
