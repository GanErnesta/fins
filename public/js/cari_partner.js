
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
    let sendingInvite = false;

    const pageSize = 6;

    const chosen = type => checks
        .filter(input => input.checked && input.dataset.filter === type)
        .map(input => input.value);

    function matches(card) {
        if (
            query &&
            !(card.dataset.search || '')
                .toLowerCase()
                .includes(query.toLowerCase())
        ) {
            return false;
        }

        const skills = (card.dataset.skills || '').split('|');
        const interests = (card.dataset.interests || '').split('|');

        if (
            chosen('skill').length &&
            !chosen('skill').some(value =>
                skills.some(skill =>
                    skill.toLowerCase().includes(value.toLowerCase())
                )
            )
        ) {
            return false;
        }

        if (
            chosen('study').length &&
            !chosen('study').includes(card.dataset.study)
        ) {
            return false;
        }

        if (
            chosen('semester').length &&
            !chosen('semester').includes(card.dataset.semester)
        ) {
            return false;
        }

        if (
            chosen('interest').length &&
            !chosen('interest').some(value =>
                interests.some(interest =>
                    interest.toLowerCase().includes(value.toLowerCase())
                )
            )
        ) {
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

            const text =
                label?.querySelector('.option-text')?.textContent.trim() ||
                `Semester ${input.value}`;

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
            found.sort((a, b) =>
                (a.dataset.name || '').localeCompare(
                    b.dataset.name || '',
                    'id'
                )
            );
        } else if (sortBy === 'semester') {
            found.sort((a, b) =>
                Number(a.dataset.semester) -
                Number(b.dataset.semester)
            );
        } else {
            found.sort((a, b) =>
                Number(b.dataset.match) -
                Number(a.dataset.match)
            );
        }

        const total = found.length;
        const last = Math.max(1, Math.ceil(total / pageSize));

        page = Math.min(page, last);

        cards.forEach(card => {
            card.hidden = true;
            card.classList.remove('cp-is-visible');
        });

        found.forEach((card, index) => {
            card.hidden =
                index < (page - 1) * pageSize ||
                index >= page * pageSize;

            grid.appendChild(card);
        });

        grid.hidden = !total;

        if (empty) empty.hidden = !!total;

        if (resultCount) {
            resultCount.textContent = `${total} Mahasiswa`;
        }

        if (info) {
            const start = total
                ? (page - 1) * pageSize + 1
                : 0;

            info.textContent =
                `Menampilkan ${start}–${Math.min(page * pageSize, total)} ` +
                `dari ${total} mahasiswa potensial`;
        }

        if (pages) {
            pages.innerHTML = '';

            const makeButton = (
                text,
                targetPage,
                disabled,
                active
            ) => {
                const button = document.createElement('button');

                button.type = 'button';
                button.textContent = text;
                button.disabled = disabled;

                if (active) {
                    button.className = 'active';
                    button.setAttribute('aria-current', 'page');
                }

                button.addEventListener('click', () => {
                    page = targetPage;
                    render();
                });

                pages.appendChild(button);
            };

            makeButton(
                '‹',
                Math.max(1, page - 1),
                page === 1,
                false
            );

            for (let i = 1; i <= last; i++) {
                makeButton(String(i), i, false, i === page);
            }

            makeButton(
                '›',
                Math.min(last, page + 1),
                page === last,
                false
            );
        }

        renderChips();

        requestAnimationFrame(() => {
            updateVisibleReveals();
            requestParallax();
        });
    }

    checks.forEach(input => {
        input.addEventListener('change', () => {
            page = 1;
            render();
        });
    });

    document.getElementById('partnerSearchForm')
        ?.addEventListener('submit', event => {
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

    document.getElementById('clearPartnerSearch')
        ?.addEventListener('click', () => {
            if (search) search.value = '';

            query = '';
            page = 1;

            render();
        });

    sort?.addEventListener('change', () => {
        page = 1;
        render();
    });

    document.getElementById('applyFilters')
        ?.addEventListener('click', () => {
            page = 1;
            render();
        });

    function reset() {
        checks.forEach(input => {
            input.checked = false;
        });

        if (search) search.value = '';

        query = '';

        if (skillFilterSearch) {
            skillFilterSearch.value = '';
        }

        document.querySelectorAll(
            '#skillFilterList .check-option'
        ).forEach(option => {
            option.hidden = false;
        });

        page = 1;
        render();
    }

    [
        'resetFilters',
        'clearAllFilters',
        'emptyResetFilters'
    ].forEach(id => {
        document.getElementById(id)
            ?.addEventListener('click', reset);
    });

    skillFilterSearch?.addEventListener('input', event => {
        const searchText = event.target.value.toLowerCase();

        document.querySelectorAll(
            '#skillFilterList .check-option'
        ).forEach(option => {
            option.hidden = !option.textContent
                .toLowerCase()
                .includes(searchText);
        });
    });

    // =====================================
    // 3. MODAL AJAK KOLABORASI
    // =====================================
    function close() {
        if (!modal || sendingInvite) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');

        document.body.style.overflow = '';
        target = null;

        if (closeTimer) {
            clearTimeout(closeTimer);
        }

        closeTimer = null;
    }

    function open(card) {
        if (!modal || !card || sendingInvite) return;

        if (closeTimer) {
            clearTimeout(closeTimer);
        }

        closeTimer = null;
        target = card;

        const name =
            card.querySelector('.person-info h3')?.textContent ||
            'Partner';

        const nameInput =
            document.getElementById('invitePartnerName');

        const competitionInput =
            document.getElementById('inviteCompetition');

        const roleInput =
            document.getElementById('inviteRole');

        const messageInput =
            document.getElementById('inviteMessage');

        const feedback =
            document.getElementById('inviteFeedback');

        if (nameInput) {
            nameInput.value = name;
        }

        if (competitionInput) {
            competitionInput.value = '';
        }

        if (roleInput) {
            roleInput.value = '';
        }

        if (messageInput) {
            messageInput.value =
                `Hai ${name.split(' ')[0]}! ` +
                `Aku ingin mengajakmu berkolaborasi dalam tim lomba. ` +
                `Apakah kamu tertarik berdiskusi lebih lanjut?`;
        }

        if (feedback) {
            feedback.textContent = '';
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');

        document.body.style.overflow = 'hidden';
    }

    grid.addEventListener('click', event => {
        const invite = event.target.closest('[data-invite]');
        const profile = event.target.closest('[data-profile]');

        if (invite && !invite.disabled) {
            open(invite.closest('.partner-card'));
        }

        if (profile) {
            const card = profile.closest('.partner-card');

            const name =
                card?.querySelector('.person-info h3')?.textContent ||
                'Partner';

            alert(
                `Profil ${name}\n` +
                `Halaman detail profil dapat dihubungkan ` +
                `pada tahap berikutnya.`
            );
        }
    });

    document.getElementById('closeInviteModal')
        ?.addEventListener('click', close);

    document.getElementById('cancelInvite')
        ?.addEventListener('click', close);

    modal?.addEventListener('click', event => {
        if (event.target === modal) {
            close();
        }
    });

    document.addEventListener('keydown', event => {
        if (
            event.key === 'Escape' &&
            modal?.classList.contains('is-open')
        ) {
            close();
        }
    });

    // =====================================
    // 4. KIRIM UNDANGAN KE LARAVEL
    // =====================================
    document.getElementById('inviteForm')
        ?.addEventListener('submit', async event => {
            event.preventDefault();

            if (!target || sendingInvite) return;

            const receiverId = Number(target.dataset.id);
            const form = event.currentTarget;

            const submit = form.querySelector(
                'button[type="submit"]'
            );

            const feedback =
                document.getElementById('inviteFeedback');

            const csrf = document.querySelector(
                'meta[name="csrf-token"]'
            )?.content;

            const url = modal?.dataset.storeUrl;

            if (
                !Number.isInteger(receiverId) ||
                receiverId <= 0 ||
                !url ||
                !csrf
            ) {
                if (feedback) {
                    feedback.textContent =
                        'Konfigurasi undangan belum lengkap.';
                }

                return;
            }

            sendingInvite = true;
            submit.disabled = true;

            const originalSubmitText = submit.textContent;
            submit.textContent = 'Mengirim...';

            if (feedback) {
                feedback.textContent = 'Mengirim undangan...';
                feedback.style.color = '#786b60';
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        receiver_id: receiverId,

                        competition_name:
                            document.getElementById(
                                'inviteCompetition'
                            ).value.trim(),

                        role:
                            document.getElementById(
                                'inviteRole'
                            ).value,

                        message:
                            document.getElementById(
                                'inviteMessage'
                            ).value.trim()
                    })
                });

                const contentType =
                    response.headers.get('content-type') || '';

                if (!contentType.includes('application/json')) {
                    throw new Error(
                        response.status === 419
                            ? 'Sesi login kedaluwarsa. Muat ulang halaman dan coba lagi.'
                            : `Server mengembalikan respons yang tidak sesuai (HTTP ${response.status}).`
                    );
                }

                const result = await response.json();

                if (!response.ok || !result.success) {
                    const errors = result.errors
                        ? Object.values(result.errors)
                            .flat()
                            .join(' ')
                        : '';

                    throw new Error(
                        errors ||
                        result.message ||
                        `Gagal mengirim (HTTP ${response.status}).`
                    );
                }

                // Ubah tombol hanya jika server berhasil
                const button = target.querySelector('[data-invite]');

                if (button) {
                    button.textContent = 'Undangan Terkirim';
                    button.disabled = true;
                }

                if (feedback) {
                    feedback.style.color = '#167a4a';
                    feedback.textContent =
                        'Undangan berhasil disimpan dan siap dilihat penerima.';
                }

                closeTimer = setTimeout(close, 1400);

            } catch (error) {
                if (feedback) {
                    feedback.style.color = '#a21d1d';
                    feedback.textContent = error.message;
                }

            } finally {
                sendingInvite = false;
                submit.disabled = false;
                submit.textContent = originalSubmitText;
            }
        });

    // =====================================
    // 5. CEK UNDANGAN YANG SUDAH DIKIRIM
    // =====================================
    async function loadSentInvitations() {
        const url = modal?.dataset.sentUrl;

        if (!url) return;

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) return;

            const result = await response.json();

            if (!result.success || !Array.isArray(result.data)) {
                return;
            }

            const pendingIds = new Set(
                result.data
                    .filter(invitation =>
                        invitation.status === 'pending'
                    )
                    .map(invitation =>
                        String(invitation.receiver_id)
                    )
            );

            cards.forEach(card => {
                const button = card.querySelector('[data-invite]');

                if (!button) return;

                if (pendingIds.has(card.dataset.id)) {
                    button.textContent = 'Undangan Terkirim';
                    button.disabled = true;
                }
            });

        } catch (error) {
            console.warn(
                'Status undangan belum dapat dimuat:',
                error
            );
        }
    }

    // =====================================
    // 6. PARALLAX DUA ARAH
    // =====================================
    const hero = document.querySelector('.cp-hero');
    const heroText = hero?.querySelector('div:first-child');
    const heroPill = hero?.querySelector('.cp-active-pill');

    const searchBar = document.querySelector(
        '.cp-main > .cp-search'
    );

    const filterPanel = document.querySelector('.cp-filter-panel');
    const resultsHeader = document.querySelector('.results-header');
    const footer = document.querySelector('.cp-footer');

    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    );

    const clamp = (value, min, max) =>
        Math.min(Math.max(value, min), max);

    let parallaxPending = false;

    function progressInViewport(element) {
        const rect = element.getBoundingClientRect();

        return clamp(
            (window.innerHeight - rect.top) /
            (window.innerHeight + rect.height),
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

            heroText.style.setProperty(
                '--cp-hero-text-y',
                `${progress * (mobile ? 20 : 52)}px`
            );

            heroPill?.style.setProperty(
                '--cp-hero-pill-y',
                `${-progress * (mobile ? 14 : 35)}px`
            );
        }

        if (searchBar) {
            const progress =
                progressInViewport(searchBar) - 0.5;

            searchBar.style.setProperty(
                '--cp-search-y',
                `${progress * (mobile ? 12 : 26)}px`
            );
        }

        if (filterPanel) {
            const progress =
                progressInViewport(filterPanel) - 0.5;

            filterPanel.style.setProperty(
                '--cp-filter-y',
                `${-progress * (mobile ? 8 : 20)}px`
            );
        }

        if (resultsHeader) {
            const progress =
                progressInViewport(resultsHeader) - 0.5;

            resultsHeader.style.setProperty(
                '--cp-results-y',
                `${progress * (mobile ? 8 : 20)}px`
            );
        }

        cards.forEach(card => {
            if (card.hidden) return;

            const rect = card.getBoundingClientRect();

            if (
                rect.bottom <= 0 ||
                rect.top >= window.innerHeight
            ) {
                return;
            }

            const progress =
                progressInViewport(card) - 0.5;

            card.style.setProperty(
                '--cp-person-y',
                `${progress * (mobile ? 10 : 22)}px`
            );

            card.style.setProperty(
                '--cp-bio-y',
                `${-progress * (mobile ? 5 : 10)}px`
            );
        });
    }

    function requestParallax() {
        if (parallaxPending) return;

        parallaxPending = true;

        requestAnimationFrame(updateParallax);
    }

    // =====================================
    // 7. REVEAL SAAT SCROLL
    // =====================================
    const revealElements = [
        hero,
        filterPanel,
        resultsHeader,
        ...cards,
        footer
    ].filter(Boolean);

    const revealObserver =
        !reduceMotion.matches &&
        'IntersectionObserver' in window

            ? new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.target.hidden) return;

                    entry.target.classList.toggle(
                        'cp-is-visible',
                        entry.isIntersecting
                    );
                });

            }, {
                threshold: 0.06,
                rootMargin: '0px 0px -4% 0px'
            })

            : null;

    if (revealObserver) {
        revealElements.forEach(element => {
            element.classList.add('cp-scroll-reveal');

            if (element.classList.contains('partner-card')) {
                const order = cards.indexOf(element);

                element.style.transitionDelay =
                    `${(order % 3) * 75}ms`;
            }

            revealObserver.observe(element);
        });
    }

    function updateVisibleReveals() {
        if (!revealObserver) return;

        cards.forEach(card => {
            if (card.hidden) return;

            const rect = card.getBoundingClientRect();

            const inView =
                rect.top < window.innerHeight * 0.96 &&
                rect.bottom > 0;

            if (inView) {
                card.classList.add('cp-is-visible');
            }
        });
    }

    window.addEventListener(
        'scroll',
        requestParallax,
        { passive: true }
    );

    window.addEventListener(
        'resize',
        requestParallax
    );

    reduceMotion.addEventListener?.('change', () => {
        if (reduceMotion.matches) {
            revealElements.forEach(element => {
                element.classList.remove(
                    'cp-scroll-reveal',
                    'cp-is-visible'
                );
            });
        } else {
            requestParallax();
        }
    });

    render();
    requestParallax();
    loadSentInvitations();
});
