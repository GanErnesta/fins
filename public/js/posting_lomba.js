
document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // MENGAMBIL ELEMEN HTML
    // ==========================================

    const get = (id) => document.getElementById(id);

    // ==========================================
    // DATA POSISI ANGGOTA DEFAULT
    // ==========================================

    const defaultPositions = [
        {
            name: 'UI/UX Designer',
            count: 1,
            skills: [
                'Figma',
                'Wireframing',
                'Usability Testing'
            ]
        },
        {
            name: 'Penyusun Proposal / Riset',
            count: 1,
            skills: [
                'Research',
                'Literatur Review'
            ]
        }
    ];

    let positions = defaultPositions.map(position => ({
        ...position,
        skills: [...position.skills]
    }));

    // Mengembalikan data jika validasi Laravel gagal
    try {
        const oldDataElement = get('oldPositionsData');

        if (oldDataElement) {
            const oldData = JSON.parse(
                oldDataElement.textContent
            );

            if (Array.isArray(oldData)) {
                positions = oldData.map(item => ({
                    name: String(item.name || ''),
                    count: Number(item.count) || 1,
                    skills: Array.isArray(item.skills)
                        ? item.skills
                        : String(item.skills || '')
                            .split(',')
                            .map(skill => skill.trim())
                            .filter(Boolean)
                }));
            }
        }
    } catch (error) {
        console.warn('Data posisi lama tidak dapat dibaca.');
    }

    // ==========================================
    // MENCEGAH HTML INJECTION
    // ==========================================

    function escapeHTML(value) {
        return String(value).replace(
            /[&<>"']/g,
            function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[char];
            }
        );
    }

    // ==========================================
    // MENAMPILKAN DAFTAR SKILL
    // ==========================================

    function renderSkills(skills) {
        return `
            <div class="skill-list">
                ${skills.map(skill => `
                    <span>${escapeHTML(skill)}</span>
                `).join('')}
            </div>
        `;
    }

    // ==========================================
    // MENAMPILKAN POSISI ANGGOTA
    // ==========================================

    function renderPositions() {

        const list = get('positionsList');
        const preview = get('previewPositions');
        const hidden = get('positionHiddenInputs');

        list.innerHTML = '';
        preview.innerHTML = '';
        hidden.innerHTML = '';

        positions.forEach((position, index) => {

            // Kartu posisi di sebelah kiri
            list.innerHTML += `
                <div class="position-card">

                    <div class="position-top">
                        <strong>
                            ${escapeHTML(position.name)}
                        </strong>

                        <span class="count-tag">
                            ${position.count} orang
                        </span>

                        <button
                            type="button"
                            class="remove-position"
                            data-index="${index}"
                            aria-label="Hapus posisi"
                        >
                            🗑
                        </button>
                    </div>

                    ${renderSkills(position.skills)}
                </div>
            `;

            // Posisi di Live Preview
            preview.innerHTML += `
                <div class="preview-position">

                    <strong>
                        ${escapeHTML(position.name)}
                        (${position.count} orang)
                    </strong>

                    ${renderSkills(position.skills)}
                </div>
            `;

            // Menyimpan data posisi dalam input hidden
            const fields = {
                name: position.name,
                count: position.count,
                skills: position.skills.join(', ')
            };

            Object.entries(fields).forEach(([field, value]) => {

                const input = document.createElement('input');

                input.type = 'hidden';
                input.name = `positions[${index}][${field}]`;
                input.value = value;

                hidden.appendChild(input);
            });
        });

        // Menghitung total anggota
        const total = positions.reduce(
            (sum, position) => sum + Number(position.count),
            0
        );

        get('previewCount').textContent = total;

        get('totalTeamText').textContent =
            `${total} orang`;
    }

    // ==========================================
    // MENENTUKAN JENIS LOMBA
    // ==========================================

    function getMode() {
        return document.querySelector(
            'input[name="mode_lomba"]:checked'
        )?.value || 'existing';
    }

    // ==========================================
    // LIVE PREVIEW INFORMASI LOMBA
    // ==========================================

    function updatePreview() {

        let title = '';
        let category = '';
        let organizer = '';
        let deadline = '';

        if (getMode() === 'existing') {

            const select = get('cari_lomba_id');
            const selected = select.selectedOptions[0];

            if (selected && selected.value) {

                title = selected.dataset.title || '';
                category = selected.dataset.category || '';
                organizer = selected.dataset.organizer || '';
                deadline = selected.dataset.deadline || '';
            }

        } else {

            title = get('nama_lomba').value.trim();
            category = get('kategori').value;
            organizer = get('penyelenggara').value.trim();
            deadline = get('deadline').value;
        }

        get('previewTitle').textContent =
            title || 'Judul Perlombaan Anda';

        get('previewCategory').textContent =
            category || 'Kategori Lomba';

        get('previewOrganizer').textContent =
            organizer || 'Belum dipilih';

        get('previewDeadline').textContent =
            deadline || '—';

        get('previewDescription').textContent =
            get('deskripsi').value.trim()
            || 'Deskripsi perlombaan dan ide tim akan muncul di sini.';

        get('previewTarget').textContent =
            get('target').value.trim()
            || 'Target tim belum ditentukan.';
    }

    // ==========================================
    // LIVE PREVIEW GAMBAR / POSTER
    // ==========================================

    const posterInput = get('image');
    const posterPreview = get('previewPoster');
    const posterPlaceholder = get('previewPosterPlaceholder');
    const existingLombaSelect = get('cari_lomba_id');

    // Menyimpan URL sementara gambar yang diupload
    let uploadedPosterURL = null;

    // Menampilkan gambar pada Live Preview
    function setPosterPreview(src) {

        if (!posterPreview || !posterPlaceholder) {
            return;
        }

        if (src) {

            posterPreview.src = src;

            posterPreview.hidden = false;

            posterPlaceholder.hidden = true;

        } else {

            posterPreview.removeAttribute('src');

            posterPreview.hidden = true;

            posterPlaceholder.hidden = false;
        }
    }

    // Memilih sumber gambar untuk preview
    function updatePosterPreview() {

        // Prioritaskan gambar yang baru diupload
        if (uploadedPosterURL) {

            setPosterPreview(uploadedPosterURL);

            return;
        }

        // Jika memilih lomba yang sudah ada
        if (getMode() === 'existing') {

            const selected =
                existingLombaSelect.selectedOptions[0];

            const existingPoster =
                selected?.dataset.image || '';

            setPosterPreview(existingPoster);

        } else {

            // Jika lomba baru belum mengupload gambar
            setPosterPreview('');
        }
    }

    // ==========================================
    // EVENT UPLOAD GAMBAR
    // ==========================================

    if (posterInput) {

        posterInput.addEventListener('change', function () {

            const file = this.files[0];

            // Hapus URL sementara sebelumnya
            if (uploadedPosterURL) {

                URL.revokeObjectURL(uploadedPosterURL);

                uploadedPosterURL = null;
            }

            // Jika tidak ada gambar dipilih
            if (!file) {

                updatePosterPreview();

                return;
            }

            // Format gambar yang diizinkan
            const allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            // Validasi format file
            if (!allowedTypes.includes(file.type)) {

                alert(
                    'Format gambar harus JPG, JPEG, PNG, atau WEBP.'
                );

                this.value = '';

                updatePosterPreview();

                return;
            }

            // Validasi maksimal 2 MB
            const maxFileSize = 2 * 1024 * 1024;

            if (file.size > maxFileSize) {

                alert(
                    'Ukuran poster maksimal 2 MB.'
                );

                this.value = '';

                updatePosterPreview();

                return;
            }

            // Membuat URL preview gambar
            uploadedPosterURL = URL.createObjectURL(file);

            // Memperbarui preview
            updatePosterPreview();
        });
    }

    // Jika gambar gagal dimuat
    if (posterPreview) {

        posterPreview.addEventListener('error', function () {

            setPosterPreview('');
        });
    }

    // ==========================================
    // MENGGANTI MODE LOMBA
    // ==========================================

    function toggleLombaMode() {

        const existing = getMode() === 'existing';

        get('existingLombaFields').hidden = !existing;

        get('newLombaFields').hidden = existing;

        const selectLomba = get('cari_lomba_id');

        selectLomba.disabled = !existing;

        selectLomba.required = existing;

        // Field ketika membuat lomba baru
        const newFields = [
            'nama_lomba',
            'kategori',
            'penyelenggara',
            'deadline'
        ];

        newFields.forEach(id => {

            const element = get(id);

            element.disabled = existing;

            element.required = !existing;
        });

        // Update preview informasi dan gambar
        updatePreview();

        updatePosterPreview();
    }

    // ==========================================
    // EVENT PILIH JENIS LOMBA
    // ==========================================

    document.querySelectorAll(
        'input[name="mode_lomba"]'
    ).forEach(radio => {

        radio.addEventListener(
            'change',
            toggleLombaMode
        );
    });

    // ==========================================
    // EVENT PILIH LOMBA DARI SUPABASE
    // ==========================================

    if (existingLombaSelect) {

        existingLombaSelect.addEventListener(
            'change',
            function () {

                updatePreview();

                updatePosterPreview();
            }
        );
    }

    // ==========================================
    // EVENT INPUT LIVE PREVIEW
    // ==========================================

    [
        'nama_lomba',
        'kategori',
        'penyelenggara',
        'deadline',
        'deskripsi',
        'target'
    ].forEach(id => {

        const element = get(id);

        if (!element) return;

        element.addEventListener(
            'input',
            updatePreview
        );

        element.addEventListener(
            'change',
            updatePreview
        );
    });

    // ==========================================
    // MENAMBAHKAN POSISI ANGGOTA BARU
    // ==========================================

    get('addPosition').addEventListener('click', function () {

        const name = get('newRole').value.trim();

        const count = Number(get('newCount').value);

        const skills = get('newSkills').value
            .split(',')
            .map(skill => skill.trim())
            .filter(Boolean);

        // Validasi posisi
        if (
            !name ||
            !Number.isInteger(count) ||
            count < 1 ||
            count > 30 ||
            skills.length === 0
        ) {

            get('positionError').textContent =
                'Isi nama posisi, jumlah 1–30 orang, dan minimal satu skill.';

            return;
        }

        // Maksimal 20 posisi
        if (positions.length >= 20) {

            get('positionError').textContent =
                'Maksimal 20 posisi anggota.';

            return;
        }

        // Tambahkan posisi ke array
        positions.push({
            name,
            count,
            skills
        });

        // Kosongkan input
        get('newRole').value = '';

        get('newCount').value = '1';

        get('newSkills').value = '';

        get('positionError').textContent = '';

        // Render ulang posisi
        renderPositions();
    });

    // ==========================================
    // MENGHAPUS POSISI ANGGOTA
    // ==========================================

    get('positionsList').addEventListener(
        'click',
        function (event) {

            const button = event.target.closest(
                '.remove-position'
            );

            if (!button) return;

            const index = Number(button.dataset.index);

            positions.splice(index, 1);

            renderPositions();
        }
    );

    // ==========================================
    // VALIDASI SEBELUM PUBLIKASI
    // ==========================================

    get('postingForm').addEventListener(
        'submit',
        function (event) {

            // Minimal satu posisi anggota
            if (positions.length === 0) {

                event.preventDefault();

                get('positionError').textContent =
                    'Minimal harus ada satu posisi anggota.';

                get('addPosition').focus();

                return;
            }

            // Menyiapkan data posisi untuk Laravel
            renderPositions();
        }
    );

    // ==========================================
    // DEADLINE MINIMAL HARI INI
    // ==========================================

    const today = new Date();

    const localToday = new Date(
        today.getTime()
        - today.getTimezoneOffset() * 60000
    ).toISOString().slice(0, 10);

    get('deadline').min = localToday;

    // ==========================================
    // INISIALISASI SAAT HALAMAN DIBUKA
    // ==========================================

    toggleLombaMode();

    renderPositions();

    updatePreview();

    updatePosterPreview();

});
