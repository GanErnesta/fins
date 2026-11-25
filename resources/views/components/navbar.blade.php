{{-- NAVBAR BERSAMA UNTUK SEMUA HALAMAN FINS --}}

<header class="fins-header">
    <div class="fins-header__inner">

        {{-- LOGO FINS --}}
        <a href="{{ route('home', [], false) }}"
           class="fins-header__brand"
           aria-label="FINS - Beranda">

            <span class="fins-header__brand-f">F</span>
            <span class="fins-header__brand-ub">UB</span>
        </a>

        {{-- NAVIGASI UTAMA --}}
        <nav class="fins-header__nav"
             aria-label="Navigasi utama">

            <a href="{{ route('home', [], false) }}"
               class="{{ request()->routeIs('home') ? 'active' : '' }}"
               @if(request()->routeIs('home'))
                   aria-current="page"
               @endif>
                Beranda
            </a>

            <a href="{{ route('cari-lomba', [], false) }}"
               class="{{ request()->routeIs('cari-lomba', 'cari_lomba.show') ? 'active' : '' }}"
               @if(request()->routeIs('cari-lomba', 'cari_lomba.show'))
                   aria-current="page"
               @endif>
                Lomba
            </a>

            <a href="{{ route('cari-partner', [], false) }}"
               class="{{ request()->routeIs('cari-partner') ? 'active' : '' }}"
               @if(request()->routeIs('cari-partner'))
                   aria-current="page"
               @endif>
                Cari Partner
            </a>

            <a href="{{ route('lomba-saya', [], false) }}"
               class="{{ request()->routeIs('lomba-saya') ? 'active' : '' }}"
               @if(request()->routeIs('lomba-saya'))
                   aria-current="page"
               @endif>
                Lomba Saya
            </a>

        </nav>

        {{-- AKSI NAVBAR --}}
        <div class="fins-header__actions">

            {{-- CHAT --}}
            <a href="{{ route('chat', [], false) }}"
               class="fins-header__icon"
               aria-label="Buka chat">

                <svg viewBox="0 0 24 24"
                     aria-hidden="true"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z" />

                    <path d="M8 10h8M8 14h5" />

                </svg>
            </a>

            {{-- NOTIFIKASI --}}
            <details class="fins-header__notification"
                     id="finsNavbarNotification">

                <summary class="fins-header__icon"
                         aria-label="Buka notifikasi"
                         id="finsNavbarBell"
                         style="position:relative;">

                    <svg viewBox="0 0 24 24"
                         aria-hidden="true"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.8"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />

                    </svg>

                    {{-- TITIK MERAH NOTIFIKASI --}}
                    <span id="finsNavbarNotifDot"
      hidden
      style="
          position:absolute;
          top:-4px;
          right:-6px;
          min-width:17px;
          height:17px;
          padding:0 3px;
          background:#D14F65;
          color:#fff;
          border:2px solid #fff;
          border-radius:20px;
          font-size:10px;
          font-weight:700;
          align-items:center;
          justify-content:center;
          box-sizing:content-box;
      ">
</span>

                </summary>

                {{-- POPUP NOTIFIKASI --}}
                <div class="fins-header__dropdown"
                     id="finsNavbarNotifDropdown"
                     style="
                         min-width:320px;
                         max-width:380px;
                         max-height:440px;
                         overflow-y:auto;
                     ">

                    {{-- HEADER --}}
                    <div style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:12px;
                        margin-bottom:12px;
                    ">

                        <strong style="
                            font-size:15px;
                            color:#252525;
                        ">
                            Notifikasi
                        </strong>

                        <span id="finsNavbarNotifCount"
                              style="
                                  font-size:11px;
                                  color:#b91c1c;
                                  font-weight:600;
                              ">
                        </span>

                    </div>

                    {{-- LIST NOTIFIKASI --}}
                    <div id="finsNavbarNotifList"
                         aria-live="polite">

                        <p style="
                            font-size:13px;
                            color:#777;
                            margin:0;
                        ">
                            Memuat notifikasi...
                        </p>

                    </div>

                    {{-- FOOTER --}}
                    <div style="
                        border-top:1px solid #eee;
                        margin-top:12px;
                        padding-top:12px;
                        text-align:center;
                    ">

                        <a href="/notifikasi"
                           style="
                               color:#990000;
                               font-size:12px;
                               font-weight:600;
                               text-decoration:none;
                           ">
                            Lihat Semua Notifikasi
                        </a>

                    </div>

                </div>

            </details>

            {{-- FOTO PROFIL PENGGUNA --}}
            <a href="{{ route('profile', [], false) }}"
               class="fins-header__profile"
               aria-label="Buka profil">

                @php
                    $finsNavPhotoPath = auth()->user()?->profile_photo_path;

                    $finsNavPhotoUrl = \App\Support\ProfilePhotoUrl::publicUrl(
                        $finsNavPhotoPath
                    );
                @endphp

                {{-- ICON DEFAULT --}}
                <span class="fins-header__default-avatar"
                      aria-hidden="true">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="24"
                         height="24"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.8"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 21v-2a8 8 0 0 1 16 0v2" />

                    </svg>

                </span>

                {{-- FOTO ASLI --}}
                @if($finsNavPhotoUrl)
                    <img id="finsNavbarAvatar"
                         src="{{ $finsNavPhotoUrl }}"
                         alt="Foto profil"
                         onerror="this.remove()">
                @endif

            </a>

        </div>
    </div>
</header>

{{-- JAVASCRIPT NOTIFIKASI NAVBAR --}}
<script>
(function () {
    "use strict";
    if (window.finsNavbarNotifInitialized) return;
    const navbar = document.getElementById("finsNavbarNotification");
    const list = document.getElementById("finsNavbarNotifList");
    const dot = document.getElementById("finsNavbarNotifDot");
    const count = document.getElementById("finsNavbarNotifCount");
    if (!navbar || !list) return;
    window.finsNavbarNotifInitialized = true;
    let loading = false;
    let reloadRequested = false;

    function escapeHTML(value) {
        return String(value ?? "").replace(/[&<>"']/g, c => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
        })[c]);
    }
    function timestamp(value) {
        return Date.parse(String(value || "").replace(" ", "T").replace(/\+00$/, "+00:00")) || 0;
    }
    function formatWaktu(value) {
        const time = timestamp(value);
        return time ? new Date(time).toLocaleString("id-ID", {
            day: "numeric", month: "short", hour: "2-digit", minute: "2-digit"
        }) : "";
    }
    async function fetchJSON(url) {
        const response = await fetch(url, {
            credentials: "same-origin", cache: "no-store",
            headers: { Accept: "application/json" }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
    }
    function renderNotifications(invitations, notifications, unreadCount) {
        const pending = invitations.filter(item => item.status === "pending");
        const items = [
            ...pending.map(item => ({
                title: "Ajakan Kolaborasi",
                message: `${item.sender_name} mengajakmu berkolaborasi dalam lomba ${item.competition_name}.`,
                created_at: item.created_at, unread: true,
                status: "Menunggu Respons", color: "#b45309",
                link: "/notifikasi?invitation=" + encodeURIComponent(item.id),
                action: "Lihat Undangan"
            })),
            ...notifications.map(item => ({
                title: item.title, message: item.message,
                created_at: item.created_at, unread: !item.is_read,
                status: item.type === "invitation_accepted" ? "Diterima" : item.type === "invitation_rejected" ? "Ditolak" : "Notifikasi",
                color: item.type === "invitation_accepted" ? "#168451" : item.type === "invitation_rejected" ? "#b91c1c" : "#990000",
                link: "/notifikasi?notification=" + encodeURIComponent(item.id),
                action: "Lihat Notifikasi"
            }))
        ].sort((a, b) => timestamp(b.created_at) - timestamp(a.created_at));
        const total = pending.length + Number(unreadCount || 0);
        if (dot) {
            dot.hidden = total === 0;
            dot.style.display = total ? "flex" : "none";
            dot.textContent = total > 99 ? "99+" : total;
        }
        if (count) count.textContent = total ? `${total} belum dibaca / direspons` : "";
        if (!items.length) {
            list.innerHTML = '<p style="margin:0;font-size:13px;color:#777">Belum ada notifikasi.</p>';
            return;
        }
        list.innerHTML = items.slice(0, 5).map(item => `
            <a href="${escapeHTML(item.link)}" style="display:block;padding:12px 8px;margin-bottom:5px;text-decoration:none;border-bottom:1px solid #f0f0f0;border-radius:8px;color:#333;background:${item.unread ? '#fff1f3' : '#ffffff'};">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                    ${item.unread ? '<span style="width:7px;height:7px;flex-shrink:0;border-radius:50%;background:#D14F65;"></span>' : ''}
                    <strong style="font-size:12px;color:#990000;">${escapeHTML(item.title)}</strong>
                    <span style="font-size:10px;color:${item.color};margin-left:auto;white-space:nowrap;">${escapeHTML(item.status)}</span>
                </div>
                <p style="margin:0 0 5px;font-size:12px;line-height:1.5;color:#444;">${escapeHTML(item.message)}</p>
                <small style="display:block;color:#999;font-size:10px;margin-top:6px;">${escapeHTML(formatWaktu(item.created_at))}</small>
                <span style="display:inline-block;margin-top:7px;color:#990000;font-size:11px;font-weight:600;">${escapeHTML(item.action)}</span>
            </a>
        `).join("");
    }
    async function loadNotifications() {
        if (loading) { reloadRequested = true; return; }
        loading = true;
        try {
            const [inv, notif] = await Promise.all([
                fetchJSON("/partner-invitations/received"),
                fetchJSON("/notifications")
            ]);
            if (!inv.success || !Array.isArray(inv.data) || !notif.success || !Array.isArray(notif.notifications)) {
                throw new Error("Format data notifikasi tidak valid.");
            }
            renderNotifications(inv.data, notif.notifications, notif.unread_count);
        } catch (error) {
            console.error("[FINS Navbar] Gagal memuat notifikasi:", error);
            list.innerHTML = '<p style="font-size:12px;color:#b91c1c;margin:0;">Gagal memuat notifikasi.</p>';
        } finally {
            loading = false;
            if (reloadRequested) { reloadRequested = false; loadNotifications(); }
        }
    }
    window.addEventListener("fins:notifications-updated", loadNotifications);
    window.addEventListener("fins:invitation-updated", loadNotifications);
    navbar.addEventListener("toggle", () => { if (navbar.open) loadNotifications(); });
    document.addEventListener("visibilitychange", () => { if (!document.hidden) loadNotifications(); });
    loadNotifications();
    setInterval(() => { if (!document.hidden) loadNotifications(); }, 30000);
})();
</script>
{{-- SUPABASE REALTIME --}}
@auth
    <script
        src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.49.4/dist/umd/supabase.js"
        defer>
    </script>

    <script
        src="/js/fins-realtime.js?v={{ filemtime(public_path('js/fins-realtime.js')) }}"
        defer>
    </script>
@endauth
