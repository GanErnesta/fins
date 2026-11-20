
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Posting Lomba | FINS UB</title>

    <!-- CSS terbaru -->
    <link rel="stylesheet" href="{{ asset('css/posting_lomba.css') }}?v=5">

    <!-- JavaScript -->
    <script src="{{ asset('js/posting_lomba.js') }}" defer></script>
</head>

<body>

<!-- NAVBAR -->
<header class="navbar">

    <!-- Logo Kiri -->
    <a href="{{ url('/home') }}" class="navbar-logo"
       aria-label="FINS UB - Beranda">
        <span class="logo-f">F</span>
        <small class="logo-ub">UB</small>
    </a>

    <!-- Menu Tengah -->
    <nav class="nav-links" aria-label="Navigasi utama">

        <a href="{{ url('/home') }}">Beranda</a>

        <a href="{{ url('/cari-lomba') }}" class="active">
            Lomba
        </a>

        <a href="{{ url('/cari-partner') }}">
            Cari Partner
        </a>

        <a href="{{ url('/lomba-saya') }}">
            Lomba Saya
        </a>

    </nav>

    <!-- Ikon Kanan -->
    <div class="nav-actions">

        <!-- Chat -->
        <a href="{{ url('/chat') }}"
           class="nav-icon"
           title="Chat"
           aria-label="Chat">

            <svg viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.8"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 aria-hidden="true">

                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8
                         8.5 8.5 0 0 1-7.6 4.7
                         8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7
                         a8.38 8.38 0 0 1-.9-3.8
                         8.5 8.5 0 0 1 4.7-7.6
                         8.38 8.38 0 0 1-.9-3.8
                         h.5a8.48 8.48 0 0 1 8 8v.5z"/>

                <path d="M8 10h8M8 14h5"/>
            </svg>
        </a>

        <!-- Notifikasi -->
        <a href="{{ url('/notifikasi') }}"
           class="nav-icon notification-icon"
           title="Notifikasi"
           aria-label="Notifikasi">

            <svg viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.8"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 aria-hidden="true">

                <path d="M18 8a6 6 0 0 0-12 0
                         c0 7-3 7-3 9h18
                         c0-2-3-2-3-9"/>

                <path d="M10 21h4"/>
            </svg>

            <span class="notification-dot"></span>
        </a>

        <!-- Profil -->
        <a href="{{ url('/profile') }}"
           class="profile-logo"
           title="Profil"
           aria-label="Profil">

            <span>F</span>
            <small>UB</small>
        </a>

    </div>
</header>

<!-- HEADER HALAMAN -->
<section class="page-heading">
    <div class="container">

        <p class="subtitle">
            Kolaborasi & Rekrutmen Tim
        </p>

        <h1>
            Buat Postingan Pencarian Anggota Tim
        </h1>

        <p class="heading-description">
            Publikasikan kebutuhan formasi tim lomba Anda agar mahasiswa
            Universitas Brawijaya yang memiliki skill relevan dapat
            mengajukan diri atau Anda ajak secara langsung.
        </p>

    </div>
</section>

<!-- KONTEN UTAMA -->
<main class="container main-layout">

    <!-- FORM KIRI -->
    <form
        action="{{ route('posting_lomba.store') }}"
        method="POST"
        id="postingForm"
        class="form-container"
    >
        @csrf

        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- SECTION 1 -->
        <div class="section-title">
            <span class="step-number">1</span>
            <h2>Informasi Perlombaan</h2>
        </div>

        <div class="form-section">

            <div class="form-group">
                <label for="nama_lomba">
                    Nama Lomba / Kompetisi <span>*</span>
                </label>

                <input
                    type="text"
                    id="nama_lomba"
                    name="nama_lomba"
                    value="{{ old('nama_lomba') }}"
                    placeholder="Contoh : Divisi UX 2026"
                    maxlength="150"
                    required
                >
            </div>

            <div class="form-row">

                <div class="form-group">
                    <label for="kategori">
                        Kategori Perlombaan
                    </label>

                    <select id="kategori" name="kategori" required>
                        <option value="">Pilih kategori</option>

                        @foreach([
                            'Teknologi & AI',
                            'UI/UX Design',
                            'Business Plan',
                            'Karya Tulis Ilmiah',
                            'Robotika',
                            'Hackathon',
                            'Lainnya'
                        ] as $kategori)

                            <option value="{{ $kategori }}"
                                @selected(old('kategori') === $kategori)>
                                {{ $kategori }}
                            </option>

                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="penyelenggara">
                        Penyelenggara
                    </label>

                    <input
                        type="text"
                        id="penyelenggara"
                        name="penyelenggara"
                        value="{{ old('penyelenggara') }}"
                        placeholder="Contoh : Kemendikbud / Puspresnas"
                        maxlength="150"
                        required
                    >
                </div>

            </div>

            <div class="form-group">
                <label for="deadline" class="larger-label">
                    Batas Akhir / Deadline Pendaftaran
                </label>

                <input
                    type="date"
                    id="deadline"
                    name="deadline"
                    value="{{ old('deadline') }}"
                    required
                >
            </div>

        </div>

        <!-- SECTION 2 -->
        <div class="section-title">
            <span class="step-number">2</span>
            <h2>Deskripsi Tim & Ekspektasi</h2>
        </div>

        <div class="form-section">

            <div class="form-group">
                <label for="deskripsi">
                    Deskripsi Ide & Rencana Tim <span>*</span>
                </label>

                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    rows="4"
                    maxlength="3000"
                    placeholder="Jelaskan gagasan atau fokus produk/karya yang akan diangkat tim anda..."
                    required
                >{{ old('deskripsi') }}</textarea>
            </div>

            <div class="form-group">
                <label for="target">
                    Target & Komitmen Tim <span>*</span>
                </label>

                <input
                    type="text"
                    id="target"
                    name="target"
                    maxlength="300"
                    value="{{ old('target', 'Target lolos ke babak final & meraih juara di tingkat nasional.') }}"
                    required
                >
            </div>

        </div>

        <!-- SECTION 3 -->
        <div class="section-title">
            <span class="step-number">3</span>
            <h2>Posisi & Keahlian yang Dicari</h2>
        </div>

        <div class="form-section final-section">

            <!-- LIST POSISI -->
            <div id="positionsList" class="positions-list"></div>

            <!-- TAMBAH POSISI -->
            <div class="add-position-box">

                <h3>+ Tambah Posisi Anggota Baru</h3>

                <div class="add-position-row">

                    <input
                        type="text"
                        id="newRole"
                        placeholder="Nama Peran : (misal data analisis)"
                        maxlength="100"
                    >

                    <input
                        type="number"
                        id="newCount"
                        min="1"
                        max="30"
                        value="1"
                        aria-label="Jumlah orang"
                    >

                </div>

                <input
                    type="text"
                    id="newSkills"
                    placeholder="Keahlian yang dibutuhkan"
                    maxlength="500"
                >

                <button
                    type="button"
                    id="addPosition"
                    class="add-position-btn"
                >
                    Tambahkan Posisi Ini
                </button>

                <p id="positionError" class="position-error"></p>

            </div>

            <!-- KANAL DISKUSI -->
            <div class="form-group channel-group">
                <label for="kanal_diskusi">
                    Kanal Diskusi Tim
                </label>

                <input
                    type="text"
                    id="kanal_diskusi"
                    name="kanal_diskusi"
                    value="{{ old('kanal_diskusi', 'Chat Fins/Grup WA Tim') }}"
                    maxlength="255"
                    required
                >
            </div>

            <!-- INPUT POSISI DATABASE -->
            <div id="positionHiddenInputs"></div>

            <!-- TOMBOL SIMPAN -->
            <button type="submit" class="publish-btn">
                ➤ &nbsp; Publikasikan Postingan
            </button>

        </div>
    </form>

    <!-- LIVE PREVIEW KANAN -->
    <aside class="preview-section">

        <p class="preview-label">
            Pratinjau Tampilan Postingan (Live Preview)
        </p>

        <div class="preview-card">

            <div class="preview-top">

                <span class="category-badge" id="previewCategory">
                    Teknologi & AI
                </span>

                <span class="deadline-text">
                    Deadline:
                    <span id="previewDeadline">—</span>
                </span>

            </div>

            <h2 id="previewTitle">
                Judul Perlombaan Anda
            </h2>

            <p class="preview-organizer">
                Penyelenggara:
                <span id="previewOrganizer">
                    Puspresnas / Balai Prestasi UB
                </span>
            </p>

            <div class="preview-description" id="previewDescription">
                Eduact Competition 2026
            </div>

            <p class="preview-target">
                <strong>Target:</strong>
                <span id="previewTarget">
                    Target lolos ke babak final & meraih juara
                    di tingkat nasional.
                </span>
            </p>

            <h3 class="preview-position-title">
                Formasi yang Dicari
                (<span id="previewCount">2</span> orang):
            </h3>

            <div id="previewPositions" class="preview-positions"></div>

        </div>

    </aside>

</main>

<!-- FOOTER -->
<footer class="footer">

    <div class="container footer-grid">

        <div class="footer-column">

            <div class="footer-logo">
                F <small>UB</small>
            </div>

            <p>
                Find Your Skills Partner - Ekosistem kolaborasi
                mahasiswa Universitas Brawijaya untuk menemukan
                tim lomba lintas fakultas dan berprestasi di
                kancah nasional maupun internasional.
            </p>

            <p>
                Kampus Utama Universitas Brawijaya,
                Malang, Jawa Timur.
            </p>

        </div>

        <div class="footer-column">

            <h3>KATEGORI KOMPETISI</h3>

            <p>
                Puspresnas Gemastik & Satria Data<br>
                Program Kreativitas Mahasiswa<br>
                Business Plan & Case Competition<br>
                Karya Tulis Ilmiah<br>
                UI/UX Design & Software Development<br>
                Robotic cup<br>
                Hackathon Competition
            </p>

        </div>

        <div class="footer-column">

            <h3>RUMPUN FAKULTAS</h3>

            <p>
                Menghubungkan mahasiswa FILKOM, FEB, FT, FIA,
                FK, FTP, FISIP, FMIPA, FP, FH, dan FIB
                dalam satu sinergi juara
            </p>

            <p>
                <strong class="tips">Tips Juara :</strong>
                Tim multidisiplin (Teknologi + Bisnis + Riset)
                memiliki tingkat lolos 2–3x lebih tinggi
                di ajang bergengsi.
            </p>

        </div>

    </div>

</footer>

<!-- Data posisi setelah validasi gagal -->
<script id="oldPositionsData" type="application/json">
    @json(old('positions'))
</script>

</body>
</html>
