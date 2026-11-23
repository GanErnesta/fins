
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Notifikasi | FINS</title>

    <link rel="stylesheet" href="{{ asset('css/notifikasi.css') }}?v=1">
    <script src="{{ asset('js/notifikasi.js') }}?v=1" defer></script>
</head>

<body>

    <!-- NAVBAR -->
    <header class="fins-navbar">
        <a href="{{ url('/home') }}" class="fins-logo">
            <span>F<small>UB</small></span>
        </a>

        <nav class="fins-nav">
            <a href="{{ url('/home') }}">Beranda</a>
            <a href="{{ url('/cari-lomba') }}">Cari Lomba</a>
            <a href="{{ url('/cari-partner') }}">Cari Partner</a>
            <a href="{{ url('/lomba-saya') }}">Lomba Saya</a>
            <a href="{{ url('/tim-saya') }}">Tim Saya</a>
        </nav>

        <div class="navbar-actions">
            <a href="{{ url('/chat') }}" class="nav-icon" aria-label="Chat">
                &#9993;
            </a>

            <button type="button" class="nav-icon notification-bell"
                    id="notificationBell" aria-label="Notifikasi">
                &#9834;
                <span class="notification-dot" id="notificationDot"></span>
            </button>

            <a href="{{ url('/profile') }}" class="profile-circle"
               aria-label="Profil"></a>
        </div>
    </header>

    <!-- POPUP NOTIFIKASI -->
    <div class="notification-popup" id="notificationPopup" hidden>
        <div class="popup-header">
            <div>
                <h3>Notifikasi</h3>
                <p id="popupUnreadCount">0 belum dibaca</p>
            </div>

            <div class="popup-actions">
                <button id="popupMarkAll" title="Tandai semua dibaca">
                    &#10003;
                </button>
                <button id="popupDeleteAll" title="Hapus semua">
                    &#128465;
                </button>
                <button id="popupClose" title="Tutup">
                    &times;
                </button>
            </div>
        </div>

        <div class="popup-list" id="popupNotificationList"></div>

        <div class="popup-footer">
            <button id="popupTestNotification">+ Uji Notifikasi</button>
            <a href="{{ url('/notifikasi') }}">Buka Halaman Penuh ↗</a>
        </div>
    </div>

    <!-- HALAMAN NOTIFIKASI -->
    <main class="notification-page">

        <div class="page-heading">
            <div>
                <p class="page-eyebrow">Halaman Notifikasi Penuh</p>
                <h1>Notifikasi Fins</h1>
                <p class="page-subtitle">
                    Semua kabar terbaru, dalam satu tempat.
                </p>
            </div>

            <button class="primary-button" id="markAllRead">
                ✓ Tandai Semua Dibaca
            </button>
        </div>

        <!-- STATISTIK -->
        <section class="notification-stats">
            <div class="stat-card stat-unread">
                <span>Belum Dibaca</span>
                <strong id="unreadCount">0</strong>
            </div>

            <div class="stat-card">
                <span>Total Notifikasi</span>
                <strong id="totalCount">0</strong>
            </div>

            <div class="stat-card">
                <span>Lamaran & Undangan</span>
                <strong id="applicationCount">0</strong>
            </div>

            <div class="stat-card">
                <span>Deadline & Pengumuman</span>
                <strong id="deadlineCount">0</strong>
            </div>
        </section>

        <!-- KELOLA NOTIFIKASI -->
        <section class="notification-manager">
            <div class="manager-heading">
                <h2>Kelola notifikasi</h2>

                <div class="manager-actions">
                    <button id="addTestNotification">
                        + Notifikasi Uji ✨
                    </button>

                    <button id="deleteAllNotifications" class="danger-button">
                        Hapus Semua 🗑
                    </button>
                </div>
            </div>

            <div class="search-filter-row">
                <input
                    type="search"
                    id="notificationSearch"
                    placeholder="Cari notifikasi, nama pendaftar, atau lomba..."
                >

                <label class="unread-filter">
                    <input type="checkbox" id="unreadOnly">
                    Belum Dibaca Saja
                </label>
            </div>

            <div class="notification-tabs" id="notificationTabs">
                <button class="tab active" data-filter="semua">
                    Semua
                </button>
                <button class="tab" data-filter="lamaran">
                    Lamaran Tim
                </button>
                <button class="tab" data-filter="undangan">
                    Undangan
                </button>
                <button class="tab" data-filter="chat">
                    Chat
                </button>
                <button class="tab" data-filter="deadline">
                    Deadline
                </button>
                <button class="tab" data-filter="sistem">
                    Sistem
                </button>
            </div>

            <div class="bulk-actions">
                <label>
                    <input type="checkbox" id="selectAllNotifications">
                    Pilih semua
                </label>

                <button id="deleteSelectedNotifications" hidden>
                    Hapus yang Dipilih
                </button>

                <button id="resetDemoNotifications">
                    Reset Data Contoh
                </button>
            </div>
        </section>

        <!-- DAFTAR NOTIFIKASI -->
        <section class="notification-list"
                 id="notificationList"
                 aria-live="polite">
        </section>

        <div class="empty-state" id="notificationEmpty" hidden>
            <h3>Belum ada notifikasi</h3>
            <p>Notifikasi baru akan muncul di sini.</p>
        </div>

    </main>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div class="confirm-overlay" id="deleteConfirmOverlay" hidden>
        <div class="confirm-modal"
             role="dialog"
             aria-modal="true"
             aria-labelledby="deleteConfirmTitle">

            <h3 id="deleteConfirmTitle">Hapus Notifikasi?</h3>

            <p id="deleteConfirmMessage">
                Notifikasi yang dihapus tidak dapat dikembalikan.
            </p>

            <div class="confirm-actions">
                <button id="cancelDeleteNotification">
                    Batal
                </button>

                <button id="confirmDeleteNotification"
                        class="danger-button">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

</body>
</html>
