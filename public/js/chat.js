(() => {
    'use strict';

    const $ = id => document.getElementById(id);
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

    if (![list, area, form, search, input, sort, messenger, fileInput, attachButton].every(Boolean)) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const conversationsUrl = form.dataset.conversationsUrl || '/chat/conversations';
    const messagesUrlTemplate = form.dataset.messagesUrlTemplate || '/chat/conversations/__ID__/messages';
    const uploadUrl = form.dataset.uploadUrl || '/chat/attachments/upload';
    const downloadUrlTemplate = form.dataset.downloadUrlTemplate || '/chat/attachments/__ID__/download';
    const editUrlTemplate = form.dataset.updateMessageUrlTemplate || '/chat/conversations/__ID__/messages/__MESSAGE_ID__';
    const deleteMeUrlTemplate = form.dataset.deleteMessageMeUrlTemplate || '/chat/conversations/__ID__/messages/__MESSAGE_ID__/me';
    const deleteEveryoneUrlTemplate = form.dataset.deleteMessageEveryoneUrlTemplate || '/chat/conversations/__ID__/messages/__MESSAGE_ID__/everyone';
    const sendButton = form.querySelector('button[type="submit"]');

    const MAX_FILE_SIZE = 10 * 1024 * 1024;
    const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    let chats = [];
    let selectedId = null;
    let filter = 'all';
    let isLoading = true;
    let isSending = false;
    let isUploading = false;
    let isEditing = false;
    let isDeleting = false;
    let isRefreshing = false;
    let conversationError = '';
    let attachmentConversationId = null;
    let pendingDeleteChatId = null;
    let editingMessage = null;
    let deletingMessage = null;
    let realtimeTimer = null;
    let realtimeRunning = false;
    let pendingListSync = false;
    let pendingFullSync = false;
    let initialLoadFinished = false;

    const pendingMessageSync = new Set();
    const messageLoading = new Set();
    const messageErrors = new Map();
    const messageRequests = new Map();
    const messageConversationMap = new Map();
    const hiddenConversationIds = new Set();
    const receivedMessageIds = new Set();
    const deletedMessageIds = new Set();

    try {
        localStorage.removeItem('fins_chat_demo_v1');
    } catch {}

    function validId(value) {
        return Number.isSafeInteger(Number(value)) && Number(value) > 0;
    }

    function findChat(id) {
        return chats.find(chat => chat.id === Number(id));
    }

    function selected() {
        return findChat(selectedId);
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

    function renderAvatar(element, chat) {
        if (!element) return;

        const fallback = initials(chat?.name);
        const rawUrl = chat?.photoUrl;
        element.replaceChildren();
        element.textContent = fallback;
        element.style.overflow = 'hidden';

        if (!rawUrl) return;

        let url;
        try {
            url = new URL(rawUrl, window.location.origin);
            if (!['https:', 'http:'].includes(url.protocol)) return;
        } catch {
            return;
        }

        const image = document.createElement('img');
        image.alt = '';
        image.style.width = '100%';
        image.style.height = '100%';
        image.style.objectFit = 'cover';
        image.style.borderRadius = 'inherit';
        image.style.display = 'block';
        image.addEventListener('error', () => {
            if (image.parentNode === element) element.textContent = fallback;
        }, { once: true });
        element.replaceChildren(image);
        image.src = url.href;
    }

    function renderSelectedProfile() {
        const chat = selected();
        if (!chat) return;
        setText('selectedName', chat.name);
        renderAvatar($('selectedAvatar'), chat);
    }

    function setText(id, text) {
        const element = $(id);

        if (element) {
            element.textContent = String(text);
        }
    }

    function parseDate(value) {
        return new Date(String(value || '').replace(' ', 'T'));
    }

    function formatTime(value) {
        const date = parseDate(value);

        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }

    function formatDay(value) {
        const date = parseDate(value);

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
        return parseDate(
            chat.messages.at(-1)?.at ||
            chat.lastMessageAt ||
            chat.created_at
        ).getTime() || 0;
    }

    function getPreview(message) {
        if (!message) {
            return 'Mulai percakapan';
        }

        if (['file', 'image'].includes(message.type)) {
            return '📎 ' + (message.fileName || 'Lampiran');
        }

        return message.text || 'Mulai percakapan';
    }

    function getMessagesUrl(id) {
        return messagesUrlTemplate
            .replace('__CONVERSATION_ID__', encodeURIComponent(id))
            .replace('__ID__', encodeURIComponent(id));
    }

    function getDownloadUrl(id) {
        return downloadUrlTemplate.replace('__ID__', encodeURIComponent(id));
    }

    function buildMessageUrl(template, messageId, conversationId) {
        return template
            .replace('__CONVERSATION_ID__', encodeURIComponent(conversationId))
            .replace('__ID__', encodeURIComponent(conversationId))
            .replace('__MESSAGE_ID__', encodeURIComponent(messageId));
    }

    function getEditUrl(messageId, conversationId) {
        return buildMessageUrl(editUrlTemplate, messageId, conversationId);
    }

    function getDeleteUrl(messageId, conversationId, scope) {
        return buildMessageUrl(
            scope === 'everyone'
                ? deleteEveryoneUrlTemplate
                : deleteMeUrlTemplate,
            messageId,
            conversationId
        );
    }

    function getServerConversationId(chat) {
        const id = Number(chat?.dbConversationId);
        return validId(id) ? id : null;
    }

    function isRoomBeingRead(id) {
        return selectedId === Number(id) &&
            !document.hidden &&
            area.getClientRects().length > 0 &&
            area.clientWidth > 0;
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
        uploadStatus.dataset.status = isError ? 'error' : 'success';
    }

    function updateInputState() {
        const available = !isLoading && Boolean(getServerConversationId(selected()));

        input.disabled = !available;
        attachButton.disabled = !available || isUploading;

        if (sendButton) {
            sendButton.disabled = !available || isSending;
        }
    }

    function updateConversationUrl(id) {
        const url = new URL(window.location.href);

        if (validId(id)) {
            url.searchParams.set('conversation', String(id));
        } else {
            url.searchParams.delete('conversation');
        }

        window.history.replaceState({}, '', url);
    }

    async function request(url, options = {}) {
        const method = options.method || 'GET';

        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {})
        };

        if (method !== 'GET') {
            if (!csrfToken) {
                throw new Error('Token CSRF tidak ditemukan.');
            }

            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch(url, {
            ...options,
            method,
            headers,
            credentials: 'same-origin',
            cache: 'no-store'
        });

        let result;

        try {
            result = await response.json();
        } catch {
            throw new Error(`Respons server tidak valid (${response.status}).`);
        }

        if (!response.ok || result.success === false) {
            let message = result.message || `Permintaan gagal (${response.status}).`;

            if (response.status === 401) {
                message = 'Sesi login berakhir. Silakan login kembali.';
            }

            if (response.status === 403) {
                message = 'Kamu tidak memiliki akses untuk melakukan tindakan ini.';
            }

            if (response.status === 419) {
                message = 'Sesi halaman kedaluwarsa. Silakan refresh.';
            }

            if (response.status === 422) {
                message = Object.values(result.errors || {}).flat().find(Boolean) || message;
            }

            const error = new Error(message);
            error.status = response.status;

            throw error;
        }

        return result;
    }

    function normalizeMessage(data, previous = {}) {
        const message = {
            ...previous,
            ...data,
            id: Number(data.id ?? previous.id),
            text: data.text ?? data.message ?? previous.text ?? '',
            type: data.type ?? previous.type ?? 'text',
            from: data.from ?? previous.from,
            at: data.at ?? data.created_at ?? previous.at,
            fileName: data.fileName ?? data.file_name ?? previous.fileName ?? null,
            fileSize: data.fileSize ?? data.file_size ?? previous.fileSize ?? null,
            fileType: data.fileType ?? data.file_type ?? previous.fileType ?? null,
            attachmentId: data.attachmentId ?? data.attachment_id ?? previous.attachmentId ?? null,
            editedAt: data.editedAt ?? data.edited_at ?? previous.editedAt ?? null,
            updatedAt: data.updatedAt ?? data.updated_at ?? previous.updatedAt ?? null,
            deletedAt: data.deletedAt ?? data.deleted_at ?? previous.deletedAt ?? null
        };

        message.isEdited = Boolean(
            data.isEdited ??
            data.is_edited ??
            data.edited ??
            (message.editedAt || previous.isEdited)
        );

        message.isOwnMessage = message.from === 'me';

        message.canEdit = message.from === 'me' &&
            message.type === 'text' &&
            !message.deletedAt &&
            data.canEdit !== false;

        message.canDeleteForMe = message.type !== 'system' &&
            message.from !== 'system' &&
            !message.deletedAt &&
            data.canDeleteForMe !== false;

        message.canDeleteForEveryone = message.canDeleteForMe &&
            message.from === 'me' &&
            data.canDeleteForEveryone !== false;

        return message;
    }

    function normalizeMessages(messages) {
        const unique = new Map();

        for (const data of messages) {
            if (!validId(data.id)) {
                continue;
            }

            const message = normalizeMessage(data);

            if (message.deletedAt) {
                deletedMessageIds.add(message.id);
                unique.delete(message.id);
                continue;
            }

            if (!deletedMessageIds.has(message.id)) {
                unique.set(message.id, message);
            }
        }

        return Array.from(unique.values()).sort((a, b) => {
            const timeA = parseDate(a.at).getTime() || 0;
            const timeB = parseDate(b.at).getTime() || 0;

            return timeA - timeB || a.id - b.id;
        });
    }

    function normalizeConversation(data, previous = null) {
        return {
            ...data,
            id: Number(data.id),
            dbConversationId: Number(data.dbConversationId ?? data.id),
            name: data.name || 'Pengguna FINS',
            photoUrl: data.photoUrl ?? data.photo_url ?? null,
            isGroup: Boolean(data.isGroup),
            members: Array.isArray(data.members) ? data.members : [],
            messages: normalizeMessages(
                previous?.messages ||
                (Array.isArray(data.messages) ? data.messages : [])
            ),
            unread: previous?.unread ?? (Number(data.unread) || 0),
            lastMessageAt: previous?.lastMessageAt || data.lastMessageAt || data.created_at || null
        };
    }

    function invalidateMessageRequest(conversationId) {
        const id = Number(conversationId);

        messageRequests.set(id, (messageRequests.get(id) || 0) + 1);
        messageLoading.delete(id);
    }

    function removeMessage(messageId, conversationId = null) {
        if (!validId(messageId)) {
            return;
        }

        const id = Number(messageId);

        if (deletedMessageIds.has(id)) return;
        deletedMessageIds.add(id);

        const resolvedId = Number(
            conversationId ||
            messageConversationMap.get(id) ||
            chats.find(chat =>
                chat.messages.some(message => message.id === id)
            )?.id
        );

        const chat = findChat(resolvedId);

        if (chat) {
            chat.messages = chat.messages.filter(message => message.id !== id);
            chat.lastMessageAt = chat.messages.at(-1)?.at || chat.created_at;

            if (isRoomBeingRead(chat.id)) {
                chat.unread = 0;
            }
        }

        messageConversationMap.delete(id);

        if (editingMessage?.messageId === id && !isEditing) {
            closeEditModal(true);
        }

        if (deletingMessage?.messageId === id && !isDeleting) {
            closeDeleteMessageModal(true);
        }

        closeMessageMenu();
        renderList();

        if (chat && selectedId === chat.id) {
            renderRoom();
        }

        if (!chat && validId(resolvedId)) {
            queueMessageSync(resolvedId);
        }
    }

    function upsertMessage(conversationId, data, options = {}) {
        const chat = findChat(conversationId);

        if (!chat || !validId(data?.id)) {
            return false;
        }

        const id = Number(data.id);

        if (data.deletedAt || data.deleted_at) {
            removeMessage(id, conversationId);
            return true;
        }

        if (deletedMessageIds.has(id)) {
            return false;
        }

        const index = chat.messages.findIndex(message => message.id === id);
        const previous = index >= 0 ? chat.messages[index] : {};
        const message = normalizeMessage(data, previous);

        if (message.deletedAt) {
            removeMessage(id, conversationId);
            return true;
        }

        if (index >= 0) {
            const oldTime = parseDate(previous.updatedAt).getTime();
            const newTime = parseDate(message.updatedAt).getTime();

            if (Number.isFinite(oldTime) &&
                Number.isFinite(newTime) &&
                newTime < oldTime) {
                return false;
            }

            chat.messages[index] = message;
        } else {
            chat.messages.push(message);
        }

        chat.messages = normalizeMessages(chat.messages);
        chat.lastMessageAt = chat.messages.at(-1)?.at || chat.created_at;
        messageConversationMap.set(id, chat.id);
        window.FinsRealtime?.rememberMessage?.({...message, conversationId: chat.id});

        if (
            index < 0 &&
            options.incoming &&
            message.from !== 'me' &&
            message.type !== 'system' &&
            !isRoomBeingRead(chat.id)
        ) {
            chat.unread += 1;
        }

        if (isRoomBeingRead(chat.id)) {
            chat.unread = 0;
        }

        renderList();

        if (selectedId === chat.id) {
            renderRoom({
                forceScroll: Boolean(options.forceScroll)
            });
        }

        return true;
    }

    function renderLoading() {
        list.replaceChildren();
        area.replaceChildren();

        setText('selectedName', 'Memuat percakapan...');
        setText('selectedAvatar', '...');
        setText('selectedStatus', '');
        setText('unreadCount', '0');

        list.append(node('p', 'no-results', 'Memuat percakapan...'));
        area.append(node('p', 'empty-chat', 'Memuat percakapan dari server...'));

        updateInputState();
    }

    async function loadConversations() {
        if (isRefreshing) {
            return;
        }

        isLoading = true;
        isRefreshing = true;
        initialLoadFinished = false;
        conversationError = '';

        renderLoading();

        try {
            const result = await request(conversationsUrl);

            if (!Array.isArray(result.data)) {
                throw new Error('Format percakapan tidak valid.');
            }

            chats = result.data
                .map(data => normalizeConversation(data))
                .filter(chat =>
                    validId(chat.id) &&
                    validId(chat.dbConversationId) &&
                    !hiddenConversationIds.has(chat.id)
                );

            const requestedId = new URLSearchParams(window.location.search).get('conversation');

            if (requestedId !== null) {
                const chat = findChat(requestedId);

                selectedId = chat?.id ?? null;

                if (chat) {
                    chat.unread = 0;
                    messenger.classList.add('room-open');
                } else {
                    messenger.classList.remove('room-open');
                    showUploadStatus('Percakapan tidak ditemukan.', true);
                }
            } else {
                selectedId = chats[0]?.id ?? null;
            }

            isLoading = false;

            renderList();
            renderRoom();

            if (selectedId !== null) {
                await loadMessages(selectedId, { forceScroll: true });
            }
        } catch (error) {
            chats = [];
            selectedId = null;
            conversationError = error.message;
            isLoading = false;

            renderList();
            renderRoom();
            showUploadStatus(error.message, true);
        } finally {
            isLoading = false;
            isRefreshing = false;
            initialLoadFinished = true;

            updateInputState();

            if (pendingFullSync || pendingListSync || pendingMessageSync.size) {
                scheduleRealtimeSync();
            }
        }

        await loadMessageBatch(
            chats.filter(chat => chat.id !== selectedId).map(chat => chat.id)
        );
    }

    async function refreshConversations() {
        if (isLoading || isRefreshing) {
            return [];
        }

        isRefreshing = true;

        try {
            const result = await request(conversationsUrl);

            if (!Array.isArray(result.data)) {
                throw new Error('Format percakapan tidak valid.');
            }

            const previous = new Map(chats.map(chat => [chat.id, chat]));
            const newIds = [];

            chats = result.data
                .map(data => {
                    const id = Number(data.id);

                    if (!previous.has(id)) {
                        newIds.push(id);
                    }

                    return normalizeConversation(data, previous.get(id));
                })
                .filter(chat =>
                    validId(chat.id) &&
                    validId(chat.dbConversationId) &&
                    !hiddenConversationIds.has(chat.id)
                );

            for (const [id, oldChat] of previous) {
                if (!findChat(id)) {
                    invalidateMessageRequest(id);
                    messageErrors.delete(id);

                    for (const message of oldChat.messages) {
                        messageConversationMap.delete(message.id);
                    }
                }
            }

            conversationError = '';

            if (selectedId !== null && !selected()) {
                selectedId = null;
                updateConversationUrl(null);
                messenger.classList.remove('room-open');
                renderRoom();
            }

            renderList();
            renderSelectedProfile();
            updateInputState();

            return newIds.filter(id => Boolean(findChat(id)));
        } catch (error) {
            if ([401, 403].includes(error.status)) {
                chats = [];
                selectedId = null;
                conversationError = error.message;

                renderList();
                renderRoom();
            }

            throw error;
        } finally {
            isRefreshing = false;
        }
    }

    async function loadMessages(conversationId, options = {}) {
        conversationId = Number(conversationId);

        const chat = findChat(conversationId);
        const serverId = getServerConversationId(chat);

        if (!chat || !serverId) {
            return;
        }

        if (options.background && messageLoading.has(conversationId)) {
            pendingMessageSync.add(conversationId);
            scheduleRealtimeSync(1000);
            return;
        }

        const requestId = (messageRequests.get(conversationId) || 0) + 1;
        const previousIds = new Set(chat.messages.map(message => message.id));

        messageRequests.set(conversationId, requestId);
        messageLoading.add(conversationId);
        messageErrors.delete(conversationId);

        if (selectedId === conversationId && !options.background) {
            renderRoom(options);
        }

        try {
            const result = await request(getMessagesUrl(serverId));

            if (!Array.isArray(result.data)) {
                throw new Error('Format riwayat pesan tidak valid.');
            }

            if (messageRequests.get(conversationId) !== requestId) {
                return;
            }

            const currentChat = findChat(conversationId);

            if (!currentChat) {
                return;
            }

            const serverIds = new Set(
                result.data
                    .map(message => Number(message.id))
                    .filter(validId)
            );

            for (const id of previousIds) {
                if (!serverIds.has(id)) {
                    deletedMessageIds.add(id);
                    messageConversationMap.delete(id);
                }
            }

            const previousMessages = new Map(
                currentChat.messages.map(message => [message.id, message])
            );

            const synchronized = result.data.map(data => {
                const previous = previousMessages.get(Number(data.id));

                if (!previous) {
                    return data;
                }

                const previousTime = parseDate(previous.updatedAt).getTime();
                const serverTime = parseDate(data.updatedAt ?? data.updated_at).getTime();

                if (Number.isFinite(previousTime) &&
                    Number.isFinite(serverTime) &&
                    previousTime > serverTime) {
                    return previous;
                }

                return normalizeMessage(data, previous);
            });

            for (const message of currentChat.messages) {
                if (!previousIds.has(message.id) && !serverIds.has(message.id)) {
                    synchronized.push(message);
                }
            }

            currentChat.messages = normalizeMessages(synchronized);
            currentChat.lastMessageAt = currentChat.messages.at(-1)?.at || currentChat.created_at;

            for (const message of currentChat.messages) {
                messageConversationMap.set(message.id, conversationId);
                window.FinsRealtime?.rememberMessage?.({...message, conversationId});
            }

            if (isRoomBeingRead(conversationId)) {
                currentChat.unread = 0;
            }
        } catch (error) {
            if (messageRequests.get(conversationId) !== requestId) {
                return;
            }

            messageErrors.set(conversationId, error.message);

            if ([401, 403].includes(error.status)) {
                const currentChat = findChat(conversationId);

                if (currentChat) {
                    currentChat.messages = [];
                    currentChat.unread = 0;
                }

                pendingListSync = true;
                scheduleRealtimeSync();
            }
        } finally {
            if (messageRequests.get(conversationId) === requestId) {
                messageLoading.delete(conversationId);

                if (selectedId === conversationId) {
                    renderRoom(options);
                }

                renderList();
            }
        }
    }

    async function loadMessageBatch(ids) {
        const uniqueIds = [...new Set(ids.map(Number).filter(validId))];

        for (let i = 0; i < uniqueIds.length; i += 2) {
            await Promise.allSettled(
                uniqueIds.slice(i, i + 2).map(id =>
                    loadMessages(id, { background: true })
                )
            );
        }
    }

    function scheduleRealtimeSync(delay = 100) {
        if (realtimeTimer !== null) return;
        realtimeTimer = setTimeout(() => {
            realtimeTimer = null;
            flushRealtimeSync();
        }, delay);
    }

    function queueConversationSync() {
        pendingListSync = true;
        scheduleRealtimeSync();
    }

    function queueMessageSync(id) {
        if (!validId(id)) {
            return;
        }

        id = Number(id);

        pendingMessageSync.add(id);

        if (!findChat(id)) {
            pendingListSync = true;
        }

        scheduleRealtimeSync();
    }

    function queueFullSync() {
        pendingFullSync = true;
        pendingListSync = true;
        scheduleRealtimeSync();
    }

    async function flushRealtimeSync() {
        if (!initialLoadFinished || isLoading || realtimeRunning || isRefreshing) {
            scheduleRealtimeSync(1000);
            return;
        }

        if (!navigator.onLine) {
            return;
        }

        if (!pendingFullSync && !pendingListSync && !pendingMessageSync.size) {
            return;
        }

        realtimeRunning = true;

        const fullSync = pendingFullSync;
        const listSync = pendingListSync;
        const ids = new Set(pendingMessageSync);

        pendingFullSync = false;
        pendingListSync = false;
        pendingMessageSync.clear();

        try {
            if (listSync || fullSync) {
                const newIds = await refreshConversations();

                for (const id of newIds) {
                    ids.add(id);
                }
            }

            if (fullSync) {
                for (const chat of chats) {
                    ids.add(chat.id);
                }
            }

            if (selectedId !== null && ids.has(selectedId)) {
                const id = selectedId;

                ids.delete(id);

                await loadMessages(id, { background: true });
            }

            await loadMessageBatch(
                [...ids].filter(id => Boolean(findChat(id)))
            );
        } catch (error) {
            console.warn('[FINS Chat]', error.message);

            if (![401, 403].includes(error.status)) {
                pendingFullSync = true;
                scheduleRealtimeSync(2000);
            }
        } finally {
            realtimeRunning = false;

            if (pendingFullSync || pendingListSync || pendingMessageSync.size) {
                scheduleRealtimeSync(200);
            }
        }
    }

    window.addEventListener('fins:conversations-updated', event => {
        const id = event.detail?.conversationId;

        if (!validId(id) || !findChat(id)) {
            queueConversationSync();
        }
        if (validId(id)) queueMessageSync(id);
    });

    window.addEventListener('fins:message-received', event => {
        const detail = event.detail || {};
        const messageId = Number(detail.messageId);
        const conversationId = Number(detail.conversationId);

        if (!validId(messageId) || !validId(conversationId)) {
            return;
        }

        if (receivedMessageIds.has(messageId) ||
            deletedMessageIds.has(messageId)) {
            return;
        }

        receivedMessageIds.add(messageId);

        if (receivedMessageIds.size > 5000) {
            receivedMessageIds.delete(receivedMessageIds.values().next().value);
        }

        hiddenConversationIds.delete(conversationId);
        messageConversationMap.set(messageId, conversationId);

        const chat = findChat(conversationId);

        if (detail.message && chat) {
            upsertMessage(conversationId, {
                ...detail.message,
                id: messageId,
                from: detail.message.from || (
                    detail.isOwnMessage ? 'me' : 'them'
                )
            }, {
                incoming: !detail.isOwnMessage
            });

            if (['image', 'file'].includes(detail.message.type) &&
                !validId(detail.message.attachmentId)) {
                queueMessageSync(conversationId);
            }

            return;
        }

        if (chat) {
            const exists = chat.messages.some(message => message.id === messageId);

            if (!exists && !detail.isOwnMessage &&
                detail.type !== 'system' && !isRoomBeingRead(conversationId)) {
                chat.unread += 1;
            }

            chat.lastMessageAt = detail.createdAt || chat.lastMessageAt;

            renderList();
        }

        queueMessageSync(conversationId);
    });

    window.addEventListener('fins:message-updated', event => {
        const detail = event.detail || {};
        const conversationId = Number(detail.conversationId);
        const messageId = Number(detail.messageId ?? detail.message?.id);

        if (!validId(messageId)) {
            return;
        }

        if (detail.deletedAt ||
            detail.deleted_at ||
            detail.message?.deletedAt ||
            detail.message?.deleted_at) {
            removeMessage(messageId, conversationId);
            return;
        }

        if (!validId(conversationId) || deletedMessageIds.has(messageId)) {
            return;
        }

        const chat = findChat(conversationId);
        const previous = chat?.messages.find(message => message.id === messageId);

        if (previous && detail.message) {
            const updated = normalizeMessage(detail.message, previous);

            if (updated.text !== previous.text && updated.type === 'text') {
                updated.isEdited = true;
            }

            upsertMessage(conversationId, updated);
            return;
        }

        queueMessageSync(conversationId);
    });

    function handleMessageDeletion(event) {
        const detail = event.detail || {};
        const deletion = detail.deletion || detail.message || detail;

        const messageId = Number(
            detail.messageId ??
            detail.message_id ??
            deletion.messageId ??
            deletion.message_id ??
            deletion.id
        );

        const conversationId = Number(
            detail.conversationId ??
            detail.conversation_id ??
            deletion.conversationId ??
            deletion.conversation_id
        );

        const scope = detail.scope || deletion.scope;
        const userId = Number(detail.userId ?? detail.user_id ?? deletion.user_id);
        const currentUserId = Number(window.FinsRealtime?.getUserId?.());

        if (scope === 'me' &&
            validId(userId) &&
            validId(currentUserId) &&
            userId !== currentUserId) {
            return;
        }

        if (validId(messageId)) {
            removeMessage(messageId, conversationId);
        } else if (validId(conversationId)) {
            queueMessageSync(conversationId);
        } else {
            queueFullSync();
        }
    }

    window.addEventListener('fins:message-deleted', handleMessageDeletion);
    window.addEventListener('fins:message-deleted-for-me', handleMessageDeletion);
    window.addEventListener('fins:message-deleted-for-everyone', handleMessageDeletion);
    window.addEventListener('fins:message-deletion-received', handleMessageDeletion);

    function handleAttachmentEvent(event) {
        const detail = event.detail || {};
        const messageId = Number(detail.messageId);

        if (!validId(messageId) || deletedMessageIds.has(messageId)) {
            return;
        }

        const conversationId = Number(
            detail.conversationId ||
            messageConversationMap.get(messageId) ||
            chats.find(chat =>
                chat.messages.some(message => message.id === messageId)
            )?.id
        );

        if (!validId(conversationId)) {
            queueFullSync();
            return;
        }

        const chat = findChat(conversationId);
        const message = chat?.messages.find(item => item.id === messageId);

        if (message && detail.attachment) {
            const attachment = detail.attachment;

            upsertMessage(conversationId, {
                ...message,
                attachmentId: attachment.attachmentId ?? attachment.id,
                fileName: attachment.fileName ?? attachment.file_name,
                fileSize: attachment.fileSize ?? attachment.file_size,
                fileType: attachment.fileType ?? attachment.file_type
            });
        } else {
            queueMessageSync(conversationId);
        }
    }

    window.addEventListener('fins:attachment-received', handleAttachmentEvent);
    window.addEventListener('fins:attachment-updated', handleAttachmentEvent);
    window.addEventListener('fins:chat-sync-required', queueFullSync);

    window.addEventListener('fins:realtime-status', event => {
        console.info(
            '[FINS Chat] Realtime:',
            event.detail?.connected ? 'CONNECTED' : 'DISCONNECTED'
        );

        if (event.detail?.connected && initialLoadFinished) {
            queueFullSync();
        }
    });

    window.addEventListener('online', queueFullSync);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            return;
        }

        const chat = selected();

        if (chat && isRoomBeingRead(chat.id)) {
            chat.unread = 0;
            renderList();
        }

        if (!window.FinsRealtime?.isConnected()) {
            queueFullSync();
        } else {
            queueConversationSync();
            if (chat) queueMessageSync(chat.id);
        }
    });

    setInterval(() => {
        if (
            !document.hidden &&
            navigator.onLine &&
            initialLoadFinished &&
            !window.FinsRealtime?.isConnected()
        ) {
            queueFullSync();
        }
    }, 30000);

    setInterval(() => {
        if (!document.hidden && navigator.onLine && initialLoadFinished && window.FinsRealtime?.isConnected()) {
            queueConversationSync();
        }
    }, 60000);

    function renderList() {
        if (isLoading) {
            return;
        }

        const keyword = search.value.trim().toLocaleLowerCase('id');

        const visible = chats.filter(chat => {
            const matchesFilter = filter === 'all' || chat.unread > 0;

            const matchesSearch = !keyword ||
                chat.name.toLocaleLowerCase('id').includes(keyword) ||
                chat.messages.some(message =>
                    String(message.text || '').toLocaleLowerCase('id').includes(keyword) ||
                    String(message.fileName || '').toLocaleLowerCase('id').includes(keyword)
                );

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

        const previousScroll = list.scrollTop;

        list.replaceChildren();

        for (const chat of visible) {
            const item = node(
                'button',
                'conversation-item' + (chat.id === selectedId ? ' active' : '')
            );

            item.type = 'button';
            item.setAttribute('aria-pressed', String(chat.id === selectedId));

            const avatar = node('span', 'avatar');
            renderAvatar(avatar, chat);
            const meta = node('span', 'contact-meta');
            const right = node('span', 'contact-right');
            const lastMessage = chat.messages.at(-1);

            meta.append(
                node('span', 'contact-name', chat.name),
                node('span', 'contact-preview', getPreview(lastMessage))
            );

            if (lastMessage) {
                right.append(
                    node('span', 'contact-time', formatTime(lastMessage.at))
                );
            }

            if (chat.unread > 0) {
                right.append(
                    node('span', 'unread-badge', chat.unread)
                );
            }

            item.append(avatar, meta, right);

            item.addEventListener('click', () => {
                selectChat(chat.id);
            });

            list.append(item);
        }

        if (!visible.length) {
            let message = 'Tidak ada percakapan yang cocok.';

            if (conversationError) {
                message = 'Gagal memuat percakapan.';
            } else if (!chats.length) {
                message = 'Belum ada percakapan.';
            }

            list.append(node('p', 'no-results', message));

            if (conversationError) {
                const retry = node('button', 'conversation-item', 'Coba Lagi');

                retry.type = 'button';
                retry.addEventListener('click', loadConversations);

                list.append(retry);
            }
        }

        list.scrollTop = previousScroll;

        setText(
            'unreadCount',
            chats.reduce((sum, chat) => sum + (chat.unread || 0), 0)
        );

        $('filterAll')?.classList.toggle('active', filter === 'all');
        $('filterUnread')?.classList.toggle('active', filter === 'unread');

        $('filterAll')?.setAttribute('aria-pressed', String(filter === 'all'));
        $('filterUnread')?.setAttribute('aria-pressed', String(filter === 'unread'));
    }

    const editOverlay = $('editMessageOverlay') || createEditModal();
    const editForm = $('editMessageForm');
    const editInput = $('editMessageInput');
    const editError = $('editMessageError');
    const editCharCount = $('editMessageCharCount');
    const editSaveButton = editForm?.querySelector('button[type="submit"]');
    const editCancelButton = editOverlay.querySelector('.edit-message-cancel');
    const editCloseButton = editOverlay.querySelector('.edit-message-close');

    function createEditModal() {
        const overlay = node('div', 'edit-message-overlay');

        overlay.id = 'editMessageOverlay';
        overlay.hidden = true;

        overlay.innerHTML = `
            <div class="edit-message-modal"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="editMessageTitle">
                <div class="edit-message-header">
                    <h2 id="editMessageTitle">Edit Pesan</h2>
                    <button type="button"
                            class="edit-message-close"
                            id="closeEditMessageBtn"
                            aria-label="Tutup">×</button>
                </div>

                <form id="editMessageForm">
                    <div class="edit-message-body">
                        <label for="editMessageInput">Pesan</label>

                        <textarea id="editMessageInput"
                                  maxlength="2000"
                                  required></textarea>

                        <div class="edit-message-info">
                            <span id="editMessageError"
                                  class="edit-message-error"
                                  role="alert"
                                  hidden></span>

                            <span id="editMessageCharCount"
                                  class="edit-message-char-count">0/2000</span>
                        </div>
                    </div>

                    <div class="edit-message-actions">
                        <button type="button"
                                class="edit-message-cancel"
                                id="cancelEditMessageBtn">
                            Batal
                        </button>

                        <button type="submit"
                                class="edit-message-save"
                                id="saveEditMessageBtn">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        `;

        document.body.append(overlay);

        return overlay;
    }

    const deleteMessageOverlay = $('deleteMessageOverlay') || createDeleteMessageModal();
    const deleteMessageForm = $('deleteMessageForm');
    const deleteMessageTitle = $('deleteMessageTitle');
    const deleteMessageText = $('deleteMessageText');
    const deleteMessagePreview = $('deleteMessagePreview');
    const deleteMessageDescription = $('deleteMessageDescription');
    const deleteMessageError = $('deleteMessageError');

    const deleteMessageCancelButton =
        deleteMessageOverlay.querySelector('.delete-message-cancel');

    const deleteMessageCloseButton =
        deleteMessageOverlay.querySelector('.delete-message-close');

    const deleteMessageConfirmButton =
        deleteMessageOverlay.querySelector('.delete-message-confirm');

    function createDeleteMessageModal() {
        const overlay = node('div', 'delete-message-overlay');

        overlay.id = 'deleteMessageOverlay';
        overlay.hidden = true;

        overlay.innerHTML = `
            <div class="delete-message-modal"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="deleteMessageTitle">

                <div class="delete-message-header">
                    <h2 id="deleteMessageTitle">Hapus Pesan</h2>

                    <button type="button"
                            class="delete-message-close"
                            id="closeDeleteMessageBtn"
                            aria-label="Tutup">×</button>
                </div>

                <form id="deleteMessageForm">
                    <div class="delete-message-body">

                        <div class="delete-message-icon">
                            <svg viewBox="0 0 24 24"
                                 fill="none"
                                 stroke="currentColor"
                                 stroke-width="1.8"
                                 stroke-linecap="round"
                                 stroke-linejoin="round">
                                <path d="M3 6h18"></path>
                                <path d="M8 6V4h8v2"></path>
                                <path d="M19 6l-1 14H6L5 6"></path>
                                <path d="M10 10v6"></path>
                                <path d="M14 10v6"></path>
                            </svg>
                        </div>

                        <p id="deleteMessageText">
                            Apakah kamu yakin ingin menghapus pesan ini?
                        </p>

                        <div id="deleteMessagePreview"
                             class="delete-message-preview"
                             hidden></div>

                        <p id="deleteMessageDescription"
                           class="delete-message-description"></p>

                        <div id="deleteMessageError"
                             class="delete-message-error"
                             role="alert"
                             hidden></div>
                    </div>

                    <div class="delete-message-actions">
                        <button type="button"
                                id="cancelDeleteMessageBtn"
                                class="delete-message-cancel">
                            Batal
                        </button>

                        <button type="submit"
                                id="confirmDeleteMessageBtn"
                                class="delete-message-confirm">
                            Hapus
                        </button>
                    </div>
                </form>
            </div>
        `;

        document.body.append(overlay);

        return overlay;
    }

    const messageContextMenu =
        $('messageContextMenu') ||
        node('div', 'message-context-menu');

    messageContextMenu.id = 'messageContextMenu';
    messageContextMenu.hidden = true;

    messageContextMenu.replaceChildren();

    const editMenuOption = node('button', 'message-menu-option');

    editMenuOption.id = 'editMessageOption';
    editMenuOption.type = 'button';

    editMenuOption.innerHTML = `
        <svg viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="1.8"
             stroke-linecap="round"
             stroke-linejoin="round">
            <path d="M12 20h9"></path>
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"></path>
        </svg>
        <span>Edit Pesan</span>
    `;

    const deleteMeMenuOption = node(
        'button',
        'message-menu-option message-menu-danger'
    );

    deleteMeMenuOption.id = 'deleteMessageForMeOption';
    deleteMeMenuOption.type = 'button';

    deleteMeMenuOption.innerHTML = `
        <svg viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="1.8"
             stroke-linecap="round"
             stroke-linejoin="round">
            <path d="M3 6h18"></path>
            <path d="M8 6V4h8v2"></path>
            <path d="M19 6l-1 14H6L5 6"></path>
            <path d="M10 10v6"></path>
            <path d="M14 10v6"></path>
        </svg>
        <span>Hapus untuk Saya</span>
    `;

    const deleteEveryoneMenuOption = node(
        'button',
        'message-menu-option message-menu-danger'
    );

    deleteEveryoneMenuOption.id = 'deleteMessageForEveryoneOption';
    deleteEveryoneMenuOption.type = 'button';

    deleteEveryoneMenuOption.innerHTML = `
        <svg viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="1.8"
             stroke-linecap="round"
             stroke-linejoin="round">
            <path d="M3 6h18"></path>
            <path d="M8 6V4h8v2"></path>
            <path d="M19 6l-1 14H6L5 6"></path>
            <path d="M9 10l6 6"></path>
            <path d="M15 10l-6 6"></path>
        </svg>
        <span>Hapus untuk Semua Orang</span>
    `;

    messageContextMenu.append(
        editMenuOption,
        deleteMeMenuOption,
        deleteEveryoneMenuOption
    );

    document.body.append(messageContextMenu);

    let contextMessageId = null;
    let contextConversationId = null;
    let contextButton = null;

    function canEditMessage(message) {
        return Boolean(
            message &&
            message.from === 'me' &&
            message.type === 'text' &&
            !message.deletedAt &&
            !deletedMessageIds.has(Number(message.id)) &&
            message.canEdit !== false &&
            validId(message.id)
        );
    }

    function canDeleteMessageForMe(message) {
        return Boolean(
            message &&
            message.type !== 'system' &&
            message.from !== 'system' &&
            !message.deletedAt &&
            !deletedMessageIds.has(Number(message.id)) &&
            message.canDeleteForMe !== false &&
            validId(message.id)
        );
    }

    function canDeleteMessageForEveryone(message) {
        return Boolean(
            canDeleteMessageForMe(message) &&
            message.from === 'me' &&
            message.canDeleteForEveryone !== false
        );
    }

    function hasMessageOptions(message) {
        return canEditMessage(message) ||
            canDeleteMessageForMe(message) ||
            canDeleteMessageForEveryone(message);
    }

    function closeMessageMenu() {
        messageContextMenu.hidden = true;

        contextButton?.setAttribute('aria-expanded', 'false');

        contextMessageId = null;
        contextConversationId = null;
        contextButton = null;
    }

    function openMessageMenu(button, chat, message) {
        if (!hasMessageOptions(message) || isEditing || isDeleting) {
            return;
        }

        if (
            !messageContextMenu.hidden &&
            contextMessageId === message.id &&
            contextConversationId === chat.id
        ) {
            closeMessageMenu();
            return;
        }

        closeMessageMenu();
        closeRoomMenu();

        contextMessageId = message.id;
        contextConversationId = chat.id;
        contextButton = button;

        editMenuOption.hidden = !canEditMessage(message);
        deleteMeMenuOption.hidden = !canDeleteMessageForMe(message);
        deleteEveryoneMenuOption.hidden = !canDeleteMessageForEveryone(message);

        button.setAttribute('aria-expanded', 'true');

        messageContextMenu.hidden = false;
        messageContextMenu.style.visibility = 'hidden';

        const rect = button.getBoundingClientRect();
        const menuWidth = messageContextMenu.offsetWidth;
        const menuHeight = messageContextMenu.offsetHeight;

        const left = Math.max(
            10,
            Math.min(
                rect.right - menuWidth,
                window.innerWidth - menuWidth - 10
            )
        );

        let top = rect.bottom + 6;

        if (top + menuHeight > window.innerHeight - 10) {
            top = rect.top - menuHeight - 6;
        }

        messageContextMenu.style.left = `${left}px`;
        messageContextMenu.style.top = `${Math.max(10, top)}px`;
        messageContextMenu.style.visibility = 'visible';
    }

    function setEditError(message = '') {
        if (editError) {
            editError.textContent = message;
            editError.hidden = !message;
        }

        editInput?.setAttribute('aria-invalid', String(Boolean(message)));
    }

    function updateEditCharCount() {
        if (editCharCount && editInput) {
            editCharCount.textContent = `${editInput.value.length}/2000`;
        }
    }

    function updateEditState() {
        if (editInput) {
            editInput.disabled = isEditing;
        }

        if (editSaveButton) {
            editSaveButton.disabled = isEditing;
            editSaveButton.textContent = isEditing
                ? 'Menyimpan...'
                : 'Simpan Perubahan';
        }

        if (editCancelButton) {
            editCancelButton.disabled = isEditing;
        }

        if (editCloseButton) {
            editCloseButton.disabled = isEditing;
        }
    }

    function openEditModal(conversationId, messageId) {
        if (isEditing || isDeleting || !editInput || !editForm) {
            return;
        }

        const chat = findChat(conversationId);

        const message = chat?.messages.find(
            item => item.id === Number(messageId)
        );

        if (!chat || !canEditMessage(message)) {
            return;
        }

        closeDeleteMessageModal();

        editingMessage = {
            conversationId: chat.id,
            serverConversationId: getServerConversationId(chat),
            messageId: message.id,
            originalText: message.text
        };

        editInput.value = message.text || '';

        setEditError();
        updateEditCharCount();
        updateEditState();

        editOverlay.hidden = false;

        requestAnimationFrame(() => {
            editInput.focus();

            editInput.setSelectionRange(
                editInput.value.length,
                editInput.value.length
            );
        });
    }

    function closeEditModal(force = false) {
        if (isEditing && !force) {
            return;
        }

        editingMessage = null;
        editOverlay.hidden = true;

        editForm?.reset();

        setEditError();
        updateEditCharCount();
    }

    editMenuOption.addEventListener('click', event => {
        event.stopPropagation();

        const conversationId = contextConversationId;
        const messageId = contextMessageId;

        closeMessageMenu();

        if (validId(conversationId) && validId(messageId)) {
            openEditModal(conversationId, messageId);
        }
    });

    editInput?.addEventListener('input', () => {
        updateEditCharCount();
        setEditError();
    });

    editCancelButton?.addEventListener('click', () => {
        closeEditModal();
    });

    editCloseButton?.addEventListener('click', () => {
        closeEditModal();
    });

    editOverlay.addEventListener('click', event => {
        if (event.target === editOverlay) {
            closeEditModal();
        }
    });

    editForm?.addEventListener('submit', async event => {
        event.preventDefault();

        if (isEditing || isDeleting || !editingMessage || !editInput) {
            return;
        }

        const target = { ...editingMessage };
        const text = editInput.value.trim();

        const chat = findChat(target.conversationId);

        const originalMessage = chat?.messages.find(
            message => message.id === target.messageId
        );

        if (!chat || !canEditMessage(originalMessage)) {
            setEditError('Pesan tidak dapat diedit.');
            return;
        }

        if (!text) {
            setEditError('Pesan tidak boleh kosong.');
            editInput.focus();
            return;
        }

        if (text.length > 2000) {
            setEditError('Pesan maksimal 2000 karakter.');
            return;
        }

        if (text === originalMessage.text) {
            closeEditModal();
            return;
        }

        isEditing = true;

        setEditError();
        updateEditState();

        try {
            const result = await request(
                getEditUrl(
                    target.messageId,
                    target.serverConversationId
                ),
                {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: text
                    })
                }
            );

            const data = result.data?.message &&
                typeof result.data.message === 'object'
                ? result.data.message
                : result.data;

            const editedAt = data?.editedAt ||
                data?.edited_at ||
                new Date().toISOString();

            if (!deletedMessageIds.has(target.messageId)) {
                upsertMessage(target.conversationId, {
                    ...originalMessage,
                    ...(data && typeof data === 'object' ? data : {}),
                    id: target.messageId,
                    from: 'me',
                    type: 'text',
                    text: data?.text ??
                        (typeof data?.message === 'string' ? data.message : text),
                    editedAt,
                    updatedAt: data?.updatedAt ||
                        data?.updated_at ||
                        editedAt,
                    isEdited: true
                });
            }

            isEditing = false;

            closeEditModal(true);

            console.info(
                '[FINS Chat] Pesan berhasil diedit:',
                target.messageId
            );

        } catch (error) {
            setEditError(error.message || 'Gagal mengedit pesan.');

            if (error.status === 404) {
                queueMessageSync(target.conversationId);
            }

            console.error('[FINS Chat] Edit message:', error);
        } finally {
            isEditing = false;

            updateEditState();

            if (!editOverlay.hidden) {
                editInput.focus();
            }
        }
    });

    function setDeleteMessageError(message = '') {
        if (!deleteMessageError) {
            return;
        }

        deleteMessageError.textContent = message;
        deleteMessageError.hidden = !message;
    }

    function updateDeleteMessageState() {
        if (deleteMessageConfirmButton) {
            deleteMessageConfirmButton.disabled = isDeleting;
            deleteMessageConfirmButton.textContent = isDeleting
                ? 'Menghapus...'
                : 'Hapus';
        }

        if (deleteMessageCancelButton) {
            deleteMessageCancelButton.disabled = isDeleting;
        }

        if (deleteMessageCloseButton) {
            deleteMessageCloseButton.disabled = isDeleting;
        }
    }

    function openDeleteMessageModal(conversationId, messageId, scope) {
        if (isDeleting || isEditing) {
            return;
        }

        if (!['me', 'everyone'].includes(scope)) {
            return;
        }

        const chat = findChat(conversationId);

        const message = chat?.messages.find(
            item => item.id === Number(messageId)
        );

        if (!chat || !message) {
            return;
        }

        if (scope === 'everyone' && !canDeleteMessageForEveryone(message)) {
            return;
        }

        if (scope === 'me' && !canDeleteMessageForMe(message)) {
            return;
        }

        const serverConversationId = getServerConversationId(chat);

        if (!serverConversationId) {
            return;
        }

        closeMessageMenu();
        closeRoomMenu();
        closeEditModal();

        deletingMessage = {
            conversationId: chat.id,
            serverConversationId,
            messageId: message.id,
            scope
        };

        const isEveryone = scope === 'everyone';

        if (deleteMessageTitle) {
            deleteMessageTitle.textContent = isEveryone
                ? 'Hapus untuk Semua Orang'
                : 'Hapus untuk Saya';
        }

        if (deleteMessageText) {
            deleteMessageText.textContent = isEveryone
                ? 'Apakah kamu yakin ingin menghapus pesan ini untuk semua orang?'
                : 'Apakah kamu yakin ingin menghapus pesan ini untuk dirimu sendiri?';
        }

        if (deleteMessageDescription) {
            deleteMessageDescription.textContent = isEveryone
                ? 'Pesan akan dihapus dari percakapan seluruh anggota dan tidak dapat dikembalikan melalui chat.'
                : 'Pesan hanya akan hilang dari percakapanmu. Pengguna lain masih dapat melihat pesan tersebut.';

            deleteMessageDescription.hidden = false;
        }

        if (deleteMessagePreview) {
            const preview = ['file', 'image'].includes(message.type)
                ? '📎 ' + (message.fileName || message.text || 'Lampiran')
                : message.text || '';

            deleteMessagePreview.textContent = preview.length > 250
                ? preview.slice(0, 250) + '...'
                : preview;

            deleteMessagePreview.hidden = !preview;
        }

        setDeleteMessageError();
        updateDeleteMessageState();

        deleteMessageOverlay.hidden = false;

        requestAnimationFrame(() => {
            deleteMessageCancelButton?.focus();
        });
    }

    function closeDeleteMessageModal(force = false) {
        if (isDeleting && !force) {
            return;
        }

        deletingMessage = null;
        deleteMessageOverlay.hidden = true;

        if (deleteMessagePreview) {
            deleteMessagePreview.textContent = '';
            deleteMessagePreview.hidden = true;
        }

        setDeleteMessageError();
        updateDeleteMessageState();
    }

    deleteMeMenuOption.addEventListener('click', event => {
        event.stopPropagation();

        const conversationId = contextConversationId;
        const messageId = contextMessageId;

        closeMessageMenu();

        if (validId(conversationId) && validId(messageId)) {
            openDeleteMessageModal(
                conversationId,
                messageId,
                'me'
            );
        }
    });

    deleteEveryoneMenuOption.addEventListener('click', event => {
        event.stopPropagation();

        const conversationId = contextConversationId;
        const messageId = contextMessageId;

        closeMessageMenu();

        if (validId(conversationId) && validId(messageId)) {
            openDeleteMessageModal(
                conversationId,
                messageId,
                'everyone'
            );
        }
    });

    deleteMessageCancelButton?.addEventListener('click', () => {
        closeDeleteMessageModal();
    });

    deleteMessageCloseButton?.addEventListener('click', () => {
        closeDeleteMessageModal();
    });

    deleteMessageOverlay.addEventListener('click', event => {
        if (event.target === deleteMessageOverlay) {
            closeDeleteMessageModal();
        }
    });

    async function submitDeleteMessage(event) {
        event?.preventDefault();

        if (isDeleting || isEditing || !deletingMessage) {
            return;
        }

        const target = { ...deletingMessage };

        const chat = findChat(target.conversationId);

        const message = chat?.messages.find(
            item => item.id === target.messageId
        );

        if (deletedMessageIds.has(target.messageId)) {
            closeDeleteMessageModal(true);
            return;
        }

        if (!chat || !message) {
            setDeleteMessageError('Pesan tidak ditemukan.');
            queueMessageSync(target.conversationId);
            return;
        }

        if (target.scope === 'everyone' &&
            !canDeleteMessageForEveryone(message)) {
            setDeleteMessageError(
                'Kamu hanya dapat menghapus pesan milikmu untuk semua orang.'
            );

            return;
        }

        if (target.scope === 'me' &&
            !canDeleteMessageForMe(message)) {
            setDeleteMessageError('Pesan tidak dapat dihapus.');
            return;
        }

        isDeleting = true;

        setDeleteMessageError();
        updateDeleteMessageState();

        try {
            await request(
                getDeleteUrl(
                    target.messageId,
                    target.serverConversationId,
                    target.scope
                ),
                {
                    method: 'DELETE'
                }
            );

            removeMessage(
                target.messageId,
                target.conversationId
            );
            window.FinsRealtime?.markMessageDeleted?.(target.messageId, target.conversationId, target.scope);

            isDeleting = false;

            closeDeleteMessageModal(true);

            console.info(
                '[FINS Chat] Pesan berhasil dihapus:',
                target.messageId,
                target.scope
            );
        } catch (error) {
            if (deletedMessageIds.has(target.messageId)) {
                isDeleting = false;
                closeDeleteMessageModal(true);
                return;
            }

            setDeleteMessageError(
                error.message || 'Gagal menghapus pesan.'
            );

            if (error.status === 404) {
                queueMessageSync(target.conversationId);
            }

            console.error(
                '[FINS Chat] Delete message:',
                error
            );
        } finally {
            isDeleting = false;

            updateDeleteMessageState();
        }
    }

    if (deleteMessageForm) {
        deleteMessageForm.addEventListener(
            'submit',
            submitDeleteMessage
        );
    }

    if (
        deleteMessageConfirmButton &&
        (
            !deleteMessageForm ||
            deleteMessageConfirmButton.type !== 'submit' ||
            deleteMessageConfirmButton.form !== deleteMessageForm
        )
    ) {
        deleteMessageConfirmButton.addEventListener(
            'click',
            submitDeleteMessage
        );
    }

    document.addEventListener('click', event => {
        if (
            event.target instanceof Element &&
            !event.target.closest('.message-context-menu') &&
            !event.target.closest('.message-menu-btn') &&
            !event.target.closest('.message-menu-button')
        ) {
            closeMessageMenu();
        }
    });

    area.addEventListener('scroll', closeMessageMenu, {
        passive: true
    });

    window.addEventListener('resize', closeMessageMenu);

    function renderRoom(options = {}) {
        if (isLoading) {
            return;
        }

        const chat = selected();
        const previousScroll = area.scrollTop;

        const nearBottom =
            area.scrollHeight -
            area.scrollTop -
            area.clientHeight < 120;

        closeMessageMenu();

        area.replaceChildren();

        updateInputState();

        if (!chat) {
            setText('selectedName', 'Pilih percakapan');
            setText('selectedAvatar', '?');
            setText('selectedStatus', '');

            area.append(
                node(
                    'p',
                    'empty-chat',
                    conversationError ||
                    'Pilih percakapan untuk mulai berdiskusi.'
                )
            );

            return;
        }

        setText('selectedName', chat.name);
        renderAvatar($('selectedAvatar'), chat);

        setText(
            'selectedStatus',
            chat.isGroup
                ? `${chat.members?.length || 1} anggota`
                : 'Percakapan'
        );

        if (messageLoading.has(chat.id) && !chat.messages.length) {
            area.append(
                node('p', 'empty-chat', 'Memuat riwayat pesan...')
            );

            return;
        }

        if (messageErrors.has(chat.id)) {
            const errorText = node(
                'p',
                'empty-chat',
                messageErrors.get(chat.id)
            );

            const retry = node('button', '', 'Muat Ulang Pesan');

            retry.type = 'button';

            retry.addEventListener('click', () => {
                loadMessages(chat.id, {
                    forceScroll: true
                });
            });

            area.append(errorText, retry);
        }

        const visibleMessages = chat.messages.filter(message =>
            !message.deletedAt &&
            !deletedMessageIds.has(Number(message.id))
        );

        if (!visibleMessages.length) {
            if (!messageErrors.has(chat.id)) {
                area.append(
                    node(
                        'p',
                        'empty-chat',
                        'Belum ada pesan. Mulai percakapan!'
                    )
                );
            }

            return;
        }

        let previousDay = '';

        for (const message of visibleMessages) {
            const day = formatDay(message.at);

            if (day && day !== previousDay) {
                area.append(node('div', 'day-separator', day));

                previousDay = day;
            }

            if (message.type === 'system' || message.from === 'system') {
                area.append(
                    node(
                        'div',
                        'system-announcement',
                        message.text || ''
                    )
                );

                continue;
            }

            const bubble = node(
                'div',
                'message ' + (message.from === 'me' ? 'mine' : 'theirs')
            );

            bubble.dataset.messageId = String(message.id);

            const isAttachment = ['file', 'image'].includes(message.type);

            const fileUrl = validId(message.attachmentId)
                ? getDownloadUrl(message.attachmentId)
                : null;

            if (isAttachment && fileUrl) {
                const attachment = node('a', 'message-attachment');

                attachment.href = fileUrl;
                attachment.target = '_blank';
                attachment.rel = 'noopener noreferrer';

                const fileIcon = node(
                    'span',
                    'attachment-file-icon',
                    message.type === 'image' ? '🖼️' : '📄'
                );

                const fileInfo = node('span', 'attachment-file-info');

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
                        message.fileName || 'Memuat lampiran...'
                    )
                );
            } else {
                bubble.append(
                    node('span', 'message-text', message.text || '')
                );
            }

            if (hasMessageOptions(message)) {
                bubble.classList.add('has-menu');

                const menuButton = node(
                    'button',
                    'message-menu-btn',
                    '⋮'
                );

                menuButton.type = 'button';
                menuButton.title = 'Opsi pesan';

                menuButton.setAttribute('aria-label', 'Opsi pesan');
                menuButton.setAttribute('aria-expanded', 'false');
                menuButton.setAttribute('aria-haspopup', 'true');

                menuButton.addEventListener('click', event => {
                    event.stopPropagation();

                    openMessageMenu(menuButton, chat, message);
                });

                bubble.append(menuButton);
            }

            const meta = node('div', 'message-meta');

            if (message.isEdited || message.editedAt) {
                meta.append(
                    node('span', 'message-edited', 'Diedit')
                );
            }

            meta.append(
                node('span', 'message-time', formatTime(message.at))
            );

            bubble.append(meta);

            area.append(bubble);
        }

        const renderedChatId = chat.id;

        requestAnimationFrame(() => {
            if (selectedId !== renderedChatId) {
                return;
            }

            if (options.forceScroll || nearBottom) {
                area.scrollTop = area.scrollHeight;
            } else {
                area.scrollTop = previousScroll;
            }
        });
    }

    async function selectChat(id) {
        if (isLoading) {
            return;
        }

        const chat = findChat(id);

        if (!chat) {
            return;
        }

        closeMessageMenu();
        closeRoomMenu();

        if (!isEditing) {
            closeEditModal();
        }

        if (!isDeleting) {
            closeDeleteMessageModal();
        }

        selectedId = chat.id;
        chat.unread = 0;

        showUploadStatus('');

        messenger.classList.add('room-open');

        updateConversationUrl(getServerConversationId(chat));

        renderList();

        renderRoom({
            forceScroll: true
        });

        await loadMessages(chat.id, {
            forceScroll: true
        });
    }

    $('filterAll')?.addEventListener('click', () => {
        filter = 'all';

        renderList();
    });

    $('filterUnread')?.addEventListener('click', () => {
        filter = 'unread';

        renderList();
    });

    sort.addEventListener('change', renderList);
    search.addEventListener('input', renderList);

    $('searchButton')?.addEventListener('click', () => {
        search.focus();

        renderList();
    });

    $('backToList')?.addEventListener('click', () => {
        closeMessageMenu();
        closeRoomMenu();

        messenger.classList.remove('room-open');
    });

    const groupOverlay = $('groupOverlay');
    const groupForm = $('createGroupForm');
    const groupName = $('groupName');
    const memberSearch = $('groupMemberSearch');
    const memberList = $('groupMemberList');

    const selectedGroupMembers = new Set();

    function renderGroupMembers() {
        if (!memberList || !memberSearch) {
            return;
        }

        const keyword = memberSearch.value.trim().toLowerCase();

        memberList.replaceChildren();

        const members = chats.filter(chat =>
            !chat.isGroup &&
            chat.name.toLowerCase().includes(keyword)
        );

        if (!members.length) {
            const empty = node(
                'p',
                'no-results',
                'Belum ada anggota yang tersedia.'
            );

            empty.style.color = '#850000';

            memberList.append(empty);

            return;
        }

        for (const chat of members) {
            const label = node('label', 'group-member-item');
            const checkbox = document.createElement('input');

            checkbox.type = 'checkbox';
            checkbox.value = String(chat.id);
            checkbox.checked = selectedGroupMembers.has(chat.id);

            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    selectedGroupMembers.add(chat.id);
                } else {
                    selectedGroupMembers.delete(chat.id);
                }
            });

            label.append(
                checkbox,
                node('span', '', chat.name)
            );

            memberList.append(label);
        }
    }

    $('createGroupBtn')?.addEventListener('click', () => {
        if (
            isLoading ||
            !groupOverlay ||
            !groupForm ||
            !groupName ||
            !memberSearch
        ) {
            return;
        }

        closeMessageMenu();
        closeRoomMenu();

        selectedGroupMembers.clear();

        groupForm.reset();

        memberSearch.value = '';

        renderGroupMembers();

        groupOverlay.hidden = false;

        groupName.focus();
    });

    $('closeGroupBtn')?.addEventListener('click', () => {
        if (groupOverlay) {
            groupOverlay.hidden = true;
        }
    });

    groupOverlay?.addEventListener('click', event => {
        if (event.target === groupOverlay) {
            groupOverlay.hidden = true;
        }
    });

    memberSearch?.addEventListener('input', renderGroupMembers);

    groupForm?.addEventListener('submit', event => {
        event.preventDefault();

        const name = groupName?.value.trim();

        if (!name) {
            alert('Masukkan nama grup.');
            return;
        }

        if (!selectedGroupMembers.size) {
            alert('Pilih minimal satu anggota.');
            return;
        }

        alert(
            'Fitur pembuatan grup sedang dalam tahap integrasi database.'
        );
    });

    const roomDropdown = $('roomDropdown');
    const roomMenuButton = $('roomMenu');
    const deletePersonalChat = $('deletePersonalChat');
    const deleteConfirmOverlay = $('deleteConfirmOverlay');
    const deleteConfirmText = $('deleteConfirmText');
    const cancelDeleteBtn = $('cancelDeleteBtn');
    const confirmDeleteBtn = $('confirmDeleteBtn');

    function closeRoomMenu() {
        if (roomDropdown) {
            roomDropdown.hidden = true;
        }

        roomMenuButton?.setAttribute('aria-expanded', 'false');
    }

    roomMenuButton?.addEventListener('click', event => {
        event.stopPropagation();

        closeMessageMenu();

        const chat = selected();

        if (!chat || !roomDropdown) {
            return;
        }

        if (chat.isGroup) {
            closeRoomMenu();

            const members = (chat.members || []).map(member =>
                typeof member === 'string'
                    ? member
                    : member.name ||
                      member.fullname ||
                      member.profile_fullname ||
                      'Anggota'
            );

            alert(
                'Grup: ' +
                chat.name +
                '\n\nAnggota:\n' +
                members.join('\n')
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
        if (
            event.target instanceof Element &&
            !event.target.closest('.room-menu-wrapper')
        ) {
            closeRoomMenu();
        }
    });

    deletePersonalChat?.addEventListener('click', () => {
        const chat = selected();

        if (!chat || chat.isGroup || !deleteConfirmOverlay) {
            return;
        }

        pendingDeleteChatId = chat.id;

        if (deleteConfirmText) {
            deleteConfirmText.textContent =
                'Sembunyikan percakapan dengan ' +
                chat.name +
                ' sementara dari daftar chat? ' +
                'Pesan tetap tersimpan di database.';
        }

        closeRoomMenu();
        closeMessageMenu();

        deleteConfirmOverlay.hidden = false;
    });

    function closeDeleteModal() {
        pendingDeleteChatId = null;

        if (deleteConfirmOverlay) {
            deleteConfirmOverlay.hidden = true;
        }
    }

    cancelDeleteBtn?.addEventListener('click', closeDeleteModal);

    deleteConfirmOverlay?.addEventListener('click', event => {
        if (event.target === deleteConfirmOverlay) {
            closeDeleteModal();
        }
    });

    confirmDeleteBtn?.addEventListener('click', async () => {
        if (pendingDeleteChatId === null) {
            return;
        }

        const deletedId = pendingDeleteChatId;

        hiddenConversationIds.add(deletedId);

        chats = chats.filter(chat => chat.id !== deletedId);

        invalidateMessageRequest(deletedId);

        messageErrors.delete(deletedId);
        pendingMessageSync.delete(deletedId);

        closeDeleteModal();

        if (selectedId === deletedId) {
            selectedId = chats[0]?.id ?? null;

            updateConversationUrl(selectedId);
        }

        renderList();

        if (selectedId !== null) {
            await selectChat(selectedId);
        } else {
            messenger.classList.remove('room-open');

            renderRoom();
        }
    });

    attachButton.addEventListener('click', () => {
        const chat = selected();

        if (!chat || isLoading || isUploading) {
            return;
        }

        const conversationId = getServerConversationId(chat);

        if (!conversationId) {
            alert('Percakapan belum terhubung ke database.');
            return;
        }

        attachmentConversationId = conversationId;

        fileInput.click();
    });

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        const conversationId = attachmentConversationId;

        attachmentConversationId = null;

        if (!file || isUploading) {
            fileInput.value = '';
            return;
        }

        const chat = chats.find(
            item => getServerConversationId(item) === conversationId
        );

        if (!chat || !conversationId) {
            alert('Percakapan tidak tersedia.');

            fileInput.value = '';

            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            alert('Ukuran file maksimal 10 MB.');

            fileInput.value = '';

            return;
        }

        const extension = file.name.split('.').pop().toLowerCase();

        if (
            !ALLOWED_EXTENSIONS.includes(extension) ||
            (file.type && !ALLOWED_MIME_TYPES.includes(file.type))
        ) {
            alert('Format file harus JPG, JPEG, PNG, WEBP, atau PDF.');

            fileInput.value = '';

            return;
        }

        const formData = new FormData();

        formData.append('conversation_id', String(conversationId));
        formData.append('file', file);

        isUploading = true;

        updateInputState();

        attachButton.title = 'Mengunggah file...';

        showUploadStatus(`Mengunggah ${file.name}...`);

        try {
            const result = await request(uploadUrl, {
                method: 'POST',
                body: formData
            });

            const data = result.data || {};

            const serverMessage =
                data.message && typeof data.message === 'object'
                    ? data.message
                    : {};

            const messageId = Number(
                data.message_id ??
                data.messageId ??
                serverMessage.id
            );

            const attachmentId = Number(
                data.attachment_id ??
                data.attachmentId ??
                serverMessage.attachmentId ??
                serverMessage.attachment_id
            );

            if (!validId(messageId) || !validId(attachmentId)) {
                queueMessageSync(chat.id);

                throw new Error(
                    'Server tidak mengembalikan ID lampiran yang valid.'
                );
            }

            const type = file.type.startsWith('image/') ||
                ['jpg', 'jpeg', 'png', 'webp'].includes(extension)
                ? 'image'
                : 'file';

            upsertMessage(chat.id, {
                ...serverMessage,
                id: messageId,
                from: 'me',
                type: serverMessage.type || type,
                text: serverMessage.text || file.name,
                fileName: serverMessage.fileName || file.name,
                fileSize: serverMessage.fileSize || file.size,
                fileType: serverMessage.fileType || file.type,
                attachmentId,
                at: serverMessage.at ||
                    serverMessage.created_at ||
                    new Date().toISOString(),
                updatedAt: serverMessage.updatedAt ||
                    serverMessage.updated_at ||
                    new Date().toISOString(),
                isOwnMessage: true,
                canEdit: false,
                canDeleteForMe: true,
                canDeleteForEveryone: true
            }, {
                forceScroll: selectedId === chat.id
            });

            if (selectedId === chat.id) {
                showUploadStatus('File berhasil dikirim.');
            }

            console.info(
                '[FINS Chat] File berhasil dikirim:',
                attachmentId
            );
        } catch (error) {
            console.error('[FINS Chat] Upload:', error);

            showUploadStatus(
                error.message || 'Gagal mengunggah file.',
                true
            );
        } finally {
            isUploading = false;

            attachButton.title = 'Kirim file';

            fileInput.value = '';

            updateInputState();
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        if (isSending || isLoading) {
            return;
        }

        const message = input.value.trim();
        const chat = selected();

        if (!chat || !message) {
            return;
        }

        if (message.length > 2000) {
            alert('Pesan maksimal 2000 karakter.');
            return;
        }

        const conversationId = getServerConversationId(chat);

        if (!conversationId) {
            alert('Percakapan belum terhubung ke database.');
            return;
        }

        isSending = true;

        updateInputState();

        showUploadStatus('');

        try {
            const result = await request(
                getMessagesUrl(conversationId),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message
                    })
                }
            );

            if (
                selectedId === chat.id &&
                input.value.trim() === message
            ) {
                input.value = '';
            }

            const data = result.data?.message &&
                typeof result.data.message === 'object'
                ? result.data.message
                : result.data;

            if (validId(data?.id)) {
                upsertMessage(chat.id, {
                    ...data,
                    from: 'me',
                    type: data.type || 'text',
                    text: data.text ??
                        (typeof data.message === 'string' ? data.message : message),
                    at: data.at ||
                        data.created_at ||
                        new Date().toISOString(),
                    updatedAt: data.updatedAt ||
                        data.updated_at ||
                        new Date().toISOString(),
                    isOwnMessage: true,
                    canEdit: true,
                    canDeleteForMe: true,
                    canDeleteForEveryone: true
                }, {
                    forceScroll: selectedId === chat.id
                });
            } else {
                queueMessageSync(chat.id);
            }

            console.info('[FINS Chat] Pesan berhasil dikirim.');
        } catch (error) {
            console.error('[FINS Chat] Send message:', error);

            alert(
                error.message ||
                'Terjadi kesalahan saat mengirim pesan.'
            );
        } finally {
            isSending = false;

            updateInputState();

            if (selectedId === chat.id) {
                input.focus();
            }
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') {
            return;
        }

        closeMessageMenu();
        closeRoomMenu();
        closeDeleteModal();
        closeEditModal();
        closeDeleteMessageModal();

        if (groupOverlay) {
            groupOverlay.hidden = true;
        }
    });

    async function initChat() {
        await loadConversations();

        console.info('[FINS Chat] Halaman chat siap.');
        console.info('[FINS Chat] Realtime listener siap.');
        console.info('[FINS Chat] Edit pesan siap.');
        console.info('[FINS Chat] Hapus pesan untuk saya siap.');
        console.info('[FINS Chat] Hapus pesan untuk semua orang siap.');

        if (window.FinsRealtime?.isConnected()) {
            queueFullSync();
        }
    }

    initChat();

})();