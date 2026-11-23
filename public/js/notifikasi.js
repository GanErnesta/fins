
document.addEventListener("DOMContentLoaded", () => {
    const STORAGE_KEY = "fins_notifications_v1";

    const contohNotifikasi = [
        {
            id: 1,
            kategori: "lamaran",
            judul: "Permohonan Gabung Tim Baru",
            pesan: "Kezia Aurellia (FIA UB) mengajukan diri sebagai UX Researcher untuk kompetisi Gemastik XVIII.",
            waktu: "15 menit yang lalu",
            dibaca: false,
            link: "/lomba-saya"
        },
        {
            id: 2,
            kategori: "chat",
            judul: "Pesan Baru dari Sarah Nabila",
            pesan: 'Sarah (FEB UB): "Deck awal untuk BBCC sudah aku upload di drive tim, tolong dicek bagian arsitektur ya!"',
            waktu: "1 jam yang lalu",
            dibaca: false,
            link: "/chat"
        },
        {
            id: 3,
            kategori: "undangan",
            judul: "Undangan Kolaborasi Tim",
            pesan: "Dimas Arya Pratama (FT UB) mengundang Anda bergabung ke tim PKM-KC AquaGuard UB.",
            waktu: "3 jam yang lalu",
            dibaca: true,
            link: "/tim-saya"
        },
        {
            id: 4,
            kategori: "deadline",
            judul: "Batas Pendaftaran Segera Ditutup",
            pesan: "Kompetisi Brawijaya Business Case Competition (BBCC) 2026 akan menutup pendaftaran dalam 3 hari.",
            waktu: "5 jam yang lalu",
            dibaca: true,
            link: "/cari-lomba"
        },
        {
            id: 5,
            kategori: "sistem",
            judul: "Akun Mahasiswa Terverifikasi",
            pesan: "Akun mahasiswa Anda telah berhasil diverifikasi. Sekarang Anda dapat mendaftar lomba dan membentuk tim.",
            waktu: "Kemarin",
            dibaca: true,
            link: "/profile"
        }
    ];

    function ambilNotifikasi() {
        try {
            const data = localStorage.getItem(STORAGE_KEY);

            if (data !== null) {
                const hasil = JSON.parse(data);
                if (Array.isArray(hasil)) return hasil;
            }
        } catch (error) {
            console.error("Gagal membaca notifikasi:", error);
        }

        return contohNotifikasi.map(item => ({ ...item }));
    }

    let notifikasi = ambilNotifikasi();
    let kategoriAktif = "semua";

    const simpan = () => {
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify(notifikasi)
        );
        render();
    };

    function aman(teks) {
        return String(teks ?? "").replace(
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

    function hitungBelumDibaca() {
        return notifikasi.filter(n => !n.dibaca).length;
    }

    function tandaiDibaca(id) {
        notifikasi = notifikasi.map(n =>
            String(n.id) === String(id) ? { ...n, dibaca: true } : n
        );
        simpan();
    }

    function hapusNotifikasi(id) {
        if (!confirm("Hapus notifikasi ini?")) return;

        notifikasi = notifikasi.filter(n => n.id !== id);
        simpan();
    }

    function tandaiSemuaDibaca() {
        notifikasi = notifikasi.map(n => ({
            ...n,
            dibaca: true
        }));
        simpan();
    }

    function hapusSemua() {
        if (!confirm("Yakin ingin menghapus semua notifikasi?")) {
            return;
        }

        notifikasi = [];
        simpan();
    }

    function bukaNotifikasi(id) {
        const item = notifikasi.find(n => String(n.id) === String(id));
        if (!item) return;

        tandaiDibaca(id);

        if (item.link && item.link.startsWith("/")) {
            window.location.href = item.link;
        }
    }

    function filterNotifikasi() {
        const pencarian = document.querySelector(
            "#notificationSearch"
        )?.value.toLowerCase().trim() || "";

        const hanyaBelumDibaca = document.querySelector(
            "#unreadOnly"
        )?.checked || false;

        return notifikasi.filter(n => {
            const cocokKategori =
                kategoriAktif === "semua" ||
                n.kategori === kategoriAktif;

            const cocokPencarian =
                `${n.judul} ${n.pesan}`
                    .toLowerCase()
                    .includes(pencarian);

            const cocokStatus =
                !hanyaBelumDibaca || !n.dibaca;

            return cocokKategori &&
                cocokPencarian &&
                cocokStatus;
        });
    }

    function renderStatistik() {
        const statistik = {
            unread: hitungBelumDibaca(),
            total: notifikasi.length,
            collaboration: notifikasi.filter(n =>
                ["lamaran", "undangan"].includes(n.kategori)
            ).length,
            deadline: notifikasi.filter(n =>
                n.kategori === "deadline"
            ).length
        };

        Object.entries(statistik).forEach(([nama, jumlah]) => {
            const elemen = document.querySelector(
                `[data-stat="${nama}"]`
            );

            if (elemen) elemen.textContent = jumlah;
        });

        document.querySelectorAll(
            "[data-notification-count]"
        ).forEach(elemen => {
            elemen.textContent = statistik.unread;
            elemen.hidden = statistik.unread === 0;
        });

        document.querySelectorAll(
            ".notification-dot"
        ).forEach(elemen => {
            elemen.hidden = statistik.unread === 0;
        });
    }

    function renderHalaman() {
        const daftar = document.querySelector(
            "#notificationList"
        );

        if (!daftar) return;

        const hasil = filterNotifikasi();

        if (hasil.length === 0) {
            daftar.innerHTML = `
                <div class="empty-state">
                    <h3>Tidak ada notifikasi</h3>
                    <p>Belum ada notifikasi yang sesuai.</p>
                </div>
            `;
            return;
        }

        daftar.innerHTML = hasil.map(n => `
            <article class="notification-card ${n.dibaca ? "" : "unread"}">
                <div style="display:flex;justify-content:space-between;gap:15px;align-items:start;">
                    <div>
                        <small>${aman(n.kategori.toUpperCase())}</small>
                        ${!n.dibaca ? "<small> • Baru</small>" : ""}
                        <h3>${aman(n.judul)}</h3>
                    </div>
                    <small>${aman(n.waktu)}</small>
                </div>

                <p>${aman(n.pesan)}</p>

                <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-top:20px;">
                    <button class="primary-button"
                        data-action="open"
                        data-id="${n.id}">
                        Buka Fitur
                    </button>

                    ${!n.dibaca ? `
                        <button data-action="read"
                            data-id="${n.id}">
                            Tandai Dibaca
                        </button>
                    ` : ""}

                    <button class="danger-button"
                        data-action="delete"
                        data-id="${n.id}">
                        Hapus
                    </button>
                </div>
            </article>
        `).join("");
    }

    function renderPopup() {
        const daftar = document.querySelector("#popupNotificationList");
        if (!daftar) return;

        const terbaru = notifikasi.slice(0, 5);

        if (terbaru.length === 0) {
            daftar.innerHTML = `
                <div class="empty-state">
                    Belum ada notifikasi.
                </div>
            `;
            return;
        }

        daftar.innerHTML = terbaru.map(n => `
            <div class="popup-item ${n.dibaca ? "" : "unread"}">
                <strong>${aman(n.judul)}</strong>
                <p>${aman(n.pesan)}</p>
                <small>${aman(n.waktu)}</small>

                <div style="margin-top:10px;display:flex;gap:12px;">
                    <button data-action="open"
                        data-id="${n.id}">
                        Buka
                    </button>
                    <button data-action="delete"
                        data-id="${n.id}">
                        Hapus
                    </button>
                </div>
            </div>
        `).join("");

        const jumlah = document.querySelector("#popupUnreadCount");
        if (jumlah) {
            jumlah.textContent =
                `${hitungBelumDibaca()} belum dibaca`;
        }
    }

    function render() {
        renderStatistik();
        renderHalaman();
        renderPopup();
    }

    document.addEventListener("click", event => {
        const tombol = event.target.closest("[data-action]");

        if (tombol) {
            const aksi = tombol.dataset.action;
           const id = tombol.dataset.id;

            if (aksi === "open") bukaNotifikasi(id);
            if (aksi === "read") tandaiDibaca(id);
            if (aksi === "delete") hapusNotifikasi(id);
        }

        const tab = event.target.closest("[data-filter]");

        if (tab) {
            kategoriAktif = tab.dataset.filter;

            document.querySelectorAll("[data-category]")
                .forEach(item => {
                    item.classList.toggle(
                        "active",
                        item === tab
                    );
                });

            renderHalaman();
        }
    });

    document.querySelector("#notificationSearch")
        ?.addEventListener("input", renderHalaman);

    document.querySelector("#unreadOnly")
        ?.addEventListener("change", renderHalaman);

    document.querySelector("#markAllRead")
        ?.addEventListener("click", tandaiSemuaDibaca);

    document.querySelector("#deleteAllNotifications")
        ?.addEventListener("click", hapusSemua);

    document.querySelector("#notificationBell")
        ?.addEventListener("click", () => {
            const popup = document.querySelector(
                "#notificationPopup"
            );

            if (popup) popup.hidden = !popup.hidden;
        });

    document.querySelector("#popupClose")
        ?.addEventListener("click", () => {
            const popup = document.querySelector(
                "#notificationPopup"
            );

            if (popup) popup.hidden = true;
        });

    document.querySelector("#resetDemoNotifications")
        ?.addEventListener("click", () => {
            notifikasi = contohNotifikasi.map(n => ({ ...n }));
            simpan();
        });

    // Perbarui tampilan saat fitur lain membuat notifikasi
    window.addEventListener("fins:notification-added", () => {
        notifikasi = ambilNotifikasi();
        render();
    });

    // Sinkronisasi jika localStorage berubah dari tab lain
    window.addEventListener("storage", (event) => {
        if (event.key === STORAGE_KEY) {
            notifikasi = ambilNotifikasi();
            render();
        }
    });

    // Perbarui angka statistik pada halaman
    function updateStatistik() {
        const data = {
            unreadCount: hitungBelumDibaca(),
            totalCount: notifikasi.length,
            applicationCount: notifikasi.filter(n =>
                ["lamaran", "undangan"].includes(n.kategori)
            ).length,
            deadlineCount: notifikasi.filter(n =>
                ["deadline", "sistem"].includes(n.kategori)
            ).length
        };

        Object.entries(data).forEach(([id, jumlah]) => {
            const elemen = document.getElementById(id);
            if (elemen) elemen.textContent = jumlah;
        });
    }

    const renderSebelumnya = render;

    render = function () {
        renderSebelumnya();
        updateStatistik();
    };

    render();
});

const FINS_NOTIFICATION_KEY = "fins_notifications_v1";

function tambahNotifikasi(data) {
    const notifikasi = JSON.parse(
        localStorage.getItem(FINS_NOTIFICATION_KEY) || "[]"
    );

    const baru = {
        id: crypto.randomUUID(),
        kategori: data.kategori,
        judul: data.judul,
        pesan: data.pesan,
        waktu: "Baru saja",
        created_at: new Date().toISOString(),
        dibaca: false,
        link: data.link || "/notifikasi"
    };

    notifikasi.unshift(baru);

    localStorage.setItem(
        FINS_NOTIFICATION_KEY,
        JSON.stringify(notifikasi)
    );

    window.dispatchEvent(
        new CustomEvent("fins:notification-added", {
            detail: baru
        })
    );

    return baru;
}

window.tambahNotifikasi = tambahNotifikasi;
