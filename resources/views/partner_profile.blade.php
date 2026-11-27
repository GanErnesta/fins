<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $profile['fullname'] }} | FINS</title>

    <link rel="stylesheet" href="/css/navbar.css?v={{ filemtime(public_path('css/navbar.css')) }}">
    <link rel="stylesheet" href="/css/profile.css?v={{ filemtime(public_path('css/profile.css')) }}">

    <style>
        .partner-breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        .partner-breadcrumb a {
            color: #900000;
            text-decoration: none;
            font-weight: 600;
        }

        .partner-breadcrumb a:hover {
            text-decoration: underline;
        }

        .partner-username {
            font-size: 14px;
            color: #987b75;
            margin: 5px 0 12px;
        }

        .partner-faculty {
            font-size: 14px;
            color: #806762;
            margin: 8px 0;
        }

        .partner-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 14px;
            background: #e5f4eb;
            color: #23794a;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            margin: 12px 0;
        }

        .partner-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 100%;
            margin-top: 22px;
        }

        .partner-actions .fins-button {
            width: 100%;
            text-decoration: none;
            text-align: center;
            justify-content: center;
            box-sizing: border-box;
            cursor: pointer;
        }

        .partner-actions button:disabled {
            background: #f1ded6;
            color: #96766e;
            cursor: not-allowed;
            border: 1px solid #e5cec5;
        }

        .partner-back-button {
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #a00000;
            border-radius: 12px;
            padding: 12px 18px;
            color: #900000;
            background: transparent;
            text-decoration: none;
            font-weight: 600;
            transition: background .2s;
        }

        .partner-back-button:hover {
            background: #f8e3dc;
        }

        .partner-info-card {
            padding: 28px;
        }

        .partner-info-card h2 {
            margin-bottom: 24px;
            color: #900000;
        }

        .partner-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .partner-info-item {
            padding: 18px;
            border: 1px solid #edd6cb;
            border-radius: 14px;
            background: #fff8f2;
            overflow-wrap: anywhere;
        }

        .partner-info-item small {
            display: block;
            color: #96766e;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .partner-info-item strong {
            color: #800000;
            font-size: 15px;
            line-height: 1.6;
        }

        .partner-about {
            margin-top: 22px;
            padding: 20px;
            background: #fff8f2;
            border-radius: 14px;
            border: 1px solid #edd6cb;
        }

        .partner-about h3 {
            font-size: 16px;
            color: #900000;
            margin: 0 0 12px;
        }

        .partner-about p {
            color: #806762;
            font-size: 14px;
            line-height: 1.8;
            margin: 0;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .partner-collaboration-card {
            padding: 28px;
        }

        .partner-collaboration-card h2 {
            color: #900000;
            margin-bottom: 18px;
        }

        .partner-collaboration-status {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 20px;
            background: #fff5ec;
            border: 1px solid #ead5c7;
            border-radius: 16px;
        }

        .partner-status-icon {
            width: 46px;
            height: 46px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5d9d2;
            color: #900000;
            border-radius: 12px;
            font-size: 23px;
        }

        .partner-status-content h3 {
            color: #900000;
            font-size: 15px;
            margin: 0 0 8px;
        }

        .partner-status-content p {
            color: #806762;
            font-size: 13px;
            line-height: 1.7;
            margin: 0;
        }

        .partner-status-accepted {
            background: #eff8f1;
            border-color: #cce4d1;
        }

        .partner-status-accepted .partner-status-icon {
            background: #d8efdf;
            color: #287b46;
        }

        .partner-status-accepted h3 {
            color: #287b46;
        }

        .partner-status-pending {
            background: #fff7e7;
            border-color: #f1dfb8;
        }

        .partner-status-pending .partner-status-icon {
            background: #f9e9c5;
            color: #a16a15;
        }

        .partner-status-pending h3 {
            color: #a16a15;
        }

        .partner-invite-modal {
            width: min(500px, calc(100% - 32px));
            max-height: 90vh;
            overflow-y: auto;
            border: none;
            border-radius: 20px;
            padding: 0;
            background: #fffaf5;
            color: #800000;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .18);
        }

        .partner-invite-modal::backdrop {
            background: rgba(35, 15, 15, .55);
            backdrop-filter: blur(3px);
        }

        .partner-modal-header {
            padding: 24px;
            background: #900000;
            color: #fff;
        }

        .partner-modal-header h2 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        .partner-modal-header p {
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
        }

        .partner-modal-body {
            padding: 24px;
        }

        .partner-modal-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 18px;
        }

        .partner-modal-field label {
            font-size: 13px;
            font-weight: 600;
        }

        .partner-modal-field input,
        .partner-modal-field textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #e5c9bc;
            border-radius: 10px;
            background: #fff;
            padding: 12px 14px;
            color: #51342e;
            font: inherit;
            font-size: 14px;
            outline: none;
        }

        .partner-modal-field input:focus,
        .partner-modal-field textarea:focus {
            border-color: #b83e50;
        }

        .partner-modal-field textarea {
            min-height: 110px;
            resize: vertical;
        }

        .partner-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 0 24px 24px;
        }

        .partner-modal-footer button {
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            cursor: pointer;
        }

        .partner-modal-cancel {
            background: #f1e2da;
            color: #900000;
        }

        .partner-modal-submit {
            background: #900000;
            color: #fff;
        }

        .partner-modal-submit:disabled {
            opacity: .6;
            cursor: wait;
        }

        .partner-form-message {
            font-size: 13px;
            line-height: 1.6;
            color: #b42318;
            margin: 0;
        }

        .partner-contact-value {
            overflow-wrap: anywhere;
            font-size: 14px;
        }

        .partner-avatar-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: inherit;
        }

        .partner-avatar-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .partner-avatar-fallback[hidden] {
            display: none;
        }

        @media (max-width: 768px) {
            .partner-info-grid {
                grid-template-columns: 1fr;
            }

            .partner-info-card,
            .partner-collaboration-card {
                padding: 20px;
            }

            .partner-collaboration-status {
                padding: 15px;
                gap: 12px;
            }

            .partner-modal-footer {
                flex-wrap: wrap;
            }

            .partner-modal-footer button {
                flex: 1;
            }
        }
    </style>
</head>

<body>

@php
    $socialLinks = [];

    $socialPlatforms = [
        'instagram' => [
            'base' => 'https://www.instagram.com/',
            'hosts' => ['instagram.com', 'www.instagram.com'],
            'label' => 'Instagram',
            'icon' => '◎',
            'class' => 'instagram-logo'
        ],
        'github' => [
            'base' => 'https://github.com/',
            'hosts' => ['github.com', 'www.github.com'],
            'label' => 'GitHub',
            'icon' => '●',
            'class' => 'github-logo'
        ],
        'linkedin' => [
            'base' => 'https://www.linkedin.com/in/',
            'hosts' => ['linkedin.com', 'www.linkedin.com', 'id.linkedin.com'],
            'label' => 'LinkedIn',
            'icon' => 'in',
            'class' => 'linkedin-logo'
        ]
    ];

    foreach ($socialPlatforms as $key => $platform) {
        $value = trim((string) ($profile[$key] ?? ''));
        $url = null;

        if ($value !== '') {
            $candidate = $value;

            if (preg_match('/^(www\.)?(instagram\.com|github\.com|linkedin\.com|id\.linkedin\.com)\//i', $candidate)) {
                $candidate = 'https://' . $candidate;
            }

            if (filter_var($candidate, FILTER_VALIDATE_URL)) {
                $scheme = strtolower(parse_url($candidate, PHP_URL_SCHEME) ?? '');
                $host = strtolower(parse_url($candidate, PHP_URL_HOST) ?? '');

                if (
                    in_array($scheme, ['http', 'https'], true) &&
                    in_array($host, $platform['hosts'], true)
                ) {
                    $url = $candidate;
                }
            } else {
                $username = ltrim($value, '@');

                if ($key === 'linkedin' && str_starts_with($username, 'in/')) {
                    $username = substr($username, 3);
                }

                if (preg_match('/^[A-Za-z0-9._-]+$/', $username)) {
                    $url = $platform['base'] . rawurlencode($username);
                }
            }
        }

        $socialLinks[$key] = [
            'value' => $value,
            'url' => $url,
            'label' => $platform['label'],
            'icon' => $platform['icon'],
            'class' => $platform['class']
        ];
    }
@endphp

<div class="fins-profile-page">

    @include('components.navbar')

    <main class="fins-profile-main">

        <div class="fins-breadcrumb partner-breadcrumb">
            <a href="{{ route('cari-partner', [], false) }}">
                CARI PARTNER
            </a>

            <span>›</span>

            <span>PROFIL MAHASISWA</span>
        </div>

        <div class="fins-profile-grid">

            <section class="fins-profile-left">

                <article class="fins-card fins-user-card">

                    <div class="fins-avatar fins-avatar-large">

                        @if(!empty($profile['photo_url']))

                            <img
                                class="profile-photo-image partner-avatar-image"
                                id="partnerAvatarImage"
                                src="{{ $profile['photo_url'] }}"
                                alt="Foto profil {{ $profile['fullname'] }}"
                                loading="eager"
                            >

                            <span
                                class="partner-avatar-fallback"
                                id="partnerAvatarFallback"
                                hidden
                            >
                                {{ $profile['initials'] ?? 'F' }}
                            </span>

                        @else

                            <span>
                                {{ $profile['initials'] ?? 'F' }}
                            </span>

                        @endif

                    </div>

                    <h1>{{ $profile['fullname'] }}</h1>

                    @if(!empty($profile['username']))

                        <p class="partner-username">
                            {{ '@' . ltrim($profile['username'], '@') }}
                        </p>

                    @endif

                    <p class="fins-study-program">
                        {{ $profile['study_program'] ?: 'Program studi belum diisi' }}
                    </p>

                    <p class="partner-faculty">
                        {{ $profile['faculty'] ?: 'Fakultas belum diisi' }}
                    </p>

                    <span class="partner-badge">
                        ✓ Mahasiswa FINS
                    </span>

                    <p class="fins-bio-quote">
                        {{ $profile['bio'] ? '“' . $profile['bio'] . '”' : 'Belum ada deskripsi profil.' }}
                    </p>

                    <div class="partner-actions">

                        @if($invitationStatus === 'accepted' && $conversationId)

                            <a
                                href="{{ route('chat', ['conversation' => $conversationId], false) }}"
                                class="fins-button fins-button-primary"
                            >
                                Buka Percakapan
                            </a>

                        @elseif($invitationStatus === 'accepted')

                            <button
                                class="fins-button fins-button-primary"
                                type="button"
                                disabled
                            >
                                Sudah Terhubung
                            </button>

                        @elseif($invitationStatus === 'pending' && $invitationDirection === 'received')

                            <a
                                href="{{ route('notifikasi', [], false) }}"
                                class="fins-button fins-button-primary"
                            >
                                Lihat Undangan Masuk
                            </a>

                        @elseif($invitationStatus === 'pending')

                            <button
                                class="fins-button fins-button-primary"
                                type="button"
                                disabled
                            >
                                Undangan Terkirim
                            </button>

                        @elseif($canInvite)

                            <button
                                class="fins-button fins-button-primary"
                                id="openPartnerInvite"
                                type="button"
                            >
                                + Ajak Kolaborasi
                            </button>

                        @endif

                        <a
                            class="partner-back-button"
                            href="{{ route('cari-partner', [], false) }}"
                        >
                            ← Kembali ke Cari Partner
                        </a>

                    </div>

                </article>

                <article class="fins-card fins-skills-card">

                    <div class="fins-section-heading">

                        <h2>Skills</h2>

                        <span>
                            {{ count($profile['skills']) }} Terpasang
                        </span>

                    </div>

                    <div class="fins-skill-list">

                        @forelse($profile['skills'] as $skill)

                            <span class="fins-skill-chip">
                                • {{ $skill }}
                            </span>

                        @empty

                            <span class="fins-empty-data">
                                Belum ada keahlian yang ditambahkan.
                            </span>

                        @endforelse

                    </div>

                </article>

                <article class="fins-card fins-contact-card">

                    <h2>Contact</h2>

                    @foreach($socialLinks as $social)

                        <div class="fins-contact-row">

                            <span class="fins-contact-logo {{ $social['class'] }}">
                                {{ $social['icon'] }}
                            </span>

                            <div>

                                <small>{{ $social['label'] }}</small>

                                @if($social['url'])

                                    <a
                                        class="partner-contact-value"
                                        href="{{ $social['url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ $social['value'] }}
                                    </a>

                                @else

                                    <span class="partner-contact-value">
                                        {{ $social['value'] ?: 'Belum diisi' }}
                                    </span>

                                @endif

                            </div>

                        </div>

                    @endforeach

                </article>

            </section>

            <section class="fins-profile-right">

                <article class="fins-card fins-stats-card">

                    <div class="fins-stat">

                        <strong>—</strong>

                        <span>Lomba Diikuti</span>

                        <small>Data belum tersedia</small>

                    </div>

                    <div class="fins-stat">

                        <strong>
                            {{ count($profile['skills']) }}
                        </strong>

                        <span>Keahlian</span>

                        <small>Dari profil pengguna</small>

                    </div>

                    <div class="fins-stat">

                        <strong>—</strong>

                        <span>Kolaborasi Tim</span>

                        <small>Data belum tersedia</small>

                    </div>

                </article>

                <article class="fins-card partner-info-card">

                    <h2>Informasi Mahasiswa</h2>

                    <div class="partner-info-grid">

                        <div class="partner-info-item">

                            <small>Nama Lengkap</small>

                            <strong>
                                {{ $profile['fullname'] }}
                            </strong>

                        </div>

                        <div class="partner-info-item">

                            <small>Username</small>

                            <strong>
                                {{ $profile['username'] ? '@' . ltrim($profile['username'], '@') : 'Belum diisi' }}
                            </strong>

                        </div>

                        <div class="partner-info-item">

                            <small>Fakultas</small>

                            <strong>
                                {{ $profile['faculty'] ?: 'Belum diisi' }}
                            </strong>

                        </div>

                        <div class="partner-info-item">

                            <small>Program Studi</small>

                            <strong>
                                {{ $profile['study_program'] ?: 'Belum diisi' }}
                            </strong>

                        </div>

                        <div class="partner-info-item">

                            <small>Universitas</small>

                            <strong>
                                Universitas Brawijaya
                            </strong>

                        </div>

                        <div class="partner-info-item">

                            <small>Jumlah Keahlian</small>

                            <strong>
                                {{ count($profile['skills']) }} Skills
                            </strong>

                        </div>

                    </div>

                    <div class="partner-about">

                        <h3>Tentang Mahasiswa</h3>

                        <p>{{ $profile['bio'] ?: 'Mahasiswa ini belum menambahkan deskripsi profil.' }}</p>

                    </div>

                </article>

                <article class="fins-card partner-collaboration-card">

                    <h2>Status Kolaborasi</h2>

                    @if($invitationStatus === 'accepted')

                        <div class="partner-collaboration-status partner-status-accepted">

                            <div class="partner-status-icon">✓</div>

                            <div class="partner-status-content">

                                <h3>Sudah Terhubung</h3>

                                <p>
                                    Kamu dan {{ $profile['fullname'] }}
                                    telah terhubung melalui undangan kolaborasi.
                                    Mulai diskusi melalui fitur Chat FINS jika percakapan tersedia.
                                </p>

                            </div>

                        </div>

                    @elseif($invitationStatus === 'pending' && $invitationDirection === 'received')

                        <div class="partner-collaboration-status partner-status-pending">

                            <div class="partner-status-icon">✉</div>

                            <div class="partner-status-content">

                                <h3>Undangan Masuk</h3>

                                <p>
                                    Mahasiswa ini telah mengirimkan undangan
                                    kolaborasi kepadamu. Buka halaman notifikasi
                                    untuk memberikan respons.
                                </p>

                            </div>

                        </div>

                    @elseif($invitationStatus === 'pending')

                        <div class="partner-collaboration-status partner-status-pending">

                            <div class="partner-status-icon">◷</div>

                            <div class="partner-status-content">

                                <h3>Menunggu Respons</h3>

                                <p>
                                    Undangan kolaborasi telah dikirim.
                                    Kamu akan mendapatkan notifikasi
                                    ketika mahasiswa memberikan respons.
                                </p>

                            </div>

                        </div>

                    @else

                        <div class="partner-collaboration-status">

                            <div class="partner-status-icon">＋</div>

                            <div class="partner-status-content">

                                <h3>Belum Terhubung</h3>

                                <p>
                                    Kirimkan undangan kolaborasi untuk
                                    mengajak mahasiswa ini membentuk tim
                                    dan mengikuti perlombaan bersama.
                                </p>

                            </div>

                        </div>

                    @endif

                </article>

                <article class="fins-card fins-activity-card">

                    <h2>
                        Riwayat Aktivitas
                        <span aria-hidden="true">◴</span>
                    </h2>

                    <div class="fins-activity-list">

                        <p class="fins-empty-data">
                            Data riwayat aktivitas mahasiswa belum tersedia.
                        </p>

                    </div>

                </article>

            </section>

        </div>

    </main>

</div>

@if($canInvite)

    <dialog
        class="partner-invite-modal"
        id="partnerInviteModal"
        aria-labelledby="partnerInviteTitle"
    >

        <form
            id="partnerInviteForm"
            method="POST"
            action="{{ route('partner-invitations.store', [], false) }}"
        >

            @csrf

            <input
                type="hidden"
                name="receiver_id"
                value="{{ $partnerId }}"
            >

            <div class="partner-modal-header">

                <h2 id="partnerInviteTitle">
                    Ajak Kolaborasi
                </h2>

                <p>
                    Kirim undangan kolaborasi kepada
                    {{ $profile['fullname'] }}.
                </p>

            </div>

            <div class="partner-modal-body">

                <div class="partner-modal-field">

                    <label for="partnerCompetition">
                        Nama Perlombaan
                    </label>

                    <input
                        type="text"
                        id="partnerCompetition"
                        name="competition_name"
                        placeholder="Masukkan nama perlombaan"
                        maxlength="150"
                        required
                    >

                </div>

                <div class="partner-modal-field">

                    <label for="partnerRole">
                        Posisi yang Dibutuhkan
                    </label>

                    <input
                        type="text"
                        id="partnerRole"
                        name="role"
                        placeholder="Contoh: UI/UX Designer"
                        maxlength="100"
                    >

                </div>

                <div class="partner-modal-field">

                    <label for="partnerMessage">
                        Pesan Kolaborasi
                    </label>

                    <textarea
                        id="partnerMessage"
                        name="message"
                        maxlength="2000"
                        placeholder="Ceritakan tujuan kolaborasimu..."
                    ></textarea>

                </div>

                <p
                    class="partner-form-message"
                    id="partnerFormMessage"
                    role="status"
                    aria-live="polite"
                ></p>

            </div>

            <div class="partner-modal-footer">

                <button
                    class="partner-modal-cancel"
                    id="closePartnerInvite"
                    type="button"
                >
                    Batal
                </button>

                <button
                    class="partner-modal-submit"
                    id="submitPartnerInvite"
                    type="submit"
                >
                    Kirim Undangan
                </button>

            </div>

        </form>

    </dialog>

@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const avatarImage = document.getElementById('partnerAvatarImage');
        const avatarFallback = document.getElementById('partnerAvatarFallback');

        if (avatarImage && avatarFallback) {
            const showFallback = function () {
                avatarImage.style.display = 'none';
                avatarFallback.hidden = false;
            };

            avatarImage.addEventListener('error', showFallback);

            if (avatarImage.complete && avatarImage.naturalWidth === 0) {
                showFallback();
            }
        }

        const modal = document.getElementById('partnerInviteModal');
        const openButton = document.getElementById('openPartnerInvite');
        const closeButton = document.getElementById('closePartnerInvite');
        const form = document.getElementById('partnerInviteForm');
        const submitButton = document.getElementById('submitPartnerInvite');
        const messageElement = document.getElementById('partnerFormMessage');

        if (
            !modal ||
            !openButton ||
            !closeButton ||
            !form ||
            !submitButton ||
            !messageElement
        ) {
            return;
        }

        let isSubmitting = false;

        const closeModal = function () {
            if (isSubmitting) {
                return;
            }

            modal.close();
            messageElement.textContent = '';
        };

        openButton.addEventListener('click', function () {
            messageElement.textContent = '';
            modal.showModal();
        });

        closeButton.addEventListener('click', closeModal);

        modal.addEventListener('cancel', function (event) {
            if (isSubmitting) {
                event.preventDefault();
            }
        });

        modal.addEventListener('click', function (event) {
            if (event.target !== modal) {
                return;
            }

            const bounds = modal.getBoundingClientRect();

            const outside =
                event.clientX < bounds.left ||
                event.clientX > bounds.right ||
                event.clientY < bounds.top ||
                event.clientY > bounds.bottom;

            if (outside) {
                closeModal();
            }
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (isSubmitting) {
                return;
            }

            const formData = new FormData(form);

            const payload = {
                receiver_id: Number(formData.get('receiver_id')),
                competition_name: String(
                    formData.get('competition_name') || ''
                ).trim(),
                role: String(
                    formData.get('role') || ''
                ).trim(),
                message: String(
                    formData.get('message') || ''
                ).trim()
            };

            if (!payload.competition_name) {
                messageElement.textContent =
                    'Nama perlombaan wajib diisi.';

                return;
            }

            isSubmitting = true;
            submitButton.disabled = true;
            closeButton.disabled = true;
            submitButton.textContent = 'Mengirim...';
            messageElement.textContent = '';

            try {
                const csrfToken = document.querySelector(
                    'meta[name="csrf-token"]'
                ).content;

                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json().catch(function () {
                    return {};
                });

                if (!response.ok || result.success === false) {
                    const validationErrors = result.errors
                        ? Object.values(result.errors).flat()
                        : [];

                    throw new Error(
                        validationErrors[0] ||
                        result.message ||
                        'Undangan gagal dikirim.'
                    );
                }

                messageElement.style.color = '#287b46';

                messageElement.textContent =
                    'Undangan kolaborasi berhasil dikirim.';

                submitButton.textContent = 'Berhasil';

                openButton.disabled = true;

                openButton.textContent = 'Undangan Terkirim';

                window.setTimeout(function () {
                    window.location.reload();
                }, 700);

            } catch (error) {
                messageElement.style.color = '#b42318';

                messageElement.textContent =
                    error.message || 'Terjadi kesalahan. Silakan coba lagi.';

                isSubmitting = false;
                submitButton.disabled = false;
                closeButton.disabled = false;
                submitButton.textContent = 'Kirim Undangan';
            }
        });
    });
</script>

</body>
</html>