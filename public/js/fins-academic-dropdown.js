/*
 * FINS Academic Dropdown v1
 * Satu sumber data: config/fins_academics.php -> #finsAcademicData
 * Bisa digunakan pada Sign Up (fakultas/prodi) dan Edit Profil
 * (editFaculty/editStudyProgram) tanpa mengubah nama field POST Laravel.
 */
(() => {
    'use strict';

    function initAcademicDropdown() {
        const data = document.getElementById('finsAcademicData');
        if (!data) { console.error('[FINS] #finsAcademicData tidak ditemukan. Pastikan signup.blade.php versi terbaru sudah dipakai.'); return; }

        let programsByFaculty;
        let aliases;
        try {
            programsByFaculty = JSON.parse(data.dataset.faculties || '{}');
            aliases = JSON.parse(data.dataset.aliases || '{}');
        } catch (error) {
            console.error('[FINS] Data fakultas/prodi tidak valid:', error);
            return;
        }

        const faculty = document.getElementById('fakultas') || document.getElementById('editFaculty');
        const program = document.getElementById('prodi') || document.getElementById('editStudyProgram');
        if (!faculty || !program) return; // Step 2 memang tidak memiliki fakultas/prodi.
        if (faculty.dataset.finsAcademicInitialized === '1') return;
        faculty.dataset.finsAcademicInitialized = '1';

        const isSignup = faculty.id === 'fakultas';
        const initialFaculty = faculty.value;
        const initialProgram = program.value;
        const hint = document.getElementById('finsAcademicHint');
        const formMessage = document.getElementById('profileFormMessage');
        const allFaculties = Object.keys(programsByFaculty);
        const normalize = (v) => String(v || '').trim().toLocaleLowerCase('id-ID');

        function canonical(value) {
            if (programsByFaculty[value]) return value;
            if (aliases[value]) return aliases[value];
            const exact = allFaculties.find((item) => normalize(item) === normalize(value));
            if (exact) return exact;
            const old = Object.keys(aliases).find((item) => normalize(item) === normalize(value));
            return old ? aliases[old] : '';
        }

        function allowedPrograms(value) {
            return programsByFaculty[canonical(value)] || [];
        }

        const allWidgets = [];

        function createWidget(select, type) {
            const parent = select.parentNode;
            const wrapper = document.createElement('div');
            wrapper.className = 'fins-academic-select';
            wrapper.dataset.kind = type;
            parent.insertBefore(wrapper, select);
            wrapper.append(select);

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'fins-academic-trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.setAttribute('aria-label', type === 'faculty' ? 'Pilih fakultas' : 'Pilih program studi');

            const valueText = document.createElement('span');
            valueText.className = 'fins-academic-trigger__value';
            trigger.append(valueText);

            const chevron = document.createElement('span');
            chevron.className = 'fins-academic-trigger__chevron';
            chevron.setAttribute('aria-hidden', 'true');
            chevron.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
            trigger.append(chevron);

            const panel = document.createElement('div');
            panel.className = 'fins-academic-panel';
            const panelId = 'fins-academic-options-' + type;
            panel.id = panelId;
            trigger.setAttribute('aria-controls', panelId);

            const searchWrap = document.createElement('div');
            searchWrap.className = 'fins-academic-search';
            const search = document.createElement('input');
            search.type = 'search';
            search.autocomplete = 'off';
            search.placeholder = type === 'faculty' ? 'Cari fakultas...' : 'Cari program studi...';
            search.setAttribute('aria-label', search.placeholder);
            searchWrap.append(search);

            const list = document.createElement('div');
            list.className = 'fins-academic-options';
            list.setAttribute('role', 'listbox');
            list.setAttribute('aria-label', type === 'faculty' ? 'Pilihan fakultas' : 'Pilihan program studi');

            const empty = document.createElement('p');
            empty.className = 'fins-academic-empty';
            empty.textContent = 'Tidak ada pilihan yang cocok.';
            empty.hidden = true;

            panel.append(searchWrap, list, empty);
            wrapper.append(trigger, panel);

            let values = [];
            let isDisabled = false;
            let isOpen = false;

            // Batasi tinggi dropdown agar tidak keluar dari layar.
            // Bila ruang bawah sempit, tampilkan panel di atas input.
            function positionPanel() {
                const rect = wrapper.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const spaceBelow = Math.max(0, viewportHeight - rect.bottom - 12);
                const spaceAbove = Math.max(0, rect.top - 12);
                const preferredHeight = 240;
                const openUp = spaceBelow < preferredHeight + 12 && spaceAbove > spaceBelow;
                const availableSpace = openUp ? spaceAbove : spaceBelow;
                const height = Math.max(110, Math.min(preferredHeight, availableSpace));

                wrapper.classList.toggle('is-up', openUp);
                wrapper.style.setProperty('--fins-dropdown-height', `${Math.floor(height)}px`);
            }

            function updateButton() {
                const selected = values.find((item) => item.value === select.value);
                const placeholder = type === 'faculty' ? 'Pilih fakultas...' : 'Pilih program studi...';
                valueText.textContent = selected ? selected.label : (isDisabled ? 'Pilih fakultas dahulu' : placeholder);
                wrapper.classList.toggle('has-selection', Boolean(selected));
                trigger.disabled = isDisabled;
                trigger.setAttribute('aria-label', (type === 'faculty' ? 'Fakultas: ' : 'Program studi: ') + valueText.textContent);
            }

            function renderOptions() {
                const q = normalize(search.value);
                const filtered = values.filter((item) => normalize(item.label).includes(q));
                list.replaceChildren();
                filtered.forEach((item, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'fins-academic-option';
                    button.setAttribute('role', 'option');
                    button.setAttribute('aria-selected', String(select.value === item.value));
                    button.setAttribute('tabindex', '-1');
                    button.dataset.value = item.value;
                    if (select.value === item.value) button.classList.add('is-selected');
                    const label = document.createElement('span');
                    label.textContent = item.label;
                    const check = document.createElement('span');
                    check.className = 'fins-academic-check';
                    check.textContent = '✓';
                    check.setAttribute('aria-hidden', 'true');
                    button.append(label, check);
                    button.addEventListener('click', () => {
                        select.value = item.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        close();
                        trigger.focus();
                    });
                    button.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') { event.preventDefault(); close(); trigger.focus(); }
                        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                            event.preventDefault();
                            const options = Array.from(list.querySelectorAll('button'));
                            const position = options.indexOf(button);
                            const next = event.key === 'ArrowDown' ? position + 1 : position - 1;
                            (options[(next + options.length) % options.length] || search).focus();
                        }
                    });
                    list.append(button);
                });
                empty.hidden = filtered.length !== 0;
            }

            function close() {
                isOpen = false;
                wrapper.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
            }
            function open() {
                if (isDisabled) return;
                allWidgets.forEach((w) => w.close());
                positionPanel();
                isOpen = true;
                wrapper.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                search.value = '';
                renderOptions();
                search.focus();
            }

            // Menyesuaikan posisi kembali saat ukuran layar berubah.
            window.addEventListener('resize', () => {
                if (isOpen) positionPanel();
            });

            trigger.addEventListener('click', () => isOpen ? close() : open());
            trigger.addEventListener('keydown', (event) => {
                if (['Enter', ' ', 'ArrowDown'].includes(event.key) && !isOpen) {
                    event.preventDefault(); open();
                }
            });
            search.addEventListener('input', renderOptions);
            search.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') { event.preventDefault(); close(); trigger.focus(); }
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    list.querySelector('button')?.focus();
                }
            });
            select.addEventListener('change', () => { updateButton(); renderOptions(); });

            function setOptions(entries, selected = '', preserveLegacy = false) {
                values = entries.map((entry) => typeof entry === 'string'
                    ? { value: entry, label: entry }
                    : entry);
                if (preserveLegacy && selected && !values.some((item) => item.value === selected)) {
                    values.unshift({ value: selected, label: selected + ' (data sebelumnya)' });
                }
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = type === 'faculty' ? 'Pilih fakultas...' : 'Pilih program studi...';
                select.replaceChildren(placeholder);
                values.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.value;
                    option.textContent = item.label;
                    select.append(option);
                });
                select.value = values.some((item) => item.value === selected) ? selected : '';
                updateButton();
                renderOptions();
            }

            function setDisabled(disabled) { isDisabled = disabled; select.disabled = disabled; if (disabled) close(); updateButton(); }
            allWidgets.push({ close });
            wrapper.classList.add('is-ready');
            select.tabIndex = -1;
            return { setOptions, setDisabled, close, trigger, get value() { return select.value; } };
        }

        const facultyWidget = createWidget(faculty, 'faculty');
        const programWidget = createWidget(program, 'program');
        if (!facultyWidget || !programWidget) return;

        facultyWidget.setOptions(allFaculties, canonical(initialFaculty) || initialFaculty, !isSignup);

        function updatePrograms(desiredValue = '', preserveLegacy = false) {
            const selectedFaculty = faculty.value;
            const available = allowedPrograms(selectedFaculty);
            programWidget.setOptions(available, desiredValue, preserveLegacy && Boolean(selectedFaculty));
            programWidget.setDisabled(!selectedFaculty || available.length === 0);
            if (hint) {
                hint.textContent = !selectedFaculty
                    ? 'Pilih fakultas terlebih dahulu agar daftar prodi muncul.'
                    : available.length === 0
                        ? 'Belum ada daftar prodi untuk fakultas ini.'
                        : `${available.length} pilihan program studi tersedia.`;
                hint.classList.remove('is-error');
            }
        }
        faculty.addEventListener('change', () => updatePrograms('', false));
        updatePrograms(initialProgram, !isSignup);

        // Klik di luar dan Escape menutup dropdown.
        document.addEventListener('pointerdown', (event) => {
            if (!event.target.closest('.fins-academic-select')) {
                allWidgets.forEach((widget) => widget.close());
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') allWidgets.forEach((widget) => widget.close());
        });

        function setValues(fac, prog, opts = {}) {
            facultyWidget.setOptions(allFaculties, fac || '', Boolean(opts.preserveLegacy));
            updatePrograms(prog || '', Boolean(opts.preserveLegacy));
        }

        window.FinsAcademics = {
            setValues,
            isValid: () => Boolean(faculty.value && program.value
                && allowedPrograms(faculty.value).includes(program.value))
        };

        const form = faculty.closest('form');
        form?.addEventListener('submit', (event) => {
            const valid = window.FinsAcademics.isValid();
            // Jangan menghalangi penyimpanan data lama yang tidak berubah pada Edit Profil.
            const unchanged = !isSignup && faculty.value === initialFaculty && program.value === initialProgram;
            if (!valid && !unchanged) {
                event.preventDefault();
                event.stopImmediatePropagation();
                if (hint) {
                    hint.textContent = 'Pilih fakultas dan program studi yang sesuai sebelum melanjutkan.';
                    hint.classList.add('is-error');
                }
                if (!hint && formMessage) {
                    formMessage.textContent = 'Fakultas dan program studi harus sesuai.';
                    formMessage.classList.add('is-error');
                }
                if (!faculty.value) facultyWidget.trigger.focus();
                else programWidget.trigger.focus();
            }
        }, true);
    }

    // Aman untuk script normal, defer, maupun script yang dipasang setelah DOMContentLoaded.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAcademicDropdown, { once: true });
    } else {
        initAcademicDropdown();
    }
})();
