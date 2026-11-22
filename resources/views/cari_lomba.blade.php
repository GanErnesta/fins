<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Lomba | Find Your Skills Partner</title>
    <link rel="stylesheet" href="/css/cari_lomba.css?v=42">
    <link rel="stylesheet" href="/css/navbar.css?v=41">
    <script src="/js/cari_lomba.js?v=42" defer></script>
</head>
<body>

    @include('components.navbar')

    <main class="page">

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

            <span class="result-count" id="result-count" aria-live="polite">
                Menampilkan {{ $lomba->count() }} dari {{ $lomba->count() }} ajang lomba
            </span>
        </section>

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
                     <option value="closing">Segera Tutup</option>
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
                    <option value="4">4 Orang</option>
                    <option value="5">5 Orang</option>
                    <option value="6">6 Orang</option>
                    <option value="7">7 Orang</option>
                    <option value="8">8 Orang</option>
                    <option value="9">9 Orang</option>
                    <option value="10">10 Orang</option>
                </select>

            </div>

            <div class="sort-row">
                <select id="sort" aria-label="Urutkan lomba">
                    <option value="soon">Deadline Terdekat</option>
                    <option value="latest">Deadline Terjauh</option>
                </select>
            </div>

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
                            aria-pressed="{{ $category === 'Semua' ? 'true' : 'false' }}"
                        >
                            {{ $category }}
                        </button>
                    @endforeach
                </div>
            </div>

        </section>

        <section class="competition-grid" id="competition-grid">

            @foreach($lomba as $competition)

                @php
                    $deadlineDate = \Illuminate\Support\Carbon::parse($competition->deadline);

                    $today = now()->startOfDay();

                    $daysLeft = (int) $today->diffInDays(
                        $deadlineDate->copy()->startOfDay(),
                        false
                    );

                    $isOpen = $daysLeft >= 0;

                    $isWarning = $daysLeft >= 0 && $daysLeft <= 3;

                    if ($daysLeft < 0) {
                        $statusClass = 'closed';
                        $statusText = 'Sudah Tutup';
                    } elseif ($isWarning) {
                        $statusClass = 'warning';
                        $statusText = 'Segera Ditutup';
                    } else {
                        $statusClass = 'open';
                        $statusText = 'Pendaftaran Buka';
                    }

                    $warningText = $isWarning
                        ? ($daysLeft === 0
                            ? 'Pendaftaran ditutup hari ini!'
                            : 'Ditutup dalam ' . $daysLeft . ' hari')
                        : '';

                    $skills = $competition->skills ?? [];

                    if (is_string($skills)) {
                        $skills = json_decode($skills, true) ?: [];
                    }

                    if ($skills instanceof \Illuminate\Support\Collection) {
                        $skills = $skills->all();
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
                    data-skills="{{ strtolower(implode(' ', $skills)) }}"
                >

                    <div class="poster-wrap">

                        @if($competition->image)
                            @php
                                // Poster lokal mengikuti domain aktif (localhost / Cloudflare).
                                // URL eksternal HTTPS tetap digunakan tanpa diubah.
                                $posterPath = trim((string) $competition->image);
                                $posterUrl = preg_match('~^https?://~i', $posterPath)
                                    ? $posterPath
                                    : '/' . ltrim($posterPath, '/');
                            @endphp
                            <img
                                src="{{ $posterUrl }}"
                                alt="Poster {{ $competition->title }}"
                                loading="lazy"
                            >
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

                            
                        
                            <span class="status {{ $statusClass }}">
                                {{ $statusText }}
                            </span>


                        </div>

                       
                        @if($isWarning)
                            <div class="deadline-warning" role="status">
                                <span class="warning-icon">⚠</span>
                                <span>{{ $warningText }}</span>
                            </div>
                        @endif


                    </div>

                    <div class="card-body">

                        <p class="organizer">
                            {{ $competition->organizer }}
                        </p>

                        <h2>{{ $competition->title }}</h2>

                        <p class="deadline-text {{ $isWarning ? 'deadline-urgent' : '' }}">
                            🗓 Deadline : {{ $deadlineDate->format('Y-m-d') }}
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

                        <a
                            href="{{ route('cari_lomba.show', ['id' => $competition->id], false) }}"
                            class="detail-button"
                        >
                            Lihat Detail
                        </a>

                    </div>

                </article>

            @endforeach

        </section>

        <div id="empty-state" class="empty-state" hidden>
            Tidak ada perlombaan yang sesuai dengan filter.
        </div>

    </main>

    <footer class="fys-footer">
        <div class="fys-footer-content">

            <div class="fys-footer-left">
                <a href="/home" class="fys-footer-logo">
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
                    Kampus Utama Universitas Brawijaya,
                    Malang, Jawa Timur.
                </p>
            </div>

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

            <div class="fys-footer-right">
                <h3>RUMPUN FAKULTAS</h3>

                <p>
                    Menghubungkan mahasiswa FILKOM, FEB, FT,
                    FIA, FK, FTP, FISIP, FMIPA, FP, FH,
                    dan FIB dalam satu sinergi juara.
                </p>

                <div class="fys-footer-tip">
                    <strong>Tips Juara :</strong>
                    Tim multidisiplin (Teknologi + Bisnis + Riset)
                    memiliki tingkat lolos 2–3x lebih tinggi
                    di ajang bergengsi.
                </div>
            </div>

        </div>
    </footer>

    <div class="tambah-lomba-container">
        <span class="tambah-lomba-label">Posting Lomba</span>

        <a
            href="{{ route('posting-lomba', [], false) }}"
            class="tambah-lomba"
            title="Tambah Lomba"
            aria-label="Tambah Lomba"
        >
            +
        </a>
    </div>

</body>
</html>
