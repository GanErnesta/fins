<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Chat | FINS UB</title>

    <link rel="stylesheet"
          href="/css/navbar.css?v={{ filemtime(public_path('css/navbar.css')) }}">

    <link rel="stylesheet"
          href="/css/chat.css?v={{ filemtime(public_path('css/chat.css')) }}">

    <script
        src="/js/chat.js?v={{ filemtime(public_path('js/chat.js')) }}"
        defer>
    </script>
</head>

<body>

@include('components.navbar')

<main class="page-shell">
    <section class="chat-page" aria-label="Halaman chat">

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

                <button
                    type="button"
                    class="create-group-btn"
                    id="createGroupBtn">
                    + Buat Grup Baru
                </button>

                <div class="list-rule"></div>

                <div
                    class="conversation-list"
                    id="conversationList">
                </div>

            </aside>

            <section
                class="chat-room"
                aria-label="Isi percakapan">

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

                <div
                    class="messages-area"
                    id="messagesArea"
                    role="log"
                    aria-live="polite">
                </div>

                <div
                    id="uploadStatus"
                    role="status"
                    aria-live="polite"
                    hidden>
                </div>

                <form
                    class="composer"
                    id="messageForm"
                    data-conversations-url="{{ route('chat.conversations.index', [], false) }}"
                    data-messages-url-template="/chat/conversations/__ID__/messages"
                    data-update-message-url-template="/chat/conversations/__ID__/messages/__MESSAGE_ID__"
                    data-delete-message-me-url-template="/chat/conversations/__ID__/messages/__MESSAGE_ID__/me"
                    data-delete-message-everyone-url-template="/chat/conversations/__ID__/messages/__MESSAGE_ID__/everyone"
                    data-upload-url="{{ route('chat.attachments.upload', [], false) }}"
                    data-download-url-template="/chat/attachments/__ID__/download"
                    enctype="multipart/form-data"
                >

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

                    <input
                        type="file"
                        id="fileInput"
                        name="file"
                        accept=".pdf,.png,.jpg,.jpeg,.webp"
                        hidden
                    >

                    <input
                        id="messageInput"
                        type="text"
                        maxlength="2000"
                        placeholder="Ketik pesan..."
                        autocomplete="off"
                        aria-label="Ketik pesan"
                    >

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

<div
    class="message-context-menu"
    id="messageContextMenu"
    hidden>

    <button
        type="button"
        class="message-menu-option"
        id="editMessageOption"
        hidden>

        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true">

            <path d="M12 20h9"/>
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L9 17l-4 1 1-4Z"/>

        </svg>

        <span>Edit Pesan</span>

    </button>

    <button
        type="button"
        class="message-menu-option message-menu-danger"
        id="deleteMessageForMeOption"
        hidden>

        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true">

            <path d="M3 6h18"/>
            <path d="M8 6V4h8v2"/>
            <path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 10v6"/>
            <path d="M14 10v6"/>

        </svg>

        <span>Hapus untuk Saya</span>

    </button>

    <button
        type="button"
        class="message-menu-option message-menu-danger"
        id="deleteMessageForEveryoneOption"
        hidden>

        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true">

            <path d="M3 6h18"/>
            <path d="M8 6V4h8v2"/>
            <path d="M19 6l-1 14H6L5 6"/>
            <path d="m10 11 4 4"/>
            <path d="m14 11-4 4"/>

        </svg>

        <span>Hapus untuk Semua Orang</span>

    </button>

</div>

<div
    class="edit-message-overlay"
    id="editMessageOverlay"
    hidden>

    <div
        class="edit-message-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editMessageTitle">

        <div class="edit-message-header">

            <h2 id="editMessageTitle">
                Edit Pesan
            </h2>

            <button
                type="button"
                class="edit-message-close"
                id="closeEditMessageBtn"
                aria-label="Tutup">
                ×
            </button>

        </div>

        <form id="editMessageForm">

            <div class="edit-message-body">

                <label for="editMessageInput">
                    Isi pesan
                </label>

                <textarea
                    id="editMessageInput"
                    name="message"
                    maxlength="2000"
                    rows="4"
                    placeholder="Edit pesanmu..."
                    required
                ></textarea>

                <div class="edit-message-info">

                    <span
                        id="editMessageError"
                        class="edit-message-error"
                        role="alert"
                        hidden>
                    </span>

                    <span
                        id="editMessageCharCount"
                        class="edit-message-char-count">
                        0/2000
                    </span>

                </div>

            </div>

            <div class="edit-message-actions">

                <button
                    type="button"
                    class="edit-message-cancel"
                    id="cancelEditMessageBtn">
                    Batal
                </button>

                <button
                    type="submit"
                    class="edit-message-save"
                    id="saveEditMessageBtn">
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>

</div>

<div
    class="delete-message-overlay"
    id="deleteMessageOverlay"
    hidden>

    <div
        class="delete-message-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deleteMessageTitle"
        aria-describedby="deleteMessageText">

        <div class="delete-message-header">

            <h2 id="deleteMessageTitle">
                Hapus Pesan
            </h2>

            <button
                type="button"
                class="delete-message-close"
                id="closeDeleteMessageBtn"
                aria-label="Tutup">
                ×
            </button>

        </div>

        <form id="deleteMessageForm">

            <div class="delete-message-body">

                <div
                    class="delete-message-icon"
                    aria-hidden="true">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round">

                        <path d="M3 6h18"/>
                        <path d="M8 6V4h8v2"/>
                        <path d="M19 6l-1 14H6L5 6"/>
                        <path d="M10 10v6"/>
                        <path d="M14 10v6"/>

                    </svg>

                </div>

                <p id="deleteMessageText">
                    Apakah kamu yakin ingin menghapus pesan ini?
                </p>

                <div
                    class="delete-message-preview"
                    id="deleteMessagePreview"
                    hidden>
                </div>

                <p
                    class="delete-message-description"
                    id="deleteMessageDescription">
                    Pesan yang dihapus tidak dapat dikembalikan.
                </p>

                <span
                    class="delete-message-error"
                    id="deleteMessageError"
                    role="alert"
                    hidden>
                </span>

            </div>

            <div class="delete-message-actions">

                <button
                    type="button"
                    class="delete-message-cancel"
                    id="cancelDeleteMessageBtn">
                    Batal
                </button>

                <button
                    type="submit"
                    class="delete-message-confirm"
                    id="confirmDeleteMessageBtn">
                    Hapus
                </button>

            </div>

        </form>

    </div>

</div>

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