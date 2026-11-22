
@php
    // Helper avatar menggunakan inisial nama
    $avatar = function (string $nama, int $size = 40) {
        $warna = [
            '#6C5CE7',
            '#4FC3F7',
            '#FF6B9D',
            '#2D3436',
            '#F6B93B',
            '#10B981'
        ];

        $bg = $warna[crc32($nama) % count($warna)];

        $ini = collect(explode(' ', trim($nama)))
            ->filter()
            ->map(fn ($k) => mb_substr($k, 0, 1))
            ->take(2)
            ->join('');

        $fs = round($size * 0.38);

        return '<span class="avatar" style="width:'.$size.'px;height:'.$size.'px;background:'.$bg.';font-size:'.$fs.'px">'
            .e($ini).
            '</span>';
    };
@endphp

<!DOCTYPE html>
<html lang="id">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Lomba Saya — FINS</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- CSS Lomba Saya -->
    
    <link rel="stylesheet" href="{{ asset('css/lomba_saya.css') }}?v=12">
</head>


<body>

    {{-- ================= NAVBAR ================= --}}

    <header class="navbar">

        
        <a href="{{ url('/home') }}" class="navbar__logo" aria-label="FINS UB">
            <img
                src="{{ asset('assets/images/logo fins.png') }}"
                alt="Logo FINS UB"
                style="width: 48px; height: 48px; object-fit: contain; display: block;"
            >
        </a>


        <nav class="navbar__menu">

            <a href="{{ url('/home') }}">
                Beranda
            </a>

            <a href="{{ url('/cari-lomba') }}">
                Cari Lomba
            </a>

            <a href="{{ url('/cari-partner') }}">
                Cari Partner
            </a>

            <a href="{{ url('/lomba-saya') }}" class="active">
                Lomba Saya
            </a>

        </nav>

        
    <div class="navbar__actions">

        <!-- IKON CHAT -->
        <a href="{{ url('/chat') }}"
        class="nav-icon"
        aria-label="Chat">

            <svg width="25" height="25"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M20 11.5a8 8 0 0 1-8 8
                        8.5 8.5 0 0 1-3.5-.8L4 20l1.3-4.5
                        A8 8 0 1 1 20 11.5Z"/>

                <path d="M8 10h8"/>
                <path d="M8 14h5"/>

            </svg>
        </a>

        <!-- IKON NOTIFIKASI -->
        <a href="{{ url('/notifikasi') }}"
        class="nav-icon notification-icon"
        aria-label="Notifikasi">

            <svg width="25" height="25"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M18 8a6 6 0 0 0-12 0
                        c0 7-3 8-3 8h18s-3-1-3-8"/>

                <path d="M10 20a2 2 0 0 0 4 0"/>

            </svg>

            <span class="notification-dot"></span>
        </a>

        <!-- IKON PROFIL -->
        <a href="{{ url('/profile') }}"
        class="navbar-profile"
        aria-label="Profil">

            <img
                src="{{ asset('assets/images/logo fins.png') }}"
                alt="Profil FINS"
                class="navbar-profile-img"
            >

        </a>

    </div>


    </header>


    {{-- ================= MAIN ================= --}}

    <main class="page">

        {{-- ================= HERO ================= --}}

        
        <section class="hero">
            <div>
                <p class="hero__eyebrow">
                    DASHBOARD PRIBADI MAHASISWA
                </p>

                <h1>Lomba & Aktivitas Tim Saya</h1>

                <p class="hero__desc">
                    Pantau status perlombaan yang diikuti,
                    manajemen tim aktif, tinjau calon anggota
                    yang mendaftar, serta status permohonan
                    gabung tim Anda.
                </p>
            </div>
        </section>



        {{-- ================= TABS ================= --}}

        <div class="tabs" role="tablist">

            <button type="button"
                    class="tab active"
                    data-tab="lomba-diikuti"
                    role="tab">

                Lomba Diikuti ({{ count($lombaDiikuti) }})

            </button>

            <button type="button"
                    class="tab"
                    data-tab="tim-saya"
                    role="tab">

                Tim Saya

            </button>

            <button type="button"
                    class="tab"
                    data-tab="posting-rekrut"
                    role="tab">

                Posting Rekrut Saya ({{ count($postingRekrut) }})

            </button>

            <button type="button"
                    class="tab"
                    data-tab="pengajuan"
                    role="tab">

                Pengajuan Saya ({{ count($pengajuan) }})

            </button>

        </div>


        {{-- ========================================= --}}
        {{-- TAB 1 : LOMBA DIIKUTI --}}
        {{-- ========================================= --}}

        <section class="panel active" id="lomba-diikuti">

            <div class="grid grid--3">

                @forelse ($lombaDiikuti as $l)

                    <article class="card">

                        <div class="card__head">

                            <span class="card__category">
                                {{ $l['kategori'] }}
                            </span>

                            <span class="badge badge--{{ $l['status'] }}">
                                {{ $l['status_label'] }}
                            </span>

                        </div>

                        <h3 class="card__title">
                            {{ $l['judul'] }}
                        </h3>

                        <p class="card__team">
                            Tim: {{ $l['tim'] }} · {{ $l['peran'] }}
                        </p>

                        <div class="milestone">

                            <small>
                                TARGET MILESTONE TERDEKAT:
                            </small>

                            <strong>
                                {{ $l['milestone'] }}
                            </strong>

                            <em>
                                Batas Waktu: {{ $l['deadline'] }}
                            </em>

                        </div>

                        <p class="muted">
                            Rekan Formasi Tim:
                        </p>

                        <ul class="members members--compact">

                            @foreach ($l['anggota'] as $a)

                                <li>

                                    {!! $avatar($a['nama'], 32) !!}

                                    <b>
                                        {{ $a['nama'] }}
                                    </b>

                                    <span>
                                        {{ $a['peran'] }}
                                    </span>

                                </li>

                            @endforeach

                        </ul>

                        <div class="card__foot">

                            <span class="muted">
                                Kesiapan Dokumen:
                                {{ $l['kesiapan'] }}%
                            </span>

                            <a href="{{ url('/chat') }}" class="link">
                                Buka Diskusi Tim →
                            </a>

                        </div>

                    </article>

                @empty

                    <p class="muted">
                        Belum ada lomba yang diikuti.
                    </p>

                @endforelse

            </div>

        </section>


        {{-- ========================================= --}}
        {{-- TAB 2 : TIM SAYA --}}
        {{-- ========================================= --}}

        <section class="panel" id="tim-saya">

            <div class="grid grid--2">

                @forelse ($timSaya as $t)

                    <article class="card card--tim">

                        <div class="card__head">

                            <h3 class="card__title card__title--lg">
                                {{ $t['nama'] }}
                            </h3>

                            <span class="badge
                                {{ $t['status'] === 'Aktif' ? 'badge--tim-aktif' :
                                ($t['status'] === 'Menunggu' ? 'badge--tim-menunggu' : 'badge--tim-diterima') }}">
                                {{ $t['status'] }}
                            </span>

                        </div>

                        <p class="card__sub">
                            {{ $t['lomba'] }}
                        </p>

                        <p class="label">
                            ANGGOTA FORMASI TIM:
                        </p>

                        <ul class="members members--grid">

                            @foreach ($t['anggota'] as $a)

                                <li class="member-box">

                                    {!! $avatar($a['nama'], 50) !!}

                                    <div>

                                        <b>
                                            {{ $a['nama'] }}
                                        </b>

                                        <span>
                                            {{ $a['peran'] }}
                                        </span>

                                    </div>

                                </li>

                            @endforeach

                        </ul>

                        <div class="coord">

                            <span>
                                Pusat Koordinasi:
                                Google Drive & Chat FINS
                            </span>

                            <a href="{{ url('/chat') }}" class="link">
                                Buka Chat Grup
                            </a>

                        </div>

                    </article>

                @empty

                    <p class="muted">
                        Kamu belum bergabung dengan tim.
                    </p>

                @endforelse

            </div>

        </section>


        
{{-- ========================================= --}}
{{-- TAB 3 : POSTING REKRUT SAYA + CRUD --}}
{{-- ========================================= --}}

<section class="panel" id="posting-rekrut">

    <div class="rekrut-crud-header">
        <div>
            <h3>Posting Rekrut Saya</h3>
            <p>Kelola postingan pencarian anggota tim kamu.</p>
        </div>

        <button type="button"
                class="btn-tambah-rekrut"
                id="btnTambahRekrut">
            + Tambah Rekrutmen
        </button>
    </div>

    {{-- Daftar postingan ditampilkan oleh JavaScript --}}
    <div id="daftarPostingRekrut"></div>

</section>


{{-- ========================================= --}}
{{-- MODAL TAMBAH DAN EDIT REKRUTMEN --}}
{{-- ========================================= --}}

<div class="modal-rekrut" id="modalRekrut" aria-hidden="true">

    <div class="modal-rekrut-content"
         role="dialog"
         aria-modal="true"
         aria-labelledby="judulModalRekrut">

        <div class="modal-rekrut-header">
            <h2 id="judulModalRekrut">Tambah Rekrutmen</h2>

            <button type="button"
                    id="tutupModalRekrut"
                    class="modal-rekrut-close"
                    aria-label="Tutup formulir">
                &times;
            </button>
        </div>

        <form id="formRekrut">

            <input type="hidden" id="editRekrutId">

            <div class="form-group-rekrut">
                <label for="judulRekrut">Nama Lomba *</label>
                <input type="text"
                       id="judulRekrut"
                       placeholder="Contoh: Gemastik XVIII"
                       required>
            </div>

            <div class="form-group-rekrut">
                <label for="kategoriRekrut">Kategori *</label>
                <input type="text"
                       id="kategoriRekrut"
                       placeholder="Contoh: UI/UX & Desain"
                       required>
            </div>

            <div class="form-group-rekrut">
                <label for="posisiRekrut">Posisi yang Dicari *</label>
                <input type="text"
                       id="posisiRekrut"
                       placeholder="Contoh: UX Researcher"
                       required>
            </div>

            <div class="form-group-rekrut">
                <label for="slotRekrut">Jumlah Slot *</label>
                <input type="number"
                       id="slotRekrut"
                       min="1"
                       max="100"
                       value="1"
                       required>
            </div>

            <div class="form-group-rekrut">
                <label for="skillRekrut">Keahlian yang Dibutuhkan *</label>
                <input type="text"
                       id="skillRekrut"
                       placeholder="Figma, User Interview, Microcopy"
                       required>
                <small>Pisahkan setiap keahlian dengan koma.</small>
            </div>

            <div class="form-group-rekrut">
                <label for="statusRekrut">Status Rekrutmen</label>

                <select id="statusRekrut">
                    <option value="Aktif">Aktif</option>
                    <option value="Ditutup">Ditutup</option>
                </select>
            </div>

            <div class="modal-rekrut-actions">
                <button type="button"
                        class="btn-batal-rekrut"
                        id="batalRekrut">
                    Batal
                </button>

                <button type="submit"
                        class="btn-simpan-rekrut">
                    Simpan Rekrutmen
                </button>
            </div>

        </form>
    </div>
</div>

{{-- Data contoh dari controller untuk tampilan awal --}}
<script>
    window.postingRekrutAwal =
        {{ Illuminate\Support\Js::from($postingRekrut) }};
</script>



        {{-- ========================================= --}}
        {{-- TAB 4 : PENGAJUAN SAYA --}}
        {{-- ========================================= --}}

        <section class="panel" id="pengajuan">

            @forelse ($pengajuan as $p)

                <article class="card card--wide">

                    <div class="card__head">

                        <h3 class="card__title card__title--lg">
                            {{ $p['judul'] }}
                        </h3>

                        <span
                            class="badge badge--{{ $p['status'] === 'Diterima' ? 'diterima-hijau' : 'menunggu' }}">

                            {{ $p['status'] }}

                        </span>

                    </div>

                    <p class="card__team card__team--plain">

                        Tim Tujuan:
                        <b>{{ $p['tim'] }}</b>

                    </p>

                    <div class="note">

                        <strong>
                            Posisi Diajukan: {{ $p['posisi'] }}
                        </strong>

                        <p>
                            {{ $p['catatan'] }}
                        </p>

                    </div>

                    <div class="card__foot">

                        <span class="muted">
                            Diajukan pada: {{ $p['waktu'] }}
                        </span>

                        <a href="{{ url('/chat') }}" class="link">
                            Hubungi Ketua Tim →
                        </a>

                    </div>

                </article>

            @empty

                <p class="muted">
                    Belum ada pengajuan bergabung dengan tim.
                </p>

            @endforelse

        </section>

    </main>


    {{-- ========================================= --}}
    {{-- FOOTER --}}
    {{-- ========================================= --}}

    <footer class="footer">

        <div class="footer__grid">

            <div>

                <img
                    src="{{ asset('assets/images/logo fins.png') }}"
                    alt="Logo FINS UB"
                    class="logo-fins-footer"
                >

                <p class="footer__desc">
                    Find Your Skills Partner — Ekosistem kolaborasi
                    mahasiswa Universitas Brawijaya untuk menemukan
                    tim lomba lintas fakultas dan berprestasi
                    di kancah nasional maupun internasional.
                </p>

                <p class="footer__small">
                    Kampus Utama Universitas Brawijaya,<br>
                    Malang, Jawa Timur
                </p>

            </div>

            <div>

                <h4>
                    EKSPLORASI PLATFORM
                </h4>

                <ul>

                    <li>
                        <a href="{{ url('/cari-lomba') }}">
                            Daftar Kompetisi Mahasiswa
                        </a>
                    </li>

                    <li>
                        <a href="{{ url('/cari-partner') }}">
                            Cari Rekan & Partner Tim
                        </a>
                    </li>

                    <li>
                        <a href="{{ url('/posting-lomba') }}">
                            Publikasi Kebutuhan Tim
                        </a>
                    </li>

                    <li>
                        <a href="{{ url('/lomba-saya') }}">
                            Dashboard Lomba Saya
                        </a>
                    </li>

                    <li>
                        <a href="{{ url('/lomba-saya') }}#tim-saya">
                            Manajemen Tim Saya
                        </a>
                    </li>

                    <li>
                        <a href="#">
                            Pusat Notifikasi & Aktivitas
                        </a>
                    </li>

                </ul>

            </div>

            <div>

                <h4>
                    KATEGORI KOMPETISI
                </h4>

                <ul>

                    <li>
                        <a href="#">
                            Puspresnas Gemastik & Satria Data
                        </a>
                    </li>

                    <li>
                        <a href="#">
                            Program Kreativitas Mahasiswa (PKM)
                        </a>
                    </li>

                    <li>
                        <a href="#">
                            Business Plan & Case Competition
                        </a>
                    </li>

                    <li>
                        <a href="#">
                            Lomba Karya Tulis Ilmiah (LKTI)
                        </a>
                    </li>

                    <li>
                        <a href="#">
                            UI/UX Design & Software Development
                        </a>
                    </li>

                </ul>

            </div>

            <div>

                <h4>
                    RUMPUN FAKULTAS UB
                </h4>

                <p class="footer__faculty">
                    Menghubungkan mahasiswa FILKOM, FEB, FT, FIA,
                    FK, FTP, FISIP, FMIPA, dan fakultas lainnya
                    dalam satu sinergi juara.
                </p>

                <div class="tips">

                    <strong>Tips Juara</strong>:
                    Tim multidisiplin (Teknologi + Bisnis + Riset)
                    memiliki tingkat lolos 3.2× lebih tinggi
                    di ajang bergengsi.

                </div>

            </div>

        </div>

        <div class="footer__bottom">

            <span>
                © 2026 FINS — Find Your Skills Partner.
                Universitas Brawijaya.
            </span>

            <span class="footer__links">

                <a href="#">
                    Pedoman Etika Kolaborasi
                </a>

                <a href="#">
                    Panduan Verifikasi UB SSO
                </a>

                <a href="#">
                    Pusat Bantuan
                </a>

            </span>

        </div>

    </footer>

    {{-- ================= JAVASCRIPT ================= --}}

    <script src="{{ asset('js/lomba_saya.js') }}?v=12"></script>

</body>
</html>
