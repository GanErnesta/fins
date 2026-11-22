
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login | FINS</title>

    <!-- CSS LOGIN: MENGIKUTI DOMAIN SAAT INI -->
    <link rel="stylesheet" href="/css/login.css?v=5">

    <!-- CSS KHUSUS LINK LUPA PASSWORD -->
    <style>
        .fins-forgot-link {
            display: block;
            width: fit-content;
            margin: 10px 0 18px auto;
            color: #8b0000;
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease,
                        transform 0.2s ease;
        }

        .fins-forgot-link:hover {
            color: #b23a3a;
            text-decoration: underline;
            transform: translateX(-2px);
        }

        .fins-forgot-link:focus-visible {
            outline: 2px solid #8b0000;
            outline-offset: 4px;
            border-radius: 3px;
        }
    </style>
</head>

<body>

<div class="login-container">

    <!-- ========================================
         BAGIAN FORM LOGIN
    ========================================= -->
    <section class="login-form">

        <h1>
            Selamat Datang Kembali<br>
            FINS!!
        </h1>

        <p class="subtitle">
            Silakan masuk menggunakan NIM
            dan kata sandi Anda
        </p>

        <!-- PESAN BERHASIL -->
        @if (session('status'))
            <div class="success-message"
                 role="status"
                 style="
                    padding: 12px 14px;
                    margin-bottom: 18px;
                    border-radius: 10px;
                    background: #edf8ee;
                    color: #226b34;
                    border: 1px solid #b7dec0;
                    font-size: 13px;
                 ">
                {{ session('status') }}
            </div>
        @endif

        <!-- PESAN ERROR -->
        @if ($errors->any())
            <div class="error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- FORM LOGIN -->
        <form
            action="{{ route('login.submit', [], false) }}"
            method="POST"
        >
            @csrf

            <!-- INPUT NIM -->
            <label for="nim">
                NIM
            </label>

            <input
                type="text"
                id="nim"
                name="nim"
                value="{{ old('nim') }}"
                placeholder="NIM sesuai KTM"
                inputmode="numeric"
                pattern="[0-9]+"
                maxlength="20"
                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                autocomplete="username"
                required
            >

            <!-- INPUT PASSWORD -->
            <label for="password">
                Password
            </label>

            <div class="password-wrapper">

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan Password"
                    autocomplete="current-password"
                    required
                >

                <!-- TOGGLE PASSWORD -->
                <button
                    type="button"
                    id="togglePassword"
                    aria-label="Tampilkan password"
                    aria-pressed="false"
                >
                    <svg
                        viewBox="0 0 24 24"
                        width="20"
                        height="20"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path
                            d="M2 12s3.5-7 10-7 10 7
                               10 7-3.5 7-10 7S2 12 2 12z"
                        />
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>

            </div>

            <!-- LUPA PASSWORD -->
            <a
                href="{{ route('password.request', [], false) }}"
                class="fins-forgot-link"
            >
                Lupa Password?
            </a>

            <!-- TOMBOL LOGIN -->
            <button
                type="submit"
                class="login-button"
            >
                Login
            </button>

            <!-- LINK SIGN UP -->
            <p class="signup">
                Belum punya akun?

                <a href="{{ route('signup', [], false) }}">
                    Daftar
                </a>
            </p>

        </form>

    </section>

    <!-- ========================================
         BAGIAN BANNER FINS
    ========================================= -->
    <section class="login-banner">

        <!-- LOGO -->
        <div class="logo">
            <img
                src="/assets/images/Group4.png"
                alt="Logo FINS UB"
            >
        </div>

        <!-- TEKS BANNER -->
        <div class="banner-text">

            <p>
                TEMUKAN KONEKSI YANG TEPAT
            </p>

            <h2>
                Skill berbeda, tujuan yang sama.
            </h2>

            <p>
                Hubungkan skill dan minatmu
                dengan mahasiswa lain untuk
                menemukan kesempatan yang sesuai.
            </p>

        </div>

        <!-- ILUSTRASI -->
        <div class="illustration">
            <img
                src="/assets/images/fins-login.png"
                alt="Ilustrasi mahasiswa FINS"
            >
        </div>

        <!-- FOOTER -->
        <footer>
            FINS Universitas Brawijaya
        </footer>

    </section>

</div>

<!-- JS LOGIN: MENGIKUTI DOMAIN SAAT INI -->
<script src="/js/login.js?v=5" defer></script>

</body>
</html>
