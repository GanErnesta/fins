
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign Up | FINS</title>

    <link rel="stylesheet" href="{{ asset('css/signup.css') }}">
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

                    <label for="nama">
                        Nama Lengkap
                    </label>

                    <input
                        id="nama"
                        name="nama"
                        type="text"
                        placeholder="Masukkan nama lengkap kamu"
                        value="{{ old('nama', $old['nama'] ?? '') }}"
                        autocomplete="name"
                        required
                    >


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

                <div class="form-row">
                <div class="form-group">
                <label for="fakultas">Fakultas</label>
                    
                <select id="fakultas" name="fakultas" required>
                    <option value="">Pilih Fakultas...</option>
                    <option value="FH">Fakultas Hukum</option>
                    <option value="FEB">Fakultas Ekonomi dan Bisnis</option>
                    <option value="FIA">Fakultas Ilmu Administrasi</option>
                    <option value="FBiPK">Fakultas Bio-Industri Pertanian dan Kehutanan</option>
                    <option value="FAST">Fakultas Sains dan Teknologi Peternakan</option>
                    <option value="FT">Fakultas Teknik</option>
                    <option value="FK">Fakultas Kedokteran</option>
                    <option value="FPIK">Fakultas Perikanan dan Ilmu Kelautan</option>
                    <option value="FSTeM">Fakultas Sains, Teknologi, dan Matematika</option>
                    <option value="FTAB">Fakultas Teknologi Agroindustri dan Biosistem</option>
                    <option value="FISIP">Fakultas Ilmu Sosial dan Ilmu Politik</option>
                    <option value="FIB">Fakultas Ilmu Budaya</option>
                    <option value="FKH">Fakultas Kedokteran Hewan</option>
                    <option value="FILKOM">Fakultas Ilmu Komputer</option>
                    <option value="FKG">Fakultas Kedokteran Gigi</option>
                    <option value="FIKES">Fakultas Ilmu Kesehatan</option>
                </select>
                </div>

                <div class="form-group">
                    <label for="prodi">Program Studi</label>
                    <select id="prodi" name="prodi" required disabled>
                        <option value="">Pilih Fakultas terlebih dahulu</option>
                    </select>
                </div>
            </div>

                    <button
                        class="primary-button"
                        type="submit"
                    >
                        Next
                        <span aria-hidden="true">➜</span>
                    </button>

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

                    <label for="password">
                        Create Password
                    </label>

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

                    <label for="confirmPassword">
                        Confirm Password
                    </label>

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

                    <button
                        class="primary-button"
                        type="submit"
                    >
                        Sign Up
                    </button>

                    <p class="signin-link">
                        Have an account?
                        <a href="{{ route('login') }}">
                            Sign in
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

<script src="{{ asset('js/signup.js') }}"></script>

</body>
</html>
