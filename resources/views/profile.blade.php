<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profil Mahasiswa | FINS</title>

    {{-- Pertahankan CSS berukuran besar & warna soft dari revisi sebelumnya. --}}
    <link rel="stylesheet" href="/css/navbar.css?v={{ filemtime(public_path('css/navbar.css')) }}">
    <link rel="stylesheet" href="/css/profile.css?v={{ filemtime(public_path('css/profile.css')) }}">
    <link rel="stylesheet" href="/css/fins-academic-dropdown.css?v={{ filemtime(public_path('css/fins-academic-dropdown.css')) }}">
    <link rel="stylesheet" href="/css/profile-logout.css?v={{ filemtime(public_path('css/profile-logout.css')) }}">
    <script src="/js/fins-academic-dropdown.js?v={{ filemtime(public_path('js/fins-academic-dropdown.js')) }}" defer></script>
    <script src="/js/profile.js?v={{ filemtime(public_path('js/profile.js')) }}" defer></script>
    <script src="/js/profile-logout.js?v={{ filemtime(public_path('js/profile-logout.js')) }}" defer></script>
</head>
<body>
<div class="fins-profile-page">

    {{-- Gunakan navbar component yang sudah dipakai di halaman lain. --}}
    @include('components.navbar')

    <main class="fins-profile-main">
        <div class="fins-breadcrumb">PROFIL MAHASISWA <span>›</span></div>

        <div class="fins-profile-grid">
            <section class="fins-profile-left">
                <!-- Informasi mahasiswa dari tabel users -->
                <article class="fins-card fins-user-card">
                    <div class="fins-avatar fins-avatar-large" id="profileAvatar">
                        @if($profile['photo_url'])
                            <img class="profile-photo-image" src="{{ $profile['photo_url'] }}" alt="Foto profil" loading="eager">
                        @else
                            <span>{{ $profile['initials'] }}</span>
                        @endif
                    </div>

                    <h1 id="profileName">{{ $profile['fullname'] }}</h1>
                    <p class="fins-study-program" id="profileStudyProgram">
                        {{ $profile['study_program'] ?: 'Program studi belum diisi' }}
                    </p>
                    @if($profile['nim'])
                        <p class="fins-profile-nim">NIM {{ $profile['nim'] }}</p>
                    @endif
                    <p class="fins-bio-quote" id="profileBio">
                        {{ $profile['bio'] ? '“'.$profile['bio'].'”' : 'Tambahkan bio atau motto melalui Edit Profil' }}
                    </p>
                    <button class="fins-button fins-button-primary fins-edit-profile"
                            id="openEditProfile" type="button">
                        Edit Profil <span aria-hidden="true">✎</span>
                    </button>

                    {{-- Tombol logout di halaman profil --}}
                    <button class="fins-logout-trigger"
                            id="openLogoutModal"
                            type="button"
                            aria-haspopup="dialog"
                            aria-controls="finsLogoutModal">
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none"
                             stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <path d="m16 17 5-5-5-5" />
                            <path d="M21 12H9" />
                        </svg>
                        Keluar Akun
                    </button>
                </article>

                <!-- Skills yang disimpan per pengguna -->
                <article class="fins-card fins-skills-card">
                    <div class="fins-section-heading">
                        <h2>Skills</h2>
                        <span id="skillsCount">{{ count($profile['skills']) }} Terpasang</span>
                    </div>
                    <div class="fins-skill-list" id="profileSkills">
                        @forelse($profile['skills'] as $skill)
                            <span class="fins-skill-chip">• {{ $skill }}</span>
                        @empty
                            <span class="fins-empty-data">Belum ada skill. Tambahkan melalui Edit Profil.</span>
                        @endforelse
                    </div>
                </article>

                <!-- Kontak media sosial -->
                <article class="fins-card fins-contact-card">
                    <h2>Contact</h2>
                    <div class="fins-contact-row">
                        <span class="fins-contact-logo instagram-logo">◎</span>
                        <div><small>Instagram</small><a href="#" id="profileInstagram">{{ $profile['instagram'] ?: 'Belum diisi' }}</a></div>
                    </div>
                    <div class="fins-contact-row">
                        <span class="fins-contact-logo github-logo">●</span>
                        <div><small>GitHub</small><a href="#" id="profileGithub">{{ $profile['github'] ?: 'Belum diisi' }}</a></div>
                    </div>
                    <div class="fins-contact-row">
                        <span class="fins-contact-logo linkedin-logo">in</span>
                        <div><small>LinkedIn</small><a href="#" id="profileLinkedin">{{ $profile['linkedin'] ?: 'Belum diisi' }}</a></div>
                    </div>
                </article>
            </section>

            <section class="fins-profile-right">
                <!-- Statistik lomba & tim belum terhubung ke tabel relasi -->
                <article class="fins-card fins-stats-card">
                    <div class="fins-stat">
                        <strong>—</strong>
                        <span>Lomba Diikuti</span>
                        <small>Belum terhubung</small>
                    </div>
                    <div class="fins-stat">
                        <strong id="statSkills">{{ count($profile['skills']) }}</strong>
                        <span>Keahlian</span>
                        <small>Dari profil pengguna</small>
                    </div>
                    <div class="fins-stat">
                        <strong>—</strong>
                        <span>Kolaborasi Tim</span>
                        <small>Belum terhubung</small>
                    </div>
                </article>

                <!-- Daftar contoh kategori, bukan data minat dari database -->
                <article class="fins-card fins-interest-card">
                    <h2>Jelajahi Minat Kompetisi <span aria-hidden="true">▥</span></h2>
                    <div class="fins-interest-list">
                        <div class="fins-interest-item">
                            <span class="fins-interest-icon">&lt;/&gt;</span>
                            <b>Hackathon</b><small>Rapid Build</small>
                        </div>
                        <div class="fins-interest-item">
                            <span class="fins-interest-icon">＄</span>
                            <b>Business Plan</b><small>Product Idea</small>
                        </div>
                        <div class="fins-interest-item">
                            <span class="fins-interest-icon">▤</span>
                            <b>Essay Competition</b><small>Science Problem</small>
                        </div>
                        <div class="fins-interest-item">
                            <span class="fins-interest-icon">☷</span>
                            <b>UI/UX</b><small>Design System</small>
                        </div>
                    </div>
                </article>

                <article class="fins-card fins-activity-card">
                    <h2>Riwayat Aktivitas <span aria-hidden="true">◴</span></h2>
                    <div class="fins-activity-list">
                        <p class="fins-empty-data">
                            Belum ada data aktivitas yang terhubung dengan akun ini.
                            Bagian ini dapat diintegrasikan setelah relasi keikutsertaan lomba tersedia.
                        </p>
                    </div>
                </article>
            </section>
        </div>
    </main>
</div>

<!-- Modal Edit Profil: seluruh field ditulis ke baris users milik akun aktif -->
<div class="fins-modal-backdrop" id="editProfileModal" aria-hidden="true">
    <section class="fins-edit-modal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="fins-modal-banner">
            <h2 id="editModalTitle">Edit Profil Mahasiswa</h2>
            <p>Perbarui informasi profil kamu, Finsbe!</p>
        </div>

        <form id="editProfileForm" method="POST" action="{{ route('profile.update', [], false) }}">
            @csrf
            @method('PUT')
            <div class="fins-modal-content">
                <div class="fins-photo-section">
                    <div class="fins-avatar fins-avatar-modal" id="modalAvatar">
                        @if($profile['photo_url'])
                            <img class="profile-photo-image" src="{{ $profile['photo_url'] }}" alt="Foto profil" loading="eager">
                        @else
                            <span>{{ $profile['initials'] }}</span>
                        @endif
                    </div>
                    <div class="fins-photo-actions">
                        <label class="fins-button fins-button-primary fins-upload-button" for="profilePhotoInput">
                            ♧ &nbsp; Unggah Foto Baru
                        </label>
                        <input type="file" id="profilePhotoInput" name="profile_photo"
                               accept="image/jpeg,image/png,image/webp" hidden>
                        <button class="fins-remove-photo" id="removePhotoButton" type="button">
                            ▤ &nbsp; Hapus Foto
                        </button>
                    </div>
                </div>

                <div class="fins-form-grid">
                    <label class="fins-field">
                        <span>Nama Lengkap</span>
                        <input type="text" name="fullname" id="editFullname"
                               value="{{ $profile['fullname'] }}" required maxlength="100">
                    </label>
                    <label class="fins-field">
                        <span>Username Profil</span>
                        <input type="text" name="username" id="editUsername"
                               value="{{ $profile['username'] }}" maxlength="30"
                               placeholder="@username">
                    </label>
                    <label class="fins-field">
                        <span>Fakultas</span>
                        <select name="faculty" id="editFaculty">
                            <option value="">Pilih fakultas</option>
                            @foreach($faculties as $faculty)
                                <option value="{{ $faculty }}" @selected($profile['faculty'] === $faculty)>
                                    {{ $faculty }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="fins-field">
                        <span>Program Studi</span>
                        <select name="study_program" id="editStudyProgram">
                            <option value="">Pilih program studi</option>
                            @foreach($programs as $program)
                                <option value="{{ $program }}" @selected($profile['study_program'] === $program)>
                                    {{ $program }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="fins-field fins-field-full">
                        <span>Bio / Motto Singkat</span>
                        <textarea name="bio" id="editBio" maxlength="180"
                                  placeholder="Tulis bio atau motto singkat...">{{ $profile['bio'] }}</textarea>
                    </label>
                    <label class="fins-field">
                        <span>Instagram</span>
                        <input type="text" name="instagram" id="editInstagram"
                               value="{{ $profile['instagram'] }}" maxlength="150" placeholder="@username">
                    </label>
                    <label class="fins-field">
                        <span>GitHub</span>
                        <input type="text" name="github" id="editGithub"
                               value="{{ $profile['github'] }}" maxlength="150" placeholder="@username">
                    </label>
                    <label class="fins-field fins-field-full">
                        <span>LinkedIn</span>
                        <input type="text" name="linkedin" id="editLinkedin"
                               value="{{ $profile['linkedin'] }}" maxlength="200"
                               placeholder="Username atau URL profil LinkedIn">
                    </label>
                </div>

                <!-- Skills -->
                <div class="fins-skills-editor">
                    <div class="fins-skills-editor-heading">
                        <label for="skillSearchInput">Keahlian Utama (Skills)</label>
                        <span>Maksimal 6 keahlian</span>
                    </div>
                    <div class="fins-skill-input-box">
                        <div class="fins-selected-skills" id="selectedSkills">
                            @foreach($profile['skills'] as $skill)
                                <span class="fins-edit-skill-chip" data-skill="{{ $skill }}">
                                    • {{ $skill }}
                                    <button type="button" data-remove-skill="{{ $skill }}"
                                            aria-label="Hapus {{ $skill }}">×</button>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <button class="fins-add-skill-trigger" id="addSkillButton"
                            type="button" aria-expanded="false" aria-controls="skillDropdown">
                        <span aria-hidden="true">＋</span> Add Skill
                    </button>
                    <div class="fins-skill-dropdown" id="skillDropdown" hidden>
                        <label class="fins-skill-search">
                            <span aria-hidden="true">⌕</span>
                            <input type="search" id="skillSearchInput"
                                   placeholder="Cari keahlian, cth: React, Python, Machine Learning">
                        </label>
                        <div class="fins-skill-dropdown-heading">
                            <span>Rekomendasi Skills</span>
                            <span id="dropdownSkillCount">{{ count($profile['skills']) }} Dipilih</span>
                        </div>
                        <div class="fins-skill-options" id="skillOptions">
                            @foreach([
                                'Machine Learning / AI', 'Python', 'Pitching Specialist',
                                'Data Science', 'Cyber Security', 'Public Speaking',
                                'UI/UX Research', 'React JS', 'Product Manager',
                                'Web Development', 'Prompt Engineer', 'Jurnal Research',
                                'UI/UX', 'Laravel', 'JavaScript', 'Flutter'
                            ] as $skill)
                                <button type="button" class="fins-skill-option"
                                        data-skill-option="{{ $skill }}">
                                    <span>{{ $skill }}</span><i>＋</i>
                                </button>
                            @endforeach
                        </div>
                        <p class="fins-skill-empty" id="skillEmptyState" hidden>Keahlian tidak ditemukan.</p>
                    </div>
                    <p class="fins-skill-hint" id="skillHint">Pilih hingga 6 skill.</p>
                </div>
            </div>

            <div class="fins-modal-footer">
                <p class="fins-form-message" id="profileFormMessage" role="status"></p>
                <div class="fins-modal-footer-actions">
                    <button class="fins-button fins-button-light" id="cancelEditProfile" type="button">Batal</button>
                    <button class="fins-button fins-button-primary" id="saveProfileButton" type="submit">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </section>
</div>



{{-- Modal konfirmasi logout, terpisah dari modal Edit Profil --}}
<div class="fins-logout-overlay" id="finsLogoutModal" hidden>
    <section class="fins-logout-dialog"
             role="dialog"
             aria-modal="true"
             aria-labelledby="finsLogoutTitle"
             aria-describedby="finsLogoutDescription"
             tabindex="-1">
        <div class="fins-logout-symbol" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <path d="m16 17 5-5-5-5" />
                <path d="M21 12H9" />
            </svg>
        </div>
        <h2 id="finsLogoutTitle">Keluar dari FINS?</h2>
        <p id="finsLogoutDescription">
            Kamu akan keluar dari akun ini dan kembali ke halaman login.
        </p>
        <div class="fins-logout-actions">
            <button class="fins-logout-cancel" id="cancelLogoutModal" type="button">
                Batal
            </button>
            {{-- POST + CSRF wajib; jangan ubah menjadi link GET. --}}
            <form method="POST" action="{{ route('logout', [], false) }}" id="finsLogoutForm">
                @csrf
                <button class="fins-logout-confirm" type="submit" id="confirmLogoutButton">
                    Ya, Keluar
                </button>
            </form>
        </div>
    </section>
</div>

<div id="finsAcademicData" hidden
     data-faculties="{{ json_encode(config('fins_academics.faculties', []), JSON_UNESCAPED_UNICODE) }}"
     data-aliases="{{ json_encode(config('fins_academics.aliases', []), JSON_UNESCAPED_UNICODE) }}"></div>

<!-- Konfigurasi Profile dari Laravel -->
<div
    id="finsProfileSettings"
    hidden
    data-update-url="{{ route('profile.update', [], false) }}"
    data-photo-upload-url="{{ route('profile.photo.upload', [], false) }}"
    data-photo-remove-url="{{ route('profile.photo.remove', [], false) }}"
    data-initial-skills="{{ json_encode($profile['skills'] ?? []) }}"
    data-initials="{{ $profile['initials'] ?? 'F' }}"
    data-photo-url="{{ $profile['photo_url'] ?? '' }}"
    data-photo-path="{{ $profile['photo_path'] ?? '' }}"
    data-default-navbar-photo="/assets/images/Group4.png"
></div>

<script>
    const profileSettings =
        document.getElementById('finsProfileSettings').dataset;

    window.finsProfileConfig = {
        updateUrl: profileSettings.updateUrl,

        photoUploadUrl: profileSettings.photoUploadUrl,

        photoRemoveUrl: profileSettings.photoRemoveUrl,

        initialSkills: JSON.parse(
            profileSettings.initialSkills || '[]'
        ),

        initials: profileSettings.initials,

        photoUrl: profileSettings.photoUrl || null,

        photoPath: profileSettings.photoPath || null,

        defaultNavbarPhoto:
            profileSettings.defaultNavbarPhoto
    };
</script>

</body>
</html>
