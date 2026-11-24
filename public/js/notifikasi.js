
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    // ==========================================
    // 1. KONFIGURASI
    // ==========================================

    const userId = document.body.dataset.notificationUser;

    const READ_KEY = `fins_invitation_read_v1_${userId}`;

    const API = {
        notifications: "/notifications",
        received: "/partner-invitations/received",

        read: id =>
            `/notifications/${encodeURIComponent(id)}/read`,

        readAll: "/notifications/read-all",

        respond: id =>
            `/partner-invitations/${encodeURIComponent(id)}/respond`
    };

    const csrf = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content;

    const $ = id => document.getElementById(id);

    let notifications = [];
    let invitations = [];

    let kategoriAktif = "semua";
    let selectedInvitation = null;
    let sedangMerespons = false;

    let loading = false;
    let loaded = false;
    let pendingReload = false;
    let reloadTimer = null;
    let loadError = false;

    // ==========================================
    // 2. HELPER
    // ==========================================

    function aman(value) {
        return String(value ?? "").replace(
            /[&<>"']/g,
            karakter => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;"
            })[karakter]
        );
    }

    function ambilArray(key) {
        try {
            const data = JSON.parse(
                localStorage.getItem(key) || "[]"
            );

            return Array.isArray(data) ? data : [];

        } catch {
            return [];
        }
    }

    function simpanArray(key, data) {
        try {
            localStorage.setItem(
                key,
                JSON.stringify(data)
            );
        } catch (error) {
            console.warn(
                "[FINS Notifications] Storage:",
                error.message
            );
        }
    }

    let dibacaUndangan = ambilArray(READ_KEY);

    function emit(name, detail = {}) {
        window.dispatchEvent(
            new CustomEvent(name, { detail })
        );
    }

    function formatWaktu(value) {
        if (!value) return "Baru saja";

        const tanggal = new Date(
            String(value).replace(" ", "T")
        );

        if (Number.isNaN(tanggal.getTime())) {
            return "Baru saja";
        }

        return tanggal.toLocaleString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function waktuSort(value) {
        return Date.parse(
            String(value || "").replace(" ", "T")
        ) || 0;
    }

    async function request(url, options = {}) {
        const method = options.method || "GET";

        const headers = {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            ...(options.headers || {})
        };

        if (method !== "GET") {
            if (!csrf) {
                throw new Error(
                    "Token CSRF tidak ditemukan."
                );
            }

            headers["X-CSRF-TOKEN"] = csrf;
        }

        if (options.body) {
            headers["Content-Type"] = "application/json";
        }

        const response = await fetch(url, {
            ...options,
            method,
            credentials: "same-origin",
            cache: "no-store",
            headers
        });

        let result;

        try {
            result = await response.json();
        } catch {
            throw new Error(
                `Respons API tidak valid (${response.status}).`
            );
        }

        if (!response.ok || result.success === false) {
            throw new Error(
                result.message ||
                `Permintaan gagal (${response.status}).`
            );
        }

        return result;
    }

    // ==========================================
    // 3. GABUNGKAN DATA NOTIFIKASI
    // ==========================================

    function semuaNotifikasi() {

        const dariUndangan = invitations

            .filter(item => item.status === "pending")

            .map(item => ({

                id: `inv-${item.id}`,

                invitationId: item.id,

                sumber: "partner_invitation",

                kategori: "undangan",

                judul: "Ajakan Kolaborasi",

                pesan:
                    `${item.sender_name || "Pengguna FINS"} ` +
                    `mengajakmu berkolaborasi dalam lomba ` +
                    `${item.competition_name || "kompetisi"}.`,

                waktu: formatWaktu(item.created_at),

                created_at: item.created_at,

                dibaca: dibacaUndangan.includes(
                    String(item.id)
                ),

                status: item.status,

                sender_name: item.sender_name,

                competition_name: item.competition_name,

                role: item.role,

                message: item.message

            }));

        const dariServer = notifications.map(item => {

            let kategori = "sistem";

            const type = String(item.type || "");

            if (type.startsWith("invitation_")) {
                kategori = "undangan";
            } else if (type.startsWith("chat_")) {
                kategori = "chat";
            } else if (type.startsWith("application_")) {
                kategori = "lamaran";
            } else if (type.startsWith("deadline_")) {
                kategori = "deadline";
            }

            return {

                id: `notif-${item.id}`,

                serverId: item.id,

                invitationId: item.invitation_id,

                sumber: "server",

                kategori,

                judul: item.title,

                pesan: item.message,

                waktu: formatWaktu(item.created_at),

                created_at: item.created_at,

                dibaca: item.is_read === true,

                type: item.type,

                actor_name: item.actor_name,

                actor_fullname: item.actor_fullname,

                actor_photo_path: item.actor_photo_path

            };
        });

        return [
            ...dariUndangan,
            ...dariServer
        ].sort((a, b) => {

            return waktuSort(b.created_at) -
                waktuSort(a.created_at);

        });
    }

    function cariNotifikasi(id) {
        return semuaNotifikasi().find(
            item => String(item.id) === String(id)
        );
    }

    function hitungBelumDibaca() {
        return semuaNotifikasi().filter(
            item => !item.dibaca
        ).length;
    }

    // ==========================================
    // 4. AMBIL DATA API LARAVEL
    // ==========================================

    async function loadNotifications() {

        const result = await request(
            API.notifications
        );

        if (!Array.isArray(result.notifications)) {
            throw new Error(
                "Format data notifikasi tidak valid."
            );
        }

        notifications = result.notifications;
    }

    async function loadInvitations() {

        const result = await request(
            API.received
        );

        if (!Array.isArray(result.data)) {
            throw new Error(
                "Format data undangan tidak valid."
            );
        }

        invitations = result.data;
    }

    async function loadAll() {

        if (loading) {
            pendingReload = true;
            return;
        }

        loading = true;
        loadError = false;

        try {

            const results = await Promise.allSettled([
                loadNotifications(),
                loadInvitations()
            ]);

            results.forEach(result => {

                if (result.status === "rejected") {

                    loadError = true;

                    console.error(
                        "[FINS Notifications]",
                        result.reason
                    );
                }
            });

            loaded = true;

            render();

            console.info(
                "[FINS Notifications] Data diperbarui."
            );

        } finally {

            loading = false;

            if (pendingReload) {

                pendingReload = false;

                jadwalkanReload();
            }
        }
    }

    function jadwalkanReload() {

        clearTimeout(reloadTimer);

        reloadTimer = setTimeout(() => {

            loadAll();

        }, 100);
    }

    // ==========================================
    // 5. FILTER NOTIFIKASI
    // ==========================================

    function filterNotifikasi() {

        const pencarian = (
            $("notificationSearch")?.value || ""
        ).toLowerCase().trim();

        const hanyaBelumDibaca =
            $("unreadOnly")?.checked || false;

        return semuaNotifikasi().filter(item => {

            const cocokKategori =
                kategoriAktif === "semua" ||
                item.kategori === kategoriAktif;

            const cocokPencarian =
                `${item.judul} ${item.pesan}`
                    .toLowerCase()
                    .includes(pencarian);

            const cocokStatus =
                !hanyaBelumDibaca ||
                !item.dibaca;

            return cocokKategori &&
                cocokPencarian &&
                cocokStatus;
        });
    }

    // ==========================================
    // 6. RENDER STATISTIK
    // ==========================================

    function renderStatistik() {

        const data = semuaNotifikasi();

        const statistik = {

            unreadCount: data.filter(
                item => !item.dibaca
            ).length,

            totalCount: data.length,

            applicationCount: data.filter(
                item => [
                    "lamaran",
                    "undangan"
                ].includes(item.kategori)
            ).length,

            deadlineCount: data.filter(
                item => [
                    "deadline",
                    "sistem"
                ].includes(item.kategori)
            ).length
        };

        Object.entries(statistik).forEach(
            ([id, jumlah]) => {

                const element = $(id);

                if (element) {
                    element.textContent = jumlah;
                }
            }
        );

        const popupCount = $("popupUnreadCount");

        if (popupCount) {

            popupCount.textContent =
                `${statistik.unreadCount} belum dibaca`;
        }
    }

    // ==========================================
    // 7. RENDER HALAMAN NOTIFIKASI
    // ==========================================

    function renderHalaman() {

        const daftar = $("notificationList");
        const empty = $("notificationEmpty");

        if (!daftar) return;

        if (!loaded) {

            if (empty) empty.hidden = true;

            daftar.innerHTML = `
                <div class="empty-state">
                    <p>Memuat notifikasi...</p>
                </div>
            `;

            return;
        }

        const hasil = filterNotifikasi();

        if (hasil.length === 0) {

            daftar.innerHTML = "";

            if (empty) {

                empty.hidden = false;

                const title = empty.querySelector("h3");
                const description = empty.querySelector("p");

                if (title) {
                    title.textContent = loadError
                        ? "Gagal memuat notifikasi"
                        : "Belum ada notifikasi";
                }

                if (description) {
                    description.textContent = loadError
                        ? "Data akan dicoba dimuat kembali."
                        : "Tidak ada notifikasi yang sesuai.";
                }
            }

            return;
        }

        if (empty) {
            empty.hidden = true;
        }

        daftar.innerHTML = hasil.map(item => {

            let statusLabel = "";

            if (item.type === "invitation_accepted") {

                statusLabel = `
                    <span class="invite-accepted-label">
                        Diterima
                    </span>
                `;

            } else if (
                item.type === "invitation_rejected"
            ) {

                statusLabel = `
                    <span class="invite-rejected-label">
                        Ditolak
                    </span>
                `;

            } else if (
                item.sumber === "partner_invitation"
            ) {

                statusLabel = `
                    <span style="
                        color:#b86b16;
                        font-size:12px;
                        font-weight:600;
                    ">
                        Menunggu Respons
                    </span>
                `;
            }

            return `

                <article class="notification-card
                    ${item.dibaca ? "" : "unread"}">

                    <div style="
                        display:flex;
                        justify-content:space-between;
                        align-items:flex-start;
                        gap:15px;
                    ">

                        <div>

                            <small>
                                ${aman(
                                    item.kategori.toUpperCase()
                                )}
                            </small>

                            ${!item.dibaca
                                ? "<small> • Baru</small>"
                                : ""
                            }

                            <h3>
                                ${aman(item.judul)}
                            </h3>

                            ${statusLabel}

                        </div>

                        <small>
                            ${aman(item.waktu)}
                        </small>

                    </div>

                    <p>
                        ${aman(item.pesan)}
                    </p>

                    <div style="
                        display:flex;
                        gap:12px;
                        flex-wrap:wrap;
                        align-items:center;
                        margin-top:20px;
                    ">

                        <button
                            type="button"
                            class="primary-button"
                            data-action="open"
                            data-id="${aman(item.id)}">

                            ${item.sumber === "partner_invitation"
                                ? "Lihat Undangan"
                                : "Lihat Detail"
                            }

                        </button>

                        ${!item.dibaca ? `

                            <button
                                type="button"
                                data-action="read"
                                data-id="${aman(item.id)}">

                                Tandai Dibaca

                            </button>

                        ` : ""}

                    </div>

                </article>
            `;

        }).join("");
    }

    // ==========================================
    // 8. RENDER POPUP
    // ==========================================

    function renderPopup() {

        const daftar = $("popupNotificationList");

        if (!daftar) return;

        const terbaru = semuaNotifikasi().slice(0, 5);

        if (!terbaru.length) {

            daftar.innerHTML = `
                <div class="empty-state">
                    ${loaded
                        ? "Belum ada notifikasi."
                        : "Memuat notifikasi..."
                    }
                </div>
            `;

            return;
        }

        daftar.innerHTML = terbaru.map(item => `

            <div class="popup-item
                ${item.dibaca ? "" : "unread"}">

                <strong>
                    ${aman(item.judul)}
                </strong>

                <p>
                    ${aman(item.pesan)}
                </p>

                <small>
                    ${aman(item.waktu)}
                </small>

                <div style="margin-top:10px;">

                    <button
                        type="button"
                        data-action="open"
                        data-id="${aman(item.id)}">

                        Lihat Detail

                    </button>

                </div>

            </div>

        `).join("");
    }

    function render() {

        renderStatistik();
        renderHalaman();
        renderPopup();
    }

    // ==========================================
    // 9. TANDAI NOTIFIKASI DIBACA
    // ==========================================

    async function tandaiDibaca(id) {

        const item = cariNotifikasi(id);

        if (!item) return false;

        if (item.dibaca) return true;

        try {

            if (item.sumber === "server") {

                await request(
                    API.read(item.serverId),
                    { method: "PATCH" }
                );

                notifications = notifications.map(n =>

                    String(n.id) === String(item.serverId)

                        ? {
                            ...n,
                            is_read: true
                        }

                        : n
                );

            } else {

                const invitationId = String(
                    item.invitationId
                );

                dibacaUndangan = [
                    ...new Set([
                        ...dibacaUndangan,
                        invitationId
                    ])
                ];

                simpanArray(
                    READ_KEY,
                    dibacaUndangan
                );
            }

            render();

            emit("fins:notifications-updated");

            return true;

        } catch (error) {

            console.error(error);

            alert(
                error.message ||
                "Gagal menandai notifikasi."
            );

            return false;
        }
    }

    // ==========================================
    // 10. TANDAI SEMUA DIBACA
    // ==========================================

    async function tandaiSemuaDibaca() {

        const button = $("markAllRead");

        if (button) {
            button.disabled = true;
        }

        try {

            await request(API.readAll, {
                method: "PATCH"
            });

            notifications = notifications.map(item => ({
                ...item,
                is_read: true
            }));

            dibacaUndangan = [
                ...new Set([
                    ...dibacaUndangan,

                    ...invitations

                        .filter(
                            item => item.status === "pending"
                        )

                        .map(
                            item => String(item.id)
                        )
                ])
            ];

            simpanArray(
                READ_KEY,
                dibacaUndangan
            );

            render();

            emit("fins:notifications-updated");

        } catch (error) {

            console.error(error);

            alert(
                error.message ||
                "Gagal menandai semua notifikasi."
            );

        } finally {

            if (button) {
                button.disabled = false;
            }
        }
    }

    // ==========================================
    // 11. MODAL DETAIL
    // ==========================================

    function tutupModal() {

        if (sedangMerespons) return;

        const modal = $("partnerInviteDetail");

        if (modal) {
            modal.hidden = true;
        }

        selectedInvitation = null;
    }

    function tampilkanModal(
        title,
        content,
        actions,
        eyebrow = "NOTIFIKASI FINS"
    ) {

        const modal = $("partnerInviteDetail");

        if (!modal) return;

        if ($("inviteDetailEyebrow")) {
            $("inviteDetailEyebrow").textContent =
                eyebrow;
        }

        if ($("inviteDetailTitle")) {
            $("inviteDetailTitle").textContent =
                title;
        }

        if ($("inviteDetailContent")) {
            $("inviteDetailContent").innerHTML =
                content;
        }

        if ($("inviteDetailActions")) {
            $("inviteDetailActions").innerHTML =
                actions;
        }

        if ($("inviteDetailFeedback")) {
            $("inviteDetailFeedback").textContent = "";
        }

        modal.hidden = false;
    }

    // ==========================================
    // 12. DETAIL UNDANGAN MASUK
    // ==========================================

    function bukaDetailUndangan(item) {

        selectedInvitation = item;

        let status = "Menunggu Respons";

        if (item.status === "accepted") {
            status = "Diterima";
        }

        if (item.status === "rejected") {
            status = "Ditolak";
        }

        const content = `

            <div class="invite-detail-row">

                <strong>Pengirim</strong>

                <p>
                    ${aman(item.sender_name)}
                </p>

            </div>

            <div class="invite-detail-row">

                <strong>Nama Lomba</strong>

                <p>
                    ${aman(item.competition_name)}
                </p>

            </div>

            <div class="invite-detail-row">

                <strong>Peran</strong>

                <p>
                    ${aman(
                        item.role ||
                        "Belum ditentukan"
                    )}
                </p>

            </div>

            <div class="invite-detail-row">

                <strong>Pesan</strong>

                <p>
                    ${aman(
                        item.message ||
                        "Tidak ada pesan tambahan."
                    )}
                </p>

            </div>

            <div class="invite-detail-row">

                <strong>Status</strong>

                <p>
                    ${aman(status)}
                </p>

            </div>
        `;

        let actions = "";

        if (item.status === "pending") {

            actions = `

                <button
                    type="button"
                    class="invite-reject-btn"
                    data-invite-response="rejected">

                    Tolak

                </button>

                <button
                    type="button"
                    class="invite-accept-btn"
                    data-invite-response="accepted">

                    Terima

                </button>
            `;

        } else if (item.status === "accepted") {

            actions = `

                <span class="invite-accepted-label">

                    Undangan Diterima

                </span>

                <button
                    type="button"
                    class="invite-accept-btn"
                    data-invite-chat>

                    Chat

                </button>
            `;

        } else {

            actions = `

                <span class="invite-rejected-label">

                    Undangan Ditolak

                </span>

                <button
                    type="button"
                    class="invite-reject-btn"
                    data-close-notification>

                    Tutup

                </button>
            `;
        }

        tampilkanModal(
            "Detail Ajakan",
            content,
            actions,
            "UNDANGAN KOLABORASI"
        );
    }

    // ==========================================
    // 13. DETAIL NOTIFIKASI SERVER
    // ==========================================

    function bukaDetailServer(item) {

        selectedInvitation = null;

        let status = "";

        if (item.type === "invitation_accepted") {

            status = `

                <div class="invite-detail-row">

                    <strong>Status</strong>

                    <p class="invite-accepted-label">

                        Ajakan Diterima

                    </p>

                </div>
            `;

        } else if (
            item.type === "invitation_rejected"
        ) {

            status = `

                <div class="invite-detail-row">

                    <strong>Status</strong>

                    <p class="invite-rejected-label">

                        Ajakan Ditolak

                    </p>

                </div>
            `;
        }

        const content = `

            <div class="invite-detail-row">

                <strong>Pengguna</strong>

                <p>
                    ${aman(
                        item.actor_fullname ||
                        item.actor_name ||
                        "FINS"
                    )}
                </p>

            </div>

            <div class="invite-detail-row">

                <strong>Pesan</strong>

                <p>
                    ${aman(item.pesan)}
                </p>

            </div>

            <div class="invite-detail-row">

                <strong>Waktu</strong>

                <p>
                    ${aman(item.waktu)}
                </p>

            </div>

            ${status}
        `;

        const actions = `

            <button
                type="button"
                class="invite-reject-btn"
                data-close-notification>

                Tutup

            </button>

            ${item.type === "invitation_accepted" ? `

                <button
                    type="button"
                    class="invite-accept-btn"
                    data-invite-chat>

                    Buka Chat

                </button>

            ` : ""}
        `;

        tampilkanModal(
            item.judul,
            content,
            actions
        );
    }

    // ==========================================
    // 14. BUKA NOTIFIKASI
    // ==========================================

    async function bukaNotifikasi(id) {

        const item = cariNotifikasi(id);

        if (!item) return;

        if (item.sumber === "partner_invitation") {

            bukaDetailUndangan(item);

        } else {

            bukaDetailServer(item);
        }

        await tandaiDibaca(id);
    }

    // ==========================================
    // 15. TERIMA ATAU TOLAK UNDANGAN
    // ==========================================

    async function responsUndangan(status) {

        if (
            !selectedInvitation ||
            sedangMerespons
        ) return;

        if (
            status !== "accepted" &&
            status !== "rejected"
        ) return;

        const item = selectedInvitation;
        const id = item.invitationId;

        const feedback = $("inviteDetailFeedback");

        const buttons = document.querySelectorAll(
            "[data-invite-response]"
        );

        sedangMerespons = true;

        buttons.forEach(button => {
            button.disabled = true;
        });

        if (feedback) {

            feedback.textContent =
                "Memproses undangan...";
        }

        try {

            await request(API.respond(id), {

                method: "PATCH",

                body: JSON.stringify({
                    status
                })
            });

            invitations = invitations.map(inv =>

                String(inv.id) === String(id)

                    ? {
                        ...inv,
                        status
                    }

                    : inv
            );

            bukaDetailUndangan({
                ...item,
                status
            });

            if (feedback) {

                feedback.textContent =
                    status === "accepted"

                        ? "Ajakan berhasil diterima."

                        : "Ajakan berhasil ditolak.";

                feedback.style.color =
                    status === "accepted"

                        ? "#168451"

                        : "#b91c1c";
            }

            render();

            emit("fins:invitation-updated", {

                invitationId: id,

                status
            });

            emit("fins:notifications-updated");

            jadwalkanReload();

        } catch (error) {

            console.error(
                "[FINS Invitations]",
                error
            );

            if (feedback) {

                feedback.textContent =
                    error.message;

                feedback.style.color =
                    "#b91c1c";
            }

        } finally {

            sedangMerespons = false;

            buttons.forEach(button => {

                button.disabled = false;
            });
        }
    }

    // ==========================================
    // 16. EVENT TOMBOL
    // ==========================================

    document.addEventListener("click", event => {

        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const closeButton = target.closest(
            "[data-close-notification], #closeInviteDetail"
        );

        if (closeButton) {

            tutupModal();

            return;
        }

        const responseButton = target.closest(
            "[data-invite-response]"
        );

        if (responseButton) {

            responsUndangan(
                responseButton.dataset.inviteResponse
            );

            return;
        }

        const chatButton = target.closest(
            "[data-invite-chat]"
        );

        if (chatButton) {

            window.location.href = "/chat";

            return;
        }

        const tombol = target.closest(
            "#notificationList [data-action], " +
            "#popupNotificationList [data-action]"
        );

        if (tombol) {

            const aksi = tombol.dataset.action;
            const id = tombol.dataset.id;

            if (aksi === "open") {

                bukaNotifikasi(id);
            }

            if (aksi === "read") {

                tandaiDibaca(id);
            }

            return;
        }

        const tab = target.closest(
            "#notificationTabs [data-filter]"
        );

        if (tab) {

            kategoriAktif = tab.dataset.filter;

            document.querySelectorAll(
                "#notificationTabs [data-filter]"
            ).forEach(item => {

                item.classList.toggle(
                    "active",
                    item === tab
                );
            });

            renderHalaman();
        }
    });

    // ==========================================
    // 17. EVENT HALAMAN
    // ==========================================

    $("notificationSearch")?.addEventListener(
        "input",
        renderHalaman
    );

    $("unreadOnly")?.addEventListener(
        "change",
        renderHalaman
    );

    $("markAllRead")?.addEventListener(
        "click",
        tandaiSemuaDibaca
    );

    $("popupMarkAll")?.addEventListener(
        "click",
        tandaiSemuaDibaca
    );

    $("popupClose")?.addEventListener(
        "click",
        () => {

            const popup = $("notificationPopup");

            if (popup) {
                popup.hidden = true;
            }
        }
    );

    $("partnerInviteDetail")?.addEventListener(
        "click",
        event => {

            if (
                event.target ===
                $("partnerInviteDetail")
            ) {

                tutupModal();
            }
        }
    );

    document.addEventListener(
        "keydown",
        event => {

            if (event.key === "Escape") {

                tutupModal();
            }
        }
    );

    // ==========================================
    // 18. EVENT SUPABASE REALTIME
    // Koneksi dikelola oleh fins-realtime.js
    // ==========================================

    window.addEventListener(
        "fins:notification-received",
        event => {

            console.info(
                "[FINS Notifications] Notifikasi realtime masuk."
            );

            const item = event.detail?.notification;

            if (
                item?.id &&
                String(item.user_id) === String(userId)
            ) {

                const index = notifications.findIndex(
                    n => String(n.id) === String(item.id)
                );

                if (index === -1) {

                    notifications.unshift(item);

                } else {

                    notifications[index] = {
                        ...notifications[index],
                        ...item
                    };
                }

                render();
            }

            jadwalkanReload();
        }
    );

    window.addEventListener(
        "fins:notifications-updated",
        () => {

            jadwalkanReload();
        }
    );

    window.addEventListener(
        "fins:invitation-received",
        () => {

            console.info(
                "[FINS Notifications] Undangan realtime masuk."
            );

            jadwalkanReload();
        }
    );

    window.addEventListener(
        "fins:invitation-updated",
        () => {

            console.info(
                "[FINS Notifications] Undangan diperbarui."
            );

            jadwalkanReload();
        }
    );

    window.addEventListener(
        "fins:sent-invitation-updated",
        () => {

            jadwalkanReload();
        }
    );

    window.addEventListener(
        "fins:realtime-status",
        event => {

            if (event.detail?.connected) {

                jadwalkanReload();
            }
        }
    );

    // ==========================================
    // 19. SINKRONISASI ANTAR TAB
    // ==========================================

    window.addEventListener(
        "storage",
        event => {

            if (event.key === READ_KEY) {

                dibacaUndangan = ambilArray(
                    READ_KEY
                );

                render();
            }
        }
    );

    window.addEventListener(
        "online",
        jadwalkanReload
    );

    document.addEventListener(
        "visibilitychange",
        () => {

            if (!document.hidden) {

                jadwalkanReload();
            }
        }
    );

    // ==========================================
    // 20. BUKA DETAIL DARI URL NAVBAR
    // ==========================================

    let urlHandled = false;

    function bukaDariURL() {

        if (urlHandled) return;

        const params = new URLSearchParams(
            window.location.search
        );

        const invitationId = params.get(
            "invitation"
        );

        const notificationId = params.get(
            "notification"
        );

        let item = null;

        if (invitationId) {

            item = cariNotifikasi(
                `inv-${invitationId}`
            );

        } else if (notificationId) {

            item = cariNotifikasi(
                `notif-${notificationId}`
            );

        } else {

            urlHandled = true;

            return;
        }

        if (item) {

            urlHandled = true;

            bukaNotifikasi(item.id);
        }
    }

    // ==========================================
    // 21. INITIAL LOAD
    // ==========================================

    async function init() {

        render();

        await loadAll();

        bukaDariURL();

        console.info(
            "[FINS Notifications] Halaman siap."
        );
    }

    init();

    // ==========================================
    // 22. POLLING CADANGAN
    // ==========================================

    setInterval(async () => {

        if (!document.hidden) {

            await loadAll();

            bukaDariURL();
        }

    }, 30000);

});
