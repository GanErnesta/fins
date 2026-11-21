
(() => {
    'use strict';

    // STORAGE
    const STORAGE_KEY = 'fins_chat_demo_v1';

    // INITIAL CONVERSATIONS
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

    // DOM ELEMENTS
    const $ = (id) => document.getElementById(id);

    const list = $('conversationList');
    const area = $('messagesArea');
    const form = $('messageForm');
    const search = $('chatSearch');
    const input = $('messageInput');
    const sort = $('sortChats');

    const messenger = document.querySelector('.messenger');

    // LOAD CHAT DATA
    let chats;

    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));

        chats = Array.isArray(saved)
            ? saved
            : structuredClone(initialConversations);

    } catch (_) {
        chats = structuredClone(initialConversations);
    }

    // INITIAL STATE
    let selectedId = chats.some(c => c.id === 2)
        ? 2
        : (chats[0]?.id ?? null);

    let filter = 'all';
    

   // FITUR GRUP DEMO
const groupOverlay = $('groupOverlay');
const groupForm = $('createGroupForm');
const groupName = $('groupName');
const memberSearch = $('groupMemberSearch');
const memberList = $('groupMemberList');

// Menyimpan anggota yang dipilih
const selectedGroupMembers = new Set();

// Menampilkan daftar anggota
function renderGroupMembers() {
    const keyword = memberSearch.value.toLowerCase();

    memberList.replaceChildren();

    chats.filter(c => !c.isGroup)
        .filter(c => c.name.toLowerCase().includes(keyword))
        .forEach(c => {
            const label = document.createElement('label');
            label.className = 'group-member-item';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = String(c.id);
            checkbox.checked = selectedGroupMembers.has(c.id);

            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    selectedGroupMembers.add(c.id);
                } else {
                    selectedGroupMembers.delete(c.id);
                }
            });

            const name = document.createElement('span');
            name.textContent = c.name;

            label.append(checkbox, name);
            memberList.append(label);
        });
}

    $('createGroupBtn').addEventListener('click', () => {
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
        // Simpan pilihan saat pengguna mencari anggota lain
        renderGroupMembers();
    });

    groupForm.addEventListener('submit', event => {
        event.preventDefault();

        const name = groupName.value.trim();

        const ids = [...memberList.querySelectorAll('input:checked')]
            .map(el => Number(el.value));

        const members = chats
            .filter(c => ids.includes(c.id) && !c.isGroup)
            .map(c => c.name);

        if (!name) return;

        if (members.length === 0) {
            alert('Pilih minimal satu anggota.');
            return;
        }

        const group = {
            id: Date.now(),
            name: name,
            isGroup: true,
            members: ['Kamu', ...members],
            unread: 0,
            messages: [{
    id: Date.now() + 1,
    from: 'system',
    type: 'system',
    text: 'Grup ' + name + ' berhasil dibuat!',
    at: new Date().toISOString()
}]
        };

        chats.push(group);
        groupOverlay.hidden = true;

        save();
        selectChat(group.id);
    });

    // SAVE DATA
    const save = () => {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(chats)
            );
        } catch (_) {}
    };

    // HELPER FUNCTIONS
    const initials = (name) => {
        return String(name || '?')
            .trim()
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map(w => Array.from(w)[0].toUpperCase())
            .join('') || '?';
    };

    const lastAt = c => {
        return c.messages.length
            ? new Date(c.messages[c.messages.length - 1].at).getTime()
            : 0;
    };

    const selected = () => {
        return chats.find(c => c.id === selectedId);
    };

    const formatTime = iso => {
        return new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(iso));
    };

    const formatDay = iso => {
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(new Date(iso));
    };

    function node(tag, className, text) {
        const el = document.createElement(tag);

        if (className) {
            el.className = className;
        }

        if (text !== undefined) {
            el.textContent = String(text);
        }

        return el;
    }

    // RENDER CONVERSATION LIST
    function renderList() {
        const term = search.value
            .trim()
            .toLocaleLowerCase('id');

        let visible = chats.filter(c =>
            (filter === 'all' || c.unread > 0) &&
            (
                !term ||
                c.name.toLocaleLowerCase('id').includes(term) ||
                c.messages.some(m =>
                   (m.text || '').toLocaleLowerCase('id').includes(term) ||
                (m.fileName || '').toLocaleLowerCase('id').includes(term)   
                )
            )
        );

        // SORT CONVERSATIONS
        visible.sort((a, b) => {
            if (sort.value === 'az') {
                return a.name.localeCompare(b.name, 'id');
            }

            if (sort.value === 'za') {
                return b.name.localeCompare(a.name, 'id');
            }

            return sort.value === 'oldest'
                ? lastAt(a) - lastAt(b)
                : lastAt(b) - lastAt(a);
        });

        list.replaceChildren();

        // DISPLAY EACH CONVERSATION
        for (const c of visible) {
            const item = node(
                'button',
                'conversation-item' +
                (c.id === selectedId ? ' active' : '')
            );

            item.type = 'button';

            item.setAttribute(
                'aria-pressed',
                String(c.id === selectedId)
            );

            // AVATAR
            item.append(
                node('span', 'avatar', initials(c.name))
            );

            // CONTACT INFORMATION
            const meta = node('span', 'contact-meta');

            meta.append(
                node('span', 'contact-name', c.name)
            );

            meta.append(
                node(
                    'span',
                    'contact-preview',
                    c.messages.at(-1)?.type === 'file'
                    ? '📎 ' + c.messages.at(-1).fileName
                    : (c.messages.at(-1)?.text || 'Mulai percakapan')
                )
            );

            item.append(meta);

            // TIME AND UNREAD BADGE
            const right = node('span', 'contact-right');

            if (c.messages.length) {
                right.append(
                    node(
                        'span',
                        'contact-time',
                        formatTime(c.messages.at(-1).at)
                    )
                );
            }

            if (c.unread > 0) {
                right.append(
                    node('span', 'unread-badge', c.unread)
                );
            }

            item.append(right);

            // SELECT CONVERSATION
            item.addEventListener('click', () => {
                selectChat(c.id);
            });

            list.append(item);
        }

        // EMPTY SEARCH RESULT
        if (!visible.length) {
            list.append(
                node(
                    'p',
                    'no-results',
                    'Tidak ada percakapan yang cocok.'
                )
            );
        }

        // UPDATE UNREAD COUNT
        $('unreadCount').textContent = chats.reduce(
            (sum, c) => sum + c.unread,
            0
        );

        // UPDATE FILTER BUTTONS
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

    // RENDER CHAT ROOM
    function renderRoom() {
        const c = selected();

        area.replaceChildren();

        // NO CONVERSATION SELECTED
        if (!c) {
    $('selectedName').textContent = 'Pilih percakapan';
    $('selectedAvatar').textContent = '?';
    $('selectedStatus').textContent = '';
    input.disabled = true;

    return;
}

        // UPDATE CHAT HEADER
        input.disabled = false;

        $('selectedName').textContent = c.name;
        $('selectedAvatar').textContent = initials(c.name);
        $('selectedStatus').textContent = c.isGroup ? `${c.members.length} anggota`: 'Percakapan';

        // EMPTY CHAT
        if (!c.messages.length) {
            area.append(
                node(
                    'p',
                    'empty-chat',
                    'Belum ada pesan. Mulai percakapan!'
                )
            );

            return;
        }

        // DISPLAY MESSAGES
        let previousDay = '';

        for (const msg of c.messages) {
            const day = formatDay(msg.at);

            // DAY SEPARATOR
            if (day !== previousDay) {
                area.append(
                    node('div', 'day-separator', day)
                );

                previousDay = day;
            }
            
         // MESSAGE BUBBLE

// PENGUMUMAN SISTEM GRUP
if (
    msg.type === 'system' ||
    msg.from === 'system' ||
    (c.isGroup && msg.text?.startsWith('Grup ') &&
     msg.text?.endsWith(' berhasil dibuat!'))
) {

    area.append(announcement);
    continue;
}

const bubble = node(
    'div',
    'message ' +
    (msg.from === 'me' ? 'mine' : 'theirs')
);

            if (msg.type === 'file' && msg.fileUrl) {

                const attachment = node(
                    'a',
                    'message-attachment'
                );

                attachment.href = msg.fileUrl;
                attachment.setAttribute('download', '');
                attachment.target = '_blank';
                attachment.rel = 'noopener noreferrer';

                const fileIcon = node(
                    'span',
                    'attachment-file-icon',
                    '📄'
                );

                const fileInfo = node(
                    'span',
                    'attachment-file-info'
                );

                const fileName = node(
                    'strong',
                    'attachment-file-name',
                    msg.fileName || 'Lampiran'
                );

                const fileSize = node(
                    'small',
                    'attachment-file-size',
                    msg.fileSize
                        ? (msg.fileSize / 1024).toFixed(1) + ' KB'
                        : 'File'
                );

                fileInfo.append(fileName, fileSize);

                attachment.append(
                    fileIcon,
                    fileInfo,
                    node('span', 'attachment-download', '↓')
                );

                bubble.append(attachment);

            } else {
                bubble.append(
                    node('span', 'message-text', msg.text || '')
                );
            }

            bubble.append(
                node('span', 'message-time', formatTime(msg.at))
            );


            area.append(bubble);
        }

        // AUTO SCROLL TO LATEST MESSAGE
        requestAnimationFrame(() => {
            area.scrollTop = area.scrollHeight;
        });
    }

    // SELECT CHAT
    function selectChat(id) {
        selectedId = id;

        const c = selected();

        if (!c) return;

        c.unread = 0;

        save();
        renderList();
        renderRoom();

        messenger.classList.add('room-open');
    }

    // FILTER EVENTS
    $('filterAll').addEventListener('click', () => {
        filter = 'all';
        renderList();
    });

    $('filterUnread').addEventListener('click', () => {
        filter = 'unread';
        renderList();
    });

    // SORT EVENT
    sort.addEventListener('change', renderList);

    // SEARCH EVENTS
    search.addEventListener('input', renderList);

    $('searchButton').addEventListener('click', () => {
        search.focus();
        renderList();
    });

    // BACK TO CONVERSATION LIST
    $('backToList').addEventListener('click', () => {
        messenger.classList.remove('room-open');
    });

   
    // ROOM MENU - PERSONAL CHAT
    const roomDropdown = $('roomDropdown');
    const roomMenuButton = $('roomMenu');
    const deletePersonalChat = $('deletePersonalChat');
    const deleteConfirmOverlay = $('deleteConfirmOverlay');
    const deleteConfirmText = $('deleteConfirmText');
    const cancelDeleteBtn = $('cancelDeleteBtn');
    const confirmDeleteBtn = $('confirmDeleteBtn');

    let pendingDeleteChatId = null;

    roomMenuButton.addEventListener('click', (event) => {
        event.stopPropagation();

        const c = selected();
        if (!c) return;

        if (c.isGroup) {
            roomDropdown.hidden = true;
            roomMenuButton.setAttribute('aria-expanded', 'false');

            alert(
                'Grup: ' + c.name +
                '\n\nAnggota:\n' +
                (c.members || []).join('\n')
            );
            return;
        }

        roomDropdown.hidden = !roomDropdown.hidden;
        roomMenuButton.setAttribute(
            'aria-expanded',
            String(!roomDropdown.hidden)
        );
    });

    // TUTUP MENU SAAT KLIK DI LUAR
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.room-menu-wrapper')) {
            roomDropdown.hidden = true;
            roomMenuButton.setAttribute('aria-expanded', 'false');
        }
    });

    // HAPUS PERCAKAPAN PERSONAL
deletePersonalChat.addEventListener('click', () => {
    const c = selected();

    if (!c || c.isGroup) return;

    pendingDeleteChatId = c.id;

    deleteConfirmText.textContent =
        'Yakin ingin menghapus percakapan dengan ' +
        c.name +
        '? Semua pesan pada percakapan ini akan dihapus dari browser.';

    roomDropdown.hidden = true;
    roomMenuButton.setAttribute('aria-expanded', 'false');

    deleteConfirmOverlay.hidden = false;
});


// TOMBOL BATAL
cancelDeleteBtn.addEventListener('click', () => {
    pendingDeleteChatId = null;
    deleteConfirmOverlay.hidden = true;
});


// KLIK DI LUAR POPUP UNTUK MENUTUP
deleteConfirmOverlay.addEventListener('click', (event) => {
    if (event.target === deleteConfirmOverlay) {
        pendingDeleteChatId = null;
        deleteConfirmOverlay.hidden = true;
    }
});


// TOMBOL KONFIRMASI HAPUS
confirmDeleteBtn.addEventListener('click', () => {
    if (pendingDeleteChatId === null) return;

    chats = chats.filter(chat => chat.id !== pendingDeleteChatId);

    selectedId = chats[0]?.id ?? null;

    pendingDeleteChatId = null;
    deleteConfirmOverlay.hidden = true;

    save();
    renderList();
    renderRoom();
});


    // ATTACHMENT BUTTON - UPLOAD FILE
    const fileInput = $('fileInput');
    const attachButton = $('attachButton');

    attachButton.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        const c = selected();

        if (!file) return;

        if (!c) {
            alert('Pilih percakapan terlebih dahulu.');
            fileInput.value = '';
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            alert('Maksimal ukuran file adalah 10 MB.');
            fileInput.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]'
        ).content;

        attachButton.disabled = true;
        attachButton.title = 'Mengunggah file...';

        try {
            const response = await fetch('/chat/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Gagal mengunggah file.'
                );
            }

            c.messages.push({
                id: Date.now(),
                from: 'me',
                type: 'file',
                text: '',
                fileName: result.name,
                fileSize: result.size,
                fileUrl: result.url,
                at: new Date().toISOString()
            });

            save();
            renderList();
            renderRoom();

        } catch (error) {
            console.error('Upload gagal:', error);
            alert(error.message || 'Gagal mengunggah file.');
        } finally {
            attachButton.disabled = false;
            attachButton.title = 'Kirim file';
            fileInput.value = '';
        }
    });



    // SEND MESSAGE
    form.addEventListener('submit', event => {
        event.preventDefault();

        const message = input.value.trim();
        const c = selected();

        if (!c || !message) return;

        c.messages.push({
            id: Date.now(),
            from: 'me',
            text: message,
            at: new Date().toISOString()
        });

        input.value = '';

        save();
        renderList();
        renderRoom();

        input.focus();
    });

    // INITIAL RENDER
    // Pemilihan awal tidak langsung menghapus indikator belum dibaca.
    renderList();
    renderRoom();

})();
