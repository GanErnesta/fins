
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Verifikasi Berhasil | FINS</title>

    <!-- CSS VERIFICATION -->
    <link
        rel="stylesheet"
        href="{{ asset('css/verification.css') }}"
    >
</head>

<body>

<main class="verification-layout result-layout">

    <!-- PANEL KIRI -->
    <section class="result-panel">

        <!-- LOGO FINS -->
        <a
            class="brand-logo"
            href="{{ route('signup') }}"
            aria-label="FINS"
        >
            <span>F</span>
            <small>UB</small>
        </a>

        <!-- KONTEN BERHASIL -->
        <div class="result-content">

            <!-- ICON CENTANG -->
            <div
                class="result-icon success-icon"
                aria-hidden="true"
            >
                ✓
            </div>

            <!-- JUDUL -->
            <h1>Verifikasi Berhasil</h1>

            <!-- DESKRIPSI -->
            <p>
                Akun kamu telah berhasil diverifikasi.
                <br>
                Sekarang kamu bisa melanjutkan ke FINS.
            </p>

            <!-- TOMBOL LOGIN -->
            <a
                class="primary-button button-link"
                href="{{ route('login') }}"
            >
                Back to Sign In
            </a>

        </div>

    </section>

    <!-- PANEL KANAN -->
    <section class="verification-brand-panel result-brand-panel">

        <div class="brand-copy">

            <p class="eyebrow">
                SELAMAT, KAMU SIAP!
            </p>

            <h2>
                Verifikasi Berhasil!
            </h2>

            <p>
                Akunmu sudah siap digunakan.
                Yuk, mulai temukan peluang dan
                koneksi baru di FINS.
            </p>

        </div>

        <!-- ILUSTRASI -->
        <img
            src="{{ asset('assets/images/fins-login.png') }}"
            alt="Ilustrasi keberhasilan verifikasi"
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
