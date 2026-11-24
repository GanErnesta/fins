
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Posting Lomba | FINS UB</title>

    <link rel="stylesheet"
          href="/css/posting_lomba.css?v={{ filemtime(public_path('css/posting_lomba.css')) }}">

    <script src="/js/posting_lomba.js?v={{ filemtime(public_path('js/posting_lomba.js')) }}"
            defer></script>
</head>

<body>

<!-- ================= NAVBAR ================= -->
<header class="navbar">

    <a href="/home"
       class="navbar-logo"
       aria-label="FINS UB">
        <span class="logo-f">F</span>
        <small class="logo-ub">UB</small>
    </a>

    <nav class="nav-links">
        <a href="/home">Beranda</a>

        <a href="/cari-lomba" class="active">
            Lomba
        </a>

        <a href="/cari-partner">
            Cari Partner
        </a>

        <a href="/lomba-saya">
            Lomba Saya
        </a>
    </nav>

    <div class="nav-actions">

        <!-- Chat -->
        <a href="/chat"
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
                         8.38 8.38 0 0 1-3.8-.9
                         L3 21l1.9-5.7
                         a8.38 8.38 0 0 1-.9-3.8
                         8.5 8.5 0 0 1 4.7-7.6
                         8.38 8.38 0 0 1 3.8-.9h.5
                         a8.48 8.48 0 0 1 8 8v.5z"/>
                <path d="M8 10h8M8 14h5"/>
            </svg>
        </a>

        <!-- Notifikasi -->
        <a href="/notifikasi"
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
        <a href="/profile"
           class="profile-logo"
           title="Profil">
            <span>F</span>
            <small>UB</small>
        </a>

    </div>
</header>

<!-- ================= HEADER ================= -->
<section class="page-heading">
    <div class="container">

        <p class="subtitle">
            Kolaborasi & Rekrutmen Tim
        </p>

        <h1>Buat Postingan Pencarian Anggota Tim</h1>

        <p class="heading-description">
            Publikasikan kebutuhan formasi tim lomba Anda
            agar mahasiswa Universitas Brawijaya yang
            memiliki skill relevan dapat mengajukan diri
            atau Anda ajak secara langsung.
        </p>

    </div>
</section>

<!-- ================= KONTEN ================= -->
<main class="container main-layout">

    <form
        action="{{ route('posting-lomba.store', [], false) }}"
        method="POST"
        enctype="multipart/form-data"
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
                <strong>Periksa data berikut:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ================= BAGIAN 1 ================= -->
        <div class="section-title">
            <span class="step-number">1</span>
            <h2>Informasi Perlombaan</h2>
        </div>

        <div class="form-section">

            <!-- JENIS POSTINGAN -->
            <div class="form-group">
                <label>
                    Pilih Jenis Postingan <span>*</span>
                </label>

                <div class="mode-options">

                    <label class="mode-option">
                        <input
                            type="radio"
                            name="mode_lomba"
                            value="existing"
                            @checked(old('mode_lomba', 'existing') === 'existing')
                        >
                        <span>Gunakan Lomba yang Sudah Ada</span>
                    </label>

                    <label class="mode-option">
                        <input
                            type="radio"
                            name="mode_lomba"
                            value="new"
                            @checked(old('mode_lomba') === 'new')
                        >
                        <span>Buat Lomba Baru</span>
                    </label>

                </div>
            </div>

            <!-- LOMBA DARI SUPABASE -->
            <div id="existingLombaFields">

                <div class="form-group">
                    <label for="cari_lomba_id">
                        Pilih Kompetisi <span>*</span>
                    </label>

                    <select
                        id="cari_lomba_id"
                        name="cari_lomba_id"
                    >
                        <option value="">
                            -- Pilih Lomba --
                        </option>

                        @foreach($lombas as $item)
                            <option
                                value="{{ $item->id }}"
                                data-title="{{ $item->title }}"
                                data-category="{{ $item->category }}"
                                data-organizer="{{ $item->organizer }}"
                                data-deadline="{{ $item->deadline }}"
                                data-image="{{ $item->image ? (filter_var($item->image, FILTER_VALIDATE_URL) ? $item->image : ('/' . ltrim($item->image, '/'))) : '' }}"
                                @selected(old('cari_lomba_id') == $item->id)
                            >
                                {{ $item->title }} - {{ $item->organizer }}
                            </option>
                        @endforeach

                    </select>
                </div>

                <p class="field-hint">
                    Daftar kompetisi diambil dari database
                    Cari Lomba di Supabase.
                </p>

                @if($lombas->isEmpty())
                    <p class="position-error">
                        Belum ada lomba di database.
                        Silakan pilih Buat Lomba Baru.
                    </p>
                @endif

            </div>

            <!-- MEMBUAT LOMBA BARU -->
            <div id="newLombaFields" hidden>

                <div class="form-group">
                    <label for="nama_lomba">
                        Nama Lomba / Kompetisi <span>*</span>
                    </label>

                    <input
                        type="text"
                        id="nama_lomba"
                        name="nama_lomba"
                        value="{{ old('nama_lomba') }}"
                        placeholder="Contoh: National UI UX Creative Challenge 2027"
                        maxlength="150"
                    >
                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label for="kategori">
                            Kategori Perlombaan <span>*</span>
                        </label>

                        <select id="kategori" name="kategori">
                            <option value="">Pilih Kategori</option>

                            @foreach([
                                'Teknologi dan AI',
                                'PKM Kemendikbud',
                                'Business Plan & Case',
                                'UI/UX & Desain',
                                'Essay',
                                'Hackathon',
                                'Poster'
                            ] as $kategori)

                                <option
                                    value="{{ $kategori }}"
                                    @selected(old('kategori') === $kategori)
                                >
                                    {{ $kategori }}
                                </option>

                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="penyelenggara">
                            Penyelenggara <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="penyelenggara"
                            name="penyelenggara"
                            value="{{ old('penyelenggara') }}"
                            placeholder="Contoh: Universitas Brawijaya"
                            maxlength="150"
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
                    >
                </div>

            </div>

            <!-- ================= UPLOAD GAMBAR ================= -->
            <div class="form-group poster-upload-group">

                <label for="image">
                    Upload Gambar / Poster Lomba
                </label>

                <div class="poster-upload-box">

                    <div class="poster-upload-icon">▧</div>

                    <p>Tambahkan poster untuk postingan lomba</p>

                    <span>
                        Pilih gambar dari perangkat kamu
                    </span>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>
                        JPG, PNG, WEBP • Maksimal 2 MB
                    </small>

                </div>

                @error('image')
                    <p class="position-error">
                        {{ $message }}
                    </p>
                @enderror

            </div>

        </div>

        <!-- ================= BAGIAN 2 ================= -->
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
                    placeholder="Jelaskan ide, konsep karya, atau rencana tim yang akan mengikuti kompetisi..."
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
                    value="{{ old('target', 'Target lolos ke babak final dan meraih juara tingkat nasional.') }}"
                    required
                >
            </div>

        </div>

        <!-- ================= BAGIAN 3 ================= -->
        <div class="section-title">
            <span class="step-number">3</span>
            <h2>Posisi & Keahlian yang Dicari</h2>
        </div>

        <div class="form-section final-section">

            <div
                id="positionsList"
                class="positions-list"
            ></div>

            <!-- TAMBAH POSISI -->
            <div class="add-position-box">

                <h3>+ Tambah Posisi Anggota Baru</h3>

                <div class="add-position-row">

                    <input
                        type="text"
                        id="newRole"
                        placeholder="Nama peran, contoh: UI/UX Designer"
                        maxlength="100"
                    >

                    <input
                        type="number"
                        id="newCount"
                        min="1"
                        max="30"
                        value="1"
                        aria-label="Jumlah anggota"
                    >

                </div>

                <input
                    type="text"
                    id="newSkills"
                    placeholder="Skill: Figma, Wireframing, Research"
                    maxlength="500"
                >

                <button
                    type="button"
                    id="addPosition"
                    class="add-position-btn"
                >
                    Tambahkan Posisi Ini
                </button>

                <p
                    id="positionError"
                    class="position-error"
                ></p>

            </div>

            <p class="total-team-info">
                Total anggota yang dicari:
                <strong id="totalTeamText">0 orang</strong>
            </p>

            <!-- KANAL DISKUSI -->
            <div class="form-group channel-group">
                <label for="kanal_diskusi">
                    Kanal Diskusi Tim
                </label>

                <input
                    type="text"
                    id="kanal_diskusi"
                    name="kanal_diskusi"
                    value="{{ old('kanal_diskusi', 'Chat FINS / Grup WhatsApp Tim') }}"
                    maxlength="255"
                    required
                >
            </div>

            <!-- INPUT HIDDEN POSISI -->
            <div id="positionHiddenInputs"></div>

            <button
                type="submit"
                class="publish-btn"
            >
                ➤ &nbsp; Publikasikan Postingan
            </button>

        </div>

    </form>

    <!-- ================= LIVE PREVIEW ================= -->
    <aside class="preview-section">

        <p class="preview-label">
            Pratinjau Tampilan Postingan (Live Preview)
        </p>

        <div class="preview-card">

            <div class="preview-top">

                <span
                    class="category-badge"
                    id="previewCategory"
                >
                    Kategori Lomba
                </span>

                <span class="deadline-text">
                    Deadline:
                    <span id="previewDeadline">—</span>
                </span>

            </div>

            <!-- PREVIEW POSTER -->
            <div class="preview-poster" id="previewPosterBox">

                <img
                    id="previewPoster"
                    alt="Pratinjau poster lomba"
                    hidden
                >

                <div id="previewPosterPlaceholder"
                     class="preview-poster-placeholder">

                    <span>FINS COMPETITION</span>

                    <strong>
                        Poster Perlombaan
                    </strong>

                    <small>
                        Gambar lomba akan ditampilkan di sini
                    </small>

                </div>

            </div>

            <h2 id="previewTitle">
                Judul Perlombaan Anda
            </h2>

            <p class="preview-organizer">
                Penyelenggara:
                <span id="previewOrganizer">
                    Belum dipilih
                </span>
            </p>

            <div
                class="preview-description"
                id="previewDescription"
            >
                Deskripsi perlombaan dan ide tim
                akan muncul di sini.
            </div>

            <p class="preview-target">
                <strong>Target:</strong>
                <span id="previewTarget">
                    Target tim belum ditentukan.
                </span>
            </p>

            <h3 class="preview-position-title">
                Formasi yang Dicari
                (<span id="previewCount">0</span> orang):
            </h3>

            <div
                id="previewPositions"
                class="preview-positions"
            ></div>

        </div>

    </aside>

</main>

<!-- ================= FOOTER ================= -->
<footer class="footer">

    <div class="container footer-grid">

        <div class="footer-column">

            <div class="footer-logo">
                F <small>UB</small>
            </div>

            <p>
                Find Your Skills Partner - Ekosistem
                kolaborasi mahasiswa Universitas Brawijaya
                untuk menemukan tim lomba lintas fakultas
                dan berprestasi di kancah nasional
                maupun internasional.
            </p>

            <p>
                Kampus Utama Universitas Brawijaya,
                Malang, Jawa Timur.
            </p>

        </div>

        <div class="footer-column">

            <h3>KATEGORI KOMPETISI</h3>

            <p>
                Puspresnas Gemastik &amp; Satria Data<br>
                Program Kreativitas Mahasiswa<br>
                Business Plan &amp; Case Competition<br>
                Karya Tulis Ilmiah<br>
                UI/UX Design &amp; Software Development<br>
                Robotic Cup<br>
                Hackathon Competition
            </p>

        </div>

        <div class="footer-column">

            <h3>RUMPUN FAKULTAS</h3>

            <p>
                Menghubungkan mahasiswa FILKOM, FEB, FT,
                FIA, FK, FTP, FISIP, FMIPA, FP, FH
                dan FIB dalam satu sinergi juara.
            </p>

            <p>
                <strong class="tips">Tips Juara:</strong>
                Tim multidisiplin Teknologi, Bisnis,
                dan Riset dapat membantu memperkuat
                kolaborasi dan inovasi.
            </p>

        </div>

    </div>

</footer>

<!-- DATA POSISI DARI VALIDASI LARAVEL -->
<script id="oldPositionsData" type="application/json">
    @json(old('positions'))
</script>

</body>
</html>
