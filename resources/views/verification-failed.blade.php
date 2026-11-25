<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verifikasi Gagal | FINS</title>

    {{-- CSS DENGAN VERSI OTOMATIS --}}
    <link rel="stylesheet"
          href="/css/verification.css?v={{ filemtime(public_path('css/verification.css')) }}">

</head>

<body>

    <main class="verification-layout result-layout">

        <!-- ================= PANEL VERIFIKASI ================= -->
        <section class="result-panel">

            <!-- LOGO FINS -->
            <a class="brand-logo"
               href="{{ route('signup', [], false) }}"
               aria-label="FINS - Kembali ke pendaftaran">

                <span>F</span><small>UB</small>

            </a>

            <!-- HASIL VERIFIKASI -->
            <div class="result-content">

                <div class="result-icon failed-icon"
                     aria-hidden="true">
                    ×
                </div>

                <h1>Verifikasi Gagal</h1>

                <p>
                    Kode verifikasi sudah terlalu sering dicoba
                    atau tidak valid.<br>
                    Silakan ulangi pendaftaran untuk meminta kode baru.
                </p>

                <!-- TOMBOL ULANGI PENDAFTARAN -->
                <a class="primary-button button-link"
                   href="{{ route('signup', [], false) }}">

                    Ulangi Pendaftaran

                </a>

            </div>

        </section>

        <!-- ================= BANNER FINS ================= -->
        <section class="verification-brand-panel result-brand-panel">

            <div class="brand-copy">

                <p class="eyebrow">
                    KEAMANAN AKUN FINS
                </p>

                <h2>Masih Bisa Dicoba Lagi!</h2>

                <p>
                    Pastikan kode yang kamu masukkan berasal
                    dari email verifikasi terbaru.
                    Kami siap membantumu bergabung di FINS.
                </p>

            </div>

            <!-- ILUSTRASI -->
            <img
                src="/assets/images/fins-login.png"
                alt="Ilustrasi mahasiswa FINS"
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