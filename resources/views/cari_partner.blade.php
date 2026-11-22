
@php
    /** @var array $partners */
    /** @var array $skillOptions */
    /** @var array $studyOptions */

    $partners = $partners ?? [];
    $skillOptions = $skillOptions ?? [];
    $studyOptions = $studyOptions ?? [];
@endphp

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Cari Partner | FINS</title>

<link rel="stylesheet" href="{{ secure_asset('css/navbar.css') }}?v=48">
<link rel="stylesheet" href="{{ secure_asset('css/cari_partner.css') }}?v=48">

<script src="{{ secure_asset('js/cari_partner.js') }}?v=50" defer></script>
</head>

<body>

    @include('components.navbar')

    <main class="cp-main">

        <!-- HERO -->
        <section class="cp-hero">
            <div>
                <span class="cp-eyebrow">
                    ♟ Talent Discovery Brawijaya
                </span>

                <h1>Cari Partner</h1>

                <p>
                    Temukan rekan mahasiswa lintas fakultas dengan keahlian
                    dan minat yang saling melengkapi untuk
                    <br class="desktop-break">
                    membentuk tim lomba kebanggaan Universitas Brawijaya.
                </p>
            </div>

            <div class="cp-active-pill">
                <i></i>
                <strong>{{ count($partners) }}</strong>
                Mahasiswa Tersedia
            </div>
        </section>

        <!-- PENCARIAN -->
        <form class="cp-search" id="partnerSearchForm">
            <span class="search-icon">⌕</span>

            <input
                id="partnerSearchInput"
                type="search"
                placeholder="Cari nama mahasiswa, skill (Figma, React, AI), atau program studi..."
            >

            <button
                type="button"
                id="clearPartnerSearch"
                class="clear-search"
            >
                ×
            </button>

            <button type="submit" class="cp-search-button">
                Cari
            </button>
        </form>

        <!-- FILTER AKTIF -->
        <div class="cp-active-filters" id="activeFiltersBar">
            <b>FILTER AKTIF:</b>

            <div id="activeFilterChips"></div>

            <button type="button" id="clearAllFilters">
                Hapus Semua
            </button>
        </div>

        <div class="cp-layout">

            <!-- SIDEBAR FILTER -->
            <aside class="cp-filter-panel">

                <div class="cp-filter-title">
                    <h2>☷ Filter Pencarian</h2>
                    <span id="activeFilterCount">0 Aktif</span>
                </div>

                <!-- FILTER SKILL -->
                <section class="filter-section">

                    <div class="section-heading">
                        <h3>Keahlian / Skill</h3>
                        <small>Multi-pilih</small>
                    </div>

                    <label class="skill-search">
                        <input
                            id="skillFilterSearch"
                            placeholder="Cari skill..."
                        >
                        <span>⌕</span>
                    </label>

                    <div class="check-list" id="skillFilterList">

                        @forelse ($skillOptions as $skill => $total)
                            <label class="check-option">
                                <input
                                    type="checkbox"
                                    data-filter="skill"
                                    value="{{ $skill }}"
                                >

                                <i></i>

                                <span class="option-text">
                                    {{ $skill }}
                                </span>

                                <small>{{ $total }}</small>
                            </label>
                        @empty
                            <p>Belum ada data keahlian.</p>
                        @endforelse

                    </div>
                </section>

                <!-- FILTER PROGRAM STUDI -->
                <section class="filter-section">

                    <div class="section-heading">
                        <h3>Program Studi</h3>
                    </div>

                    <div class="check-list">

                        @forelse ($studyOptions as $study)
                            <label class="check-option">
                                <input
                                    type="checkbox"
                                    data-filter="study"
                                    value="{{ $study }}"
                                >

                                <i></i>

                                <span class="option-text">
                                    {{ $study }}
                                </span>
                            </label>
                        @empty
                            <p>Belum ada data program studi.</p>
                        @endforelse

                    </div>
                </section>

                <button
                    class="apply-filters"
                    id="applyFilters"
                    type="button"
                >
                    Terapkan Filter
                </button>

                <button
                    class="reset-filters"
                    id="resetFilters"
                    type="button"
                >
                    Reset Filter
                </button>

            </aside>

            <!-- HASIL PENCARIAN -->
            <section class="cp-results">

                <div class="results-header">

                    <div>
                        <h2>
                            Partner yang Cocok untukmu

                            <span id="resultCount">
                                {{ count($partners) }} Mahasiswa
                            </span>
                        </h2>

                        <p>
                            Temukan mahasiswa FINS berdasarkan profil
                            dan keahlian yang tersedia.
                        </p>
                    </div>

                    <label class="sort-label">
                        Urutkan:

                        <select id="partnerSort">
                            <option value="relevance">
                                Urutan Default
                            </option>

                            <option value="name">
                                Nama A–Z
                            </option>
                        </select>
                    </label>

                </div>

                <!-- KARTU PARTNER -->
                <div class="card-grid" id="partnerCardGrid">

                    @foreach ($partners as $p)

                        <article
                            class="partner-card"
                            data-id="{{ $p['id'] }}"
                            data-name="{{ mb_strtolower($p['name']) }}"
                            data-study="{{ $p['study'] }}"
                            data-semester="{{ $p['semester'] ?? '' }}"
                            data-skills="{{ implode('|', $p['skills']) }}"
                            data-interests="{{ implode('|', $p['interests']) }}"
                            data-match="{{ $p['match'] ?? 0 }}"
                            data-search="{{ mb_strtolower(
                                $p['name'] . ' ' .
                                $p['study'] . ' ' .
                                implode(' ', $p['skills']) . ' ' .
                                implode(' ', $p['interests']) . ' ' .
                                $p['bio']
                            ) }}"
                        >

                            <!-- IDENTITAS -->
                            <div class="person-row">

                                <div class="avatar">
                                    {{ $p['initials'] }}
                                </div>

                                <div class="person-info">

                                    <h3>{{ $p['name'] }}</h3>

                                    <p>
                                        {{ $p['study'] }}

                                        @if (!empty($p['semester']))
                                            · S{{ $p['semester'] }}
                                        @endif
                                    </p>

                                    <span class="match-tag {{ $p['kind'] }}">
                                        {{ $p['kind'] === 'same' ? '◎' : '✣' }}
                                        {{ $p['tag'] }}
                                    </span>

                                </div>

                                <i class="online"></i>

                            </div>

                            <!-- BIO -->
                            <p class="bio">
                                "{{ $p['bio'] }}"
                            </p>

                            <!-- INFORMASI PROFIL -->
                            <div class="match-score">
                                <span>Profil Mahasiswa</span>
                                <strong>{{ $p['tag'] }}</strong>
                            </div>

                            <!-- SKILL -->
                            <div class="card-skills">

                                <small>Keahlian:</small>

                                <div>
                                    @forelse ($p['skills'] as $skill)
                                        <span>{{ $skill }}</span>
                                    @empty
                                        <span>Belum diisi</span>
                                    @endforelse
                                </div>

                            </div>

                            <!-- FAKULTAS -->
                            <div class="card-interest">
                                <b>♧</b>
                                {{ $p['category'] }}
                            </div>

                            <!-- TOMBOL -->
                            <div class="card-actions">

                                <button
                                    type="button"
                                    class="profile-button"
                                    data-profile="{{ $p['id'] }}"
                                >
                                    Lihat Profil
                                </button>

                                <button
                                    type="button"
                                    class="invite-button"
                                    data-invite="{{ $p['id'] }}"
                                >
                                    Ajak Kolaborasi
                                </button>

                            </div>

                        </article>

                    @endforeach

                </div>

                <!-- KETIKA TIDAK ADA PARTNER -->
                <div
                    class="empty-state"
                    id="emptyPartnerState"
                    hidden
                >
                    <h3>Partner belum ditemukan</h3>

                    <p>
                        Coba ubah kata kunci atau sesuaikan
                        filter pencarianmu.
                    </p>

                    <button
                        type="button"
                        id="emptyResetFilters"
                    >
                        Reset Filter
                    </button>
                </div>

                <!-- PAGINATION -->
                <div class="pagination">

                    <span id="paginationInfo"></span>

                    <div id="pageButtons"></div>

                </div>

            </section>

        </div>

    </main>

    <!-- FOOTER -->
    <footer class="cp-footer">

        <span>
            © {{ date('Y') }} FINS • Universitas Brawijaya.
            Kolaborasi Karya &amp; Prestasi Mahasiswa.
        </span>

        <div>
            <a href="#">Pusat Bantuan</a>
            <a href="#">Pedoman Komunitas</a>
            <a href="#">Kebijakan Privasi</a>
        </div>

    </footer>

    <!-- MODAL AJAK KOLABORASI -->
    <div
    class="modal-backdrop"
    id="inviteModal"
    data-store-url="{{ route('partner-invitations.store') }}"
    data-sent-url="{{ route('partner-invitations.sent') }}"
    aria-hidden="true"
>
        <section
            class="invite-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="inviteModalTitle"
        >

            <button
                type="button"
                class="modal-close"
                id="closeInviteModal"
                aria-label="Tutup"
            >
                ×
            </button>

            <span class="modal-eyebrow">
                FINS COLLABORATION
            </span>

            <h2 id="inviteModalTitle">
                Ajak Kolaborasi
            </h2>

            <p class="modal-desc">
                Kirim undangan kepada calon partner
                untuk bergabung dalam tim lombamu.
            </p>

            <form id="inviteForm">

                <!-- NAMA PENERIMA -->
                <label>
                    Calon Partner

                    <input
                        id="invitePartnerName"
                        type="text"
                        readonly
                    >
                </label>

                <!-- NAMA LOMBA -->
                <label>
                    Lomba / Proyek

                    <input
                        id="inviteCompetition"
                        type="text"
                        maxlength="150"
                        placeholder="Contoh: GEMASTIK UI/UX"
                        required
                    >
                </label>

                <!-- PERAN -->
                <label>
                    Peran yang dibutuhkan

                    <select id="inviteRole" required>

                        <option value="">
                            Pilih peran
                        </option>

                        <option value="UI/UX Designer">
                            UI/UX Designer
                        </option>

                        <option value="Frontend Developer">
                            Frontend Developer
                        </option>

                        <option value="Backend Developer">
                            Backend Developer
                        </option>

                        <option value="Data Scientist / AI">
                            Data Scientist / AI
                        </option>

                        <option value="Business / Pitching">
                            Business / Pitching
                        </option>

                        <option value="Anggota Tim">
                            Anggota Tim
                        </option>

                    </select>
                </label>

                <!-- PESAN UNDANGAN -->
                <label>
                    Pesan undangan

                    <textarea
                        id="inviteMessage"
                        rows="3"
                        maxlength="2000"
                        required
                    ></textarea>
                </label>

                <!-- FEEDBACK -->
                <p
                    id="inviteFeedback"
                    class="invite-feedback"
                    role="status"
                    aria-live="polite"
                ></p>

                <!-- TOMBOL MODAL -->
                <div class="modal-actions">

                    <button
                        type="button"
                        class="profile-button"
                        id="cancelInvite"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="invite-button"
                        id="submitInvite"
                    >
                        Kirim Undangan
                    </button>

                </div>

            </form>

        </section>

    </div>

</body>
</html>
