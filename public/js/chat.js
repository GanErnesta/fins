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

    
/* ==========================================
   MIGRASI DATA GRUP LAMA - MULTI ADMIN
========================================== */

// Menyesuaikan grup lama tanpa menghapus data
let groupDataUpdated = false;

chats.forEach(chat => {
    if (!chat.isGroup) return;

    // Pastikan daftar anggota berbentuk array
    if (!Array.isArray(chat.members)) {
        chat.members = ['Kamu'];
        groupDataUpdated = true;
    }

    // Grup lama belum memiliki properti admins
    if (!Array.isArray(chat.admins)) {
        chat.admins = chat.members.includes('Kamu')
            ? ['Kamu']
            : (chat.members.length ? [chat.members[0]] : []);

        groupDataUpdated = true;
    }

    // Pastikan Admin benar-benar anggota grup
    const validAdmins = [
        ...new Set(
            chat.admins.filter(
                name => chat.members.includes(name)
            )
        )
    ];

    // Jika grup memiliki anggota, harus ada minimal satu Admin
    if (validAdmins.length === 0 && chat.members.length > 0) {
        validAdmins.push(chat.members[0]);
    }

    if (JSON.stringify(chat.admins) !== JSON.stringify(validAdmins)) {
        chat.admins = validAdmins;
        groupDataUpdated = true;
    }
});

// Simpan hanya jika ada data grup yang diperbarui
if (groupDataUpdated) {
    try {
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify(chats)
        );
    } catch (error) {
        console.error('Gagal menyimpan migrasi Admin grup:', error);
    }
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
    admins: ['Kamu'],
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

    // Pesan tersembunyi hanya untuk browser ini.
    const visibleMessages = c => c.messages.filter(m => !m.hiddenForMe);
    const messagePreview = c => {
        const msg = visibleMessages(c).at(-1);
        if (!msg) return 'Mulai percakapan';
        if (msg.deletedForAll) return 'Pesan ini telah dihapus';
        return msg.type === 'file' ? '📎 ' + (msg.fileName || 'Lampiran') : (msg.text || 'Mulai percakapan');
    };

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
                visibleMessages(c).some(m =>
                   !m.deletedForAll && ((m.text || '').toLocaleLowerCase('id').includes(term) ||
                   (m.fileName || '').toLocaleLowerCase('id').includes(term))
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
                    messagePreview(c)
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
        if (!visibleMessages(c).length) {
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
            if (msg.hiddenForMe) continue;
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

    area.append(node('div', 'system-announcement', msg.text || 'Pengumuman grup'));
    continue;
}

const bubble = node(
    'div',
    'message ' +
    (msg.from === 'me' ? 'mine' : 'theirs')
);

            if (msg.deletedForAll) {
                bubble.classList.add('deleted-message');
                bubble.append(node('span', 'message-text', 'Pesan ini telah dihapus'));
            } else if (msg.type === 'file' && msg.fileUrl) {

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

            const time = node('span', 'message-time', formatTime(msg.at));
            if (msg.editedAt && !msg.deletedForAll) {
                time.prepend(node('span', 'message-edited-label', '(diedit) '));
            }
            bubble.append(time);

            const row = node('div', 'message-row ' + (msg.from === 'me' ? 'mine' : 'theirs'));
            row.append(bubble);

            // Pesan sistem dan pesan yang sudah ditarik tidak mempunyai menu.
            if (!msg.deletedForAll) {
                const actions = node('div', 'message-actions');
                const toggle = node('button', 'message-actions-toggle', '⋮');
                toggle.type = 'button';
                toggle.setAttribute('aria-label', 'Opsi pesan');
                toggle.setAttribute('aria-expanded', 'false');

                const menu = node('div', 'message-actions-menu');
                menu.hidden = true;
                const option = (label, callback) => {
                    const button = node('button', 'message-action-item', label);
                    button.type = 'button';
                    button.addEventListener('click', event => {
                        event.stopPropagation();
                        menu.hidden = true;
                        callback(c.id, msg.id);
                    });
                    menu.append(button);
                };

                if (msg.from === 'me' && msg.type !== 'file') {
                    option('✎ Edit pesan', openEditMessage);
                }
                option('Hapus untuk saya', (chatId, msgId) => openDeleteMessage(chatId, msgId, 'me'));
                if (msg.from === 'me') {
                    option('Hapus untuk semua orang', (chatId, msgId) => openDeleteMessage(chatId, msgId, 'all'));
                }
                toggle.addEventListener('click', event => {
                    event.stopPropagation();
                    document.querySelectorAll('.message-actions-menu').forEach(other => {
                        if (other !== menu) other.hidden = true;
                    });
                    menu.hidden = !menu.hidden;
                    toggle.setAttribute('aria-expanded', String(!menu.hidden));
                });
                actions.append(toggle, menu);
                row.append(actions);
            }
            area.append(row);
        }

        // AUTO SCROLL TO LATEST MESSAGE
        requestAnimationFrame(() => {
            area.scrollTop = area.scrollHeight;
        });
    }

    // EDIT / HAPUS PESAN - DEMO LOCALSTORAGE
    const editMessageOverlay = $('editMessageOverlay');
    const editMessageInput = $('editMessageInput');
    const editMessageForm = $('editMessageForm');
    const deleteMessageOverlay = $('deleteMessageOverlay');
    const deleteMessageText = $('deleteMessageText');
    let editingMessage = null;
    let deletingMessage = null;

    function findMessage(chatId, messageId) {
        const conversation = chats.find(c => c.id === chatId);
        return { conversation, message: conversation?.messages.find(m => m.id === messageId) };
    }

    function openEditMessage(chatId, messageId) {
        const { message } = findMessage(chatId, messageId);
        if (!message || message.from !== 'me' || message.type === 'file' || message.deletedForAll) return;
        editingMessage = { chatId, messageId };
        editMessageInput.value = message.text || '';
        editMessageOverlay.hidden = false;
        editMessageInput.focus();
    }

    function openDeleteMessage(chatId, messageId, scope) {
        const { message } = findMessage(chatId, messageId);
        if (!message || message.from === 'system' || message.deletedForAll) return;
        if (scope === 'all' && message.from !== 'me') return;
        deletingMessage = { chatId, messageId, scope };
        deleteMessageText.textContent = scope === 'all'
            ? 'Pesan ini akan ditandai sebagai dihapus untuk semua orang pada data demo browser ini.'
            : 'Pesan ini akan disembunyikan dari percakapanmu di browser ini.';
        deleteMessageOverlay.hidden = false;
    }

    function closeEditMessage() {
        editingMessage = null;
        editMessageOverlay.hidden = true;
    }
    function closeDeleteMessage() {
        deletingMessage = null;
        deleteMessageOverlay.hidden = true;
    }

    $('cancelEditMessage').addEventListener('click', closeEditMessage);
    editMessageOverlay.addEventListener('click', e => {
        if (e.target === editMessageOverlay) closeEditMessage();
    });
    editMessageForm.addEventListener('submit', e => {
        e.preventDefault();
        if (!editingMessage) return;
        const text = editMessageInput.value.trim();
        if (!text) return;
        const { message } = findMessage(editingMessage.chatId, editingMessage.messageId);
        if (!message || message.from !== 'me' || message.type === 'file' || message.deletedForAll) return;
        if (text !== message.text) {
            message.text = text;
            message.editedAt = new Date().toISOString();
            save();
        }
        closeEditMessage();
        renderList();
        renderRoom();
    });

    $('cancelDeleteMessage').addEventListener('click', closeDeleteMessage);
    deleteMessageOverlay.addEventListener('click', e => {
        if (e.target === deleteMessageOverlay) closeDeleteMessage();
    });
    $('confirmDeleteMessage').addEventListener('click', () => {
        if (!deletingMessage) return;
        const { chatId, messageId, scope } = deletingMessage;
        const { message } = findMessage(chatId, messageId);
        if (!message || (scope === 'all' && message.from !== 'me')) return;
        if (scope === 'me') {
            message.hiddenForMe = true;
        } else {
            message.deletedForAll = true;
            message.text = '';
            // Referensi file dipertahankan di data lokal; tombol unduh tidak lagi ditampilkan.
        }
        closeDeleteMessage();
        save();
        renderList();
        renderRoom();
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('.message-actions')) {
            document.querySelectorAll('.message-actions-menu').forEach(menu => menu.hidden = true);
        }
    });

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

    // POPUP INFORMASI GRUP
const groupInfoOverlay = $('groupInfoOverlay');
const groupInfoName = $('groupInfoName');
const groupInfoCount = $('groupInfoCount');
const groupInfoMemberList = $('groupInfoMemberList');
const closeGroupInfoBtn = $('closeGroupInfoBtn');
const closeGroupInfoFooter = $('closeGroupInfoFooter');

// Menampilkan informasi grup yang sedang dipilih
function openGroupInfo() {
    const c = selected();

    if (!c || !c.isGroup) return;

    groupInfoName.textContent = c.name;

    // Avatar otomatis menggunakan inisial nama grup
$('groupInfoAvatar').textContent = initials(c.name);

    const members = Array.isArray(c.members) ? c.members : [];

    groupInfoCount.textContent = `${members.length} Anggota`;

    groupInfoMemberList.replaceChildren();

    members.forEach((member, index) => {
        const item = document.createElement('div');
        item.className = 'group-info-member-item';

        const avatar = document.createElement('div');
        avatar.className = 'group-info-member-avatar';
        avatar.textContent = initials(member);

        const name = document.createElement('span');
        name.className = 'group-info-member-name';
        name.textContent = member;

        

const role = document.createElement('span');
role.className = 'group-info-member-role';

// Status Admin berdasarkan data admins
const isAdmin = (c.admins || []).includes(member);
const currentUserIsAdmin = (c.admins || []).includes('Kamu');

role.textContent = isAdmin ? 'Admin' : 'Anggota';

item.append(avatar, name, role);

// Tombol pengelolaan hanya muncul untuk Admin
if (currentUserIsAdmin && member !== 'Kamu') {

    // Jadikan Admin untuk anggota biasa
    if (!isAdmin) {
        const makeAdminBtn = document.createElement('button');

        makeAdminBtn.type = 'button';
        makeAdminBtn.className = 'group-make-admin-btn';
        makeAdminBtn.textContent = 'Jadikan Admin';

        makeAdminBtn.addEventListener('click', () => {
            makeGroupAdmin(c.id, member);
        });

        item.append(makeAdminBtn);
    }

    // Tombol Keluarkan tetap tersedia
    // untuk anggota biasa.
    if (!isAdmin) {
        const removeBtn = document.createElement('button');

        removeBtn.type = 'button';
        removeBtn.className = 'group-remove-member-btn';
        removeBtn.textContent = 'Keluarkan';

        removeBtn.addEventListener('click', () => {
            openRemoveMember(c.id, member);
        });

        item.append(removeBtn);
    }
}

groupInfoMemberList.append(item);

    });

    groupInfoOverlay.hidden = false;
}

// Menutup popup
function closeGroupInfo() {
    groupInfoOverlay.hidden = true;
}



/* =========================================
   POPUP JADIKAN ADMIN - FINS UB
========================================= */

const makeAdminOverlay = $('makeAdminOverlay');
const makeAdminText = $('makeAdminText');
const cancelMakeAdminBtn = $('cancelMakeAdminBtn');
const confirmMakeAdminBtn = $('confirmMakeAdminBtn');

let pendingMakeAdmin = null;

// Membuka popup Jadikan Admin
function makeGroupAdmin(groupId, memberName) {
    const group = chats.find(c => c.id === groupId);

    if (!group || !group.isGroup) return;

    // Hanya Admin yang boleh menunjuk Admin baru
    if (!(group.admins || []).includes('Kamu')) return;

    // Pastikan target adalah anggota, belum menjadi Admin
    if (!(group.members || []).includes(memberName)) return;
    if ((group.admins || []).includes(memberName)) return;

    pendingMakeAdmin = { groupId, memberName };

    makeAdminText.textContent =
        `Yakin ingin menjadikan ${memberName} sebagai Admin grup ${group.name}?`;

    groupInfoOverlay.hidden = true;
    makeAdminOverlay.hidden = false;
}

// Menutup popup dan kembali ke Informasi Grup
function closeMakeAdmin() {
    makeAdminOverlay.hidden = true;
    pendingMakeAdmin = null;

    if (selected()?.isGroup) {
        openGroupInfo();
    }
}

// Tombol Batal
cancelMakeAdminBtn.addEventListener('click', closeMakeAdmin);

// Klik di luar popup
makeAdminOverlay.addEventListener('click', event => {
    if (event.target === makeAdminOverlay) {
        closeMakeAdmin();
    }
});

// Konfirmasi Jadikan Admin
confirmMakeAdminBtn.addEventListener('click', () => {
    if (!pendingMakeAdmin) return;

    const { groupId, memberName } = pendingMakeAdmin;
    const group = chats.find(c => c.id === groupId);

    if (
        !group ||
        !group.isGroup ||
        !(group.admins || []).includes('Kamu') ||
        !(group.members || []).includes(memberName) ||
        (group.admins || []).includes(memberName)
    ) {
        closeMakeAdmin();
        return;
    }

    // Tambah Admin baru tanpa menghapus Admin sebelumnya
    group.admins.push(memberName);

    // Pengumuman sistem
    group.messages.push({
        id: Date.now(),
        from: 'system',
        type: 'system',
        text: `${memberName} sekarang menjadi Admin grup.`,
        at: new Date().toISOString()
    });

    save();
    renderList();
    renderRoom();
    closeMakeAdmin();
});


// POPUP PERINGATAN ADMIN
const adminWarningOverlay = $('adminWarningOverlay');
const closeAdminWarningBtn = $('closeAdminWarningBtn');

function showAdminWarning() {
    adminWarningOverlay.hidden = false;
    closeAdminWarningBtn.focus();
}

function closeAdminWarning() {
    adminWarningOverlay.hidden = true;
}

closeAdminWarningBtn.addEventListener('click', closeAdminWarning);

adminWarningOverlay.addEventListener('click', event => {
    if (event.target === adminWarningOverlay) {
        closeAdminWarning();
    }
});


/* =========================================
   FITUR KELUAR DARI GRUP - FINS UB
========================================= */

const leaveGroupBtn = $('leaveGroupBtn');
const leaveGroupOverlay = $('leaveGroupOverlay');
const leaveGroupText = $('leaveGroupText');
const cancelLeaveGroupBtn = $('cancelLeaveGroupBtn');
const confirmLeaveGroupBtn = $('confirmLeaveGroupBtn');

let pendingLeaveGroupId = null;

// Membuka popup konfirmasi Keluar Grup
leaveGroupBtn.addEventListener('click', () => {
    const group = selected();

    if (!group || !group.isGroup) return;

    const members = Array.isArray(group.members)
        ? group.members
        : [];

    // Pada sistem demo, Kamu merupakan Admin grup
    // dan belum tersedia pemindahan Admin.
    // Periksa jumlah Admin yang masih aktif
const admins = (group.admins || []).filter(
    admin => members.includes(admin)
);

const currentUserIsAdmin = admins.includes('Kamu');

// Admin terakhir tidak boleh keluar
if (currentUserIsAdmin && admins.length <= 1) {
    groupInfoOverlay.hidden = true;
    showAdminWarning();
    return;
}
    pendingLeaveGroupId = group.id;

    leaveGroupText.textContent =
        `Yakin ingin keluar dari grup ${group.name}?`;

    groupInfoOverlay.hidden = true;
    leaveGroupOverlay.hidden = false;
});

// Menutup popup konfirmasi
function closeLeaveGroup() {
    leaveGroupOverlay.hidden = true;
    pendingLeaveGroupId = null;

    const group = selected();

    if (group && group.isGroup) {
        openGroupInfo();
    }
}

// Tombol Batal
cancelLeaveGroupBtn.addEventListener(
    'click',
    closeLeaveGroup
);

// Klik area luar popup untuk menutup
leaveGroupOverlay.addEventListener('click', event => {
    if (event.target === leaveGroupOverlay) {
        closeLeaveGroup();
    }
});

// Konfirmasi Keluar Grup
confirmLeaveGroupBtn.addEventListener('click', () => {
    if (pendingLeaveGroupId === null) return;

    const group = chats.find(
        c => c.id === pendingLeaveGroupId
    );

    if (!group || !group.isGroup) {
        closeLeaveGroup();
        return;
    }

    const members = Array.isArray(group.members)
        ? group.members
        : [];

    // Validasi ulang sebelum mengeluarkan pengguna
    // Validasi ulang sebelum keluar grup
const admins = (group.admins || []).filter(
    admin => members.includes(admin)
);

const currentUserIsAdmin = admins.includes('Kamu');

if (currentUserIsAdmin && admins.length <= 1) {
    leaveGroupOverlay.hidden = true;
    pendingLeaveGroupId = null;

    showAdminWarning();
    return;
}
    // Hilangkan grup dari daftar percakapan lokal
    chats = chats.filter(c => c.id !== group.id);

    // Pilih percakapan lain yang masih tersedia
    selectedId = chats[0]?.id ?? null;

    // Tutup kedua popup
    leaveGroupOverlay.hidden = true;
    groupInfoOverlay.hidden = true;
    pendingLeaveGroupId = null;

    // Simpan perubahan ke localStorage
    save();

    // Perbarui tampilan
    renderList();
    renderRoom();

    // Kembali ke daftar percakapan pada mobile
    messenger.classList.remove('room-open');
});


/* =========================================
   FITUR KELUARKAN ANGGOTA GRUP
========================================= */

const removeMemberOverlay = $('removeMemberOverlay');
const removeMemberText = $('removeMemberText');
const cancelRemoveMemberBtn = $('cancelRemoveMemberBtn');
const confirmRemoveMemberBtn = $('confirmRemoveMemberBtn');

let pendingRemoveMember = null;

// Membuka popup konfirmasi
function openRemoveMember(groupId, memberName) {
    const group = chats.find(c => c.id === groupId);

    if (!group || !group.isGroup) return;

    // Admin demo adalah anggota pertama
    if (group.members?.[0] === memberName) return;

    if (!group.members?.includes(memberName)) return;

    pendingRemoveMember = {
        groupId,
        memberName
    };

    removeMemberText.textContent =
        `Yakin ingin mengeluarkan ${memberName} dari grup ${group.name}?`;

    groupInfoOverlay.hidden = true;
    removeMemberOverlay.hidden = false;
}

// Menutup popup dan kembali ke Informasi Grup
function closeRemoveMember() {
    removeMemberOverlay.hidden = true;
    pendingRemoveMember = null;

    if (selected()?.isGroup) {
        openGroupInfo();
    }
}

// Tombol batal
cancelRemoveMemberBtn.addEventListener(
    'click',
    closeRemoveMember
);

// Klik di luar popup
removeMemberOverlay.addEventListener('click', event => {
    if (event.target === removeMemberOverlay) {
        closeRemoveMember();
    }
});

// Konfirmasi keluarkan anggota
confirmRemoveMemberBtn.addEventListener('click', () => {
    if (!pendingRemoveMember) return;

    const { groupId, memberName } = pendingRemoveMember;

    const group = chats.find(c => c.id === groupId);

    if (!group || !group.isGroup) {
        closeRemoveMember();
        return;
    }

    // Jangan sampai admin dikeluarkan
    if (
        group.members?.[0] === memberName ||
        !group.members?.includes(memberName)
    ) {
        closeRemoveMember();
        return;
    }

    // Hapus anggota dari daftar
    group.members = group.members.filter(
        name => name !== memberName
    );

    // Tambahkan pengumuman sistem
    group.messages.push({
        id: Date.now(),
        from: 'system',
        type: 'system',
        text: `${memberName} telah dikeluarkan dari grup.`,
        at: new Date().toISOString()
    });

    // Simpan perubahan
    save();

    // Perbarui tampilan
    renderList();
    renderRoom();

    // Tutup konfirmasi dan perbarui popup informasi
    closeRemoveMember();
});


/* ==========================================
   FITUR TAMBAH ANGGOTA GRUP - FINS UB
========================================== */

const addGroupMemberOverlay = $('addGroupMemberOverlay');
const addGroupMemberForm = $('addGroupMemberForm');
const addGroupMemberSearch = $('addGroupMemberSearch');
const addGroupMemberList = $('addGroupMemberList');

const openAddGroupMemberBtn = $('openAddGroupMemberBtn');
const closeAddGroupMemberBtn = $('closeAddGroupMemberBtn');
const cancelAddGroupMemberBtn = $('cancelAddGroupMemberBtn');

// ID grup yang sedang dikelola
let activeGroupId = null;

// ID kontak yang dipilih
const selectedNewMembers = new Set();

// Menampilkan daftar kontak yang bisa ditambahkan
function renderAvailableGroupMembers() {
    const group = chats.find(c => c.id === activeGroupId);

    if (!group || !group.isGroup) return;

    const keyword = addGroupMemberSearch.value
        .trim()
        .toLowerCase();

    addGroupMemberList.replaceChildren();

    // Kontak yang sudah menjadi anggota tidak ditampilkan
    const availableContacts = chats.filter(c =>
        !c.isGroup &&
        !(group.members || []).includes(c.name) &&
        c.name.toLowerCase().includes(keyword)
    );

    if (availableContacts.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'add-group-member-empty';
        empty.textContent = 'Tidak ada kontak yang tersedia.';

        addGroupMemberList.append(empty);
        return;
    }

    availableContacts.forEach(contact => {
        const item = document.createElement('label');
        item.className = 'add-group-member-item';

        const avatar = document.createElement('span');
        avatar.className = 'add-group-member-avatar';
        avatar.textContent = initials(contact.name);

        const name = document.createElement('span');
        name.className = 'add-group-member-name';
        name.textContent = contact.name;

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = String(contact.id);

        checkbox.checked = selectedNewMembers.has(contact.id);

        checkbox.addEventListener('change', () => {
            if (checkbox.checked) {
                selectedNewMembers.add(contact.id);
            } else {
                selectedNewMembers.delete(contact.id);
            }
        });

        item.append(avatar, name, checkbox);
        addGroupMemberList.append(item);
    });
}

// Membuka popup Tambah Anggota
openAddGroupMemberBtn.addEventListener('click', () => {
    const group = selected();

    if (!group || !group.isGroup) return;

    activeGroupId = group.id;
    selectedNewMembers.clear();

    addGroupMemberForm.reset();
    addGroupMemberSearch.value = '';

    renderAvailableGroupMembers();

    groupInfoOverlay.hidden = true;
    addGroupMemberOverlay.hidden = false;

    addGroupMemberSearch.focus();
});

// Menutup popup Tambah Anggota
function closeAddGroupMember() {
    addGroupMemberOverlay.hidden = true;
    activeGroupId = null;
    selectedNewMembers.clear();

    // Kembali ke popup Informasi Grup
    const group = selected();

    if (group && group.isGroup) {
        openGroupInfo();
    }
}

closeAddGroupMemberBtn.addEventListener(
    'click',
    closeAddGroupMember
);

cancelAddGroupMemberBtn.addEventListener(
    'click',
    closeAddGroupMember
);

// Tutup ketika klik bagian luar popup
addGroupMemberOverlay.addEventListener('click', event => {
    if (event.target === addGroupMemberOverlay) {
        closeAddGroupMember();
    }
});

// Pencarian kontak
addGroupMemberSearch.addEventListener(
    'input',
    renderAvailableGroupMembers
);

// Menyimpan anggota baru
addGroupMemberForm.addEventListener('submit', event => {
    event.preventDefault();

    const group = chats.find(c => c.id === activeGroupId);

    if (!group || !group.isGroup) return;

    if (selectedNewMembers.size === 0) {
        alert('Pilih minimal satu anggota.');
        return;
    }

    const newMembers = chats
        .filter(c =>
            !c.isGroup &&
            selectedNewMembers.has(c.id) &&
            !(group.members || []).includes(c.name)
        )
        .map(c => c.name);

    if (newMembers.length === 0) {
        alert('Anggota yang dipilih sudah ada di grup.');
        return;
    }

    // Tambahkan anggota tanpa menghapus anggota lama
    group.members = [
        ...(group.members || []),
        ...newMembers
    ];

    // Tambahkan pengumuman grup
    group.messages.push({
        id: Date.now(),
        from: 'system',
        type: 'system',
        text: newMembers.join(', ') +
              ' telah ditambahkan ke grup.',
        at: new Date().toISOString()
    });

    save();
    renderList();
    renderRoom();

    closeAddGroupMember();
});


closeGroupInfoBtn.addEventListener('click', closeGroupInfo);
closeGroupInfoFooter.addEventListener('click', closeGroupInfo);

groupInfoOverlay.addEventListener('click', event => {
    if (event.target === groupInfoOverlay) {
        closeGroupInfo();
    }
});

    let pendingDeleteChatId = null;

    roomMenuButton.addEventListener('click', (event) => {
        event.stopPropagation();

        const c = selected();
        if (!c) return;

        if (c.isGroup) {
    roomDropdown.hidden = true;
    roomMenuButton.setAttribute('aria-expanded', 'false');

    openGroupInfo();
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
