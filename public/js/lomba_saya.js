document.addEventListener("DOMContentLoaded", () => {
    const tabs = document.querySelectorAll(".tab[data-tab]");
    const panels = document.querySelectorAll(".panel");

    function aktifkanTab(id) {
        const tabAda = Array.from(tabs).some(tab => tab.dataset.tab === id);
        if (!tabAda) return;

        tabs.forEach(tab => {
            tab.classList.toggle("active", tab.dataset.tab === id);
        });

        panels.forEach(panel => {
            panel.classList.toggle("active", panel.id === id);
        });
    }

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            const id = tab.dataset.tab;
            aktifkanTab(id);
            history.replaceState(null, "", "#" + id);
        });
    });

    aktifkanTab(location.hash.slice(1));

    // Pertahankan interaksi pada kartu contoh di bagian lain halaman.
    document.querySelectorAll(".applicant__actions .btn").forEach(button => {
        button.addEventListener("click", () => {
            const actions = button.closest(".applicant__actions");
            if (!actions) return;

            const pesan = document.createElement("p");
            pesan.className = "applicant__done";
            pesan.textContent = button.textContent.trim() === "Terima"
                ? "Telah ditambahkan ke tim"
                : "Pelamar ditolak";

            actions.replaceWith(pesan);
        });
    });

    const el = id => document.getElementById(id);

    const daftar = el("daftarPostingRekrut");
    const modal = el("modalRekrut");
    const form = el("formRekrut");
    const tombolTambah = el("btnTambahRekrut");

    if (!daftar || !modal || !form || !tombolTambah) return;

    const tombolSimpan = form.querySelector('[type="submit"]');
    const tabRekrut = document.querySelector('.tab[data-tab="posting-rekrut"]');
    const endpoint = form.dataset.url;
    const labelSimpanAwal = tombolSimpan.textContent.trim();

    let posting = [];
    let daftarLomba = [];
    let sedangMemuat = false;
    let sedangMenyimpan = false;

    // Status diumumkan juga kepada pengguna pembaca layar.
    daftar.setAttribute("aria-live", "polite");

    function aman(value) {
        const karakter = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"
        };

        return String(value ?? "").replace(
            /[&<>"']/g,
            huruf => karakter[huruf]
        );
    }

    async function kirimRequest(url, method = "GET", data) {
        const headers = {
            Accept: "application/json"
        };

        if (method !== "GET") {
            headers["Content-Type"] = "application/json";
            headers["X-CSRF-TOKEN"] = form.elements.namedItem("_token").value;
        }

        const response = await fetch(url, {
            method,
            headers,
            credentials: "same-origin",
            body: data === undefined ? undefined : JSON.stringify(data)
        });

        if (!response.ok) {
            const hasil = await response.json().catch(() => ({}));
            const validasi = Object.values(hasil.errors ?? {}).flat().join("\n");

            if (response.status === 419) {
                throw new Error("Sesi sudah berakhir. Refresh halaman lalu coba lagi.");
            }

            throw new Error(
                validasi ||
                hasil.message ||
                `Permintaan gagal (HTTP ${response.status}).`
            );
        }

        if (response.status === 204) return null;

        return response.json();
    }

    function tampilkanPosting() {
        if (tabRekrut) {
            tabRekrut.textContent = `Posting Rekrut Saya (${posting.length})`;
        }

        if (posting.length === 0) {
            daftar.innerHTML = `
                <div class="rekrut-empty">
                    <h3>Belum Ada Postingan Rekrutmen</h3>
                    <p>Klik Tambah Rekrutmen untuk membuat postingan pencarian anggota tim.</p>
                </div>
            `;
            return;
        }

        daftar.innerHTML = posting.map(item => {
            const skill = Array.isArray(item.skill) ? item.skill : [];
            const pelamar = Array.isArray(item.pelamar) ? item.pelamar : [];

            const chips = skill.map(nama => `
                <span class="chip">${aman(nama)}</span>
            `).join("");

            const daftarPelamar = pelamar.map(orang => `
                <div class="applicant">
                    <div class="applicant__body">
                        <div class="applicant__name">
                            <b>${aman(orang.nama)}</b>
                            <small>${aman(orang.asal)}</small>
                            <span class="badge badge--xs">
                                ${aman(orang.status || "Menunggu")}
                            </span>
                        </div>
                        <p class="applicant__role">
                            Melamar posisi: ${aman(orang.posisi)}
                        </p>
                        <blockquote>${aman(orang.pesan)}</blockquote>
                    </div>
                </div>
            `).join("");

            const warnaStatus = item.status === "Aktif"
                ? "badge--outline-green"
                : "badge--diterima";

            return `
                <article class="card card--wide rekrut-item">
                    <div class="card__head">
                        <div class="post-meta">
                            <span class="tag">${aman(item.kategori)}</span>
                            <span class="muted">· Diposting ${aman(item.waktu)}</span>
                        </div>
                        <span class="badge ${warnaStatus}">
                            Status: ${aman(item.status)}
                        </span>
                    </div>

                    <h3 class="card__title card__title--lg">${aman(item.judul)}</h3>

                    <div class="slot-box">
                        <div class="slot-box__top">
                            <strong>${aman(item.posisi)}</strong>
                            <span class="slot-box__slot">
                                ${aman(item.slot)} slot kosong
                            </span>
                        </div>
                        <div class="chips">${chips}</div>
                    </div>

                    <p class="label label--icon">
                        PELAMAR / MAHASISWA YANG INGIN BERGABUNG (${pelamar.length})
                    </p>

                    ${daftarPelamar || '<p class="muted">Belum ada mahasiswa yang melamar.</p>'}

                    <div class="rekrut-crud-actions">
                        <button type="button"
                                class="btn-edit-rekrut"
                                data-edit-id="${aman(item.id)}">
                            Edit Posisi
                        </button>
                        <button type="button"
                                class="btn-hapus-rekrut"
                                data-hapus-id="${aman(item.id)}">
                            Hapus Posisi
                        </button>
                    </div>
                </article>
            `;
        }).join("");
    }

    async function muatPosting() {
        if (sedangMemuat) return;

        sedangMemuat = true;
        tombolTambah.disabled = true;
        daftar.setAttribute("aria-busy", "true");

        daftar.innerHTML = `
            <div class="rekrut-empty" role="status">
                <p>Memuat rekrutmen…</p>
            </div>
        `;

        try {
            const hasil = await kirimRequest(endpoint);

            if (!Array.isArray(hasil.posting) || !Array.isArray(hasil.lomba)) {
                throw new Error("Format data rekrutmen dari server tidak sesuai.");
            }

            posting = hasil.posting;
            daftarLomba = hasil.lomba;

            tampilkanPosting();
            tombolTambah.disabled = false;
        } catch (error) {
            daftar.innerHTML = `
                <div class="rekrut-empty">
                    <p role="alert">${aman(error.message)}</p>
                    <button type="button" class="btn" data-muat-ulang>
                        Coba lagi
                    </button>
                </div>
            `;
        } finally {
            sedangMemuat = false;
            daftar.setAttribute("aria-busy", "false");
        }
    }

    function bukaModal(id = "") {
        if (sedangMemuat || sedangMenyimpan) return;

        const item = id
            ? posting.find(baris => String(baris.id) === String(id))
            : null;

        if (id && !item) return;

        form.reset();

        const edit = Boolean(item);
        const pilihanLomba = el("judulRekrut");

        el("editRekrutId").value = item?.id ?? "";
        el("judulModalRekrut").textContent = edit
            ? "Edit Rekrutmen"
            : "Tambah Rekrutmen";

        pilihanLomba.replaceChildren(new Option("Pilih lomba", ""));

        daftarLomba.forEach(lomba => {
            pilihanLomba.add(new Option(lomba.title, String(lomba.id)));
        });

        pilihanLomba.disabled = edit;

        const rincianBaru = el("rincianBaru");
        rincianBaru.hidden = edit;

        rincianBaru.querySelectorAll("input, textarea, select").forEach(input => {
            input.disabled = edit;
        });

        if (edit) {
            const lombaAda = daftarLomba.some(
                lomba => String(lomba.id) === String(item.competition)
            );

            if (lombaAda) {
                pilihanLomba.value = String(item.competition);
            } else {
                pilihanLomba.add(new Option(item.judul, "lomba-lama"));
                pilihanLomba.value = "lomba-lama";
            }

            el("kategoriRekrut").value = item.kategori ?? "";
            el("posisiRekrut").value = item.posisi ?? "";
            el("slotRekrut").value = item.slot ?? 1;
            el("skillRekrut").value = (item.skill ?? []).join(", ");
            el("statusRekrut").value = item.status === "Ditutup" ? "Ditutup" : "Aktif";
        }

        tombolSimpan.textContent = edit ? "Simpan perubahan" : labelSimpanAwal;
        tombolSimpan.disabled = false;

        modal.classList.add("show");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";

        (edit ? el("posisiRekrut") : pilihanLomba).focus();
    }

    function tutupModal() {
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
        form.reset();
        tombolTambah.focus();
    }

    function batalJikaBisa() {
        if (!sedangMenyimpan) tutupModal();
    }

    tombolTambah.addEventListener("click", () => bukaModal());
    el("tutupModalRekrut").addEventListener("click", batalJikaBisa);
    el("batalRekrut").addEventListener("click", batalJikaBisa);

    modal.addEventListener("click", event => {
        if (event.target === modal) batalJikaBisa();
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape" && modal.classList.contains("show")) {
            batalJikaBisa();
        }
    });

    el("judulRekrut").addEventListener("change", event => {
        const lomba = daftarLomba.find(
            item => String(item.id) === event.target.value
        );

        el("kategoriRekrut").value = lomba?.category ?? "";
    });

    form.addEventListener("submit", async event => {
        event.preventDefault();

        if (sedangMenyimpan || sedangMemuat) return;

        const id = el("editRekrutId").value;
        const data = {
            posisi: el("posisiRekrut").value.trim(),
            slot: Number(el("slotRekrut").value),
            skill: el("skillRekrut").value
                .split(",")
                .map(nilai => nilai.trim())
                .filter(Boolean),
            status: el("statusRekrut").value
        };

        if (!data.skill.length) {
            alert("Masukkan minimal satu keahlian.");
            el("skillRekrut").focus();
            return;
        }

        if (id) {
            const item = posting.find(baris => String(baris.id) === id);
            if (!item) return;

            data.version = item.version;
        } else {
            data.competition = el("judulRekrut").value;
            data.deskripsi = el("deskripsi").value.trim();
            data.target = el("target").value.trim();
            data.kanal_diskusi = el("kanal_diskusi").value.trim();
        }

        const labelSebelumnya = tombolSimpan.textContent;

        sedangMenyimpan = true;
        tombolSimpan.disabled = true;
        tombolSimpan.textContent = id
            ? "Menyimpan perubahan…"
            : "Menyimpan rekrutmen…";

        form.setAttribute("aria-busy", "true");

        try {
            const hasil = await kirimRequest(
                id ? `${endpoint}/${id}` : endpoint,
                id ? "PUT" : "POST",
                data
            );

            tutupModal();

            if (!id && Array.isArray(hasil?.posting)) {
                // Controller terbaru mengembalikan kartu yang sudah tersimpan.
                posting.unshift(...hasil.posting);
                tampilkanPosting();
            } else {
                // Edit, atau controller lama yang hanya mengembalikan ID.
                await muatPosting();
            }
        } catch (error) {
            alert(error.message);
        } finally {
            sedangMenyimpan = false;
            tombolSimpan.disabled = false;
            tombolSimpan.textContent = labelSebelumnya;
            form.setAttribute("aria-busy", "false");
        }
    });

    daftar.addEventListener("click", async event => {
        if (sedangMemuat || sedangMenyimpan) return;

        if (event.target.closest("[data-muat-ulang]")) {
            await muatPosting();
            return;
        }

        const edit = event.target.closest("[data-edit-id]");
        if (edit) {
            bukaModal(edit.dataset.editId);
            return;
        }

        const hapus = event.target.closest("[data-hapus-id]");
        if (!hapus) return;

        const item = posting.find(
            baris => String(baris.id) === hapus.dataset.hapusId
        );
        if (!item) return;

        const yakin = confirm(
            `Hapus posisi "${item.posisi}" dari "${item.judul}"? ` +
            "Jika ini posisi terakhir, posting ikut dihapus."
        );

        if (!yakin) return;

        sedangMenyimpan = true;
        hapus.disabled = true;
        hapus.textContent = "Menghapus…";

        try {
            await kirimRequest(`${endpoint}/${item.id}`, "DELETE", {
                version: item.version
            });

            await muatPosting();
        } catch (error) {
            hapus.disabled = false;
            hapus.textContent = "Hapus Posisi";
            alert(error.message);
        } finally {
            sedangMenyimpan = false;
        }
    });

    muatPosting();
});