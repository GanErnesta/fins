
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Verifikasi Akun | FINS</title>

    <link rel="stylesheet"
          href="{{ asset('css/verification.css') }}">
</head>
<body>

<main class="verification-layout">

    <!-- PANEL FORM VERIFIKASI -->
    <section class="verification-form-panel">

        <a class="brand-logo"
           href="{{ route('signup') }}"
           aria-label="FINS">
            <span>F</span><small>UB</small>
        </a>

        <div class="verification-content">

            <h1>
                Masukkan kode<br>
                verifikasi
            </h1>

            <p class="verification-subtitle">
                Kami telah mengirimkan kode verifikasi
                ke email Anda.
            </p>

            <p class="verification-email">
                {{ session('signup_form.email', '') }}
            </p>

            @if (session('resent'))
                <p class="notice">
                    Kode verifikasi demo berhasil diperbarui.
                </p>
            @endif

            @if ($errors->any())
                <div class="error-message" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- FORM OTP -->
            <form
                id="verificationForm"
                method="POST"
                action="{{ route('verification.verify') }}"
            >
                @csrf

                <div
                    class="otp-inputs"
                    aria-label="Kode OTP 6 digit"
                >
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

                <input
                    type="hidden"
                    name="otp"
                    id="otpValue"
                >

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

                <button
                    class="primary-button"
                    type="submit"
                    id="verifyButton"
                >
                    Verification Account
                </button>

            </form>

            <!-- FORM TERPISAH UNTUK KIRIM ULANG OTP -->
            <form
                id="resendForm"
                action="{{ route('verification.resend') }}"
                method="POST"
                hidden
            >
                @csrf
            </form>

            <a
                class="back-link"
                href="{{ route('login') }}"
            >
                ← Kembali ke sign in
            </a>

            @if (app()->environment('local'))
                <p class="demo-hint">
                    Mode demo lokal: gunakan kode
                    <strong>123456</strong>.
                </p>
            @endif

        </div>
    </section>

    <!-- PANEL ILUSTRASI -->
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

        <img
            src="{{ asset('assets/images/fins-login.png') }}"
            alt="Ilustrasi verifikasi akun"
            class="brand-illustration"
        >

        <p class="brand-footer">
            FINS Universitas Brawijaya
        </p>

    </section>

</main>

<script src="{{ asset('js/verification.js') }}"></script>

</body>
</html>
