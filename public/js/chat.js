
(() => {
    'use strict';

    /*
    |--------------------------------------------------------------------------
    | KONFIGURASI
    |--------------------------------------------------------------------------
    */

    const STORAGE_KEY = 'fins_chat_demo_v1';

    const $ = (id) => document.getElementById(id);

    const list = $('conversationList');
    const area = $('messagesArea');
    const form = $('messageForm');
    const search = $('chatSearch');
    const input = $('messageInput');
    const sort = $('sortChats');
    const messenger = document.querySelector('.messenger');

    const fileInput = $('fileInput');
    const attachButton = $('attachButton');
    const uploadStatus = $('uploadStatus');

    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content || '';
    const conversationsUrl =
        form.dataset.conversationsUrl ||
        '/chat/conversations';

    const messagesUrlTemplate =
        form.dataset.messagesUrlTemplate ||
        '/chat/conversations/__ID__/messages';
    const uploadUrl =
        form?.dataset.uploadUrl ||
        '/chat/attachments/upload';

    const downloadUrlTemplate =
        form?.dataset.downloadUrlTemplate ||
        '/chat/attachments/__ID__/download';

    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf'
    ];

    if (!list || !area || !form || !search || !input || !sort) {
        console.error('Elemen halaman Chat FINS tidak lengkap.');
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | DATA PERCAKAPAN DEMO
    |--------------------------------------------------------------------------
    |
    | ID di bawah adalah ID lokal, bukan ID database Supabase.
    | Upload baru bisa dilakukan ketika dbConversationId tersedia.
    |
    */

    const initialConversations = [
        {
            id: 1,
            name: 'Agus',
            unread: 2,
            messages: [
                {
                    id: 1,
                    from: 'them',
                    text: 'Halo, masih mencari anggota tim lomba?',
                    at: '2026-11-19T15:12:00'
                },
                {
                    id: 2,
                    from: 'them',
                    text: 'Aku punya ide untuk lomba UI/UX.',
                    at: '2026-11-20T08:15:00'
                }
            ]
        },
        {
            id: 2,
            name: 'Amba',
            unread: 0,
            messages: [
                {
                    id: 1,
                    from: 'them',
                    text: 'Hai, kamu sudah lihat info lomba desain?',
                    at: '2026-11-20T10:00:00'
                },
                {
                    id: 2,
                    from: 'me',
                    text: 'Sudah! Kamu mau daftar bareng?',
                    at: '2026-11-20T10:01:00'
                },
                {
                    id: 3,
                    from: 'them',
                    text: 'Boleh banget, ayo kita diskusi!',
                    at: '2026-11-20T10:02:00'
                }
            ]
        },
        {
            id: 3,
            name: 'Deni',
            unread: 1,
            messages: [
                {
                    id: 1,
                    from: 'them',
                    text: 'Berkas pendaftarannya sudah aku kirim.',
                    at: '2026-11-18T16:30:00'
                }
            ]
        },
        {
            id: 4,
            name: 'Tukam',
            unread: 0,
            messages: [
                {
                    id: 1,
                    from: 'them',
                    text: 'Besok kita latihan presentasi ya.',
                    at: '2026-11-17T18:20:00'
                }
            ]
        },
        {
            id: 5,
            name: 'Aram',
            unread: 3,
            messages: [
                {
                    id: 1,
                    from: 'them',
                    text: 'Bisa bantu cek proposal tim?',
                    at: '2026-11-20T09:30:00'
                }
            ]
        },
        {
            id: 6,
            name: 'Sera♥',
            unread: 1,
            messages: [
                {
                    id: 1,
                    from: 'them',
                    text: 'Semangat lombanya! ✨',
                    at: '2026-11-16T11:35:00'
                }
            ]
        },
        {
            id: 7,
            name: 'Ugat',
            unread: 0,
            messages: [
                {
                    id: 1,
                    from: 'them',
                    text: 'Makasih sudah gabung tim.',
                    at: '2026-11-15T13:00:00'
                }
            ]
        }
    ];

    /*
    |--------------------------------------------------------------------------
    | LOAD DATA LOCAL STORAGE
    |--------------------------------------------------------------------------
    */

    let chats;

    try {
        const saved = JSON.parse(
            localStorage.getItem(STORAGE_KEY)
        );

        chats = Array.isArray(saved)
            ? saved
            : structuredClone(initialConversations);

    } catch (error) {
        chats = structuredClone(initialConversations);
    }

    chats = chats.map(chat => ({
        ...chat,
        unread: Number(chat.unread) || 0,
        messages: Array.isArray(chat.messages)
            ? chat.messages
            : [],
        dbConversationId:
            Number.isSafeInteger(Number(chat.dbConversationId)) &&
            Number(chat.dbConversationId) > 0
                ? Number(chat.dbConversationId)
                : null
    }));

    let selectedId = chats.some(c => c.id === 2)
        ? 2
        : (chats[0]?.id ?? null);

    let filter = 'all';
    let pendingDeleteChatId = null;
    let isUploading = false;

    /*
    |--------------------------------------------------------------------------
    | SAVE DATA
    |--------------------------------------------------------------------------
    */

    function save() {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(chats)
            );
        } catch (error) {
            console.warn('Gagal menyimpan chat lokal:', error);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    function selected() {
        return chats.find(c => c.id === selectedId);
    }

    function initials(name) {
        return String(name || '?')
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(word => Array.from(word)[0].toUpperCase())
            .join('') || '?';
    }

    function node(tag, className = '', text) {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text !== undefined) {
            element.textContent = String(text);
        }

        return element;
    }

    function formatTime(iso) {
        const date = new Date(iso);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    function formatDay(iso) {
        const date = new Date(iso);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(date);
    }

    function lastAt(chat) {
        const messages = chat.messages || [];

        if (!messages.length) {
            return 0;
        }

        const date = new Date(messages.at(-1).at);

        return Number.isNaN(date.getTime())
            ? 0
            : date.getTime();
    }

    function getPreview(message) {
        if (!message) {
            return 'Mulai percakapan';
        }

        if (message.type === 'file' || message.type === 'image') {
            return '📎 ' + (message.fileName || 'Lampiran');
        }

        return message.text || 'Mulai percakapan';
    }

    function getDownloadUrl(attachmentId) {
        return downloadUrlTemplate.replace(
            '__ID__',
            encodeURIComponent(String(attachmentId))
        );
    }

    function showUploadStatus(message, isError = false) {
        if (!uploadStatus) {
            if (isError) {
                alert(message);
            }
            return;
        }

        uploadStatus.textContent = message;
        uploadStatus.hidden = !message;
        uploadStatus.style.color = isError
            ? '#c0392b'
            : '';

        uploadStatus.style.padding = message
            ? '8px 16px'
            : '';
    }

    function getServerConversationId(chat) {
        const id = Number(chat?.dbConversationId);

        return Number.isSafeInteger(id) && id > 0
            ? id
            : null;
    }

async function loadConversations() {
    try {
        const response = await fetch(conversationsUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(
                'Gagal mengambil percakapan. HTTP ' +
                response.status
            );
        }

        const result = await response.json();

        if (!result.success || !Array.isArray(result.data)) {
            throw new Error('Format data percakapan tidak valid.');
        }

        chats = result.data.map(chat => ({
            ...chat,
            id: Number(chat.id),
            dbConversationId: Number(chat.dbConversationId),
            messages: Array.isArray(chat.messages)
                ? chat.messages
                : [],
            unread: Number(chat.unread) || 0
        }));

        selectedId = chats[0]?.id ?? null;

        renderList();
        renderRoom();

        if (selectedId !== null) {
            await loadMessages(selectedId);
        }

    } catch (error) {
        console.error('Gagal mengambil percakapan:', error);
        showUploadStatus(
            'Gagal memuat percakapan dari server.',
            true
        );
    }
}

async function loadMessages(conversationId) {
    const chat = chats.find(
        item => item.id === conversationId
    );

    if (!chat) {
        return;
    }

    try {
        const url = messagesUrlTemplate.replace(
            '__ID__',
            encodeURIComponent(String(conversationId))
        );

        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(
                'Gagal mengambil pesan. HTTP ' +
                response.status
            );
        }

        const result = await response.json();

        if (!result.success || !Array.isArray(result.data)) {
            throw new Error('Format riwayat pesan tidak valid.');
        }

        chat.messages = result.data;

        if (selectedId === conversationId) {
            renderRoom();
        }

        renderList();

    } catch (error) {
        console.error('Gagal memuat pesan:', error);
    }
}


    /*
    |--------------------------------------------------------------------------
    | RENDER DAFTAR PERCAKAPAN
    |--------------------------------------------------------------------------
    */

    function renderList() {
        const keyword = search.value
            .trim()
            .toLocaleLowerCase('id');

        let visible = chats.filter(chat => {
            const matchesFilter =
                filter === 'all' || chat.unread > 0;

            const matchesSearch =
                !keyword ||
                chat.name.toLocaleLowerCase('id').includes(keyword) ||
                chat.messages.some(message => {
                    const text = String(
                        message.text || ''
                    ).toLocaleLowerCase('id');

                    const fileName = String(
                        message.fileName || ''
                    ).toLocaleLowerCase('id');

                    return (
                        text.includes(keyword) ||
                        fileName.includes(keyword)
                    );
                });

            return matchesFilter && matchesSearch;
        });

        visible.sort((a, b) => {
            if (sort.value === 'az') {
                return a.name.localeCompare(b.name, 'id');
            }

            if (sort.value === 'za') {
                return b.name.localeCompare(a.name, 'id');
            }

            if (sort.value === 'oldest') {
                return lastAt(a) - lastAt(b);
            }

            return lastAt(b) - lastAt(a);
        });

        list.replaceChildren();

        for (const chat of visible) {
            const item = node(
                'button',
                'conversation-item' +
                (chat.id === selectedId ? ' active' : '')
            );

            item.type = 'button';

            item.setAttribute(
                'aria-pressed',
                String(chat.id === selectedId)
            );

            const avatar = node(
                'span',
                'avatar',
                initials(chat.name)
            );

            const meta = node('span', 'contact-meta');

            meta.append(
                node('span', 'contact-name', chat.name)
            );

            meta.append(
                node(
                    'span',
                    'contact-preview',
                    getPreview(chat.messages.at(-1))
                )
            );

            const right = node('span', 'contact-right');

            if (chat.messages.length) {
                right.append(
                    node(
                        'span',
                        'contact-time',
                        formatTime(chat.messages.at(-1).at)
                    )
                );
            }

            if (chat.unread > 0) {
                right.append(
                    node(
                        'span',
                        'unread-badge',
                        chat.unread
                    )
                );
            }

            item.append(avatar, meta, right);

            item.addEventListener('click', () => {
                selectChat(chat.id);
            });

            list.append(item);
        }

        if (!visible.length) {
            list.append(
                node(
                    'p',
                    'no-results',
                    'Tidak ada percakapan yang cocok.'
                )
            );
        }

        const totalUnread = chats.reduce(
            (sum, chat) => sum + (chat.unread || 0),
            0
        );

        $('unreadCount').textContent = totalUnread;

        $('filterAll').classList.toggle(
            'active',
            filter === 'all'
        );

        $('filterUnread').classList.toggle(
            'active',
            filter === 'unread'
        );

        $('filterAll').setAttribute(
            'aria-pressed',
            String(filter === 'all')
        );

        $('filterUnread').setAttribute(
            'aria-pressed',
            String(filter === 'unread')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER RUANG CHAT
    |--------------------------------------------------------------------------
    */

    function renderRoom() {
        const chat = selected();

        area.replaceChildren();

        if (!chat) {
            $('selectedName').textContent = 'Pilih percakapan';
            $('selectedAvatar').textContent = '?';
            $('selectedStatus').textContent = '';
            input.disabled = true;
            attachButton.disabled = true;
            return;
        }

        input.disabled = false;
        attachButton.disabled = isUploading;

        $('selectedName').textContent = chat.name;

        $('selectedAvatar').textContent = initials(chat.name);

        const memberCount = Array.isArray(chat.members)
            ? chat.members.length
            : 1;

        $('selectedStatus').textContent = chat.isGroup
            ? `${memberCount} anggota`
            : 'Percakapan';

        if (!chat.messages.length) {
            area.append(
                node(
                    'p',
                    'empty-chat',
                    'Belum ada pesan. Mulai percakapan!'
                )
            );
            return;
        }

        let previousDay = '';

        for (const message of chat.messages) {
            const day = formatDay(message.at);

            if (day !== previousDay) {
                area.append(
                    node('div', 'day-separator', day)
                );

                previousDay = day;
            }

            /*
            | PESAN SISTEM GRUP
            */

            if (
                message.type === 'system' ||
                message.from === 'system' ||
                (
                    chat.isGroup &&
                    message.text?.startsWith('Grup ') &&
                    message.text?.endsWith(' berhasil dibuat!')
                )
            ) {
                const announcement = node(
                    'div',
                    'system-announcement',
                    message.text || ''
                );

                announcement.style.textAlign = 'center';
                announcement.style.margin = '12px auto';
                announcement.style.fontSize = '12px';
                announcement.style.opacity = '0.8';

                area.append(announcement);
                continue;
            }

            /*
            | BUBBLE PESAN
            */

            const bubble = node(
                'div',
                'message ' +
                (message.from === 'me' ? 'mine' : 'theirs')
            );

            const isAttachment =
                message.type === 'file' ||
                message.type === 'image';

            const fileUrl = message.attachmentId
                ? getDownloadUrl(message.attachmentId)
                : message.fileUrl;

            if (isAttachment && fileUrl) {
                const attachment = node(
                    'a',
                    'message-attachment'
                );

                attachment.href = fileUrl;
                attachment.target = '_blank';
                attachment.rel = 'noopener noreferrer';

                const fileIcon = node(
                    'span',
                    'attachment-file-icon',
                    message.type === 'image' ? '🖼️' : '📄'
                );

                const fileInfo = node(
                    'span',
                    'attachment-file-info'
                );

                const fileName = node(
                    'strong',
                    'attachment-file-name',
                    message.fileName || 'Lampiran'
                );

                const fileSize = node(
                    'small',
                    'attachment-file-size',
                    message.fileSize
                        ? (message.fileSize / 1024).toFixed(1) + ' KB'
                        : 'File'
                );

                fileInfo.append(fileName, fileSize);

                attachment.append(
                    fileIcon,
                    fileInfo,
                    node('span', 'attachment-download', '↓')
                );

                bubble.append(attachment);

            } else if (isAttachment) {
                bubble.append(
                    node(
                        'span',
                        'message-text',
                        message.fileName || 'Lampiran'
                    )
                );

            } else {
                bubble.append(
                    node(
                        'span',
                        'message-text',
                        message.text || ''
                    )
                );
            }

            bubble.append(
                node(
                    'span',
                    'message-time',
                    formatTime(message.at)
                )
            );

            area.append(bubble);
        }

        requestAnimationFrame(() => {
            area.scrollTop = area.scrollHeight;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | PILIH PERCAKAPAN
    |--------------------------------------------------------------------------
    */

    async function selectChat(id) {
    selectedId = id;

    const chat = selected();

    if (!chat) {
        return;
    }

    chat.unread = 0;

    renderList();
    renderRoom();

    messenger.classList.add('room-open');

    if (getServerConversationId(chat)) {
        await loadMessages(chat.id);
    }
}

    /*
    |--------------------------------------------------------------------------
    | FILTER, SORT, SEARCH
    |--------------------------------------------------------------------------
    */

    $('filterAll').addEventListener('click', () => {
        filter = 'all';
        renderList();
    });

    $('filterUnread').addEventListener('click', () => {
        filter = 'unread';
        renderList();
    });

    sort.addEventListener('change', renderList);

    search.addEventListener('input', renderList);

    $('searchButton').addEventListener('click', () => {
        search.focus();
        renderList();
    });

    $('backToList').addEventListener('click', () => {
        messenger.classList.remove('room-open');
    });

    /*
    |--------------------------------------------------------------------------
    | BUAT GRUP
    |--------------------------------------------------------------------------
    */

    const groupOverlay = $('groupOverlay');
    const groupForm = $('createGroupForm');
    const groupName = $('groupName');
    const memberSearch = $('groupMemberSearch');
    const memberList = $('groupMemberList');

    const selectedGroupMembers = new Set();

    function renderGroupMembers() {
        const keyword = memberSearch.value
            .trim()
            .toLowerCase();

        memberList.replaceChildren();

        chats
            .filter(chat => !chat.isGroup)
            .filter(chat =>
                chat.name.toLowerCase().includes(keyword)
            )
            .forEach(chat => {
                const label = document.createElement('label');

                label.className = 'group-member-item';

                const checkbox = document.createElement('input');

                checkbox.type = 'checkbox';
                checkbox.value = String(chat.id);
                checkbox.checked = selectedGroupMembers.has(
                    chat.id
                );

                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        selectedGroupMembers.add(chat.id);
                    } else {
                        selectedGroupMembers.delete(chat.id);
                    }
                });

                const name = node(
                    'span',
                    '',
                    chat.name
                );

                label.append(checkbox, name);
                memberList.append(label);
            });
    }

    $('createGroupBtn').addEventListener('click', () => {
        selectedGroupMembers.clear();

        groupForm.reset();
        memberSearch.value = '';

        renderGroupMembers();

        groupOverlay.hidden = false;
        groupName.focus();
    });

    $('closeGroupBtn').addEventListener('click', () => {
        groupOverlay.hidden = true;
    });

    groupOverlay.addEventListener('click', event => {
        if (event.target === groupOverlay) {
            groupOverlay.hidden = true;
        }
    });

    memberSearch.addEventListener('input', () => {
        renderGroupMembers();
    });

    groupForm.addEventListener('submit', event => {
        event.preventDefault();

        const name = groupName.value.trim();

        const ids = [...selectedGroupMembers];

        const members = chats
            .filter(chat =>
                ids.includes(chat.id) && !chat.isGroup
            )
            .map(chat => chat.name);

        if (!name) {
            return;
        }

        if (!members.length) {
            alert('Pilih minimal satu anggota.');
            return;
        }

        const group = {
            id: Date.now(),
            dbConversationId: null,
            name,
            isGroup: true,
            members: ['Kamu', ...members],
            unread: 0,
            messages: [
                {
                    id: Date.now() + 1,
                    from: 'system',
                    type: 'system',
                    text: `Grup ${name} berhasil dibuat!`,
                    at: new Date().toISOString()
                }
            ]
        };

        chats.push(group);

        groupOverlay.hidden = true;

        save();
        selectChat(group.id);
    });

    /*
    |--------------------------------------------------------------------------
    | MENU PERCAKAPAN
    |--------------------------------------------------------------------------
    */

    const roomDropdown = $('roomDropdown');
    const roomMenuButton = $('roomMenu');
    const deletePersonalChat = $('deletePersonalChat');

    const deleteConfirmOverlay = $('deleteConfirmOverlay');
    const deleteConfirmText = $('deleteConfirmText');
    const cancelDeleteBtn = $('cancelDeleteBtn');
    const confirmDeleteBtn = $('confirmDeleteBtn');

    roomMenuButton.addEventListener('click', event => {
        event.stopPropagation();

        const chat = selected();

        if (!chat) {
            return;
        }

        if (chat.isGroup) {
            roomDropdown.hidden = true;

            roomMenuButton.setAttribute(
                'aria-expanded',
                'false'
            );

            alert(
                'Grup: ' + chat.name +
                '\n\nAnggota:\n' +
                (chat.members || []).join('\n')
            );

            return;
        }

        roomDropdown.hidden = !roomDropdown.hidden;

        roomMenuButton.setAttribute(
            'aria-expanded',
            String(!roomDropdown.hidden)
        );
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('.room-menu-wrapper')) {
            roomDropdown.hidden = true;

            roomMenuButton.setAttribute(
                'aria-expanded',
                'false'
            );
        }
    });

    /*
    |--------------------------------------------------------------------------
    | HAPUS PERCAKAPAN PERSONAL
    |--------------------------------------------------------------------------
    */

    deletePersonalChat.addEventListener('click', () => {
        const chat = selected();

        if (!chat || chat.isGroup) {
            return;
        }

        pendingDeleteChatId = chat.id;

        deleteConfirmText.textContent =
            'Yakin ingin menghapus percakapan dengan ' +
            chat.name +
            '? Semua pesan pada percakapan ini akan dihapus dari browser.';

        roomDropdown.hidden = true;

        roomMenuButton.setAttribute(
            'aria-expanded',
            'false'
        );

        deleteConfirmOverlay.hidden = false;
    });

    cancelDeleteBtn.addEventListener('click', () => {
        pendingDeleteChatId = null;
        deleteConfirmOverlay.hidden = true;
    });

    deleteConfirmOverlay.addEventListener('click', event => {
        if (event.target === deleteConfirmOverlay) {
            pendingDeleteChatId = null;
            deleteConfirmOverlay.hidden = true;
        }
    });

    confirmDeleteBtn.addEventListener('click', () => {
        if (pendingDeleteChatId === null) {
            return;
        }

        chats = chats.filter(
            chat => chat.id !== pendingDeleteChatId
        );

        selectedId = chats[0]?.id ?? null;

        pendingDeleteChatId = null;
        deleteConfirmOverlay.hidden = true;

        save();
        renderList();
        renderRoom();
    });

    /*
    |--------------------------------------------------------------------------
    | UPLOAD FILE KE SUPABASE STORAGE
    |--------------------------------------------------------------------------
    */

    attachButton.addEventListener('click', () => {
        if (!selected()) {
            alert('Pilih percakapan terlebih dahulu.');
            return;
        }

        if (isUploading) {
            return;
        }

        fileInput.click();
    });

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        const chat = selected();

        if (!file) {
            return;
        }

        if (!chat) {
            alert('Pilih percakapan terlebih dahulu.');
            fileInput.value = '';
            return;
        }

        /*
        | WAJIB MEMAKAI ID DATABASE ASLI
        */

        const conversationId = getServerConversationId(chat);

        if (!conversationId) {
            alert(
                'Percakapan ini masih menggunakan data demo lokal.\n\n' +
                'Upload ke Supabase belum dapat dilakukan sampai ' +
                'percakapan terhubung ke database.'
            );

            fileInput.value = '';
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            alert('Ukuran file maksimal 10 MB.');
            fileInput.value = '';
            return;
        }

        const extension = file.name
            .split('.')
            .pop()
            ?.toLowerCase();

        const allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp',
            'pdf'
        ];

        if (
            !allowedExtensions.includes(extension) ||
            !ALLOWED_MIME_TYPES.includes(file.type)
        ) {
            alert(
                'Format file tidak didukung.\n' +
                'Gunakan JPG, JPEG, PNG, WEBP, atau PDF.'
            );

            fileInput.value = '';
            return;
        }

        const formData = new FormData();

        formData.append(
            'conversation_id',
            String(conversationId)
        );

        formData.append('file', file);

        isUploading = true;

        attachButton.disabled = true;
        attachButton.title = 'Mengunggah file...';

        showUploadStatus(
            `Mengunggah ${file.name}...`
        );

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            let result;

            try {
                result = await response.json();
            } catch (error) {
                throw new Error(
                    'Server tidak mengembalikan respons JSON.'
                );
            }

            if (!response.ok || !result.success) {
                if (response.status === 401) {
                    throw new Error(
                        'Sesi login tidak ditemukan. Silakan login kembali.'
                    );
                }

                if (response.status === 403) {
                    throw new Error(
                        'Kamu tidak memiliki akses ke percakapan ini.'
                    );
                }

                if (response.status === 419) {
                    throw new Error(
                        'Sesi halaman kedaluwarsa. Refresh halaman.'
                    );
                }

                if (response.status === 422) {
                    const errors = result.errors || {};

                    const firstError = Object.values(errors)
                        .flat()
                        .find(Boolean);

                    throw new Error(
                        firstError ||
                        result.message ||
                        'Data upload tidak valid.'
                    );
                }

                throw new Error(
                    result.message || 'Gagal mengunggah file.'
                );
            }

            /*
            | RESPONS CHAT ATTACHMENT CONTROLLER
            |
            | {
            |   success: true,
            |   data: {
            |       message_id: 1,
            |       attachment_id: 1
            |   }
            | }
            */

            const messageId = result.data?.message_id;
            const attachmentId = result.data?.attachment_id;

            if (!messageId || !attachmentId) {
                throw new Error(
                    'Upload berhasil, tetapi ID lampiran tidak ditemukan.'
                );
            }

            const messageType = file.type.startsWith('image/')
                ? 'image'
                : 'file';

            chat.messages.push({
                id: messageId,
                from: 'me',
                type: messageType,
                text: '',
                fileName: file.name,
                fileSize: file.size,
                attachmentId: attachmentId,
                fileUrl: getDownloadUrl(attachmentId),
                at: new Date().toISOString()
            });

            save();
            renderList();
            renderRoom();

            showUploadStatus(
                'File berhasil dikirim.'
            );

        } catch (error) {
            console.error('Upload file gagal:', error);

            showUploadStatus(
                error.message || 'Gagal mengunggah file.',
                true
            );

            alert(
                error.message || 'Gagal mengunggah file.'
            );

        } finally {
            isUploading = false;

            attachButton.disabled = false;
            attachButton.title = 'Kirim file';

            fileInput.value = '';
        }
    });

    /*
    |--------------------------------------------------------------------------
    | KIRIM PESAN TEKS
    |--------------------------------------------------------------------------
    |
    | Pesan teks masih disimpan ke localStorage.
    | Integrasi database pesan teks dilakukan setelah
    | endpoint percakapan dan pesan tersedia.
    |
    */

    
form.addEventListener('submit', async event => {
    event.preventDefault();

    const message = input.value.trim();
    const chat = selected();

    if (!chat || !message) {
        return;
    }

    const conversationId = getServerConversationId(chat);

    if (!conversationId) {
        alert(
            'Percakapan belum terhubung ke database.'
        );
        return;
    }

    const sendButton = form.querySelector(
        'button[type="submit"]'
    );

    if (sendButton) {
        sendButton.disabled = true;
    }

    try {
        const url = messagesUrlTemplate.replace(
            '__ID__',
            encodeURIComponent(String(conversationId))
        );

        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                message: message
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message ||
                'Gagal mengirim pesan.'
            );
        }

        input.value = '';

        // Ambil kembali pesan dari database
        await loadMessages(conversationId);

    } catch (error) {
        console.error('Gagal mengirim pesan:', error);

        alert(
            error.message ||
            'Terjadi kesalahan saat mengirim pesan.'
        );

    } finally {
        if (sendButton) {
            sendButton.disabled = false;
        }
    }
});


    /*
    |--------------------------------------------------------------------------
    | INITIAL RENDER
    |--------------------------------------------------------------------------
    */

    renderList();
renderRoom();
loadConversations();

})();
