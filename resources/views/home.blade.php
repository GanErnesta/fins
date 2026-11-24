@php
    // Data contoh kompetisi untuk tampilan Home.
    // Autentikasi ditangani Laravel.

    $kompetisi = [
        [
            'title' => 'Business Plan Competition 2025',
            'short' => 'Business Plan',
            'description' => 'Bergabung dengan teman yang punya visi dan semangat untuk membangun solusi nyata bagi masyarakat.',
            'category' => 'Business Plan',
            'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=900&q=80'
        ],
        [
            'title' => 'Multimedia in Action 2026',
            'short' => 'Multimedia in Action 2026',
            'description' => 'Berkolaborasi membuat karya multimedia kreatif bersama tim dengan minat yang sama.',
            'category' => 'Hackathon',
            'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=900&q=80'
        ],
        [
            'title' => 'Robotics 2026',
            'short' => 'Robotics 2026',
            'description' => 'Temukan partner yang tertarik pada teknologi, inovasi, dan pengembangan robotika.',
            'category' => 'Robotics',
            'image' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=900&q=80'
        ],
        [
            'title' => 'UI/UX Design Challenge',
            'short' => 'UI/UX Design Challenge',
            'description' => 'Wujudkan ide produk digital dengan partner yang memahami desain dan kebutuhan pengguna.',
            'category' => 'UI/UX',
            'image' => 'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?auto=format&fit=crop&w=900&q=80'
        ],
        [
            'title' => 'Data Science Challenge',
            'short' => 'Data Science Challenge',
            'description' => 'Bangun solusi berbasis data dan AI bersama mahasiswa dengan keahlian yang saling melengkapi.',
            'category' => 'Data Science',
            'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=900&q=80'
        ],
        [
            'title' => 'Karya Tulis Ilmiah',
            'short' => 'Karya Tulis Ilmiah',
            'description' => 'Cari rekan untuk berdiskusi, melakukan riset, dan menyusun karya ilmiah bersama.',
            'category' => 'KTI',
            'image' => 'https://images.unsplash.com/photo-1455390582262-044c599f4a82?auto=format&fit=crop&w=900&q=80'
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Beranda | FINS</title>

    {{-- CSS dengan versi otomatis --}}
    <link rel="stylesheet"
          href="/css/home.css?v={{ filemtime(public_path('css/home.css')) }}">

    <link rel="stylesheet"
          href="/css/navbar.css?v={{ filemtime(public_path('css/navbar.css')) }}">

    {{-- JavaScript dengan versi otomatis --}}
    <script
        src="/js/home.js?v={{ filemtime(public_path('js/home.js')) }}"
        defer>
    </script>

</head>

<body>

    <!-- =====================================
         NAVBAR GLOBAL FINS
    ====================================== -->
    @include('components.navbar')

    <main>

        <!-- =====================================
             HERO SECTION
        ====================================== -->
        <section class="home-hero">

            <div class="hero-shade"></div>

            <div class="hero-content">

                <span class="hero-badge">
                    <span class="people-icon">♟♟</span>
                    Kolaborasi Dimulai di sini
                </span>

                <h1>
                    Temukan Partner, Bangun
                    <br class="desktop-break">
                    Tim, Raih Prestasi.
                </h1>

                <p>
                    Cari rekan satu tim yang memiliki
                    minat, skill, dan tujuan yang sama!
                </p>

            </div>

        </section>

        <!-- =====================================
             PENCARIAN TIM
        ====================================== -->
        <form class="team-search" id="teamSearchForm">

            <!-- BIDANG LOMBA -->
            <label class="search-select">

                <span class="select-symbol">✿</span>

                <select
                    id="competitionSelect"
                    aria-label="Cari bidang lomba"
                >
                    <option value="">
                        Cari Bidang Lomba
                    </option>

                    <option value="Business Plan">
                        Business Plan
                    </option>

                    <option value="Hackathon">
                        Hackathon
                    </option>

                    <option value="Robotics">
                        Robotics
                    </option>

                    <option value="UI/UX">
                        UI/UX Design
                    </option>

                    <option value="Data Science">
                        Data Science / AI
                    </option>

                    <option value="KTI">
                        Karya Tulis Ilmiah
                    </option>
                </select>

            </label>

            <!-- SKILL -->
            <label class="search-select">

                <span class="select-symbol">♟</span>

                <select
                    id="skillSelect"
                    aria-label="Skill yang dibutuhkan"
                >
                    <option value="">
                        Skill yang dibutuhkan
                    </option>

                    <option value="UI/UX">
                        UI/UX Design
                    </option>

                    <option value="Web Development">
                        Web Development
                    </option>

                    <option value="Programming">
                        Programming
                    </option>

                    <option value="Data Science">
                        Data Science / AI
                    </option>

                    <option value="Public Speaking">
                        Public Speaking
                    </option>
                </select>

            </label>

            <!-- LOKASI -->
            <label class="search-select">

                <span class="select-symbol">⌖</span>

                <select
                    id="locationSelect"
                    aria-label="Lokasi"
                >
                    <option value="">
                        Lokasi / online
                    </option>

                    <option value="Malang">
                        Malang
                    </option>

                    <option value="Online">
                        Online
                    </option>

                    <option value="Hybrid">
                        Hybrid
                    </option>
                </select>

            </label>

            <!-- TOMBOL CARI -->
            <button
                type="submit"
                class="find-team-button"
            >
                <span>⌕</span>
                Temukan Tim
            </button>

        </form>

        <!-- =====================================
             INTRO FINS
        ====================================== -->
        <section class="home-intro">

            <div class="intro-image">

                <img
                    src="https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1100&q=85"
                    alt="Mahasiswa berdiskusi dan bekerja sama"
                    loading="lazy"
                >

            </div>

            <div class="intro-copy">

                <h2>
                    Capai Tujuan Bersama dengan
                    Rekan dan Tim yang Tepat
                </h2>

                <p>
                    FINS memberikan kesempatan untuk kamu
                    bisa menemukan partner sesuai dengan
                    bidang, skill serta tujuan yang kamu
                    dan timmu butuhkan.
                </p>

                <a
                    class="primary-link"
                    href="{{ route('cari-partner', [], false) }}"
                >
                    Pelajari cara kerja
                    <span>→</span>
                </a>

            </div>

        </section>

        <!-- =====================================
             KOMPETISI
        ====================================== -->
        <section
            class="competition-section"
            id="kompetisi"
        >

            <h2>Gabung Sebagai Partner Lomba</h2>

            <div
                class="competition-track"
                id="competitionTrack"
            >

                @foreach($kompetisi as $item)

                    <article
                        class="competition-card"
                        data-category="{{ $item['category'] }}"
                        data-search="{{ \Illuminate\Support\Str::lower($item['title'] . ' ' . $item['description'] . ' ' . $item['category']) }}"
                    >

                        <img
                            src="{{ $item['image'] }}"
                            alt="{{ $item['title'] }}"
                            loading="lazy"
                        >

                        <h3>
                            {{ $item['short'] }}
                        </h3>

                        <p>
                            {{ $item['description'] }}
                        </p>

                        <a
                            href="{{ route('cari-partner', [], false) }}"
                            class="competition-link"
                        >
                            Lihat Partner
                            <span>→</span>
                        </a>

                    </article>

                @endforeach

            </div>

            <!-- CAROUSEL -->
            <div class="carousel-controls">

                <button
                    type="button"
                    id="previousCards"
                    aria-label="Kompetisi sebelumnya"
                >
                    ‹
                </button>

                <div
                    class="carousel-dots"
                    id="carouselDots"
                    aria-label="Pilih halaman kompetisi"
                ></div>

                <button
                    type="button"
                    id="nextCards"
                    aria-label="Kompetisi berikutnya"
                >
                    ›
                </button>

            </div>

            <p
                class="search-feedback"
                id="searchFeedback"
                aria-live="polite"
            ></p>

        </section>

    </main>

    <!-- =====================================
         FOOTER FINS
    ====================================== -->
    <footer class="home-footer">

        <div class="footer-main">

            <!-- INFORMASI FINS -->
            <div class="footer-about">

                <div class="footer-logo">
                    F<span>UB</span>
                </div>

                <p>
                    Find Your Skills Partner — Ekosistem
                    kolaborasi mahasiswa Universitas
                    Brawijaya untuk menemukan tim lomba
                    lintas fakultas dan berprestasi
                    di kancah nasional maupun internasional.
                </p>

                <small>
                    Kampus Utama Universitas Brawijaya,
                    <br>
                    Malang, Jawa Timur
                </small>

            </div>

            <!-- EKSPLORASI -->
            <div class="footer-column">

                <h3>Eksplorasi Platform</h3>

                <a href="{{ route('cari-lomba', [], false) }}">
                    Daftar Kompetisi Mahasiswa
                </a>

                <a href="{{ route('cari-partner', [], false) }}">
                    Cari Rekan &amp; Partner Tim
                </a>

                <a href="{{ route('posting-lomba', [], false) }}">
                    Publikasi Kebutuhan Tim
                </a>

                <a href="{{ route('lomba-saya', [], false) }}">
                    Dashboard Lomba Saya
                </a>

                <a href="#">
                    Manajemen Tim Saya
                </a>

                <a href="#">
                    Pusat Notifikasi &amp; Aktivitas
                </a>

            </div>

            <!-- KATEGORI -->
            <div class="footer-column">

                <h3>Kategori Kompetisi</h3>

                <a href="#kompetisi">
                    GEMASTIK &amp; Satria Data
                </a>

                <a href="#kompetisi">
                    Program Kreativitas Mahasiswa (PKM)
                </a>

                <a href="#kompetisi">
                    Business Plan &amp; Case Competition
                </a>

                <a href="#kompetisi">
                    Lomba Karya Tulis Ilmiah (LKTI)
                </a>

                <a href="#kompetisi">
                    UI/UX Design &amp; Software Development
                </a>

            </div>

            <!-- FAKULTAS -->
            <div class="footer-column faculty-column">

                <h3>Rumpun Fakultas UB</h3>

                <p>
                    Menghubungkan mahasiswa FILKOM,
                    FEB, FT, FIA, FK, FPIK, FISIP,
                    FMIPA, dan fakultas lainnya
                    dalam satu sinergi juara.
                </p>

                <div class="footer-tip">
                    <strong>Tips Juara:</strong>
                    Tim multidisiplin (Teknologi + Bisnis + Desain)
                    memiliki tingkat lolos 3,2× lebih tinggi
                    di ajang bergengsi.
                </div>

            </div>

        </div>

        <!-- FOOTER BAWAH -->
        <div class="footer-bottom">

            <span>
                © 2026 FINS — Find Your Skills Partner.
                Universitas Brawijaya.
            </span>

            <div>

                <a href="#">
                    Pedoman Etika Kolaborasi
                </a>

                <a href="#">
                    Panduan Verifikasi UB SSO
                </a>

                <a href="#">
                    Pusat Bantuan
                </a>

            </div>

        </div>

    </footer>

</body>
</html>