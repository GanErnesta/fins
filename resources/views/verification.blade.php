<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verifikasi Akun | FINS</title>

    {{-- CSS DENGAN VERSI OTOMATIS --}}
    <link rel="stylesheet"
          href="/css/verification.css?v={{ filemtime(public_path('css/verification.css')) }}">

    {{-- JAVASCRIPT DENGAN VERSI OTOMATIS --}}
    <script
        src="/js/verification.js?v={{ filemtime(public_path('js/verification.js')) }}"
        defer>
    </script>

</head>

<body>

    <main class="verification-layout">

        <!-- ================= PANEL VERIFIKASI ================= -->
        <section class="verification-form-panel">

            <!-- LOGO FINS -->
            <a class="brand-logo"
               href="{{ route('signup', [], false) }}"
               aria-label="FINS - Kembali ke pendaftaran">

                <span>F</span><small>UB</small>

            </a>

            <div class="verification-content">

                <h1>Masukkan kode<br>verifikasi</h1>

                <p class="verification-subtitle">
                    Kami telah mengirimkan kode verifikasi ke email kamu.
                </p>

                <!-- EMAIL PENDAFTAR -->
                <p class="verification-email">
                    {{ session('signup_form.email', '') }}
                </p>

                <!-- NOTIFIKASI KIRIM ULANG -->
                @if (session('resent'))

                    <p class="notice" role="status">
                        Kode verifikasi baru berhasil dikirim ke email kamu.
                    </p>

                @endif

                <!-- PESAN ERROR -->
                @if ($errors->any())

                    <div class="error-message" role="alert">

                        @foreach ($errors->all() as $error)

                            <p>{{ $error }}</p>

                        @endforeach

                    </div>

                @endif

                <!-- ================= FORM VERIFIKASI ================= -->
                <form
                    id="verificationForm"
                    method="POST"
                    action="{{ route('verification.verify', [], false) }}"
                >

                    @csrf

                    <!-- INPUT OTP 6 DIGIT -->
                    <div class="otp-inputs"
                         aria-label="Kode OTP 6 digit">

                        @for ($i = 0; $i < 6; $i++)

                            <input
                                type="text"
                                class="otp-digit"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="1"
                                aria-label="Digit {{ $i + 1 }}"
                                @if ($i === 0) autofocus @endif
                                required
                            >

                        @endfor

                    </div>

                    {{-- verification.js menggabungkan keenam digit ke input ini. --}}
                    <input
                        type="hidden"
                        name="otp"
                        id="otpValue"
                    >

                    <!-- KIRIM ULANG KODE OTP -->
                    <p class="resend-line">

                        Tidak menerima kode?

                        <button
                            type="button"
                            id="resendButton"
                            disabled
                        >

                            Kirim ulang
                            (<span id="countdown">00:58</span>)

                        </button>

                    </p>

                    <!-- TOMBOL VERIFIKASI -->
                    <button
                        class="primary-button"
                        type="submit"
                        id="verifyButton"
                    >

                        Verifikasi Akun

                    </button>

                </form>

                {{-- Tetap terpisah karena tombol resend diatur oleh verification.js. --}}
                <form
                    id="resendForm"
                    action="{{ route('verification.resend', [], false) }}"
                    method="POST"
                    hidden
                >

                    @csrf

                </form>

                <!-- KEMBALI KE LOGIN -->
                <a
                    class="back-link"
                    href="{{ route('login', [], false) }}"
                >

                    ← Kembali ke Login

                </a>

            </div>

        </section>

        <!-- ================= BANNER FINS ================= -->
        <section class="verification-brand-panel">

            <div class="brand-copy">

                <p class="eyebrow">
                    PASTIKAN AKUNMU AMAN
                </p>

                <h2>
                    Satu langkah lagi untuk terhubung.
                </h2>

                <p>
                    Verifikasi akunmu dan lanjutkan
                    perjalananmu bersama FINS.
                </p>

            </div>

            <!-- ILUSTRASI -->
            <img
                src="/assets/images/fins-login.png"
                alt="Ilustrasi verifikasi akun"
                class="brand-illustration"
            >

            <!-- FOOTER -->
            <p class="brand-footer">
                FINS Universitas Brawijaya
            </p>

        </section>

    </main>

</body>
</html>