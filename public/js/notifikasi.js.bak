
(function () {
    "use strict";

    if (window.FinsRealtime) return;

    let client = null;
    let channel = null;
    let userId = null;
    let connected = false;
    let starting = false;
    let refreshing = false;
    let refreshTimer = null;
    let retryTimer = null;
    let stopped = false;

    // ==========================================
    // 1. EVENT GLOBAL
    // ==========================================

    function emit(name, detail = {}) {
        window.dispatchEvent(
            new CustomEvent(name, { detail })
        );
    }

    function setConnected(status) {
        connected = status;

        emit("fins:realtime-status", {
            connected: status
        });
    }

    // ==========================================
    // 2. AMBIL TOKEN DARI LARAVEL
    // ==========================================

    async function getToken() {

        const response = await fetch("/realtime/token", {
            method: "GET",
            credentials: "same-origin",
            cache: "no-store",
            headers: {
                "Accept": "application/json"
            }
        });

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
            !data.user_id
        ) {
            throw new Error(
                "Konfigurasi Realtime tidak lengkap."
            );
        }

        return data;
    }

    // ==========================================
    // 3. REFRESH TOKEN OTOMATIS
    // ==========================================

    function scheduleRefresh(expiresAt) {

        clearTimeout(refreshTimer);

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
        ) return;

        refreshing = true;

        try {

            const data = await getToken();

            if (
                String(data.user_id) !== String(userId)
            ) {
                stopped = true;
                throw new Error("Akun Realtime berubah.");
            }

            if (stopped) return;

            await client.realtime.setAuth(
                data.access_token
            );

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

    // ==========================================
    // 4. PUTUSKAN CHANNEL
    // ==========================================

    async function disconnect() {

        clearTimeout(refreshTimer);

        const oldChannel = channel;

        channel = null;

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

        setConnected(false);
    }

    // ==========================================
    // 5. RECONNECT OTOMATIS
    // ==========================================

    function scheduleRetry() {

        if (stopped) return;

        clearTimeout(retryTimer);

        retryTimer = setTimeout(() => {

            start();

        }, 5000);
    }

    // ==========================================
    // 6. KONEKSI SUPABASE REALTIME
    // ==========================================

    async function start() {

        if (
            starting ||
            refreshing ||
            connected ||
            stopped
        ) return;

        starting = true;

        clearTimeout(retryTimer);

        try {

            if (!window.supabase?.createClient) {

                throw new Error(
                    "Supabase JavaScript belum dimuat."
                );
            }

            await disconnect();

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

            userId = data.user_id;

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

            activeChannel

                // ==================================
                // 7. NOTIFIKASI BARU
                // Ajakan diterima atau ditolak
                // ==================================

                .on(
                    "postgres_changes",
                    {
                        event: "INSERT",
                        schema: "public",
                        table: "notifications",
                        filter: `user_id=eq.${userId}`
                    },
                    payload => {

                        console.info(
                            "[FINS Realtime] Notifikasi baru diterima."
                        );

                        emit("fins:notification-received", {
                            notification: payload.new
                        });

                        emit("fins:notifications-updated");
                    }
                )

                // ==================================
                // 8. STATUS NOTIFIKASI DIBACA
                // ==================================

                .on(
                    "postgres_changes",
                    {
                        event: "UPDATE",
                        schema: "public",
                        table: "notifications",
                        filter: `user_id=eq.${userId}`
                    },
                    () => {

                        console.info(
                            "[FINS Realtime] Notifikasi diperbarui."
                        );

                        emit("fins:notifications-updated");
                    }
                )

                // ==================================
                // 9. UNDANGAN MASUK REALTIME
                // ==================================

                .on(
                    "postgres_changes",
                    {
                        event: "INSERT",
                        schema: "public",
                        table: "partner_invitations",
                        filter: `receiver_id=eq.${userId}`
                    },
                    payload => {

                        console.info(
                            "[FINS Realtime] Undangan kolaborasi baru masuk."
                        );

                        emit("fins:invitation-received", {
                            invitation: payload.new
                        });

                        emit("fins:invitation-updated", {
                            invitationId: payload.new.id,
                            status: payload.new.status
                        });
                    }
                )

                // ==================================
                // 10. PERUBAHAN UNDANGAN PENERIMA
                // ==================================

                .on(
                    "postgres_changes",
                    {
                        event: "UPDATE",
                        schema: "public",
                        table: "partner_invitations",
                        filter: `receiver_id=eq.${userId}`
                    },
                    payload => {

                        console.info(
                            "[FINS Realtime] Undangan masuk diperbarui."
                        );

                        emit("fins:invitation-updated", {
                            invitationId: payload.new.id,
                            status: payload.new.status
                        });
                    }
                )

                // ==================================
                // 11. PERUBAHAN UNDANGAN PENGIRIM
                // ==================================

                .on(
                    "postgres_changes",
                    {
                        event: "UPDATE",
                        schema: "public",
                        table: "partner_invitations",
                        filter: `sender_id=eq.${userId}`
                    },
                    payload => {

                        console.info(
                            "[FINS Realtime] Status ajakan berubah:",
                            payload.new.status
                        );

                        emit("fins:sent-invitation-updated", {
                            invitationId: payload.new.id,
                            status: payload.new.status,
                            invitation: payload.new
                        });
                    }
                )

                // ==================================
                // 12. STATUS SUBSCRIPTION
                // ==================================

                .subscribe(status => {

                    if (
                        channel !== activeChannel ||
                        stopped
                    ) return;

                    console.info(
                        "[FINS Realtime]",
                        status
                    );

                    if (status === "SUBSCRIBED") {

                        clearTimeout(retryTimer);

                        setConnected(true);

                        // Sinkronisasi setelah terhubung.
                        emit("fins:notifications-updated");
                    }

                    if (
                        status === "CHANNEL_ERROR" ||
                        status === "TIMED_OUT" ||
                        status === "CLOSED"
                    ) {

                        setConnected(false);

                        scheduleRetry();
                    }
                });

            scheduleRefresh(data.expires_at);

        } catch (error) {

            console.warn(
                "[FINS Realtime]",
                error.message
            );

            setConnected(false);

            if (!stopped) {

                scheduleRetry();
            }

        } finally {

            starting = false;
        }
    }

    // ==========================================
    // 13. API GLOBAL FINS
    // ==========================================

    window.FinsRealtime = {

        start,

        isConnected() {

            return connected;
        },

        getUserId() {

            return userId;
        },

        async stop() {

            stopped = true;

            clearTimeout(retryTimer);

            clearTimeout(refreshTimer);

            await disconnect();

            if (client) {

                client.realtime.disconnect();
            }
        }
    };

    // ==========================================
    // 14. EVENT KONEKSI BROWSER
    // ==========================================

    window.addEventListener("online", () => {

        if (!connected) {

            start();

        } else {

            emit("fins:notifications-updated");
        }
    });

    window.addEventListener("offline", () => {

        setConnected(false);
    });

    document.addEventListener(
        "visibilitychange",
        () => {

            if (!document.hidden) {

                if (!connected) {

                    start();
                }

                emit("fins:notifications-updated");
            }
        }
    );

    // ==========================================
    // 15. INITIAL LOAD
    // ==========================================

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
