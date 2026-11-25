<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cari Lomba | Find Your Skills Partner</title>

    {{-- CSS dengan versi otomatis --}}
    <link rel="stylesheet"
          href="/css/cari_lomba.css?v={{ filemtime(public_path('css/cari_lomba.css')) }}">

    <link rel="stylesheet"
          href="/css/navbar.css?v={{ filemtime(public_path('css/navbar.css')) }}">

    <link rel="stylesheet"
          href="/css/cari_lomba_detail.css?v={{ filemtime(public_path('css/cari_lomba_detail.css')) }}">

    {{-- JavaScript dengan versi otomatis --}}
    <script
        src="/js/cari_lomba.js?v={{ filemtime(public_path('js/cari_lomba.js')) }}"
        defer>
    </script>

    <script
        src="/js/cari_lomba_detail.js?v={{ filemtime(public_path('js/cari_lomba_detail.js')) }}"
        defer>
    </script>

    {{-- CSS tambahan khusus menu pemilik lomba --}}
    <style>
        .competition-owner-menu {
            position: relative;
            margin: 12px 0;
        }

        .competition-owner-menu summary {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            color: #8b0000;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            list-style: none;
            padding: 8px 0;
        }

        .competition-owner-menu summary::-webkit-details-marker {
            display: none;
        }

        .competition-owner-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 8px;
            background: #ffffff;
            border: 1px solid #ead5d5;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
        }

        .competition-owner-actions a,
        .competition-owner-actions button {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 12px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 500;
            text-align: left;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            background: transparent;
            cursor: pointer;
            box-sizing: border-box;
        }

        .competition-owner-actions a {
            color: #8b0000;
        }

        .competition-owner-actions button {
            color: #c62828;
        }

        .competition-owner-actions a:hover,
        .competition-owner-actions button:hover {
            background: #fff0f0;
        }

        .competition-owner-actions form {
            margin: 0;
            width: 100%;
        }

        .competition-owner-label {
            display: inline-block;
            margin-top: 10px;
            color: #8b0000;
            font-size: 11px;
            font-weight: 600;
            background: #fff0f0;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .competition-menu-icon {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .competition-error {
            margin-bottom: 16px;
            padding: 12px 16px;
            background: #fff0f0;
            color: #8b0000;
            border: 1px solid #e4a4a0;
            border-radius: 10px;
        }
    </style>

</head>
<body>

    <!-- Navbar bersama semua halaman FINS -->
    @include('components.navbar')

    <main class="page">

        <!-- Judul halaman -->
        <section class="intro">
            <div>
                <div class="eyebrow">
                    DIREKTORI KOMPETISI MAHASISWA
                </div>

                <h1>
                    EKSPLORASI DAN TEMUKAN PERLOMBAAN
                </h1>

                <p>
                    Kumpulan ajang kompetisi resmi tingkat regional,
                    nasional, dan internasional yang relevan bagi
                    mahasiswa Universitas Brawijaya.
                </p>
            </div>

            <span class="result-count"
                  id="result-count"
                  aria-live="polite">

                Menampilkan {{ $lomba->count() }}
                dari {{ $lomba->count() }} ajang lomba

            </span>
        </section>

        <!-- Notifikasi sukses -->
        @if(session('success'))
            <div class="posting-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        <!-- Notifikasi gagal -->
        @if(session('error'))
            <div class="competition-error" role="alert">
                {{ session('error') }}
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

                <select id="deadline"
                        aria-label="Filter deadline">

                    <option value="">Semua Deadline</option>
                    <option value="open">Masih Dibuka</option>
                    <option value="warning">Segera Tutup</option>
                    <option value="closed">Sudah Ditutup</option>

                </select>

                <select id="organizer"
                        aria-label="Filter penyelenggara">

                    <option value="">
                        Semua Penyelenggara
                    </option>

                    @foreach($penyelenggara as $organizer)

                        <option value="{{ $organizer }}">
                            {{ $organizer }}
                        </option>

                    @endforeach

                </select>

                <select id="team-size"
                        aria-label="Filter ukuran tim">

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

                <select id="sort"
                        aria-label="Urutkan lomba">

                    <option value="soon">
                        Deadline Terdekat
                    </option>

                    <option value="latest">
                        Deadline Terjauh
                    </option>

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
        <section class="competition-grid"
                 id="competition-grid">

            @foreach($lomba as $competition)

                @php
                    $deadlineDate = \Illuminate\Support\Carbon::parse(
                        $competition->deadline
                    );

                    $today = now()->startOfDay();

                    $daysLeft = (int) $today->diffInDays(
                        $deadlineDate->copy()->startOfDay(),
                        false
                    );

                    $isOpen = $daysLeft >= 0;

                    $isWarning = $daysLeft >= 0
                        && $daysLeft <= 3;

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
                        ? (\Illuminate\Support\Str::startsWith(
                            $image,
                            ['http://', 'https://']
                        )
                            ? $image
                            : '/' . ltrim($image, '/'))
                        : null;

                    // Mengecek apakah pengguna adalah pemilik lomba
                    $isOwner = auth()->check()
                        && $competition->user_id !== null
                        && (string) auth()->id()
                            === (string) $competition->user_id;

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

                    <!-- Poster lomba -->
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

                                <strong>
                                    {{ $competition->title }}
                                </strong>

                                <small>
                                    {{ $competition->category }}
                                </small>

                            </div>

                        @endif

                        <div class="poster-badges">

                            <span class="tag">

                                {{ $competition->subtitle
                                    ?? $competition->category }}

                            </span>

                            <span class="status {{ $statusClass }}">

                                {{ $statusText }}

                            </span>

                        </div>

                        @if($isWarning)

                            <div class="deadline-warning"
                                 role="status">

                                <span class="warning-icon">
                                    ⚠
                                </span>

                                <span>
                                    {{ $warningText }}
                                </span>

                            </div>

                        @endif

                    </div>

                    <!-- Informasi lomba -->
                    <div class="card-body">

                        <p class="organizer">
                            {{ $competition->organizer }}
                        </p>

                        <h2>
                            {{ $competition->title }}
                        </h2>

                        <p class="deadline-text {{ $isWarning ? 'deadline-urgent' : '' }}">

                            🗓 Deadline :
                            {{ $deadlineDate->format('Y-m-d') }}

                        </p>

                        <p class="description">

                            {{ $competition->description
                                ?? 'Kompetisi mahasiswa untuk mengembangkan inovasi, kreativitas, dan kolaborasi.' }}

                        </p>

                        <p class="skill-label">
                            SKILL YANG RELEVAN :
                        </p>

                        <div class="skills">

                            @forelse($skills as $skill)

                                <span>{{ $skill }}</span>

                            @empty

                                <span class="skill-empty">
                                    Belum ada skill
                                </span>

                            @endforelse

                        </div>

                        <!-- =================================
                             FITUR KHUSUS PEMILIK LOMBA
                        ================================== -->

                        @if($isOwner)

                            <span class="competition-owner-label">
                                Postingan Anda
                            </span>

                            <details class="competition-owner-menu">

                                <summary aria-label="Kelola lomba">

                                    <svg
                                        class="competition-menu-icon"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="5" r="2"/>
                                        <circle cx="12" cy="12" r="2"/>
                                        <circle cx="12" cy="19" r="2"/>
                                    </svg>

                                    Kelola Lomba

                                </summary>

                                <div class="competition-owner-actions">

                                    <!-- Tombol Edit Lomba -->
                                    <a href="{{ route('cari_lomba.edit', ['id' => $competition->id], false) }}">

                                        <svg
                                            class="competition-menu-icon"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L9 17l-4 1 1-4Z"/>
                                        </svg>

                                        Edit Lomba

                                    </a>

                                    <!-- Tombol Hapus Lomba -->
                                    <form
                                        method="POST"
                                        action="{{ route('cari_lomba.destroy', ['id' => $competition->id], false) }}"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus lomba ini? Data yang sudah dihapus tidak dapat dikembalikan.');"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit">

                                            <svg
                                                class="competition-menu-icon"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                aria-hidden="true"
                                            >
                                                <path d="M3 6h18"/>
                                                <path d="M8 6V4h8v2"/>
                                                <path d="M19 6l-1 14H6L5 6"/>
                                                <path d="M10 10v6"/>
                                                <path d="M14 10v6"/>
                                            </svg>

                                            Hapus Lomba

                                        </button>

                                    </form>

                                </div>

                            </details>

                        @endif

                        <!-- Tombol Lihat Detail -->
                        <a
                            href="{{ route('cari_lomba.show', ['id' => $competition->id], false) }}"
                            class="detail-button"
                            data-detail-link
                        >
                            Lihat Detail
                        </a>

                    </div>

                </article>

            @endforeach

        </section>

        <div id="empty-state"
             class="empty-state"
             hidden>

            Tidak ada perlombaan yang sesuai dengan filter.

        </div>

    </main>

    <!-- Footer -->
    <footer class="fys-footer">

        <div class="fys-footer-content">

            <div class="fys-footer-left">

                <a href="/home"
                   class="fys-footer-logo">

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

    <!-- Tombol posting lomba -->
    <div class="tambah-lomba-container">

        <span class="tambah-lomba-label">
            Posting Lomba
        </span>

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