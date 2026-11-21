<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat | FINS UB</title>
    <link rel="stylesheet" href="{{ asset('css/chat.css') }}?v=8">
    <script src="{{ asset('js/chat.js') }}?v=8" defer></script>
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ url('/home') }}" aria-label="FINS UB">
        <img
            src="{{ asset('assets/images/logo fins.png') }}"
            alt="Logo FINS UB"
            class="brand-logo"
        >
    </a>
    <nav class="topnav" aria-label="Navigasi utama">
        <a href="{{ url('/home') }}">Beranda</a>
        <a href="{{ url('/cari-lomba') }}">Cari Lomba</a>
        <a href="{{ url('/cari-partner') }}">Cari Partner</a>
        <a href="{{ url('/lomba-saya') }}">Lomba Saya</a>
        <a href="{{ url('/tim-saya') }}">Tim Saya</a>
    </nav>
    <div class="header-actions"><span class="chat-head-icon" title="Chat">☏</span><span class="bell" title="Notifikasi">♧</span><a href="{{ url('/profile') }}" class="header-avatar" title="Profil">👤</a></div>
</header>
<main class="page-shell">
    <section class="chat-page" aria-label="Halaman chat">
        <div class="page-titlebar">
            <h1>Chat</h1>
            <label class="search-area" for="chatSearch"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.7" cy="10.7" r="6.2"/><path d="m15.5 15.5 5 5"/></svg><input type="search" id="chatSearch" placeholder="Cari percakapan, kontak FINS...." autocomplete="off"></label>
            <button class="search-button" id="searchButton" type="button">Cari</button>
        </div>
        <div class="messenger">
            <aside class="conversation-panel" aria-label="Daftar percakapan">
                <div class="filterbar">
                    <button type="button" class="pill active" id="filterAll" aria-pressed="true">Semua</button>
                    <button type="button" class="pill" id="filterUnread" aria-pressed="false">Belum dibaca <span id="unreadCount">0</span></button>
                    <select id="sortChats" aria-label="Urutkan percakapan"><option value="latest">Terbaru</option><option value="oldest">Terlama</option><option value="az">Abjad A–Z</option><option value="za">Abjad Z–A</option></select>
                </div>

                <button type="button" class="create-group-btn" id="createGroupBtn"> + Buat Grup Baru </button>

                <div class="list-rule"></div>
                <div class="conversation-list" id="conversationList"></div>
            </aside>

            
<section class="chat-room" aria-label="Isi percakapan">

    <!-- HEADER CHAT -->
    <div class="room-header">

        <button
            class="back-button"
            id="backToList"
            type="button">
            ←
        </button>

        <div
            id="selectedAvatar"
            class="avatar avatar-header">
        </div>

        <div class="room-title">
            <strong id="selectedName">
                Pilih percakapan
            </strong>
            <span id="selectedStatus"></span>
        </div>

        <!-- MENU PERCAKAPAN -->
        <div class="room-menu-wrapper">

            <button
                class="room-menu"
                id="roomMenu"
                type="button"
                aria-label="Menu percakapan"
                aria-expanded="false">
                ☰
            </button>

            <div
                class="room-dropdown"
                id="roomDropdown"
                hidden>

                <button
                    type="button"
                    id="deletePersonalChat"
                    class="delete-chat-option">
                    🗑 Hapus Percakapan
                </button>

            </div>

        </div>

    </div>

    <!-- AREA PESAN CHAT -->
    <div
        class="messages-area"
        id="messagesArea"
        role="log"
        aria-live="polite">
    </div>

    <!-- FORM KIRIM PESAN -->
    <form class="composer" id="messageForm">

        <button
            type="button"
            id="attachButton"
            class="attach-button"
            title="Kirim file"
            aria-label="Kirim file">

            <img
                src="{{ asset('assets/images/kirimFileChat.png') }}"
                alt=""
                class="attach-icon">
        </button>

        <input
            type="file"
            id="fileInput"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.txt,.zip"
            hidden>

        <input
            id="messageInput"
            type="text"
            maxlength="2000"
            placeholder="Ketik pesan..."
            autocomplete="off"
            aria-label="Ketik pesan">

        <button
            class="send-button"
            type="submit"
            aria-label="Kirim pesan"
            title="Kirim pesan">

            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M22 2L11 13M22 2L15 22L11 13L2 9L22 2Z"/>
            </svg>
        </button>

    </form>

</section>

        </div>
    </section>
</main>

<div class="group-overlay" id="groupOverlay" hidden>
    <div class="group-modal" role="dialog"
         aria-modal="true" aria-labelledby="groupModalTitle">

        <div class="group-modal-header">
            <h2 id="groupModalTitle">Buat Grup Baru</h2>
            <button type="button" id="closeGroupBtn"
                    aria-label="Tutup">×</button>
        </div>

        <form id="createGroupForm">
            <label for="groupName">Nama Grup</label>
            <input id="groupName" type="text"
                   maxlength="60"
                   placeholder="Contoh: Tim Brawijaya Synapse"
                   required>

            <label for="groupMemberSearch">Pilih Anggota</label>
            <input id="groupMemberSearch" type="search"
                   placeholder="Cari nama kontak...">

            <div id="groupMemberList" class="group-member-list"></div>

            <button type="submit" class="group-submit-btn">
                Buat Grup
            </button>
        </form>
    </div>
</div>


<!-- =========================================
     POPUP INFORMASI GRUP - FINS UB
========================================= -->
<div class="group-info-overlay" id="groupInfoOverlay" hidden>

    <div class="group-info-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="groupInfoTitle">

        <!-- Header -->
        <div class="group-info-header">
            <h2 id="groupInfoTitle">Informasi Grup</h2>

            <button type="button"
                    id="closeGroupInfoBtn"
                    aria-label="Tutup informasi grup">
                &times;
            </button>
        </div>

        <!-- Identitas Grup -->
        <div class="group-info-profile">

            <div class="group-info-avatar" id="groupInfoAvatar">
                    T
            </div>

            <div class="group-info-identity">
                <h3 id="groupInfoName">Nama Grup</h3>

                <p id="groupInfoCount">0 Anggota</p>
            </div>

        </div>

        <!-- Daftar Anggota -->
        <div class="group-info-members">

    <div class="group-info-members-header">
        <h3>Daftar Anggota</h3>

        <button
            type="button"
            class="group-add-member-btn"
            id="openAddGroupMemberBtn">
            + Tambah Anggota
        </button>
    </div>

            <div id="groupInfoMemberList"
                 class="group-info-member-list">
                <!-- Diisi otomatis oleh JavaScript -->
            </div>

        </div>

        <!-- Tombol Tutup -->
        
<!-- Tombol bagian bawah popup Informasi Grup -->
<div class="group-info-footer">

    <button
        type="button"
        id="leaveGroupBtn"
        class="group-leave-btn">
        Keluar Grup
    </button>

    <button
        type="button"
        id="closeGroupInfoFooter"
        class="group-info-close-btn">
        Tutup
    </button>

</div>

    </div>

</div>


<!-- =========================================
     POPUP TAMBAH ANGGOTA GRUP - FINS UB
========================================= -->
<div class="add-group-member-overlay" id="addGroupMemberOverlay" hidden>

    <div class="add-group-member-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="addGroupMemberTitle">

        <div class="add-group-member-header">
            <h2 id="addGroupMemberTitle">Tambah Anggota</h2>

            <button type="button"
                    id="closeAddGroupMemberBtn"
                    aria-label="Tutup popup">
                &times;
            </button>
        </div>

        <p class="add-group-member-description">
            Pilih kontak yang ingin ditambahkan ke grup.
        </p>

        <form id="addGroupMemberForm">

            <input type="search"
                   id="addGroupMemberSearch"
                   class="add-group-member-search"
                   placeholder="Cari nama kontak..."
                   autocomplete="off">

            <div id="addGroupMemberList"
                 class="add-group-member-list">
                <!-- Diisi otomatis oleh JavaScript -->
            </div>

            <div class="add-group-member-actions">
                <button type="button"
                        id="cancelAddGroupMemberBtn"
                        class="add-group-member-cancel">
                    Batal
                </button>

                <button type="submit"
                        class="add-group-member-submit">
                    Tambah Anggota
                </button>
            </div>

        </form>

    </div>

</div>


<!-- =========================================
     POPUP KONFIRMASI KELUARKAN ANGGOTA
========================================= -->
<div class="remove-member-overlay" id="removeMemberOverlay" hidden>

    <div class="remove-member-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="removeMemberTitle">

        <div class="remove-member-header">
            <h2 id="removeMemberTitle">Keluarkan Anggota</h2>
        </div>

        <p id="removeMemberText">
            Yakin ingin mengeluarkan anggota ini dari grup?
        </p>

        <div class="remove-member-actions">

            <button type="button"
                    id="cancelRemoveMemberBtn"
                    class="remove-member-cancel">
                Batal
            </button>

            <button type="button"
                    id="confirmRemoveMemberBtn"
                    class="remove-member-confirm">
                Keluarkan
            </button>

        </div>

    </div>

</div>


<!-- =========================================
     POPUP KONFIRMASI KELUAR GRUP
========================================= -->
<div class="leave-group-overlay" id="leaveGroupOverlay" hidden>

    <div class="leave-group-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="leaveGroupTitle">

        <div class="leave-group-header">
            <h2 id="leaveGroupTitle">Keluar dari Grup</h2>
        </div>

        <p id="leaveGroupText">
            Yakin ingin keluar dari grup ini?
        </p>

        <p class="leave-group-description">
            Grup akan dihilangkan dari daftar percakapanmu.
        </p>

        <div class="leave-group-actions">

            <button type="button"
                    id="cancelLeaveGroupBtn"
                    class="leave-group-cancel">
                Batal
            </button>

            <button type="button"
                    id="confirmLeaveGroupBtn"
                    class="leave-group-confirm">
                Keluar Grup
            </button>

        </div>

    </div>

</div>


<!-- =========================================
     POPUP KONFIRMASI JADIKAN ADMIN
========================================= -->
<div class="make-admin-overlay" id="makeAdminOverlay" hidden>

    <div class="make-admin-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="makeAdminTitle">

        <div class="make-admin-header">
            <h2 id="makeAdminTitle">Jadikan Admin Grup</h2>
        </div>

        <p id="makeAdminText">
            Yakin ingin menjadikan anggota ini sebagai Admin?
        </p>

        <div class="make-admin-actions">

            <button type="button"
                    id="cancelMakeAdminBtn"
                    class="make-admin-cancel">
                Batal
            </button>

            <button type="button"
                    id="confirmMakeAdminBtn"
                    class="make-admin-confirm">
                Jadikan Admin
            </button>

        </div>

    </div>

</div>


<!-- POPUP PERINGATAN ADMIN GRUP -->
<div class="admin-warning-overlay" id="adminWarningOverlay" hidden>
    <div class="admin-warning-modal"
         role="alertdialog"
         aria-modal="true"
         aria-labelledby="adminWarningTitle"
         aria-describedby="adminWarningText">

        <div class="admin-warning-icon">!</div>

        <h2 id="adminWarningTitle">Tidak Bisa Keluar Grup</h2>

        <p id="adminWarningText">
            Kamu adalah satu-satunya Admin grup.
            Jadikan anggota lain sebagai Admin terlebih dahulu.
        </p>

        <button type="button"
                id="closeAdminWarningBtn"
                class="admin-warning-close">
            Mengerti
        </button>
    </div>
</div>


<!-- POPUP KONFIRMASI HAPUS PERCAKAPAN -->
<div class="delete-confirm-overlay" id="deleteConfirmOverlay" hidden>
    <div class="delete-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmTitle">
        <div class="delete-confirm-header">
            <h2 id="deleteConfirmTitle">Hapus Percakapan</h2>
        </div>

        <div class="delete-confirm-body">
            <p id="deleteConfirmText">
                Yakin ingin menghapus percakapan ini?
            </p>
        </div>

        <div class="delete-confirm-actions">
            <button type="button" class="delete-cancel-btn" id="cancelDeleteBtn">
                Batal
            </button>
            <button type="button" class="delete-confirm-btn" id="confirmDeleteBtn">
                Hapus
            </button>
        </div>
    </div>
</div>

<!-- POPUP EDIT PESAN -->
<div class="message-modal-overlay" id="editMessageOverlay" hidden>
    <div class="message-modal" role="dialog" aria-modal="true" aria-labelledby="editMessageTitle">
        <h2 id="editMessageTitle">Edit Pesan</h2>
        <form id="editMessageForm">
            <label for="editMessageInput">Isi pesan</label>
            <textarea id="editMessageInput" maxlength="2000" rows="4" required></textarea>
            <div class="message-modal-actions">
                <button type="button" id="cancelEditMessage" class="message-modal-cancel">Batal</button>
                <button type="submit" class="message-modal-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- POPUP HAPUS PESAN (BERBEDA DENGAN HAPUS PERCAKAPAN) -->
<div class="message-modal-overlay" id="deleteMessageOverlay" hidden>
    <div class="message-modal" role="dialog" aria-modal="true" aria-labelledby="deleteMessageTitle">
        <h2 id="deleteMessageTitle">Hapus Pesan</h2>
        <p id="deleteMessageText"></p>
        <div class="message-modal-actions">
            <button type="button" id="cancelDeleteMessage" class="message-modal-cancel">Batal</button>
            <button type="button" id="confirmDeleteMessage" class="message-modal-primary">Hapus</button>
        </div>
    </div>
</div>
</body>
</html>
