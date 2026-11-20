
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login FINS</title>

    <link rel="stylesheet"
          href="{{ asset('css/login.css') }}">
</head>

<body>

<div class="login-container">

    <!-- BAGIAN FORM LOGIN -->
    <section class="login-form">

        <h1>
            Selamat Datang Kembali<br>
            FINS!!
        </h1>

        <p class="subtitle">
            Silakan masuk menggunakan NIM dan kata sandi Anda
        </p>

        <!-- PESAN ERROR -->
        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- FORM LOGIN -->
        <form
            action="{{ route('login.submit') }}"
            method="POST"
        >
            @csrf

            <!-- INPUT NIM -->
            <label for="nim">NIM</label>

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
            <label for="password">Password</label>

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
            <div class="forgot">
                <a href="#">
                    Forgot Password?
                </a>
            </div>

            <!-- TOMBOL LOGIN -->
            <button type="submit" class="login-button">
                Login
            </button>

            <!-- DAFTAR -->
            <p class="signup">
                Don't have an account?

                <a href="{{ route('signup') }}">
                    Sign Up
                </a>
            </p>

        </form>

    </section>

    <!-- BAGIAN BANNER -->
    <section class="login-banner">

        <!-- LOGO -->
        <div class="logo">
            <img
                src="{{ asset('assets/images/Group4.png') }}"
                alt="Logo F UB"
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
                Hubungkan skill dan minatmu dengan
                mahasiswa lain untuk menemukan
                kesempatan yang sesuai.
            </p>

        </div>

        <!-- ILUSTRASI -->
        <div class="illustration">
            <img
                src="{{ asset('assets/images/fins-login.png') }}"
                alt="Ilustrasi mahasiswa"
            >
        </div>

        <footer>
            FINS Universitas Brawijaya
        </footer>

    </section>

</div>

<script src="{{ asset('js/login.js') }}"></script>

</body>
</html>
