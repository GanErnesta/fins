
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Chat | FINS UB</title>

    <!-- CSS -->
    <link rel="stylesheet" href="/css/navbar.css?v=20">
    <link rel="stylesheet" href="/css/chat.css?v=21">

    <!-- JAVASCRIPT -->
    <script src="{{ asset('js/chat.js') }}?v=22" defer></script>
</head>

<body>

<!-- NAVBAR GLOBAL -->
@include('components.navbar')

<main class="page-shell">
    <section class="chat-page" aria-label="Halaman chat">

        <!-- JUDUL DAN PENCARIAN -->
        <div class="page-titlebar">
            <h1>Chat</h1>

            <label class="search-area" for="chatSearch">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="10.7" cy="10.7" r="6.2"/>
                    <path d="m15.5 15.5 5 5"/>
                </svg>

                <input
                    type="search"
                    id="chatSearch"
                    placeholder="Cari percakapan, kontak FINS..."
                    autocomplete="off"
                >
            </label>

            <button
                class="search-button"
                id="searchButton"
                type="button">
                Cari
            </button>
        </div>

        <div class="messenger">

            <!-- PANEL DAFTAR PERCAKAPAN -->
            <aside
                class="conversation-panel"
                aria-label="Daftar percakapan">

                <div class="filterbar">

                    <button
                        type="button"
                        class="pill active"
                        id="filterAll"
                        aria-pressed="true">
                        Semua
                    </button>

                    <button
                        type="button"
                        class="pill"
                        id="filterUnread"
                        aria-pressed="false">
                        Belum dibaca
                        <span id="unreadCount">0</span>
                    </button>

                    <select
                        id="sortChats"
                        aria-label="Urutkan percakapan">

                        <option value="latest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                        <option value="az">Abjad A–Z</option>
                        <option value="za">Abjad Z–A</option>
                    </select>

                </div>

                <!-- BUAT GRUP -->
                <button
                    type="button"
                    class="create-group-btn"
                    id="createGroupBtn">
                    + Buat Grup Baru
                </button>

                <div class="list-rule"></div>

                <!-- DAFTAR CHAT -->
                <div
                    class="conversation-list"
                    id="conversationList">
                </div>

            </aside>

            <!-- RUANG CHAT -->
            <section
                class="chat-room"
                aria-label="Isi percakapan">

                <!-- HEADER CHAT -->
                <div class="room-header">

                    <button
                        class="back-button"
                        id="backToList"
                        type="button"
                        aria-label="Kembali ke daftar percakapan">
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

                <!-- AREA PESAN -->
                <div
                    class="messages-area"
                    id="messagesArea"
                    role="log"
                    aria-live="polite">
                </div>

                <!-- STATUS UPLOAD -->
                <div
                    id="uploadStatus"
                    role="status"
                    aria-live="polite"
                    hidden>
                </div>

                <!-- FORM KIRIM PESAN -->
                <form
                    class="composer"
                    id="messageForm"
                    data-conversations-url="{{ route('chat.conversations.index') }}"
                    data-messages-url-template="{{ url('/chat/conversations') }}/__ID__/messages"
                    data-upload-url="{{ route('chat.attachments.upload') }}"
                    data-download-url-template="{{ url('/chat/attachments') }}/__ID__/download"
                    enctype="multipart/form-data"
                >

                    <!-- TOMBOL LAMPIRAN -->
                    <button
                        type="button"
                        id="attachButton"
                        class="attach-button"
                        title="Kirim file ke Supabase Storage"
                        aria-label="Kirim file">

                        <img
                            src="/assets/images/kirimFileChat.png"
                            alt=""
                            class="attach-icon">
                    </button>

                    <!-- FILE INPUT -->
                    <input
                        type="file"
                        id="fileInput"
                        name="file"
                        accept=".pdf,.png,.jpg,.jpeg,.webp"
                        hidden
                    >

                    <!-- INPUT PESAN -->
                    <input
                        id="messageInput"
                        type="text"
                        maxlength="2000"
                        placeholder="Ketik pesan..."
                        autocomplete="off"
                        aria-label="Ketik pesan"
                    >

                    <!-- KIRIM PESAN -->
                    <button
                        class="send-button"
                        type="submit"
                        aria-label="Kirim pesan"
                        title="Kirim pesan">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M22 2L11 13M22 2L15 22L11 13L2 9L22 2Z"/>
                        </svg>

                    </button>

                </form>

            </section>

        </div>
    </section>
</main>

<!-- MODAL BUAT GRUP -->
<div
    class="group-overlay"
    id="groupOverlay"
    hidden>

    <div
        class="group-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="groupModalTitle">

        <div class="group-modal-header">

            <h2 id="groupModalTitle">
                Buat Grup Baru
            </h2>

            <button
                type="button"
                id="closeGroupBtn"
                aria-label="Tutup">
                ×
            </button>

        </div>

        <form id="createGroupForm">

            <label for="groupName">
                Nama Grup
            </label>

            <input
                id="groupName"
                type="text"
                maxlength="60"
                placeholder="Contoh: Tim Brawijaya Synapse"
                required
            >

            <label for="groupMemberSearch">
                Pilih Anggota
            </label>

            <input
                id="groupMemberSearch"
                type="search"
                placeholder="Cari nama kontak..."
            >

            <div
                id="groupMemberList"
                class="group-member-list">
            </div>

            <button
                type="submit"
                class="group-submit-btn">
                Buat Grup
            </button>

        </form>

    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS PERCAKAPAN -->
<div
    class="delete-confirm-overlay"
    id="deleteConfirmOverlay"
    hidden>

    <div
        class="delete-confirm-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deleteConfirmTitle">

        <div class="delete-confirm-header">

            <h2 id="deleteConfirmTitle">
                Hapus Percakapan
            </h2>

        </div>

        <div class="delete-confirm-body">

            <p id="deleteConfirmText">
                Yakin ingin menghapus percakapan ini?
            </p>

        </div>

        <div class="delete-confirm-actions">

            <button
                type="button"
                class="delete-cancel-btn"
                id="cancelDeleteBtn">
                Batal
            </button>

            <button
                type="button"
                class="delete-confirm-btn"
                id="confirmDeleteBtn">
                Hapus
            </button>

        </div>

    </div>
</div>

</body>
</html>
