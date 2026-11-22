
document.addEventListener('DOMContentLoaded', () => {

    /* =========================================
       ELEMENT HOME FINS
    ========================================= */

    const track = document.getElementById('competitionTrack');
    const cards = track
        ? Array.from(track.querySelectorAll('.competition-card'))
        : [];

    const dots = document.getElementById('carouselDots');
    const prev = document.getElementById('previousCards');
    const next = document.getElementById('nextCards');

    const form = document.getElementById('teamSearchForm');
    const feedback = document.getElementById('searchFeedback');

    const competition = document.getElementById('competitionSelect');
    const skill = document.getElementById('skillSelect');
    const location = document.getElementById('locationSelect');

    const hero = document.querySelector('.home-hero');
    const intro = document.querySelector('.home-intro');
    const introImage = document.querySelector(
        '.home-intro .intro-image'
    );

    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    );

    const clamp = (value, min, max) =>
        Math.min(Math.max(value, min), max);


    /* =========================================
       1. CAROUSEL KOMPETISI
    ========================================= */

    let activePage = 0;
    const pageSize = 3;
    let visibleCards = [...cards];

    function renderCarousel() {

        if (!track || !dots || !prev || !next) return;

        const pageCount = Math.ceil(
            visibleCards.length / pageSize
        );

        activePage = clamp(
            activePage,
            0,
            Math.max(0, pageCount - 1)
        );

        cards.forEach(card => {
            card.hidden = true;
            card.classList.remove('is-visible');
        });

        visibleCards.forEach((card, index) => {

            const start = activePage * pageSize;
            const end = start + pageSize;

            const show = index >= start && index < end;

            card.style.order = String(index);
            card.hidden = !show;

        });

        dots.innerHTML = '';

        for (let i = 0; i < pageCount; i++) {

            const dot = document.createElement('button');

            dot.type = 'button';
            dot.className = i === activePage ? 'active' : '';

            dot.setAttribute(
                'aria-label',
                `Halaman kompetisi ${i + 1}`
            );

            dot.addEventListener('click', () => {
                activePage = i;
                renderCarousel();
            });

            dots.appendChild(dot);
        }

        prev.disabled = activePage === 0;
        next.disabled =
            pageCount === 0 || activePage >= pageCount - 1;
    }

    prev?.addEventListener('click', () => {
        if (activePage > 0) {
            activePage--;
            renderCarousel();
        }
    });

    next?.addEventListener('click', () => {

        const pageCount = Math.ceil(
            visibleCards.length / pageSize
        );

        if (activePage < pageCount - 1) {
            activePage++;
            renderCarousel();
        }
    });


    /* =========================================
       2. PENCARIAN KOMPETISI
    ========================================= */

    if (form && competition && skill && location && feedback) {

        form.addEventListener('submit', event => {

            event.preventDefault();

            const selectedCompetition =
                competition.value.toLowerCase();

            visibleCards = cards.filter(card => {

                const category =
                    (card.dataset.category || '').toLowerCase();

                const search =
                    (card.dataset.search || '').toLowerCase();

                return !selectedCompetition ||
                    category.includes(selectedCompetition) ||
                    search.includes(selectedCompetition);
            });

            activePage = 0;
            renderCarousel();

            if (!visibleCards.length) {
                feedback.textContent =
                    'Belum ada kompetisi yang sesuai.';
            } else if (skill.value || location.value) {
                feedback.textContent =
                    `Menampilkan ${visibleCards.length} kompetisi. ` +
                    'Filter skill dan lokasi belum diterapkan ' +
                    'karena data kartu belum tersedia.';
            } else {
                feedback.textContent =
                    `Menampilkan ${visibleCards.length} kompetisi.`;
            }

            document.getElementById('kompetisi')
                ?.scrollIntoView({
                    behavior: reduceMotion.matches
                        ? 'auto'
                        : 'smooth',
                    block: 'start'
                });
        });
    }


    /* =========================================
       3. PARALLAX DUA ARAH
       Scroll turun = bergerak maju
       Scroll naik  = bergerak kembali
    ========================================= */

    let ticking = false;

    
function updateParallax() {
    ticking = false;

    const mobile = window.innerWidth <= 768;
    const vh = window.innerHeight;

    if (reduceMotion.matches) return;

    // =====================================
    // 1. HERO PARALLAX
    // =====================================
    if (hero) {
        const rect = hero.getBoundingClientRect();

        const progress = clamp(
            -rect.top,
            0,
            rect.height
        );

        hero.style.setProperty(
            '--hero-bg-shift',
            `${clamp(
                progress * (mobile ? 0.12 : 0.25),
                0,
                mobile ? 30 : 85
            )}px`
        );

        hero.style.setProperty(
            '--hero-text-shift',
            `${clamp(
                progress * (mobile ? 0.08 : 0.15),
                0,
                mobile ? 20 : 50
            )}px`
        );
    }

    // =====================================
    // 2. INTRO PARALLAX
    // Gambar dan teks berlawanan arah
    // =====================================
    if (intro) {
        const rect = intro.getBoundingClientRect();

        const progress = clamp(
            (vh - rect.top) / (vh + rect.height),
            0,
            1
        );

        // Gambar bergerak lebih jauh
        const imageShift =
            (progress - 0.5) * (mobile ? 55 : 110);

        // Teks bergerak berlawanan arah
        const textShift =
            (0.5 - progress) * (mobile ? 20 : 48);

        intro.style.setProperty(
            '--intro-img-shift',
            `${imageShift}px`
        );

        intro.style.setProperty(
            '--intro-copy-shift',
            `${textShift}px`
        );
    }

    // =====================================
    // 3. SEARCH BAR PARALLAX
    // =====================================
    const searchBar = document.querySelector(
        '.team-search'
    );

    if (searchBar) {
        const rect = searchBar.getBoundingClientRect();

        const progress = clamp(
            (vh - rect.top) / (vh + rect.height),
            0,
            1
        );

        const searchShift =
            (progress - 0.5) * (mobile ? 14 : 30);

        searchBar.style.setProperty(
            '--search-shift',
            `${searchShift}px`
        );
    }
}


    function requestParallax() {

        if (ticking) return;

        ticking = true;

        requestAnimationFrame(updateParallax);
    }

    window.addEventListener(
        'scroll',
        requestParallax,
        { passive: true }
    );

    window.addEventListener('resize', requestParallax);

    if (reduceMotion.addEventListener) {
        reduceMotion.addEventListener('change', requestParallax);
    }


    /* =========================================
       4. SCROLL REVEAL BERULANG
       Scroll turun dan scroll naik
    ========================================= */

    const revealElements = document.querySelectorAll(
        '.home-intro .intro-copy, ' +
        '.competition-section > h2, ' +
        '.competition-card'
    );

    if (
        !reduceMotion.matches &&
        'IntersectionObserver' in window
    ) {

        revealElements.forEach((element, index) => {

            element.classList.add('fins-scroll-reveal');

            if (element.classList.contains('competition-card')) {
                element.style.transitionDelay =
                    `${(cards.indexOf(element) % 3) * 100}ms`;
            }
        });

        const observer = new IntersectionObserver(
            entries => {

                entries.forEach(entry => {

                    if (entry.isIntersecting) {

                        // Muncul saat masuk viewport
                        entry.target.classList.add(
                            'is-visible'
                        );

                    } else {

                        // Reset saat keluar viewport.
                        // Ketika scroll kembali, animasi
                        // akan dimainkan lagi.
                        entry.target.classList.remove(
                            'is-visible'
                        );

                    }
                });
            },
            {
                threshold: 0.12,
                rootMargin: '0px 0px -5% 0px'
            }
        );

        revealElements.forEach(element => {
            observer.observe(element);
        });
    }


    /* =========================================
       5. INISIALISASI
    ========================================= */

    renderCarousel();
    requestParallax();

});
