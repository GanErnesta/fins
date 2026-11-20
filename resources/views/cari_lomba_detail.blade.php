<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cari Lomba | Find Your Skills Partner</title>

    <link rel="stylesheet"
          href="{{ asset('css/cari_lomba.css') }}?v=12">

    <script src="{{ asset('js/cari_lomba.js') }}?v=12" defer></script>
</head>

<body>

    <!-- NAVBAR -->
    <header class="topbar">
        <a href="{{ url('/home') }}" class="brand">
            <span>F</span>
            <small>UB</small>
        </a>

        <nav class="main-nav">
            <a href="{{ url('/home') }}">Beranda</a>
            <a href="{{ url('/cari-lomba') }}" class="active">Lomba</a>
            <a href="{{ url('/cari-partner') }}">Cari Partner</a>
            <a href="{{ url('/lomba-saya') }}">Lomba Saya</a>
            <a href="{{ url('/tim-saya') }}">Tim Saya</a>
        </nav>

        <div class="header-actions">
            <span class="header-icon" aria-hidden="true">♧</span>
            <span class="header-icon" aria-hidden="true">♧</span>

            <a href="{{ url('/profile') }}"
               class="profile-icon"
               aria-label="Profil">
                F
            </a>
        </div>
    </header>

    <!-- KONTEN UTAMA -->
    <main class="page">

        <!-- INTRO -->
        <section class="intro">
            <div>
                <div class="eyebrow">
                    DIREKTORI KOMPETISI MAHASISWA
                </div>

                <h1>EKSPLORASI DAN TEMUKAN PERLOMBAAN</h1>

                <p>
                    Kumpulan ajang kompetisi resmi tingkat regional,
                    nasional, dan internasional yang relevan bagi
                    mahasiswa Universitas Brawijaya.
                </p>
            </div>

            <span class="result-count"
                  id="result-count"
                  aria-live="polite">
                Menampilkan {{ $lomba->count() }} dari
                {{ $lomba->count() }} ajang lomba
            </span>
        </section>

        <!-- FILTER PANEL -->
        <section class="filter-panel">

            <div class="filters-row">

                <label class="search-box">
                    <span aria-hidden="true">⌕</span>
                    <input
                        type="search"
                        id="search"
                        placeholder="Cari nama lomba, topik riset, atau skill..."
                        aria-label="Cari lomba"
                    >
                </label>

                <select id="deadline" aria-label="Filter deadline">
                    <option value="">Semua Deadline</option>
                    <option value="open">Masih Dibuka</option>
                    <option value="closed">Sudah Ditutup</option>
                </select>

                <select id="organizer" aria-label="Filter penyelenggara">
                    <option value="">Semua Penyelenggara</option>

                    @foreach($penyelenggara as $organizer)
                        <option value="{{ $organizer }}">
                            {{ $organizer }}
                        </option>
                    @endforeach
                </select>

                <select id="team-size" aria-label="Filter ukuran tim">
                    <option value="">Semua Ukuran Tim</option>
                    <option value="1">Individu</option>
                    <option value="2">2 Orang</option>
                    <option value="3">3 Orang</option>
                    <option value="4">4">4+ Orang</option>
                </select>
            </div>

            <!-- SORT -->
            <div class="sort-row">
                <select id="sort" aria-label="Urutkan lomba">
                    <option value="soon">Urut : Deadline Terdekat</option>
                    <option value="latest">Deadline Terjauh</option>
                    <option value="name">Nama A-Z</option>
                </select>
            </div>

            <!-- KATEGORI -->
            <div class="categories">
                <span>Kategori :</span>

                <div class="category-buttons">
                    @foreach([
                        'Semua',
                        'Teknologi dan AI',
                        'PKM Kemendikbud',
                        'Business Plan & Case',
                        'UI/UX & Desain',
                        'Essay',
                        'Hackathon',
                        'Poster'
                    ] as $category)

                        <button
                            type="button"
                            class="category-pill {{ $category === 'Semua' ? 'selected' : '' }}"
                            data-category="{{ $category }}"
                            aria-pressed="{{ $category === 'Semua' ? 'true' : 'false' }}">
                            {{ $category }}
                        </button>

                    @endforeach
                </div>
            </div>
        </section>

        <!-- GRID LOMBA -->
        <section class="competition-grid" id="competition-grid">

            @foreach($lomba as $competition)
                @php
                    $deadlineDate = \Illuminate\Support\Carbon::parse(
                        $competition->deadline
                    );

                    $isOpen = $deadlineDate->copy()
                        ->endOfDay()
                        ->gte(now());

                    $skills = $competition->skills ?? [];

                    if (is_string($skills)) {
                        $skills = json_decode($skills, true) ?: [];
                    }

                    $skills = is_array($skills) ? $skills : [];
                @endphp

                <article
                    class="competition-card"
                    data-title="{{ strtolower($competition->title) }}"
                    data-category="{{ $competition->category }}"
                    data-organizer="{{ $competition->organizer }}"
                    data-deadline="{{ $deadlineDate->format('Y-m-d') }}"
                    data-open="{{ $isOpen ? '1' : '0' }}"
                    data-team="{{ $competition->team }}"
                    data-skills="{{ strtolower(implode(' ', $skills)) }}">

                    <!-- POSTER -->
                    <div class="poster-wrap">
                        @if($competition->image)
                            <img
                                src="{{ asset(ltrim($competition->image, '/')) }}"
                                alt="Poster {{ $competition->title }}"
                                loading="lazy">
                        @else
                            <div class="poster-placeholder">
                                <span>FYS COMPETITION</span>
                                <strong>{{ $competition->title }}</strong>
                                <small>{{ $competition->category }}</small>
                            </div>
                        @endif

                        <div class="poster-badges">
                            <span class="tag">
                                {{ $competition->subtitle ?? $competition->category }}
                            </span>

                            <span class="status {{ $isOpen ? 'open' : 'closed' }}">
                                {{ $isOpen ? 'Pendaftaran Buka' : 'Pendaftaran Ditutup' }}
                            </span>
                        </div>
                    </div>

                    <!-- INFORMASI LOMBA -->
                    <div class="card-body">
                        <p class="organizer">
                            {{ $competition->organizer }}
                        </p>

                        <h2>{{ $competition->title }}</h2>

                        <p class="deadline-text">
                            🗓 Deadline:
                            {{ $deadlineDate->format('Y-m-d') }}
                        </p>

                        <p class="description">
                            {{ $competition->description ?? 'Kompetisi mahasiswa untuk mengembangkan inovasi, kreativitas, dan kolaborasi.' }}
                        </p>

                        <p class="skill-label">
                            SKILL YANG RELEVAN :
                        </p>

                        <div class="skills">
                            @foreach($skills as $skill)
                                <span>{{ $skill }}</span>
                            @endforeach
                        </div>

                        <a href="{{ route('cari_lomba.show', $competition) }}"
                           class="detail-button">
                            Lihat Detail
                        </a>
                    </div>
                </article>
            @endforeach

        </section>

        <!-- EMPTY STATE -->
        <div id="empty-state" class="empty-state" hidden>
            Tidak ada perlombaan yang sesuai dengan filter.
        </div>

    </main>

    <!-- FOOTER -->
    

<!-- FOOTER -->
<footer class="fys-footer">

    <div class="fys-footer-content">

        <!-- KOLOM KIRI -->
        <div class="fys-footer-left">

            <a href="{{ url('/home') }}" class="fys-footer-logo">
                <span>F</span>
                <small>UB</small>
            </a>

            <p>
                Find Your Skills Partner - Ekosistem kolaborasi
                mahasiswa Universitas Brawijaya untuk
                menemukan tim lomba lintas fakultas dan
                berprestasi di kancah nasional maupun
                internasional.
            </p>

            <p class="fys-footer-address">
                Kampus Utama Universitas Brawijaya, Malang,
                Jawa Timur.
            </p>

        </div>

        <!-- KOLOM TENGAH -->
        <div class="fys-footer-middle">

            <h3>KATEGORI KOMPETISI</h3>

            <p>
                Puspernas Gemastik &amp; Satria Data<br>
                Program Kreativitas Mahasiswa<br>
                Business Plan &amp; Case Competition<br>
                Karya Tulis Ilmiah<br>
                UI/UX Design &amp; Software Development<br>
                Robotic Cup<br>
                Hackathon Competition
            </p>

        </div>

        <!-- KOLOM KANAN -->
        <div class="fys-footer-right">

            <h3>RUMPUN FAKULTAS</h3>

            <p>
                Menghubungkan mahasiswa FILKOM, FEB, FT,
                FIA, FK, FTP, FISIP, FMIPA, FP, FH,
                dan FIB dalam satu sinergi juara
            </p>

            <div class="fys-footer-tip">
                <strong>Tips Juara :</strong>
                Tim multidisiplin (Teknologi + Bisnis +
                Riset) memiliki tingkat lolos 2–3x
                lebih tinggi di ajang bergengsi.
            </div>

        </div>

    </div>

</footer>


    <!-- TOMBOL TAMBAH LOMBA -->
    <div class="tambah-lomba-container">
        <span class="tambah-lomba-label">Posting Lomba</span>

        <a href="{{ url('/tambah-lomba') }}"
           class="tambah-lomba"
           title="Tambah Lomba"
           aria-label="Tambah Lomba">
            +
        </a>
    </div>

</body>
</html>
