
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Lomba | Find Your Skills Partner</title>

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/cari_lomba.css') }}?v=41">
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/cari_lomba_detail.css') }}?v=3">

    <!-- JavaScript: masing-masing cukup sekali -->
    <script src="{{ asset('js/cari_lomba.js') }}?v=41" defer></script>
    <script src="{{ asset('js/cari_lomba_detail.js') }}?v=11" defer></script>
</head>
<body>

    <!-- Navbar bersama semua halaman FINS -->
    @include('components.navbar')

    <main class="page">
        <!-- Judul halaman -->
        <section class="intro">
            <div>
                <div class="eyebrow">DIREKTORI KOMPETISI MAHASISWA</div>
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

        <!-- Notifikasi sukses setelah posting lomba -->
        @if(session('success'))
            <div class="posting-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <!-- Filter lomba -->
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
                    <option value="warning">Segera Tutup</option>
                    <option value="closed">Sudah Ditutup</option>
                </select>

                <select id="organizer" aria-label="Filter penyelenggara">
                    <option value="">Semua Penyelenggara</option>
                    @foreach($penyelenggara as $organizer)
                        <option value="{{ $organizer }}">{{ $organizer }}</option>
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

        <!-- Daftar lomba -->
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

                    $image = $competition->image ?? null;
                    $posterUrl = $image
                        ? (\Illuminate\Support\Str::startsWith($image, ['http://', 'https://'])
                            ? $image
                            : asset(ltrim($image, '/')))
                        : null;
                @endphp

                <article
                    class="competition-card"
                    data-title="{{ strtolower($competition->title) }}"
                    data-category="{{ $competition->category }}"
                    data-organizer="{{ $competition->organizer }}"
                    data-deadline="{{ $deadlineDate->format('Y-m-d') }}"
                    data-open="{{ $isOpen ? '1' : '0' }}"
                    data-status="{{ $statusClass }}"
                    data-team="{{ $competition->team }}"
                    data-skills="{{ strtolower(implode(' ', $skills)) }}"
                >
                    <div class="poster-wrap">
                        @if($posterUrl)
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
                        <p class="organizer">{{ $competition->organizer }}</p>
                        <h2>{{ $competition->title }}</h2>
                        <p class="deadline-text {{ $isWarning ? 'deadline-urgent' : '' }}">
                            🗓 Deadline : {{ $deadlineDate->format('Y-m-d') }}
                        </p>
                        <p class="description">
                            {{ $competition->description ?? 'Kompetisi mahasiswa untuk mengembangkan inovasi, kreativitas, dan kolaborasi.' }}
                        </p>
                        <p class="skill-label">SKILL YANG RELEVAN :</p>
                        <div class="skills">
                            @forelse($skills as $skill)
                                <span>{{ $skill }}</span>
                            @empty
                                <span class="skill-empty">Belum ada skill</span>
                            @endforelse
                        </div>

                        <a
                            href="{{ route('cari_lomba.show', ['id' => $competition->id]) }}"
                            class="detail-button"
                            data-detail-link
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

    <!-- Footer -->
    <footer class="fys-footer">
        <div class="fys-footer-content">
            <div class="fys-footer-left">
                <a href="{{ route('home') }}" class="fys-footer-logo">
                    <span>F</span><small>UB</small>
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

    <!-- Tombol posting lomba -->
    <div class="tambah-lomba-container">
        <span class="tambah-lomba-label">Posting Lomba</span>
        <a
            href="{{ route('posting-lomba') }}"
            class="tambah-lomba"
            title="Tambah Lomba"
            aria-label="Tambah Lomba"
        >
            +
        </a>
    </div>
</body>
</html>
