document.addEventListener('DOMContentLoaded', function () {

    const get = (id) => document.getElementById(id);

    const defaultPositions = [
        {
            name: 'UI/UX Designer',
            count: 1,
            skills: [
                'Figma',
                'Usability Testing',
                'Wireframing'
            ]
        },
        {
            name: 'Penyusun Proposal / Riset',
            count: 1,
            skills: [
                'Metodologi Penelitian',
                'Literatur Review'
            ]
        }
    ];

    let positions = defaultPositions;

    // Mengembalikan posisi apabila validasi server gagal
    try {
        const oldData = JSON.parse(
            get('oldPositionsData').textContent
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
    } catch (error) {
        positions = defaultPositions;
    }

    // Mencegah HTML berbahaya masuk ke tampilan
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

    // Menampilkan skill
    function renderSkills(skills) {
        return `
            <div class="skill-list">
                ${skills.map(skill => `
                    <span>${escapeHTML(skill)}</span>
                `).join('')}
            </div>
        `;
    }

    // Render semua posisi
    function renderPositions() {

        const positionsList = get('positionsList');
        const previewPositions = get('previewPositions');
        const hiddenInputs = get('positionHiddenInputs');

        positionsList.innerHTML = '';
        previewPositions.innerHTML = '';
        hiddenInputs.innerHTML = '';

        positions.forEach((position, index) => {

            // Kartu posisi di form sebelah kiri
            positionsList.innerHTML += `
                <div class="position-card">
                    <div class="position-top">
                        <strong>${escapeHTML(position.name)}</strong>

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

            // Kartu posisi di live preview
            previewPositions.innerHTML += `
                <div class="preview-position">
                    <strong>
                        ${escapeHTML(position.name)}
                    </strong>

                    ${renderSkills(position.skills)}
                </div>
            `;

            // Input hidden agar Laravel menerima data
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

                hiddenInputs.appendChild(input);
            });

        });

        // Total anggota yang dicari
        const total = positions.reduce(
            (sum, position) => sum + Number(position.count),
            0
        );

        get('previewCount').textContent = total;
    }

    // Update live preview
    function updatePreview() {

        get('previewTitle').textContent =
            get('nama_lomba').value.trim()
            || 'Judul Perlombaan Anda';

        get('previewOrganizer').textContent =
            get('penyelenggara').value.trim()
            || 'Puspresnas / Balai Prestasi UB';

        get('previewCategory').textContent =
            get('kategori').value
            || 'Teknologi & AI';

        get('previewDescription').textContent =
            get('deskripsi').value.trim()
            || 'Eduact Competition 2026';

        get('previewTarget').textContent =
            get('target').value.trim()
            || 'Target lolos ke babak final & meraih juara di tingkat nasional.';

        get('previewDeadline').textContent =
            get('deadline').value || '—';
    }

    // Update setiap input berubah
    [
        'nama_lomba',
        'penyelenggara',
        'kategori',
        'deskripsi',
        'target',
        'deadline'
    ].forEach(id => {
        get(id).addEventListener('input', updatePreview);
    });

    // Tambah posisi anggota
    get('addPosition').addEventListener('click', function () {

        const name = get('newRole').value.trim();
        const count = Number(get('newCount').value);

        const skills = get('newSkills').value
            .split(',')
            .map(skill => skill.trim())
            .filter(skill => skill !== '');

        if (
            !name ||
            !Number.isInteger(count) ||
            count < 1 ||
            count > 30 ||
            skills.length === 0
        ) {
            get('positionError').textContent =
                'Isi nama posisi, jumlah anggota (1–30), dan minimal satu keahlian.';
            return;
        }

        if (positions.length >= 20) {
            get('positionError').textContent =
                'Maksimal 20 posisi anggota.';
            return;
        }

        positions.push({
            name: name,
            count: count,
            skills: skills
        });

        get('newRole').value = '';
        get('newCount').value = '1';
        get('newSkills').value = '';
        get('positionError').textContent = '';

        renderPositions();
    });

    // Hapus posisi anggota
    get('positionsList').addEventListener('click', function (event) {

        const button = event.target.closest('.remove-position');

        if (!button) return;

        const index = Number(button.dataset.index);

        positions.splice(index, 1);

        renderPositions();
    });

    // Validasi sebelum submit
    get('postingForm').addEventListener('submit', function (event) {

        if (positions.length === 0) {
            event.preventDefault();

            get('positionError').textContent =
                'Minimal harus ada satu posisi anggota.';
        }

    });

    // Batasi deadline minimal hari ini (waktu lokal)
    const today = new Date();
    const localToday = new Date(
        today.getTime() - today.getTimezoneOffset() * 60000
    ).toISOString().slice(0, 10);

    get('deadline').min = localToday;

    // Jalankan pertama kali
    renderPositions();
    updatePreview();

});