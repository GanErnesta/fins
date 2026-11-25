document.addEventListener('DOMContentLoaded', () => {
    const tabs   = document.querySelectorAll('.tab');
    const panels = document.querySelectorAll('.panel');

    function activate(id) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === id));
        panels.forEach(p => p.classList.toggle('active', p.id === id));
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            activate(tab.dataset.tab);
            history.replaceState(null, '', '#' + tab.dataset.tab); // simpan tab aktif di URL
        });
    });

    // Buka tab sesuai hash URL, mis. /lomba-saya#pengajuan
    const hash = location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) activate(hash);

    // Tombol terima / tolak pelamar
    document.querySelectorAll('.applicant__actions .btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const accept = btn.textContent.trim() === 'Terima';
            const box = btn.closest('.applicant__actions');
            box.outerHTML = `<p class="applicant__done">${accept ? 'Telah ditambahkan<br>ke tim' : 'Pelamar<br>ditolak'}</p>`;
            // TODO: kirim ke backend dengan fetch() ke route Laravel
        });
    });
});


/* ==========================================
   CRUD POSTING REKRUT SAYA
   TANPA DATABASE - LOCAL STORAGE
========================================== */

document.addEventListener("DOMContentLoaded", function () {

    const daftar = document.getElementById("daftarPostingRekrut");
    const modal = document.getElementById("modalRekrut");
    const form = document.getElementById("formRekrut");

    if (!daftar || !modal || !form) return;

    const STORAGE_KEY = "fins_posting_rekrut_v1";

    const el = (id) => document.getElementById(id);

    // Mencegah input pengguna menjadi HTML berbahaya
    function aman(value) {
        return String(value ?? "").replace(
            /[&<>"']/g,
            (char) => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#39;"
            })[char]
        );
    }

    function buatId() {
        return "rekrut_" + Date.now() + "_" +
            Math.random().toString(36).slice(2);
    }

    // Data awal dari Laravel Controller
    function dataAwal() {
        return (window.postingRekrutAwal || []).map(item => ({
            ...item,
            id: buatId(),
            skill: Array.isArray(item.skill)
                ? item.skill
                : [],
            pelamar: Array.isArray(item.pelamar)
                ? item.pelamar
                : []
        }));
    }

    let posting = [];

    try {
        const data = localStorage.getItem(STORAGE_KEY);

        posting = data === null
            ? dataAwal()
            : JSON.parse(data);

        if (!Array.isArray(posting)) {
            posting = dataAwal();
        }

    } catch (error) {
        posting = dataAwal();
    }

    function simpanData() {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(posting)
            );
        } catch (error) {
            alert("Data gagal disimpan di browser.");
            return false;
        }

        return true;
    }

    function inisial(nama) {
        return String(nama || "U")
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map(kata => kata.charAt(0).toUpperCase())
            .join("");
    }

    function avatar(nama) {
        const warna = [
            "#6C5CE7",
            "#4FC3F7",
            "#FF6B9D",
            "#10B981"
        ];

        let hash = 0;

        for (const huruf of String(nama)) {
            hash = (hash * 31 + huruf.charCodeAt(0)) >>> 0;
        }

        const bg = warna[hash % warna.length];

        return `
            <span class="avatar"
                  style="
                    width:60px;
                    height:60px;
                    background:${bg};
                    font-size:21px;
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    line-height:1;
                  ">
                ${aman(inisial(nama))}
            </span>
        `;
    }

    // =====================================
    // READ: TAMPILKAN POSTING REKRUT
    // =====================================

    function tampilkanPosting() {

        if (posting.length === 0) {
            daftar.innerHTML = `
                <div class="rekrut-empty">
                    <h3>Belum Ada Postingan Rekrutmen</h3>
                    <p>
                        Klik Tambah Rekrutmen untuk membuat
                        postingan pencarian anggota tim.
                    </p>
                </div>
            `;
        } else {

            daftar.innerHTML = posting.map(item => {

                const skill = Array.isArray(item.skill)
                    ? item.skill
                    : [];

                const pelamar = Array.isArray(item.pelamar)
                    ? item.pelamar
                    : [];

                const daftarSkill = skill.map(s => `
                    <span class="chip">${aman(s)}</span>
                `).join("");

                const daftarPelamar = pelamar.map((pl, i) => {

                    const status = pl.status || "Menunggu";

                    let statusClass = "badge--menunggu";

                    if (status === "Diterima") {
                        statusClass = "badge--green";
                    }

                    const tombol = status === "Diterima"
                        ? `<p class="applicant__done">
                               Telah ditambahkan ke tim
                           </p>`
                        : status === "Ditolak"
                            ? `<p class="applicant__done">
                                   Pelamar ditolak
                               </p>`
                            : `
                                <div class="applicant__actions">
                                    <button type="button"
                                        class="btn btn--primary btn--sm"
                                        data-aksi-pelamar="Diterima"
                                        data-posting-id="${aman(item.id)}"
                                        data-pelamar-index="${i}">
                                        Terima
                                    </button>

                                    <button type="button"
                                        class="btn btn--ghost btn--sm"
                                        data-aksi-pelamar="Ditolak"
                                        data-posting-id="${aman(item.id)}"
                                        data-pelamar-index="${i}">
                                        Tolak
                                    </button>
                                </div>
                            `;

                    return `
                        <div class="applicant">

                            ${avatar(pl.nama)}

                            <div class="applicant__body">

                                <div class="applicant__name">
                                    <b>${aman(pl.nama)}</b>

                                    <small>
                                        (${aman(pl.asal)})
                                    </small>

                                    <span class="badge ${statusClass} badge--xs">
                                        ${aman(status)}
                                    </span>
                                </div>

                                <p class="applicant__role">
                                    Melamar posisi:
                                    ${aman(pl.posisi)}
                                </p>

                                <div class="applicant__row">

                                    <blockquote>
                                        "${aman(pl.pesan)}"
                                    </blockquote>

                                    ${tombol}

                                </div>

                            </div>
                        </div>
                    `;

                }).join("");

                const warnaStatus = item.status === "Aktif"
                    ? "badge--outline-green"
                    : "badge--diterima";

                return `
                    <article class="card card--wide rekrut-item">

                        <div class="card__head">

                            <div class="post-meta">
                                <span class="tag">
                                    ${aman(item.kategori)}
                                </span>

                                <span class="muted">
                                    · Diposting ${aman(item.waktu)}
                                </span>
                            </div>

                            <span class="badge ${warnaStatus}">
                                Status: ${aman(item.status)}
                            </span>

                        </div>

                        <h3 class="card__title card__title--lg">
                            ${aman(item.judul)}
                        </h3>

                        <div class="slot-box">

                            <div class="slot-box__top">
                                <strong>${aman(item.posisi)}</strong>

                                <span class="slot-box__slot">
                                    ${aman(item.slot)} slot kosong
                                </span>
                            </div>

                            <div class="chips">
                                ${daftarSkill}
                            </div>

                        </div>

                        <p class="label label--icon">
                            PELAMAR / MAHASISWA YANG INGIN
                            BERGABUNG (${pelamar.length})
                        </p>

                        ${pelamar.length > 0
                            ? daftarPelamar
                            : `<p class="muted">
                                   Belum ada mahasiswa yang melamar.
                               </p>`
                        }

                        <div class="rekrut-crud-actions">

                            <button type="button"
                                class="btn-edit-rekrut"
                                data-edit-id="${aman(item.id)}">
                                Edit Postingan
                            </button>

                            <button type="button"
                                class="btn-hapus-rekrut"
                                data-hapus-id="${aman(item.id)}">
                                Hapus Postingan
                            </button>

                        </div>

                    </article>
                `;

            }).join("");
        }

        // Perbarui angka pada tab
        const tab = document.querySelector(
            '.tab[data-tab="posting-rekrut"]'
        );

        if (tab) {
            tab.textContent =
                `Posting Rekrut Saya (${posting.length})`;
        }
    }

    // =====================================
    // MODAL TAMBAH DAN EDIT
    // =====================================

    function bukaModal(id = null) {

        form.reset();

        el("editRekrutId").value = "";

        el("judulModalRekrut").textContent =
            id ? "Edit Rekrutmen" : "Tambah Rekrutmen";

        if (id) {

            const item = posting.find(p => p.id === id);

            if (!item) return;

            el("editRekrutId").value = item.id;
            el("judulRekrut").value = item.judul;
            el("kategoriRekrut").value = item.kategori;
            el("posisiRekrut").value = item.posisi;
            el("slotRekrut").value = item.slot;

            el("skillRekrut").value =
                Array.isArray(item.skill)
                    ? item.skill.join(", ")
                    : "";

            el("statusRekrut").value =
                item.status === "Ditutup"
                    ? "Ditutup"
                    : "Aktif";
        }

        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");

        document.body.style.overflow = "hidden";

        el("judulRekrut").focus();
    }

    function tutupModal() {
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");

        document.body.style.overflow = "";

        form.reset();
    }

    el("btnTambahRekrut").addEventListener(
        "click",
        () => bukaModal()
    );

    el("tutupModalRekrut").addEventListener(
        "click",
        tutupModal
    );

    el("batalRekrut").addEventListener(
        "click",
        tutupModal
    );

    modal.addEventListener("click", function (e) {
        if (e.target === modal) {
            tutupModal();
        }
    });

    document.addEventListener("keydown", function (e) {
        if (
            e.key === "Escape" &&
            modal.classList.contains("show")
        ) {
            tutupModal();
        }
    });

    // =====================================
    // CREATE DAN UPDATE
    // =====================================

    form.addEventListener("submit", function (e) {

        e.preventDefault();

        const idEdit = el("editRekrutId").value;

        const keahlian = el("skillRekrut").value
            .split(",")
            .map(s => s.trim())
            .filter(Boolean);

        if (keahlian.length === 0) {
            alert("Masukkan minimal satu keahlian.");
            return;
        }

        const jumlahSlot = Number(el("slotRekrut").value);

        if (
            !Number.isInteger(jumlahSlot) ||
            jumlahSlot < 1 ||
            jumlahSlot > 100
        ) {
            alert("Jumlah slot harus antara 1 sampai 100.");
            return;
        }

        const data = {
            judul: el("judulRekrut").value.trim(),
            kategori: el("kategoriRekrut").value.trim(),
            posisi: el("posisiRekrut").value.trim(),
            slot: jumlahSlot,
            skill: keahlian,
            status: el("statusRekrut").value
        };

        if (idEdit) {

            // UPDATE
            const index = posting.findIndex(
                p => p.id === idEdit
            );

            if (index === -1) return;

            posting[index] = {
                ...posting[index],
                ...data
            };

        } else {

            // CREATE
            posting.unshift({
                id: buatId(),
                ...data,
                waktu: new Date().toLocaleString("id-ID"),
                pelamar: []
            });
        }

        if (simpanData()) {
            tampilkanPosting();
            tutupModal();
        }
    });

    // =====================================
    // DELETE, EDIT, DAN STATUS PELAMAR
    // =====================================

    daftar.addEventListener("click", function (e) {

        const tombolEdit = e.target.closest(
            "[data-edit-id]"
        );

        const tombolHapus = e.target.closest(
            "[data-hapus-id]"
        );

        const tombolPelamar = e.target.closest(
            "[data-aksi-pelamar]"
        );

        if (tombolEdit) {
            bukaModal(tombolEdit.dataset.editId);
            return;
        }

        if (tombolHapus) {

            const id = tombolHapus.dataset.hapusId;

            const item = posting.find(p => p.id === id);

            if (!item) return;

            const yakin = confirm(
                `Yakin ingin menghapus postingan "${item.judul}"?`
            );

            if (!yakin) return;

            posting = posting.filter(p => p.id !== id);

            if (simpanData()) {
                tampilkanPosting();
            }

            return;
        }

        if (tombolPelamar) {

            const item = posting.find(
                p => p.id === tombolPelamar.dataset.postingId
            );

            if (!item) return;

            const index = Number(
                tombolPelamar.dataset.pelamarIndex
            );

            const pelamar = item.pelamar?.[index];

            if (!pelamar || pelamar.status !== "Menunggu") {
                return;
            }

            const status = tombolPelamar.dataset.aksiPelamar;

            if (!["Diterima", "Ditolak"].includes(status)) {
                return;
            }

            pelamar.status = status;

            if (simpanData()) {
                tampilkanPosting();
            }
        }
    });

    // Tampilkan data saat halaman dibuka
    tampilkanPosting();

});

