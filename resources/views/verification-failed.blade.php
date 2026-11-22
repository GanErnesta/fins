<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Gagal | FINS</title>

    {{-- Path dari root domain: aman untuk localhost dan Cloudflare HTTPS. --}}
    <link rel="stylesheet" href="/css/verification.css?v=21">
</head>
<body>
    <main class="verification-layout result-layout">
        <section class="result-panel">
            <a class="brand-logo"
               href="{{ route('signup', [], false) }}"
               aria-label="FINS - Kembali ke pendaftaran">
                <span>F</span><small>UB</small>
            </a>

            <div class="result-content">
                <div class="result-icon failed-icon" aria-hidden="true">×</div>
                <h1>Verifikasi Gagal</h1>
                <p>
                    Kode verifikasi sudah terlalu sering dicoba atau tidak valid.<br>
                    Silakan ulangi pendaftaran untuk meminta kode baru.
                </p>
                <a class="primary-button button-link"
                   href="{{ route('signup', [], false) }}">
                    Ulangi Pendaftaran
                </a>
            </div>
        </section>

        <section class="verification-brand-panel result-brand-panel">
            <div class="brand-copy">
                <p class="eyebrow">KEAMANAN AKUN FINS</p>
                <h2>Masih Bisa Dicoba Lagi!</h2>
                <p>
                    Pastikan kode yang kamu masukkan berasal dari email verifikasi terbaru.
                    Kami siap membantumu bergabung di FINS.
                </p>
            </div>
            <img src="/assets/images/fins-login.png"
                 alt="Ilustrasi mahasiswa FINS"
                 class="brand-illustration">
            <p class="brand-footer">FINS Universitas Brawijaya</p>
        </section>
    </main>
</body>
</html>
