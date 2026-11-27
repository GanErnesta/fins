(function () {
    "use strict";

    if (window.FinsRealtime) return;

    let client = null;
    let channel = null;
    let userId = null;
    let connected = false;
    let starting = false;
    let refreshing = false;
    let stopped = false;

    let refreshTimer = null;
    let retryTimer = null;
    let syncTimer = null;
    let notificationTimer = null;
    let lastSyncAt = 0;

    const RETRY_DELAY = 2000;
    const SYNC_DELAY = 100;
    const SYNC_COOLDOWN = 1000;
    const EVENT_TTL = 60000;
    const MAX_CACHE_SIZE = 2000;

    const processedEvents = new Map();
    const messageCache = new Map();
    const conversationTimers = new Map();
    const deletedMessageIds = new Set();

    function emit(name, detail = {}) {
        if (stopped) return;

        window.dispatchEvent(
            new CustomEvent(name, { detail })
        );
    }

    function setConnected(status) {
        if (connected === status) return;

        connected = status;

        emit("fins:realtime-status", {
            connected: status
        });
    }

    function validId(value) {
        const id = Number(value);

        return Number.isSafeInteger(id) && id > 0;
    }

    function isActive(activeChannel) {
        return !stopped && channel === activeChannel;
    }

    function isChatPage() {
        return Boolean(
            document.getElementById("conversationList") &&
            document.getElementById("messagesArea")
        );
    }

    function isDuplicate(key) {
        const now = Date.now();
        const previous = processedEvents.get(key);

        if (previous !== undefined && now - previous < EVENT_TTL) {
            return true;
        }

        processedEvents.delete(key);
        processedEvents.set(key, now);

        while (processedEvents.size > MAX_CACHE_SIZE) {
            processedEvents.delete(
                processedEvents.keys().next().value
            );
        }

        return false;
    }

    function rememberMessage(message) {
        const id = Number(message.id);
        const conversationId = Number(
            message.conversation_id ?? message.conversationId
        );

        if (!validId(id) || !validId(conversationId)) {
            return;
        }

        if (deletedMessageIds.has(id)) {
            return;
        }

        messageCache.delete(id);

        messageCache.set(id, {
            conversationId,
            senderId: message.sender_id ?? message.senderId,
            type: message.type,
            createdAt: message.created_at ?? message.at
        });

        while (messageCache.size > MAX_CACHE_SIZE) {
            messageCache.delete(
                messageCache.keys().next().value
            );
        }
    }

    function rememberDeletedMessage(messageId) {
        if (!validId(messageId)) return;

        const id = Number(messageId);

        deletedMessageIds.add(id);
        messageCache.delete(id);
    }

    function isDeletedMessage(messageId) {
        return deletedMessageIds.has(Number(messageId));
    }

    function resolveConversationId(messageId) {
        const cached = messageCache.get(Number(messageId));

        if (validId(cached?.conversationId)) {
            return cached.conversationId;
        }

        return null;
    }

    function scheduleNotificationSync() {
        if (stopped || notificationTimer !== null) {
            return;
        }

        notificationTimer = setTimeout(() => {
            notificationTimer = null;

            emit("fins:notifications-updated");

        }, 150);
    }

    function syncAll(reason = "manual") {
        if (stopped || !connected) return;

        scheduleNotificationSync();

        if (!isChatPage() || syncTimer !== null) {
            return;
        }

        const elapsed = Date.now() - lastSyncAt;

        const delay = Math.max(
            SYNC_DELAY,
            SYNC_COOLDOWN - elapsed
        );

        syncTimer = setTimeout(() => {
            syncTimer = null;

            if (stopped || !connected) return;

            lastSyncAt = Date.now();

            emit("fins:chat-sync-required", {
                reason
            });

        }, delay);
    }

    function emitConversationUpdated(
        conversationId,
        reason
    ) {
        if (
            stopped ||
            !isChatPage() ||
            !validId(conversationId)
        ) {
            return;
        }

        const id = Number(conversationId);

        if (conversationTimers.has(id)) {
            return;
        }

        const timer = setTimeout(() => {
            conversationTimers.delete(id);

            if (stopped) return;

            emit("fins:conversations-updated", {
                conversationId: id,
                reason
            });

        }, 50);

        conversationTimers.set(id, timer);
    }

    function emitMessageDeleted(
        messageId,
        conversationId,
        scope,
        deletedAt = null
    ) {
        if (
            stopped ||
            !isChatPage() ||
            !validId(messageId)
        ) {
            return;
        }

        const id = Number(messageId);

        if (isDeletedMessage(id)) {
            return;
        }

        const resolvedConversationId =
            validId(conversationId)
                ? Number(conversationId)
                : resolveConversationId(id);

        rememberDeletedMessage(id);

        emit("fins:message-deleted", {
            id,
            messageId: id,
            conversationId: resolvedConversationId,
            scope,
            userId: scope === "me" ? userId : null,
            deletedAt: deletedAt ?? new Date().toISOString(),
            deleted: true
        });

        console.info(
            "[FINS Realtime] Pesan dihapus:",
            id,
            scope
        );
    }

    async function getToken() {
        const controller = new AbortController();

        const timeout = setTimeout(() => {
            controller.abort();
        }, 20000);

        try {
            const response = await fetch(
                "/realtime/token",
                {
                    method: "GET",
                    credentials: "same-origin",
                    cache: "no-store",
                    signal: controller.signal,
                    headers: {
                        Accept: "application/json"
                    }
                }
            );

            if (response.status === 401) {
                stopped = true;

                throw new Error("Silakan login ulang.");
            }

            if (!response.ok) {
                throw new Error(
                    `Realtime token HTTP ${response.status}`
                );
            }

            const data = await response.json();

            if (
                !data.access_token ||
                !data.supabase_url ||
                !data.supabase_public_key ||
                !validId(data.user_id) ||
                !Number.isFinite(Number(data.expires_at)) ||
                Number(data.expires_at) <= 0
            ) {
                throw new Error(
                    "Konfigurasi Realtime tidak lengkap."
                );
            }

            return data;

        } finally {
            clearTimeout(timeout);
        }
    }

    function scheduleRefresh(expiresAt) {
        clearTimeout(refreshTimer);

        if (stopped) return;

        const delay = Math.max(
            1000,
            Number(expiresAt) * 1000 - Date.now() + 1000
        );

        refreshTimer = setTimeout(
            refreshToken,
            delay
        );
    }

    async function refreshToken() {
        if (
            stopped ||
            !client ||
            refreshing ||
            starting
        ) {
            return;
        }

        refreshing = true;

        try {
            const data = await getToken();

            if (
                String(data.user_id) !== String(userId)
            ) {
                stopped = true;

                throw new Error(
                    "Akun Realtime berubah."
                );
            }

            if (stopped) return;

            await client.realtime.setAuth(
                data.access_token
            );

            if (stopped) return;

            scheduleRefresh(data.expires_at);

        } catch (error) {
            console.warn(
                "[FINS Realtime] Refresh token gagal:",
                error.message
            );

            await disconnect();

            if (!stopped) {
                scheduleRetry();
            }

        } finally {
            refreshing = false;
        }
    }

    function clearSyncTimers() {
        clearTimeout(syncTimer);
        clearTimeout(notificationTimer);

        syncTimer = null;
        notificationTimer = null;

        for (const timer of conversationTimers.values()) {
            clearTimeout(timer);
        }

        conversationTimers.clear();
    }

    async function disconnect() {
        clearTimeout(refreshTimer);

        refreshTimer = null;

        clearSyncTimers();

        const oldChannel = channel;

        channel = null;

        setConnected(false);

        if (client && oldChannel) {
            try {
                await client.removeChannel(oldChannel);

            } catch (error) {
                console.warn(
                    "[FINS Realtime] Disconnect:",
                    error.message
                );
            }
        }

        processedEvents.clear();
    }

    function scheduleRetry() {
        if (stopped || retryTimer !== null) {
            return;
        }

        retryTimer = setTimeout(() => {
            retryTimer = null;

            if (stopped || connected) {
                return;
            }

            if (
                !navigator.onLine ||
                starting ||
                refreshing
            ) {
                scheduleRetry();
                return;
            }

            start();

        }, RETRY_DELAY);
    }

    function listen(
        activeChannel,
        event,
        table,
        callback,
        filter = null
    ) {
        const options = {
            event,
            schema: "public",
            table
        };

        if (filter) {
            options.filter = filter;
        }

        activeChannel.on(
            "postgres_changes",
            options,
            payload => {
                if (!isActive(activeChannel)) {
                    return;
                }

                if (!payload.new) {
                    return;
                }

                try {
                    callback(payload.new, payload);

                } catch (error) {
                    console.warn(
                        "[FINS Realtime] Event gagal:",
                        table,
                        error.message
                    );

                    syncAll("event-recovery");
                }
            }
        );
    }

    function registerNotificationListeners(activeChannel) {
        listen(
            activeChannel,
            "INSERT",
            "notifications",
            notification => {
                if (
                    isDuplicate(
                        `notification:insert:${notification.id}`
                    )
                ) {
                    return;
                }

                emit("fins:notification-received", {
                    notification
                });

                scheduleNotificationSync();
            },
            `user_id=eq.${userId}`
        );

        listen(
            activeChannel,
            "UPDATE",
            "notifications",
            () => {
                scheduleNotificationSync();
            },
            `user_id=eq.${userId}`
        );
    }

    function registerInvitationListeners(activeChannel) {
        listen(
            activeChannel,
            "INSERT",
            "partner_invitations",
            invitation => {
                if (
                    isDuplicate(
                        `invitation:insert:${invitation.id}`
                    )
                ) {
                    return;
                }

                emit("fins:invitation-received", {
                    invitation
                });

                emit("fins:invitation-updated", {
                    invitationId: invitation.id,
                    status: invitation.status,
                    conversationId:
                        invitation.conversation_id ?? null
                });
            },
            `receiver_id=eq.${userId}`
        );

        listen(
            activeChannel,
            "UPDATE",
            "partner_invitations",
            invitation => {
                emit("fins:invitation-updated", {
                    invitationId: invitation.id,
                    status: invitation.status,
                    conversationId:
                        invitation.conversation_id ?? null
                });

                if (
                    invitation.status === "accepted" &&
                    validId(invitation.conversation_id)
                ) {
                    emitConversationUpdated(
                        invitation.conversation_id,
                        "invitation-accepted"
                    );
                }
            },
            `receiver_id=eq.${userId}`
        );

        listen(
            activeChannel,
            "UPDATE",
            "partner_invitations",
            invitation => {
                emit("fins:sent-invitation-updated", {
                    invitationId: invitation.id,
                    status: invitation.status,
                    conversationId:
                        invitation.conversation_id ?? null,
                    invitation
                });

                if (
                    invitation.status === "accepted" &&
                    validId(invitation.conversation_id)
                ) {
                    emitConversationUpdated(
                        invitation.conversation_id,
                        "invitation-accepted"
                    );
                }
            },
            `sender_id=eq.${userId}`
        );
    }

    function registerConversationListeners(activeChannel) {
        listen(
            activeChannel,
            "INSERT",
            "conversation_members",
            member => {
                if (
                    String(member.user_id) !== String(userId) ||
                    !validId(member.conversation_id)
                ) {
                    return;
                }

                if (
                    isDuplicate(
                        `member:insert:${member.id}`
                    )
                ) {
                    return;
                }

                const conversationId = Number(
                    member.conversation_id
                );

                emit("fins:conversation-member-added", {
                    conversationId,
                    memberId: member.id,
                    userId: member.user_id
                });

                emitConversationUpdated(
                    conversationId,
                    "member-added"
                );
            },
            `user_id=eq.${userId}`
        );

        listen(
            activeChannel,
            "INSERT",
            "conversations",
            conversation => {
                if (!validId(conversation.id)) {
                    return;
                }

                if (
                    isDuplicate(
                        `conversation:insert:${conversation.id}`
                    )
                ) {
                    return;
                }

                emit("fins:conversation-created", {
                    conversationId: Number(conversation.id),
                    type: conversation.type
                });

                emitConversationUpdated(
                    conversation.id,
                    "conversation-created"
                );
            }
        );

        listen(
            activeChannel,
            "UPDATE",
            "conversations",
            conversation => {
                if (!validId(conversation.id)) {
                    return;
                }

                emitConversationUpdated(
                    conversation.id,
                    "conversation-updated"
                );
            }
        );
    }

    function normalizeMessage(message) {
        const type = message.type || "text";

        const isSystem = type === "system";

        const isOwnMessage =
            !isSystem &&
            message.sender_id !== null &&
            String(message.sender_id) === String(userId);

        const isAttachment =
            type === "image" ||
            type === "file";

        const editedAt = message.edited_at ?? null;
        const deletedAt = message.deleted_at ?? null;

        return {
            id: Number(message.id),

            conversationId: Number(
                message.conversation_id
            ),

            senderId: message.sender_id,

            from: isSystem
                ? "system"
                : isOwnMessage
                    ? "me"
                    : "them",

            type,

            text: deletedAt
                ? ""
                : message.message ?? "",

            fileName:
                isAttachment && !deletedAt
                    ? message.message
                    : null,

            fileSize: null,
            fileType: null,
            attachmentId: null,
            downloadUrl: null,

            replyToId:
                message.reply_to_id ?? null,

            at: message.created_at,
            updatedAt: message.updated_at,
            editedAt,
            deletedAt,

            isEdited: editedAt !== null,
            isDeleted: deletedAt !== null,
            isOwnMessage,

            canEdit:
                isOwnMessage &&
                type === "text" &&
                !deletedAt,

            canDeleteForMe:
                !isSystem &&
                !deletedAt,

            canDeleteForEveryone:
                isOwnMessage &&
                !deletedAt,

            attachmentPending:
                isAttachment &&
                !deletedAt
        };
    }

    function registerMessageListeners(activeChannel) {
        listen(
            activeChannel,
            "INSERT",
            "messages",
            message => {
                if (
                    !validId(message.id) ||
                    !validId(message.conversation_id) ||
                    message.deleted_at ||
                    isDeletedMessage(message.id)
                ) {
                    return;
                }

                if (
                    isDuplicate(
                        `message:insert:${message.id}`
                    )
                ) {
                    return;
                }

                rememberMessage(message);

                const normalized = normalizeMessage(message);

                emit("fins:message-received", {
                    messageId: normalized.id,
                    conversationId: normalized.conversationId,
                    senderId: normalized.senderId,
                    type: normalized.type,
                    isOwnMessage: normalized.isOwnMessage,
                    createdAt: normalized.at,
                    message: normalized
                });

                console.info(
                    "[FINS Realtime] Pesan diterima:",
                    normalized.id
                );
            }
        );

        listen(
            activeChannel,
            "UPDATE",
            "messages",
            message => {
                if (
                    !validId(message.id) ||
                    !validId(message.conversation_id)
                ) {
                    return;
                }

                if (message.deleted_at) {
                    emitMessageDeleted(
                        message.id,
                        message.conversation_id,
                        "everyone",
                        message.deleted_at
                    );

                    return;
                }

                if (isDeletedMessage(message.id)) {
                    return;
                }

                if (
                    isDuplicate(
                        `message:update:${message.id}:${message.updated_at}:${message.edited_at}:${message.message}`
                    )
                ) {
                    return;
                }

                rememberMessage(message);

                const normalized = normalizeMessage(message);

                emit("fins:message-updated", {
                    messageId: normalized.id,
                    conversationId: normalized.conversationId,
                    message: normalized
                });

                console.info(
                    "[FINS Realtime] Pesan diperbarui:",
                    normalized.id
                );
            }
        );
    }

    function registerMessageDeletionListeners(activeChannel) {
        listen(
            activeChannel,
            "INSERT",
            "message_deletions",
            deletion => {
                if (
                    !validId(deletion.message_id) ||
                    String(deletion.user_id) !== String(userId)
                ) {
                    return;
                }

                const messageId = Number(
                    deletion.message_id
                );

                if (isDeletedMessage(messageId)) {
                    return;
                }

                emitMessageDeleted(
                    messageId,
                    deletion.conversation_id ??
                        resolveConversationId(messageId),
                    "me",
                    deletion.deleted_at
                );
            },
            `user_id=eq.${userId}`
        );
    }

    function normalizeAttachment(attachment) {
        const messageId = Number(
            attachment.message_id
        );

        const cachedMessage = messageCache.get(messageId);

        const attachmentId = Number(attachment.id);

        return {
            id: attachmentId,
            attachmentId,
            messageId,

            conversationId:
                cachedMessage?.conversationId ?? null,

            senderId:
                cachedMessage?.senderId ?? null,

            fileName:
                attachment.file_name ?? "Lampiran",

            fileSize:
                Number(attachment.file_size) || 0,

            fileType:
                attachment.file_type ?? null,

            downloadUrl:
                `/chat/attachments/${attachmentId}/download`,

            createdAt:
                attachment.created_at ?? null,

            attachmentPending: false
        };
    }

    function registerAttachmentListeners(activeChannel) {
        listen(
            activeChannel,
            "INSERT",
            "message_attachments",
            attachment => {
                if (
                    !validId(attachment.id) ||
                    !validId(attachment.message_id) ||
                    isDeletedMessage(attachment.message_id)
                ) {
                    return;
                }

                if (
                    isDuplicate(
                        `attachment:insert:${attachment.id}`
                    )
                ) {
                    return;
                }

                const normalized = normalizeAttachment(
                    attachment
                );

                emit("fins:attachment-received", {
                    attachmentId: normalized.attachmentId,
                    messageId: normalized.messageId,
                    conversationId: normalized.conversationId,
                    attachment: normalized
                });

                console.info(
                    "[FINS Realtime] Lampiran diterima:",
                    normalized.attachmentId
                );
            }
        );

        listen(
            activeChannel,
            "UPDATE",
            "message_attachments",
            attachment => {
                if (
                    !validId(attachment.id) ||
                    !validId(attachment.message_id) ||
                    isDeletedMessage(attachment.message_id)
                ) {
                    return;
                }

                const normalized = normalizeAttachment(
                    attachment
                );

                emit("fins:attachment-updated", {
                    attachmentId: normalized.attachmentId,
                    messageId: normalized.messageId,
                    conversationId: normalized.conversationId,
                    attachment: normalized
                });
            }
        );
    }

    function registerChatListeners(activeChannel) {
        if (!isChatPage()) return;

        registerConversationListeners(activeChannel);
        registerMessageListeners(activeChannel);
        registerMessageDeletionListeners(activeChannel);
        registerAttachmentListeners(activeChannel);

        console.info(
            "[FINS Realtime] Listener Chat aktif."
        );
    }

    function subscribe(activeChannel) {
        activeChannel.subscribe((status, error) => {
            if (!isActive(activeChannel)) {
                return;
            }

            console.info(
                "[FINS Realtime]",
                status
            );

            if (status === "SUBSCRIBED") {
                const wasConnected = connected;

                clearTimeout(retryTimer);

                retryTimer = null;

                setConnected(true);

                if (!wasConnected) {
                    scheduleNotificationSync();
                }

                return;
            }

            if (
                status === "CHANNEL_ERROR" ||
                status === "TIMED_OUT" ||
                status === "CLOSED"
            ) {
                if (error) {
                    console.warn(
                        "[FINS Realtime] Subscription:",
                        error.message || status
                    );
                }

                setConnected(false);

                clearSyncTimers();

                scheduleRetry();
            }
        });
    }

    async function start() {
        if (
            starting ||
            refreshing ||
            connected ||
            stopped
        ) {
            return;
        }

        if (!navigator.onLine) {
            scheduleRetry();
            return;
        }

        starting = true;

        clearTimeout(retryTimer);

        retryTimer = null;

        try {
            if (!window.supabase?.createClient) {
                throw new Error(
                    "Supabase JavaScript belum dimuat."
                );
            }

            await disconnect();

            if (stopped) return;

            const data = await getToken();

            if (stopped) return;

            if (
                userId !== null &&
                String(data.user_id) !== String(userId)
            ) {
                stopped = true;

                throw new Error(
                    "Akun Realtime berubah."
                );
            }

            userId = Number(data.user_id);

            if (!client) {
                client = window.supabase.createClient(
                    data.supabase_url,
                    data.supabase_public_key,
                    {
                        auth: {
                            persistSession: false,
                            autoRefreshToken: false,
                            detectSessionInUrl: false
                        }
                    }
                );
            }

            await client.realtime.setAuth(
                data.access_token
            );

            if (stopped) return;

            const activeChannel = client.channel(
                `fins-realtime-${userId}`
            );

            channel = activeChannel;

            registerNotificationListeners(activeChannel);
            registerInvitationListeners(activeChannel);
            registerChatListeners(activeChannel);

            subscribe(activeChannel);

            scheduleRefresh(data.expires_at);

        } catch (error) {
            console.warn(
                "[FINS Realtime]",
                error.message
            );

            await disconnect();

            if (!stopped) {
                scheduleRetry();
            }

        } finally {
            starting = false;
        }
    }

    window.FinsRealtime = {
        start,

        isConnected() {
            return connected;
        },

        getUserId() {
            return userId;
        },

        rememberMessage(message) {
            if (message && typeof message === "object") {
                rememberMessage(message);
            }
        },

        isMessageDeleted(messageId) {
            return isDeletedMessage(messageId);
        },

        markMessageDeleted(
            messageId,
            conversationId = null,
            scope = "me",
            deletedAt = null
        ) {
            if (
                stopped ||
                !validId(messageId) ||
                !["me", "everyone"].includes(scope)
            ) {
                return;
            }

            emitMessageDeleted(
                messageId,
                conversationId,
                scope,
                deletedAt
            );
        },

        sync() {
            if (!stopped) {
                syncAll("manual");
            }
        },

        async stop() {
            stopped = true;

            clearTimeout(retryTimer);
            clearTimeout(refreshTimer);

            retryTimer = null;
            refreshTimer = null;

            clearSyncTimers();

            await disconnect();

            if (client) {
                client.realtime.disconnect();
            }

            messageCache.clear();
            processedEvents.clear();
            deletedMessageIds.clear();
        }
    };

    window.addEventListener("online", () => {
        if (stopped) return;

        console.info(
            "[FINS Realtime] Internet terhubung."
        );

        if (!connected) {
            start();
        } else {
            syncAll("online");
        }
    });

    window.addEventListener("offline", () => {
        if (stopped) return;

        console.warn(
            "[FINS Realtime] Internet terputus."
        );

        setConnected(false);

        clearSyncTimers();
    });

    document.addEventListener(
        "visibilitychange",
        () => {
            if (document.hidden || stopped) {
                return;
            }

            if (!connected) {
                start();
                return;
            }

            scheduleNotificationSync();
        }
    );

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            start,
            { once: true }
        );
    } else {
        start();
    }

})();