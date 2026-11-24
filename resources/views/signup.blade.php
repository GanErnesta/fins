@php
    $finsFacultyMap = config('fins_academics.faculties', []);
    $finsAllPrograms = $finsFacultyMap
        ? array_values(array_unique(array_merge(...array_values($finsFacultyMap))))
        : [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign Up | FINS</title>

  <link rel="stylesheet" href="/css/signup.css?v=51">
<link rel="stylesheet" href="/css/fins-academic-dropdown.css?v=51">
</head>

<body>

<main class="auth-layout signup-layout">

    <!-- PANEL KIRI -->
    <section class="brand-panel">

        <a class="brand-logo"
           href="{{ route('home') }}"
           aria-label="FINS">

            <span class="brand-f">F</span>
            <small>UB</small>
        </a>

        <div class="brand-copy">
            <p class="eyebrow">TEMUKAN PELUANGMU</p>

            <h2>
                Cari lomba. Temukan partner.
                Bangun koneksi.
            </h2>

            <p>
                Satu tempat untuk menemukan peluang
                kompetisi dan mahasiswa dengan skill
                yang sesuai.
            </p>
        </div>

        <img
            class="brand-illustration"
            src="{{ asset('assets/images/fins-login.png') }}"
            alt="Ilustrasi mahasiswa FINS"
        >

        <p class="brand-footer">
            FINS Universitas Brawijaya
        </p>

    </section>

    <!-- PANEL KANAN -->
    <section class="form-panel">

        <div class="form-content">

            <p class="mobile-eyebrow">
                FINS · Universitas Brawijaya
            </p>

            <h1>
                Mari mulai perjalananmu<br>
                FINSBE!!
            </h1>

            <p class="intro">
                Bergabung dengan FINS dan temukan koneksi
                yang bisa berkembang bersamamu.
            </p>

            <h2>
                {{ $step === 1
                    ? 'Lengkapi data kamu!!'
                    : 'Buat akun yaa!!' }}
            </h2>

            <!-- PESAN ERROR -->
            @if ($errors->any())
                <div class="form-alert" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- STEP 1 -->
            @if ($step === 1)

                <form
                    id="signupForm"
                    method="POST"
                    action="{{ route('signup.step1') }}"
                    novalidate
                >
                    @csrf

                    <!-- Nama Lengkap: Email hanya di Step 2 -->
                    <div class="field-group">
                        <label for="nama">Nama Lengkap</label>
                        <input
                            id="nama"
                            name="nama"
                            type="text"
                            placeholder="Nama lengkap sesuai KTM"
                            value="{{ old('nama', $old['nama'] ?? '') }}"
                            autocomplete="name"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="field-group">
                        <label for="nim">NIM</label>
                        <input
                            id="nim"
                            name="nim"
                            type="text"
                            placeholder="NIM sesuai KTM"
                            value="{{ old('nim', $old['nim'] ?? '') }}"
                            inputmode="numeric"
                            maxlength="15"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="field-row">

                        <div class="field-group">

                            <label for="fakultas">
                                Fakultas
                            </label>

                            <select
                                id="fakultas"
                                name="fakultas"
                                required
                            >
                                <option value="">
                                    Fakultas...
                                </option>

                                @foreach (array_keys($finsFacultyMap) as $faculty)

                                    <option
                                        value="{{ $faculty }}"
                                        @selected(
                                            old(
                                                'fakultas',
                                                $old['fakultas'] ?? ''
                                            ) === $faculty
                                        )
                                    >
                                        {{ $faculty }}
                                    </option>

                                @endforeach
                            </select>

                        </div>

                        <div class="field-group">

                            <label for="prodi">
                                Program Studi
                            </label>

                            <select
                                id="prodi"
                                name="prodi"
                                required
                            >
                                <option value="">
                                    Program studi...
                                </option>

                                @foreach ($finsAllPrograms as $program)

                                    <option
                                        value="{{ $program }}"
                                        @selected(
                                            old(
                                                'prodi',
                                                $old['prodi'] ?? ''
                                            ) === $program
                                        )
                                    >
                                        {{ $program }}
                                    </option>

                                @endforeach
                            </select>

                        </div>

                    </div>

                    <p id="finsAcademicHint" class="fins-academic-hint" aria-live="polite">
                        Pilih fakultas untuk melihat program studi yang sesuai.
                    </p>

                    <button
                        class="primary-button"
                        type="submit"
                    >
                        Next
                        <span aria-hidden="true">➜</span>
                    </button>

                    {{-- Pengguna yang sudah memiliki akun bisa langsung masuk --}}
                    <p class="signin-link">
                        Sudah punya akun?
                        <a href="{{ route('login') }}">Masuk di sini</a>
                    </p>

                </form>

            <!-- STEP 2 -->
            @else

                <form
                    id="signupForm"
                    method="POST"
                    action="{{ route('signup.step2') }}"
                    novalidate
                >
                    @csrf

                    <div class="field-group">
                        <label for="email">Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            placeholder="Email UB kamu"
                            value="{{ old('email', $old['email'] ?? '') }}"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="field-group">
                        <label for="password">Create Password</label>
                        <div class="password-field">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                placeholder="Buat password kamu minimal 8 karakter"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >
                            <button
                                type="button"
                                class="toggle-password"
                                data-target="password"
                                aria-label="Tampilkan password"
                            >
                                ◉
                            </button>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="password-field">
                            <input
                                id="confirmPassword"
                                name="password_confirmation"
                                type="password"
                                placeholder="Ulangi password kamu"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >
                            <button
                                type="button"
                                class="toggle-password"
                                data-target="confirmPassword"
                                aria-label="Tampilkan konfirmasi password"
                            >
                                ◉
                            </button>
                        </div>
                    </div>

                    <div class="password-hints">
                        <div class="password-meter" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <p class="helper-text">
                            Gunakan simbol, angka, dan karakter unik.
                        </p>
                    </div>

                    <button
                        class="primary-button"
                        type="submit"
                    >
                        Sign Up
                    </button>

                    <p class="signin-link">
                        Sudah punya akun?
                        <a href="{{ route('login') }}">
                            Masuk di sini
                        </a>
                    </p>

                    <a
                        class="back-link"
                        href="{{ route('signup') }}"
                    >
                        ← Kembali ke data diri
                    </a>

                </form>

            @endif

        </div>

    </section>

</main>

{{-- Data fakultas/prodi dipakai kedua halaman, bukan hardcode dalam JS. --}}
<div id="finsAcademicData" hidden
     data-faculties="{{ json_encode(config('fins_academics.faculties', []), JSON_UNESCAPED_UNICODE) }}"
     data-aliases="{{ json_encode(config('fins_academics.aliases', []), JSON_UNESCAPED_UNICODE) }}"></div>

<script src="/js/signup.js?v=51"></script>
<script src="/js/fins-academic-dropdown.js?v=51" defer></script>

</body>
</html>
