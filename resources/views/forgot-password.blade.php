<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Lupa Password | FINS</title>

    {{-- CSS dengan versi otomatis dan URL relatif --}}
    <link rel="stylesheet"
          href="/css/password-reset.css?v={{ filemtime(public_path('css/password-reset.css')) }}">
</head>

<body>
    <main class="fins-auth-layout">

        <!-- =========================================
             PANEL KIRI - BRAND FINS
        ========================================== -->
        <aside class="fins-auth-brand">

            <a
                class="fins-auth-logo"
                href="{{ route('login', [], false) }}"
                aria-label="Kembali ke Login FINS"
            >
                F<span>UB</span>
            </a>

            <div class="fins-auth-brand-copy">

                <p class="fins-auth-kicker">
                    TEMUKAN PELUANGMU
                </p>

                <h2>
                    Cari lomba. Temukan partner.
                    <br>
                    Bangun koneksi.
                </h2>

                <p>
                    Satu tempat untuk menemukan peluang
                    kompetisi dan mahasiswa dengan skill
                    yang sesuai.
                </p>

            </div>

            <img
                src="/assets/images/fins-login.png"
                class="fins-auth-illustration"
                alt="Ilustrasi mahasiswa FINS"
                loading="eager"
            >

            <small class="fins-auth-footer">
                FINS Universitas Brawijaya
            </small>

        </aside>

        <!-- =========================================
             PANEL KANAN - FORGOT PASSWORD
        ========================================== -->
        <section class="fins-auth-panel">

            <div class="fins-auth-card">

                <span class="fins-auth-eyebrow">
                    KEAMANAN AKUN
                </span>

                <h1>Lupa Password?</h1>

                <p class="fins-auth-intro">
                    Masukkan email akun FINS kamu.
                    Kami akan mengirimkan tautan
                    untuk membuat password baru.
                </p>

                <!-- PESAN SUKSES -->
                @if (session('status'))

                    <div
                        class="fins-auth-alert fins-auth-alert--success"
                        role="status"
                    >
                        {{ session('status') }}
                    </div>

                @endif

                <!-- PESAN ERROR -->
                @if ($errors->any())

                    <div
                        class="fins-auth-alert fins-auth-alert--error"
                        role="alert"
                    >

                        @foreach ($errors->all() as $error)

                            <p>{{ $error }}</p>

                        @endforeach

                    </div>

                @endif

                <!-- FORM FORGOT PASSWORD -->
                <form
                    id="forgotPasswordForm"
                    method="POST"
                    action="{{ route('password.email', [], false) }}"
                    class="fins-auth-form"
                >

                    @csrf

                    <label for="email">
                        Email akun FINS
                    </label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="nama@student.ub.ac.id"
                        autocomplete="email"
                        inputmode="email"
                        maxlength="255"
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        required
                        autofocus
                    >

                    <button
                        class="fins-auth-primary"
                        type="submit"
                    >
                        Kirim Tautan Reset
                        <span aria-hidden="true">→</span>
                    </button>

                </form>

                <!-- KEMBALI KE LOGIN -->
                <a
                    class="fins-auth-back"
                    href="{{ route('login', [], false) }}"
                >
                    ← Kembali ke Login
                </a>

            </div>

        </section>

    </main>
</body>
</html>