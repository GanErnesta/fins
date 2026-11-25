'use strict';

/**
 * FINS - Profile page (Laravel + tabel users)
 * Memerlukan route dari ProfileController serta window.finsProfileConfig
 * yang disediakan pada resources/views/profile.blade.php.
 */
document.addEventListener('DOMContentLoaded', () => {
    const $ = (id) => document.getElementById(id);
    const modal = $('editProfileModal');
    const form = $('editProfileForm');
    const openButton = $('openEditProfile');
    const cancelButton = $('cancelEditProfile');
    const saveButton = $('saveProfileButton') || form?.querySelector('[type="submit"]');
    const photoInput = $('profilePhotoInput');
    const removePhotoButton = $('removePhotoButton');
    const selectedSkills = $('selectedSkills');
    const addSkillButton = $('addSkillButton');
    const skillDropdown = $('skillDropdown');
    const skillSearchInput = $('skillSearchInput');
    const skillOptions = $('skillOptions');
    const dropdownSkillCount = $('dropdownSkillCount');
    const skillEmptyState = $('skillEmptyState');
    const skillHint = $('skillHint');
    const message = $('profileFormMessage');

    if (!modal || !form || !openButton) return;

    const cfg = window.finsProfileConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || form.querySelector('input[name="_token"]')?.value
        || '';

    let skills = Array.isArray(cfg.initialSkills)
        ? [...cfg.initialSkills]
        : Array.from(selectedSkills?.querySelectorAll('[data-skill]') || [])
            .map((element) => element.dataset.skill);
    let initials = cfg.initials || initialsFromName($('editFullname')?.value || 'FINS');
    let savedFormData = snapshotForm();
    let savedSkills = [...skills];
    let isSaving = false;
    let isUploadingPhoto = false;

    function initialsFromName(name) {
        return String(name || '').trim().split(/\s+/).filter(Boolean)
            .slice(0, 2).map((part) => part.charAt(0).toUpperCase()).join('') || 'F';
    }

    function showMessage(text, type = '') {
        if (!message) return;
        message.textContent = text || '';
        message.classList.remove('is-error', 'is-success');
        if (type === 'error') message.classList.add('is-error');
        if (type === 'success') message.classList.add('is-success');
        message.setAttribute('role', type === 'error' ? 'alert' : 'status');
    }

    function snapshotForm() {
        return Object.fromEntries(
            ['editFullname', 'editUsername', 'editFaculty', 'editStudyProgram',
                'editBio', 'editInstagram', 'editGithub', 'editLinkedin']
                .map((id) => [id, $(id)?.value ?? ''])
        );
    }

    function restoreForm() {
        for (const [id, value] of Object.entries(savedFormData)) {
            if ($(id)) $(id).value = value;
        }
        // Sync trigger dropdown setelah nilai form lama dipulihkan.
        window.FinsAcademics?.setValues?.(
            savedFormData.editFaculty,
            savedFormData.editStudyProgram,
            { preserveLegacy: true }
        );
        skills = [...savedSkills];
        if (skillSearchInput) skillSearchInput.value = '';
        renderSkills();
    }

    function setModal(open, resetUnsaved = true) {
        if (!open && (isSaving || isUploadingPhoto)) return;
        if (!open && resetUnsaved) restoreForm();
        modal.classList.toggle('is-open', open);
        modal.setAttribute('aria-hidden', String(!open));
        document.body.classList.toggle('fins-modal-open', open);
        if (skillDropdown) skillDropdown.hidden = true;
        addSkillButton?.setAttribute('aria-expanded', 'false');
        showMessage('');
        if (open) $('editFullname')?.focus();
        else openButton.focus();
    }

    openButton.addEventListener('click', () => setModal(true));
    cancelButton?.addEventListener('click', () => setModal(false));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) setModal(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            setModal(false);
        }
    });

    // ===== PILIH SKILL =====
    function renderSkills() {
        if (!selectedSkills) return;
        selectedSkills.replaceChildren();

        skills.forEach((skill) => {
            const chip = document.createElement('span');
            chip.className = 'fins-edit-skill-chip';
            chip.dataset.skill = skill;
            chip.append(document.createTextNode('• ' + skill + ' '));

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.dataset.removeSkill = skill;
            remove.setAttribute('aria-label', 'Hapus ' + skill);
            chip.append(remove);
            selectedSkills.append(chip);
        });

        if (dropdownSkillCount) dropdownSkillCount.textContent = `${skills.length} Dipilih`;
        if (skillHint) {
            skillHint.textContent = skills.length >= 6
                ? 'Maksimal 6 skill. Hapus satu untuk memilih yang lain.'
                : `Skill terpilih: ${skills.length}. Maksimal 6 skill.`;
            skillHint.classList.remove('is-error');
        }
        syncSkillOptions();
    }

    function syncSkillOptions() {
        if (!skillOptions) return;
        const query = (skillSearchInput?.value || '').trim().toLocaleLowerCase('id-ID');
        let visibleCount = 0;

        skillOptions.querySelectorAll('[data-skill-option]').forEach((option) => {
            const skill = option.dataset.skillOption || '';
            const selected = skills.some((item) => item.toLowerCase() === skill.toLowerCase());
            const visible = skill.toLocaleLowerCase('id-ID').includes(query);

            option.classList.toggle('is-selected', selected);
            option.setAttribute('aria-pressed', String(selected));
            option.hidden = !visible;
            option.disabled = !selected && skills.length >= 6;
            if (visible) visibleCount++;

            const icon = option.querySelector('i');
            if (icon) icon.textContent = selected ? '✓' : '＋';
        });
        if (skillEmptyState) skillEmptyState.hidden = visibleCount > 0;
    }

    addSkillButton?.addEventListener('click', () => {
        if (!skillDropdown) return;
        const shouldOpen = skillDropdown.hidden;
        skillDropdown.hidden = !shouldOpen;
        addSkillButton.setAttribute('aria-expanded', String(shouldOpen));
        if (shouldOpen) {
            syncSkillOptions();
            skillSearchInput?.focus();
        }
    });

    skillSearchInput?.addEventListener('input', syncSkillOptions);

    skillOptions?.addEventListener('click', (event) => {
        const option = event.target.closest('[data-skill-option]');
        if (!option || option.disabled) return;
        const skill = option.dataset.skillOption;
        const index = skills.findIndex((item) => item.toLowerCase() === skill.toLowerCase());

        if (index >= 0) skills.splice(index, 1);
        else if (skills.length < 6) skills.push(skill);
        renderSkills();
    });

    selectedSkills?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-skill]');
        if (!button) return;
        skills = skills.filter((item) => item !== button.dataset.removeSkill);
        renderSkills();
    });

    // ===== TAMPILAN PROFIL SETELAH BERHASIL DISIMPAN =====
    function profileLink(platform, value) {
        const raw = String(value || '').trim();
        if (!raw) return null;
        if (/^https:\/\//i.test(raw)) {
            try {
                const url = new URL(raw);
                if (url.hostname.toLowerCase() === `${platform}.com`
                    || url.hostname.toLowerCase() === `www.${platform}.com`) return url.href;
                if (platform === 'linkedin' && /(^|\.)linkedin\.com$/i.test(url.hostname)) return url.href;
            } catch (_) { /* URL tidak valid */ }
            return null;
        }
        const handle = raw.replace(/^@/, '');
        if (!/^[a-zA-Z0-9_.-]{1,100}$/.test(handle)) return null;
        if (platform === 'instagram') return `https://www.instagram.com/${encodeURIComponent(handle)}/`;
        if (platform === 'github') return `https://github.com/${encodeURIComponent(handle)}`;
        if (platform === 'linkedin') return `https://www.linkedin.com/in/${encodeURIComponent(handle)}/`;
        return null;
    }

    function renderContact(platform, value) {
        const id = 'profile' + platform.charAt(0).toUpperCase() + platform.slice(1);
        const link = $(id);
        if (!link) return;
        link.textContent = value || 'Belum diisi';
        const url = profileLink(platform, value);
        link.href = url || '#';
        if (url) {
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        } else {
            link.removeAttribute('target');
            link.removeAttribute('rel');
        }
    }

    // Menggunakan URL Supabase yang sama untuk profil, modal, dan navbar.
    // Jika URL salah/tidak ada di bucket, jangan menampilkan gambar rusak.
    function renderAvatar(photoUrl) {
        const currentUrl = String(photoUrl || '').trim();

        for (const id of ['profileAvatar', 'modalAvatar']) {
            const el = $(id);
            if (!el) continue;
            el.replaceChildren();

            if (!currentUrl) {
                const span = document.createElement('span');
                span.textContent = initials;
                el.append(span);
                continue;
            }

            const img = document.createElement('img');
            img.alt = 'Foto profil';
            img.className = 'profile-photo-image';
            img.loading = 'eager';
            img.onerror = () => {
                if (!img.isConnected) return;
                console.error('[FINS] Gagal memuat foto:', {
                    url: currentUrl,
                    pathDiDatabase: cfg.photoPath || '(kosong)'
                });
                const fallback = document.createElement('span');
                fallback.textContent = initials;
                el.replaceChildren(fallback);
                if (modal.classList.contains('is-open')) {
                    showMessage('Foto belum bisa dimuat. Samakan path foto di tabel users dengan lokasi file di bucket Supabase.', 'error');
                }
            };
            img.src = currentUrl;
            el.append(img);
        }

        const navAvatar = $('finsNavbarAvatar');
        if (navAvatar) {
            navAvatar.onerror = () => {
                navAvatar.onerror = null;
                navAvatar.src = cfg.defaultNavbarPhoto || '';
            };
            navAvatar.src = currentUrl || cfg.defaultNavbarPhoto || '';
        }
    }

    function updateProfileUI(profile) {
        if (!profile) return;
        initials = profile.initials || initialsFromName(profile.fullname);
        if ($('profileName')) $('profileName').textContent = profile.fullname || 'Pengguna FINS';
        if ($('profileStudyProgram')) {
            $('profileStudyProgram').textContent = profile.study_program || 'Program studi belum diisi';
        }
        if ($('profileBio')) {
            $('profileBio').textContent = profile.bio
                ? `“${profile.bio}”` : 'Tambahkan bio atau motto melalui Edit Profil';
        }
        const list = $('profileSkills');
        if (list) {
            list.replaceChildren();
            if (Array.isArray(profile.skills) && profile.skills.length) {
                profile.skills.forEach((skill) => {
                    const chip = document.createElement('span');
                    chip.className = 'fins-skill-chip';
                    chip.textContent = '• ' + skill;
                    list.append(chip);
                });
            } else {
                const empty = document.createElement('span');
                empty.className = 'fins-empty-data';
                empty.textContent = 'Belum ada skill. Tambahkan melalui Edit Profil.';
                list.append(empty);
            }
        }
        skills = Array.isArray(profile.skills) ? [...profile.skills] : [];
        savedSkills = [...skills];
        if ($('skillsCount')) $('skillsCount').textContent = `${skills.length} Terpasang`;
        if ($('statSkills')) $('statSkills').textContent = String(skills.length);
        ['instagram', 'github', 'linkedin'].forEach((platform) => {
            renderContact(platform, profile[platform]);
        });
        cfg.photoUrl = profile.photo_url || null;
        cfg.photoPath = profile.photo_path || null;
        renderAvatar(cfg.photoUrl);
        renderSkills();
    }

    async function parseResponse(response) {
        let data;
        try {
            data = await response.json();
        } catch (_) {
            throw new Error('Server tidak mengirim JSON. Periksa route, login, dan error Laravel.');
        }
        if (!response.ok || data.success === false) {
            const first = Object.values(data.errors || {})[0];
            const details = Array.isArray(first) ? first[0] : first;
            if (response.status === 419) throw new Error('Sesi berakhir. Refresh halaman dan coba lagi.');
            if (response.status === 401 || response.status === 403) {
                throw new Error('Akses ditolak. Silakan login kembali.');
            }
            throw new Error(details || data.message || 'Gagal memproses permintaan.');
        }
        return data;
    }

    async function request(url, method, body) {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body
        });
        return parseResponse(response);
    }

    // ===== SIMPAN KE TABEL USERS MELALUI PROFILECONTROLLER =====
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (isSaving || isUploadingPhoto) return;

        const fullname = $('editFullname')?.value.trim() || '';
        if (!fullname) {
            showMessage('Nama lengkap wajib diisi.', 'error');
            $('editFullname')?.focus();
            return;
        }
        if (skills.length > 6) {
            showMessage('Maksimal 6 skill.', 'error');
            return;
        }

        const updateUrl = cfg.updateUrl || form.action;
        if (!updateUrl || !csrf) {
            showMessage('Konfigurasi route atau CSRF belum tersedia pada profile.blade.php.', 'error');
            return;
        }

        const body = new FormData(form);
        body.delete('profile_photo'); // Foto menggunakan endpoint khusus.
        body.delete('skills');
        body.delete('skills[]');
        skills.forEach((skill) => body.append('skills[]', skill));
        if (!body.has('_method')) body.append('_method', 'PUT');

        isSaving = true;
        if (saveButton) saveButton.disabled = true;
        showMessage('Menyimpan perubahan...');
        try {
            const result = await request(updateUrl, 'POST', body);
            if (!result.profile) throw new Error('Data profil tidak ada dalam respons server.');
            updateProfileUI(result.profile);
            for (const [id, key] of Object.entries({
                editFullname: 'fullname', editUsername: 'username',
                editFaculty: 'faculty', editStudyProgram: 'study_program',
                editBio: 'bio', editInstagram: 'instagram',
                editGithub: 'github', editLinkedin: 'linkedin'
            })) {
                if ($(id)) $(id).value = result.profile[key] || '';
            }
            window.FinsAcademics?.setValues?.(
                result.profile.faculty,
                result.profile.study_program,
                { preserveLegacy: true }
            );
            savedFormData = snapshotForm();
            showMessage(result.message || 'Profil berhasil disimpan.', 'success');
            // Modal hanya ditutup setelah server mengonfirmasi perubahan berhasil.
            window.setTimeout(() => {
                isSaving = false;
                setModal(false, false);
            }, 650);
        } catch (error) {
            showMessage(error.message, 'error');
        } finally {
            isSaving = false;
            if (saveButton) saveButton.disabled = false;
        }
    });

    // ===== FOTO: LANGSUNG DISIMPAN DI STORAGE & USERS =====
    photoInput?.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        if (!file || isUploadingPhoto) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
            || file.size > 5 * 1024 * 1024) {
            showMessage('Foto harus JPG, PNG, atau WEBP dan maksimal 5 MB.', 'error');
            photoInput.value = '';
            return;
        }
        if (!cfg.photoUploadUrl || !csrf) {
            showMessage('Route upload foto belum tersedia.', 'error');
            photoInput.value = '';
            return;
        }
        const body = new FormData();
        body.append('profile_photo', file);
        isUploadingPhoto = true;
        if (removePhotoButton) removePhotoButton.disabled = true;
        showMessage('Mengunggah foto...');
        try {
            const result = await request(cfg.photoUploadUrl, 'POST', body);
            cfg.photoUrl = result.photoUrl || null;
            cfg.photoPath = result.photoPath || null;
            renderAvatar(cfg.photoUrl);
            showMessage(result.message || 'Foto berhasil diunggah.', 'success');
        } catch (error) {
            showMessage(error.message, 'error');
        } finally {
            isUploadingPhoto = false;
            if (removePhotoButton) removePhotoButton.disabled = false;
            photoInput.value = '';
        }
    });

    removePhotoButton?.addEventListener('click', async () => {
        if (isUploadingPhoto) return;
        if (!cfg.photoRemoveUrl || !csrf) {
            showMessage('Route hapus foto belum tersedia.', 'error');
            return;
        }
        isUploadingPhoto = true;
        removePhotoButton.disabled = true;
        showMessage('Menghapus foto...');
        try {
            const result = await request(cfg.photoRemoveUrl, 'DELETE');
            cfg.photoUrl = null;
            cfg.photoPath = null;
            renderAvatar(null);
            showMessage(result.message || 'Foto berhasil dihapus.', 'success');
        } catch (error) {
            showMessage(error.message, 'error');
        } finally {
            isUploadingPhoto = false;
            removePhotoButton.disabled = false;
        }
    });

    // Muat foto yang saat ini dikirim oleh Laravel, bukan path local storage.
    const initialPhotoUrl = cfg.photoUrl || $('profileAvatar')?.querySelector('img')?.getAttribute('src') || null;
    renderAvatar(initialPhotoUrl);
    renderSkills();
    renderContact('instagram', $('editInstagram')?.value || '');
    renderContact('github', $('editGithub')?.value || '');
    renderContact('linkedin', $('editLinkedin')?.value || '');
});
