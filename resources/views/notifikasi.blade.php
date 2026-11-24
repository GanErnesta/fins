
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>Notifikasi | FINS</title>

    {{-- CSS NAVBAR --}}
    <link rel="stylesheet"
          href="/css/navbar.css?v={{ filemtime(public_path('css/navbar.css')) }}">

    {{-- CSS NOTIFIKASI --}}
    <link rel="stylesheet"
          href="/css/notifikasi.css?v={{ filemtime(public_path('css/notifikasi.css')) }}">

    {{-- JAVASCRIPT NOTIFIKASI --}}
    <script
        src="/js/notifikasi.js?v={{ filemtime(public_path('js/notifikasi.js')) }}"
        defer>
    </script>

    <style>

        /* ==========================================
           PENGAMAN ELEMEN HIDDEN
        ========================================== */

        [hidden] {
            display: none !important;
        }

        /* ==========================================
           NAVBAR
        ========================================== */

        .fins-header__icon svg {
            width: 22px;
            height: 22px;
            display: block;
        }

        .fins-header__notification {
            position: relative;
        }

        .fins-header__dropdown {
            position: absolute;
            z-index: 9999;
        }

        /* ==========================================
           MODAL DETAIL NOTIFIKASI
        ========================================== */

        #partnerInviteDetail[hidden] {
            display: none !important;
        }

        #partnerInviteDetail {
            position: fixed;
            inset: 0;
            z-index: 10000;
            background: rgba(0, 0, 0, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .invite-detail-box {
            position: relative;
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-sizing: border-box;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
        }

        .invite-detail-close {
            position: absolute;
            right: 20px;
            top: 15px;
            background: transparent;
            border: none;
            font-size: 28px;
            color: #777;
            cursor: pointer;
        }

        .invite-detail-heading span {
            color: #990000;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .invite-detail-heading h2 {
            margin: 8px 25px 24px 0;
            font-size: 25px;
            color: #242424;
        }

        .invite-detail-row {
            margin-bottom: 17px;
        }

        .invite-detail-row strong {
            display: block;
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
        }

        .invite-detail-row p {
            margin: 0;
            color: #292929;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        #inviteDetailFeedback {
            font-size: 13px;
            color: #990000;
            margin: 14px 0;
        }

        .invite-detail-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .invite-detail-actions button {
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: 0.2s ease;
        }

        .invite-reject-btn {
            background: #f3f3f3;
            color: #333;
        }

        .invite-reject-btn:hover {
            background: #e8e8e8;
        }

        .invite-accept-btn {
            background: #990000;
            color: white;
        }

        .invite-accept-btn:hover {
            background: #780000;
        }

        .invite-detail-actions button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .invite-accepted-label {
            color: #168451;
            font-weight: 600;
            font-size: 13px;
        }

        .invite-rejected-label {
            color: #b91c1c;
            font-weight: 600;
            font-size: 13px;
        }

        /* ==========================================
           NOTIFIKASI BELUM DIBACA
        ========================================== */

        .notification-card.unread {
            border-left: 4px solid #D14F65;
            background: #fff5f7;
        }

        .notification-card {
            overflow-wrap: anywhere;
            transition: background 0.2s ease;
        }

        .notification-card button {
            cursor: pointer;
        }

        .notification-card button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .notification-card button[data-action="read"] {
            background: transparent;
            border: none;
            color: #990000;
            font-weight: 600;
            font-size: 13px;
        }

        .notification-card button[data-action="read"]:hover {
            text-decoration: underline;
        }

        /* ==========================================
           RESPONSIVE MODAL
        ========================================== */

        @media (max-width: 576px) {

            .invite-detail-box {
                padding: 24px 20px;
                border-radius: 16px;
            }

            .invite-detail-heading h2 {
                font-size: 21px;
            }

            .invite-detail-actions button {
                padding: 10px 18px;
            }

        }

    </style>

</head>

<body data-notification-user="{{ auth()->id() }}">

    {{-- ==========================================
         NAVBAR BERSAMA FINS
    ========================================== --}}

    @include('components.navbar')


    {{-- ==========================================
         POPUP NOTIFIKASI HALAMAN
    ========================================== --}}

    <div class="notification-popup"
         id="notificationPopup"
         hidden>

        <div class="popup-header">

            <div>

                <h3>Notifikasi</h3>

                <p id="popupUnreadCount">
                    0 belum dibaca
                </p>

            </div>

            <div class="popup-actions">

                <button
                    type="button"
                    id="popupMarkAll"
                    title="Tandai semua dibaca"
                    aria-label="Tandai semua dibaca">

                    &#10003;

                </button>

                <button
                    type="button"
                    id="popupDeleteAll"
                    disabled
                    hidden
                    title="Hapus semua">

                    &#128465;

                </button>

                <button
                    type="button"
                    id="popupClose"
                    title="Tutup"
                    aria-label="Tutup notifikasi">

                    &times;

                </button>

            </div>

        </div>

        {{-- DAFTAR NOTIFIKASI POPUP --}}

        <div class="popup-list"
             id="popupNotificationList"
             aria-live="polite">
        </div>

        <div class="popup-footer">

            <button
                type="button"
                id="popupTestNotification"
                disabled
                hidden>

                + Uji Notifikasi

            </button>

            <a href="/notifikasi">

                Buka Halaman Penuh

            </a>

        </div>

    </div>


    {{-- ==========================================
         HALAMAN NOTIFIKASI UTAMA
    ========================================== --}}

    <main class="notification-page">

        {{-- HEADER HALAMAN --}}

        <div class="page-heading">

            <div>

                <p class="page-eyebrow">

                    Halaman Notifikasi Penuh

                </p>

                <h1>Notifikasi Fins</h1>

                <p class="page-subtitle">

                    Semua kabar terbaru, dalam satu tempat.

                </p>

            </div>

            <button
                type="button"
                class="primary-button"
                id="markAllRead">

                ✓ Tandai Semua Dibaca

            </button>

        </div>


        {{-- ==========================================
             STATISTIK NOTIFIKASI
        ========================================== --}}

        <section class="notification-stats"
                 aria-label="Statistik notifikasi">

            <div class="stat-card stat-unread">

                <span>Belum Dibaca</span>

                <strong id="unreadCount"
                        aria-live="polite">

                    0

                </strong>

            </div>

            <div class="stat-card">

                <span>Total Notifikasi</span>

                <strong id="totalCount">

                    0

                </strong>

            </div>

            <div class="stat-card">

                <span>Lamaran &amp; Undangan</span>

                <strong id="applicationCount">

                    0

                </strong>

            </div>

            <div class="stat-card">

                <span>Deadline &amp; Pengumuman</span>

                <strong id="deadlineCount">

                    0

                </strong>

            </div>

        </section>


        {{-- ==========================================
             KELOLA NOTIFIKASI
        ========================================== --}}

        <section class="notification-manager">

            <div class="manager-heading">

                <h2>Kelola notifikasi</h2>

                <div class="manager-actions" hidden>

                    {{-- FITUR DEMO DINONAKTIFKAN --}}

                    <button
                        type="button"
                        id="addTestNotification"
                        disabled
                        hidden>

                        + Notifikasi Uji

                    </button>

                    {{-- MENUNGGU ENDPOINT DELETE --}}

                    <button
                        type="button"
                        id="deleteAllNotifications"
                        class="danger-button"
                        disabled
                        hidden>

                        Hapus Semua

                    </button>

                </div>

            </div>


            {{-- ==========================================
                 PENCARIAN DAN FILTER
            ========================================== --}}

            <div class="search-filter-row">

                <input
                    type="search"
                    id="notificationSearch"
                    aria-label="Cari notifikasi"
                    placeholder="Cari notifikasi, nama pendaftar, atau lomba..."
                    autocomplete="off">

                <label class="unread-filter">

                    <input
                        type="checkbox"
                        id="unreadOnly">

                    Belum Dibaca Saja

                </label>

            </div>


            {{-- ==========================================
                 KATEGORI NOTIFIKASI
            ========================================== --}}

            <div class="notification-tabs"
                 id="notificationTabs">

                <button
                    type="button"
                    class="tab active"
                    data-filter="semua">

                    Semua

                </button>

                <button
                    type="button"
                    class="tab"
                    data-filter="lamaran">

                    Lamaran Tim

                </button>

                <button
                    type="button"
                    class="tab"
                    data-filter="undangan">

                    Undangan

                </button>

                <button
                    type="button"
                    class="tab"
                    data-filter="chat">

                    Chat

                </button>

                <button
                    type="button"
                    class="tab"
                    data-filter="deadline">

                    Deadline

                </button>

                <button
                    type="button"
                    class="tab"
                    data-filter="sistem">

                    Sistem

                </button>

            </div>


            {{-- ==========================================
                 BULK ACTIONS DINONAKTIFKAN
            ========================================== --}}

            <div class="bulk-actions" hidden>

                <label hidden>

                    <input
                        type="checkbox"
                        id="selectAllNotifications"
                        disabled
                        hidden>

                    Pilih semua

                </label>

                <button
                    type="button"
                    id="deleteSelectedNotifications"
                    disabled
                    hidden>

                    Hapus yang Dipilih

                </button>

                <button
                    type="button"
                    id="resetDemoNotifications"
                    disabled
                    hidden>

                    Reset Data Contoh

                </button>

            </div>

        </section>


        {{-- ==========================================
             DAFTAR NOTIFIKASI
             Diisi otomatis oleh notifikasi.js
        ========================================== --}}

        <section class="notification-list"
                 id="notificationList"
                 aria-label="Daftar notifikasi"
                 aria-live="polite">

        </section>


        {{-- ==========================================
             EMPTY STATE
        ========================================== --}}

        <div class="empty-state"
             id="notificationEmpty"
             hidden>

            <h3>Belum ada notifikasi</h3>

            <p>

                Notifikasi baru akan muncul di sini.

            </p>

        </div>

    </main>


    {{-- ==========================================
         MODAL DETAIL NOTIFIKASI DAN UNDANGAN
    ========================================== --}}

    <div id="partnerInviteDetail" hidden>

        <div class="invite-detail-box"
             role="dialog"
             aria-modal="true"
             aria-labelledby="inviteDetailTitle">

            <button
                type="button"
                id="closeInviteDetail"
                class="invite-detail-close"
                data-close-notification
                aria-label="Tutup detail">

                &times;

            </button>

            <div class="invite-detail-heading">

                <span id="inviteDetailEyebrow">

                    NOTIFIKASI FINS

                </span>

                <h2 id="inviteDetailTitle">

                    Detail Notifikasi

                </h2>

            </div>

            {{-- ISI DETAIL DINAMIS --}}

            <div id="inviteDetailContent">

            </div>

            {{-- FEEDBACK TERIMA / TOLAK --}}

            <p id="inviteDetailFeedback"
               aria-live="polite">

            </p>

            {{-- TOMBOL AKSI DINAMIS --}}

            <div class="invite-detail-actions"
                 id="inviteDetailActions">

            </div>

        </div>

    </div>


    {{-- ==========================================
         MODAL KONFIRMASI HAPUS
         Disimpan untuk pengembangan selanjutnya
    ========================================== --}}

    <div class="confirm-overlay"
         id="deleteConfirmOverlay"
         hidden>

        <div class="confirm-modal"
             role="dialog"
             aria-modal="true"
             aria-labelledby="deleteConfirmTitle">

            <h3 id="deleteConfirmTitle">

                Hapus Notifikasi?

            </h3>

            <p id="deleteConfirmMessage">

                Notifikasi yang dihapus tidak dapat dikembalikan.

            </p>

            <div class="confirm-actions">

                <button
                    type="button"
                    id="cancelDeleteNotification">

                    Batal

                </button>

                <button
                    type="button"
                    id="confirmDeleteNotification"
                    class="danger-button"
                    disabled>

                    Ya, Hapus

                </button>

            </div>

        </div>

    </div>

</body>

</html>
