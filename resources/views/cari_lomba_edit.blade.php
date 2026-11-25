<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lomba | FINS</title>

    <link rel="stylesheet"
          href="/css/navbar.css?v={{ filemtime(public_path('css/navbar.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/cari_lomba_edit.css') }}">
</head>
<body>

    <div class="edit-overlay">

        <div class="edit-modal">

            <a href="{{ route('cari-lomba') }}"
               class="edit-close"
               aria-label="Tutup">
                ×
            </a>

            <div class="edit-hero">
                <p class="edit-category">
                    {{ old('category', $lomba->category) }}
                </p>

                <h1 class="edit-title">
                    {{ old('title', $lomba->title) }}
                </h1>
            </div>

            <div class="edit-body">

                @if($errors->any())
                    <div class="edit-alert" role="alert">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="info-cards">
                    <div class="info-card">
                        <div class="info-label">👤 Penyelenggara</div>
                        <div class="info-value">
                            {{ old('organizer', $lomba->organizer) }}
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-label">🗓️ Batas Akhir</div>
                        <div class="info-value">
                            {{ \Carbon\Carbon::parse(old('deadline', $lomba->deadline))->format('Y-m-d') }}
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-label">👥 Formasi Tim</div>
                        <div class="info-value">
                            {{ old('team', $lomba->team) }} Mahasiswa
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-label">🏆 Status</div>
                        <div class="info-value">
                            Sedang Diedit
                        </div>
                    </div>
                </div>

                <form method="POST"
                      action="{{ route('cari_lomba.update', $lomba->id) }}">

                    @csrf
                    @method('PUT')

                    <h2 class="section-title">Tentang Kompetisi</h2>

                    <div class="field-group">
                        <label for="title">Nama Lomba</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="{{ old('title', $lomba->title) }}"
                            maxlength="255"
                            required
                        >
                    </div>

                    <div class="field-group">
                        <label for="subtitle">Subjudul</label>
                        <input
                            type="text"
                            id="subtitle"
                            name="subtitle"
                            value="{{ old('subtitle', $lomba->subtitle) }}"
                            maxlength="255"
                        >
                    </div>

                    <div class="field-row">
                        <div class="field-group">
                            <label for="category">Kategori</label>
                            <select id="category"
                                    name="category"
                                    required>
                                @php
                                    $categories = [
                                        'Teknologi dan AI',
                                        'PKM Kemendikbud',
                                        'Business Plan & Case',
                                        'UI/UX & Desain',
                                        'Essay',
                                        'Hackathon',
                                        'Poster',
                                    ];
                                    $selectedCategory = old('category', $lomba->category);
                                    if (!in_array($selectedCategory, $categories)) {
                                        $categories[] = $selectedCategory;
                                    }
                                @endphp

                                @foreach($categories as $category)
                                    <option value="{{ $category }}"
                                        @selected($selectedCategory === $category)>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field-group">
                            <label for="organizer">Penyelenggara</label>
                            <input
                                type="text"
                                id="organizer"
                                name="organizer"
                                value="{{ old('organizer', $lomba->organizer) }}"
                                maxlength="255"
                                required
                            >
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field-group">
                            <label for="deadline">Batas Akhir</label>
                            <input
                                type="date"
                                id="deadline"
                                name="deadline"
                                value="{{ old('deadline', \Carbon\Carbon::parse($lomba->deadline)->format('Y-m-d')) }}"
                                required
                            >
                        </div>

                        <div class="field-group">
                            <label for="team">Formasi Tim</label>
                            <input
                                type="number"
                                id="team"
                                name="team"
                                min="1"
                                value="{{ old('team', $lomba->team) }}"
                                required
                            >
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="description">Deskripsi Kompetisi</label>
                        <textarea
                            id="description"
                            name="description"
                            required
                        >{{ old('description', $lomba->description) }}</textarea>
                    </div>

                    <h2 class="section-title">Kompetensi & Keahlian yang Relevan</h2>

                    @php
                        $skills = old('skills', $lomba->skills ?? []);
                        if (is_string($skills)) {
                            $skills = json_decode($skills, true) ?: [];
                        }
                        $skills = is_array($skills) ? $skills : [];
                        if (count($skills) === 0) {
                            $skills = [''];
                        }
                    @endphp

                    <div class="field-group">
                        <div id="skill-container" class="skills-box">
                            @foreach($skills as $skill)
                                <div class="skill-row">
                                    <input
                                        type="text"
                                        name="skills[]"
                                        value="{{ $skill }}"
                                        placeholder="Contoh: Figma Mastery"
                                        maxlength="100"
                                        required
                                    >
                                    <button type="button"
                                            class="skill-remove"
                                            onclick="removeSkill(this)"
                                            aria-label="Hapus skill">
                                        ×
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <button type="button"
                                class="skill-add"
                                onclick="addSkill()">
                            + Tambah Skill
                        </button>
                    </div>

                    <h2 class="section-title">Timeline Penting</h2>

                    <div class="timeline-card">
                        <div class="timeline-left">
                            <span class="timeline-icon">!</span>
                            <span>Batas akhir pendaftaran</span>
                        </div>

                        <div class="timeline-date">
                            {{ \Carbon\Carbon::parse(old('deadline', $lomba->deadline))->format('d M Y') }}
                        </div>
                    </div>

                    <div class="edit-actions">
                        <a href="{{ route('cari-lomba') }}"
                           class="btn-secondary">
                            Batal
                        </a>

                        <button type="submit"
                                class="btn-primary">
                            Simpan Perubahan →
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script>
        function addSkill() {
            const container = document.getElementById('skill-container');

            const row = document.createElement('div');
            row.className = 'skill-row';

            row.innerHTML = `
                <input
                    type="text"
                    name="skills[]"
                    placeholder="Contoh: Figma Mastery"
                    maxlength="100"
                    required
                >
                <button
                    type="button"
                    class="skill-remove"
                    onclick="removeSkill(this)"
                    aria-label="Hapus skill"
                >
                    ×
                </button>
            `;

            container.appendChild(row);
            row.querySelector('input').focus();
        }

        function removeSkill(button) {
            const container = document.getElementById('skill-container');
            if (container.children.length > 1) {
                button.closest('.skill-row').remove();
            } else {
                button.closest('.skill-row').querySelector('input').value = '';
            }
        }
    </script>

</body>
</html>