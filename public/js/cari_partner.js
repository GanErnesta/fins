document.addEventListener('DOMContentLoaded', () => {
    // =====================================
    // 1. ELEMENT CARI PARTNER
    // =====================================
    const grid = document.getElementById('partnerCardGrid');
    if (!grid) return;

    const cards = Array.from(grid.querySelectorAll('.partner-card'));
    const search = document.getElementById('partnerSearchInput');
    const sort = document.getElementById('partnerSort');
    const checks = Array.from(document.querySelectorAll('[data-filter]'));
    const chips = document.getElementById('activeFilterChips');
    const count = document.getElementById('activeFilterCount');
    const resultCount = document.getElementById('resultCount');
    const info = document.getElementById('paginationInfo');
    const pages = document.getElementById('pageButtons');
    const empty = document.getElementById('emptyPartnerState');
    const modal = document.getElementById('inviteModal');
    const activeFiltersBar = document.getElementById('activeFiltersBar');
    const skillFilterSearch = document.getElementById('skillFilterSearch');

    let query = '';
    let page = 1;
    let target = null;
    let closeTimer = null;
    const pageSize = 6;

    const chosen = type => checks
        .filter(input => input.checked && input.dataset.filter === type)
        .map(input => input.value);

    function matches(card) {
        if (query && !(card.dataset.search || '').toLowerCase().includes(query.toLowerCase())) {
            return false;
        }

        const skills = (card.dataset.skills || '').split('|');
        const interests = (card.dataset.interests || '').split('|');

        if (chosen('skill').length && !chosen('skill').some(value =>
            skills.some(skill => skill.toLowerCase().includes(value.toLowerCase())))) {
            return false;
        }
        if (chosen('study').length && !chosen('study').includes(card.dataset.study)) {
            return false;
        }
        if (chosen('semester').length && !chosen('semester').includes(card.dataset.semester)) {
            return false;
        }
        if (chosen('interest').length && !chosen('interest').some(value =>
            interests.some(interest => interest.toLowerCase().includes(value.toLowerCase())))) {
            return false;
        }
        return true;
    }

    function renderChips() {
        if (!chips || !count || !activeFiltersBar) return;

        chips.innerHTML = '';
        const active = checks.filter(input => input.checked);

        active.forEach(input => {
            const label = input.closest('label');
            const text = label?.querySelector('.option-text')?.textContent.trim()
                || `Semester ${input.value}`;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'filter-chip';
            button.append(document.createTextNode(text + ' '));
            const cross = document.createElement('span');
            cross.textContent = '×';
            button.appendChild(cross);
            button.addEventListener('click', () => {
                input.checked = false;
                page = 1;
                render();
            });
            chips.appendChild(button);
        });

        count.textContent = `${active.length} Aktif`;
        activeFiltersBar.style.display = active.length ? 'flex' : 'none';
    }

    // =====================================
    // 2. FILTER, SORT, PAGINATION
    // =====================================
    function render() {
        const found = cards.filter(matches);
        const sortBy = sort?.value || 'relevance';

        if (sortBy === 'name') {
            found.sort((a, b) => (a.dataset.name || '').localeCompare(b.dataset.name || '', 'id'));
        } else if (sortBy === 'semester') {
            found.sort((a, b) => Number(a.dataset.semester) - Number(b.dataset.semester));
        } else {
            found.sort((a, b) => Number(b.dataset.match) - Number(a.dataset.match));
        }

        const total = found.length;
        const last = Math.max(1, Math.ceil(total / pageSize));
        page = Math.min(page, last);

        cards.forEach(card => {
            card.hidden = true;
            card.classList.remove('cp-is-visible');
        });

        found.forEach((card, index) => {
            card.hidden = index < (page - 1) * pageSize || index >= page * pageSize;
            grid.appendChild(card);
        });

        grid.hidden = !total;
        if (empty) empty.hidden = !!total;
        if (resultCount) resultCount.textContent = `${total} Mahasiswa`;
        if (info) {
            const start = total ? (page - 1) * pageSize + 1 : 0;
            info.textContent = `Menampilkan ${start}–${Math.min(page * pageSize, total)} dari ${total} mahasiswa potensial`;
        }

        if (pages) {
            pages.innerHTML = '';
            const makeButton = (text, targetPage, disabled, active) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = text;
                button.disabled = disabled;
                if (active) button.className = 'active';
                if (active) button.setAttribute('aria-current', 'page');
                button.addEventListener('click', () => {
                    page = targetPage;
                    render();
                });
                pages.appendChild(button);
            };

            makeButton('‹', Math.max(1, page - 1), page === 1, false);
            for (let i = 1; i <= last; i++) {
                makeButton(String(i), i, false, i === page);
            }
            makeButton('›', Math.min(last, page + 1), page === last, false);
        }

        renderChips();
        requestAnimationFrame(() => {
            updateVisibleReveals();
            requestParallax();
        });
    }

    checks.forEach(input => input.addEventListener('change', () => {
        page = 1;
        render();
    }));

    document.getElementById('partnerSearchForm')?.addEventListener('submit', event => {
        event.preventDefault();
        query = search?.value.trim() || '';
        page = 1;
        render();
    });

    search?.addEventListener('input', () => {
        query = search.value.trim();
        page = 1;
        render();
    });

    document.getElementById('clearPartnerSearch')?.addEventListener('click', () => {
        if (search) search.value = '';
        query = '';
        page = 1;
        render();
    });

    sort?.addEventListener('change', () => {
        page = 1;
        render();
    });

    document.getElementById('applyFilters')?.addEventListener('click', () => {
        page = 1;
        render();
    });

    function reset() {
        checks.forEach(input => input.checked = false);
        if (search) search.value = '';
        query = '';
        if (skillFilterSearch) skillFilterSearch.value = '';
        document.querySelectorAll('#skillFilterList .check-option').forEach(option => {
            option.hidden = false;
        });
        page = 1;
        render();
    }

    ['resetFilters', 'clearAllFilters', 'emptyResetFilters'].forEach(id => {
        document.getElementById(id)?.addEventListener('click', reset);
    });

    skillFilterSearch?.addEventListener('input', event => {
        const searchText = event.target.value.toLowerCase();
        document.querySelectorAll('#skillFilterList .check-option').forEach(option => {
            option.hidden = !option.textContent.toLowerCase().includes(searchText);
        });
    });

    // =====================================
    // 3. MODAL AJAK KOLABORASI
    // =====================================
    function close() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        target = null;
        if (closeTimer) clearTimeout(closeTimer);
        closeTimer = null;
    }

    function open(card) {
        if (!modal || !card) return;
        if (closeTimer) clearTimeout(closeTimer);
        closeTimer = null;
        target = card;

        const name = card.querySelector('.person-info h3')?.textContent || 'Partner';
        const nameInput = document.getElementById('invitePartnerName');
        const competitionInput = document.getElementById('inviteCompetition');
        const roleInput = document.getElementById('inviteRole');
        const messageInput = document.getElementById('inviteMessage');
        const feedback = document.getElementById('inviteFeedback');

        if (nameInput) nameInput.value = name;
        if (competitionInput) competitionInput.value = '';
        if (roleInput) roleInput.value = '';
        if (messageInput) {
            messageInput.value = `Hai ${name.split(' ')[0]}! Aku ingin mengajakmu berkolaborasi dalam tim lomba. Apakah kamu tertarik berdiskusi lebih lanjut?`;
        }
        if (feedback) feedback.textContent = '';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    grid.addEventListener('click', event => {
        const invite = event.target.closest('[data-invite]');
        const profile = event.target.closest('[data-profile]');

        if (invite) open(invite.closest('.partner-card'));
        if (profile) {
            const card = profile.closest('.partner-card');
            const name = card?.querySelector('.person-info h3')?.textContent || 'Partner';
            alert(`Profil ${name}\nHalaman detail profil dapat dihubungkan pada tahap berikutnya.`);
        }
    });

    document.getElementById('closeInviteModal')?.addEventListener('click', close);
    document.getElementById('cancelInvite')?.addEventListener('click', close);
    modal?.addEventListener('click', event => {
        if (event.target === modal) close();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal?.classList.contains('is-open')) close();
    });

    document.getElementById('inviteForm')?.addEventListener('submit', event => {
        event.preventDefault();
        if (!target) return;
        const button = target.querySelector('[data-invite]');
        if (button) {
            button.textContent = 'Undangan Terkirim';
            button.disabled = true;
        }
        const feedback = document.getElementById('inviteFeedback');
        if (feedback) {
            feedback.textContent = 'Undangan berhasil disimulasikan (belum tersimpan ke database).';
        }
        // Sama seperti kode awal: ini masih simulasi lokal, belum kirim ke server.
        closeTimer = setTimeout(close, 1000);
    });

    // =====================================
    // 4. PARALLAX DUA ARAH (SCROLL NAIK/TURUN)
    // =====================================
    const hero = document.querySelector('.cp-hero');
    const heroText = hero?.querySelector('div:first-child');
    const heroPill = hero?.querySelector('.cp-active-pill');
    const searchBar = document.querySelector('.cp-main > .cp-search');
    const filterPanel = document.querySelector('.cp-filter-panel');
    const resultsHeader = document.querySelector('.results-header');
    const footer = document.querySelector('.cp-footer');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
    let parallaxPending = false;

    function progressInViewport(element) {
        const rect = element.getBoundingClientRect();
        return clamp(
            (window.innerHeight - rect.top) / (window.innerHeight + rect.height),
            0,
            1
        );
    }

    function updateParallax() {
        parallaxPending = false;
        if (reduceMotion.matches) return;
        const mobile = window.innerWidth <= 760;

        if (hero && heroText) {
            const progress = progressInViewport(hero) - 0.5;
            heroText.style.setProperty('--cp-hero-text-y', `${progress * (mobile ? 20 : 52)}px`);
            heroPill?.style.setProperty('--cp-hero-pill-y', `${-progress * (mobile ? 14 : 35)}px`);
        }

        if (searchBar) {
            const progress = progressInViewport(searchBar) - 0.5;
            searchBar.style.setProperty('--cp-search-y', `${progress * (mobile ? 12 : 26)}px`);
        }

        if (filterPanel) {
            const progress = progressInViewport(filterPanel) - 0.5;
            filterPanel.style.setProperty('--cp-filter-y', `${-progress * (mobile ? 8 : 20)}px`);
        }

        if (resultsHeader) {
            const progress = progressInViewport(resultsHeader) - 0.5;
            resultsHeader.style.setProperty('--cp-results-y', `${progress * (mobile ? 8 : 20)}px`);
        }

        // Bagian dalam kartu bergerak kecil berlawanan arah
        // sehingga tetap aman untuk tombol dan layout grid.
        cards.forEach(card => {
            if (card.hidden) return;
            const rect = card.getBoundingClientRect();
            if (rect.bottom <= 0 || rect.top >= window.innerHeight) return;
            const progress = progressInViewport(card) - 0.5;
            card.style.setProperty('--cp-person-y', `${progress * (mobile ? 10 : 22)}px`);
            card.style.setProperty('--cp-bio-y', `${-progress * (mobile ? 5 : 10)}px`);
        });
    }

    function requestParallax() {
        if (parallaxPending) return;
        parallaxPending = true;
        requestAnimationFrame(updateParallax);
    }

    // =====================================
    // 5. REVEAL BERULANG SAAT MASUK VIEWPORT
    // =====================================
    const revealElements = [
        hero,
        filterPanel,
        resultsHeader,
        ...cards,
        footer
    ].filter(Boolean);

    const revealObserver = !reduceMotion.matches && 'IntersectionObserver' in window
        ? new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.target.hidden) return;
                entry.target.classList.toggle('cp-is-visible', entry.isIntersecting);
            });
        }, {
            threshold: 0.06,
            rootMargin: '0px 0px -4% 0px'
        })
        : null;

    if (revealObserver) {
        revealElements.forEach((element, index) => {
            element.classList.add('cp-scroll-reveal');
            if (element.classList.contains('partner-card')) {
                const order = cards.indexOf(element);
                element.style.transitionDelay = `${(order % 3) * 75}ms`;
            }
            revealObserver.observe(element);
        });
    }

    function updateVisibleReveals() {
        if (!revealObserver) return;
        cards.forEach(card => {
            if (card.hidden) return;
            const rect = card.getBoundingClientRect();
            const inView = rect.top < window.innerHeight * 0.96 && rect.bottom > 0;
            if (inView) card.classList.add('cp-is-visible');
        });
    }

    window.addEventListener('scroll', requestParallax, { passive: true });
    window.addEventListener('resize', requestParallax);
    reduceMotion.addEventListener?.('change', () => {
        if (reduceMotion.matches) {
            revealElements.forEach(element => element.classList.remove('cp-scroll-reveal', 'cp-is-visible'));
        } else {
            // Pembacaan ulang posisi setelah pengaturan animasi berubah.
            requestParallax();
        }
    });

    // =====================================
    // 6. INITIAL LOAD
    // =====================================
    render();
    requestParallax();
});
