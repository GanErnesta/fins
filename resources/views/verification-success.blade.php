<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Berhasil | FINS</title>

    {{-- Path relatif ke domain aktif agar CSS dimuat di HP melalui HTTPS. --}}
    <link rel="stylesheet" href="/css/verification.css?v=21">
</head>
<body>
    <main class="verification-layout result-layout">
        <section class="result-panel">
            <a class="brand-logo"
               href="{{ route('login', [], false) }}"
               aria-label="FINS - Halaman login">
                <span>F</span><small>UB</small>
            </a>

            <div class="result-content">
                <div class="result-icon success-icon" aria-hidden="true">✓</div>
                <h1>Verifikasi Berhasil</h1>
                <p>
                    Akun kamu telah berhasil diverifikasi.<br>
                    Sekarang kamu bisa masuk ke FINS.
                </p>
                <a class="primary-button button-link"
                   href="{{ route('login', [], false) }}">
                    Kembali ke Login
                </a>
            </div>
        </section>

        <section class="verification-brand-panel result-brand-panel">
            <div class="brand-copy">
                <p class="eyebrow">SELAMAT, KAMU SIAP!</p>
                <h2>Verifikasi Berhasil!</h2>
                <p>
                    Akunmu sudah siap digunakan. Yuk, temukan peluang
                    dan koneksi baru bersama FINS.
                </p>
            </div>
            <img src="/assets/images/fins-login.png"
                 alt="Ilustrasi keberhasilan verifikasi"
                 class="brand-illustration">
            <p class="brand-footer">FINS Universitas Brawijaya</p>
        </section>
    </main>
</body>
</html>
