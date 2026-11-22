
/* =========================================
   FYS - CARI LOMBA
   Find Your Skills Partner
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================
       AMBIL ELEMENT HTML
    ===================================== */

    const searchInput = document.getElementById("search");
    const deadlineFilter = document.getElementById("deadline");
    const organizerFilter = document.getElementById("organizer");
    const teamFilter = document.getElementById("team-size");
    const sortFilter = document.getElementById("sort");

    const categoryButtons = document.querySelectorAll(
        ".category-pill"
    );

    const competitionGrid = document.getElementById(
        "competition-grid"
    );

    const emptyState = document.getElementById(
        "empty-state"
    );

    const resultCount = document.getElementById(
        "result-count"
    );

    if (!competitionGrid) {
        return;
    }

    const competitionCards = Array.from(
        competitionGrid.querySelectorAll(".competition-card")
    );

    let selectedCategory = "Semua";

    /* =====================================
       NORMALISASI TEKS
    ===================================== */

    function normalizeText(value) {
        return String(value ?? "")
            .toLowerCase()
            .trim()
            .replace(/\s+/g, " ");
    }

    /* =====================================
       CEK STATUS DEADLINE
    ===================================== */

    function isCompetitionOpen(card) {

        if (card.dataset.open !== undefined) {
            return card.dataset.open === "1";
        }

        const deadline = card.dataset.deadline;

        if (!deadline) {
            return false;
        }

        const today = new Date();

        const year = today.getFullYear();
        const month = String(
            today.getMonth() + 1
        ).padStart(2, "0");

        const day = String(
            today.getDate()
        ).padStart(2, "0");

        const currentDate = `${year}-${month}-${day}`;

        return deadline >= currentDate;
    }

    /* =====================================
       FILTER DEADLINE
    ===================================== */

    function matchDeadline(card, selectedDeadline) {

        if (!selectedDeadline) {
            return true;
        }

        const isOpen = isCompetitionOpen(card);

        if (selectedDeadline === "open") {
            return isOpen;
        }

        if (selectedDeadline === "closed") {
            return !isOpen;
        }

        // Segera tutup: hari ini sampai maksimal 3 hari lagi.
        if (selectedDeadline === "closing") {
            if (!isOpen || !card.dataset.deadline) return false;
            const [year, month, day] = card.dataset.deadline.split("-").map(Number);
            if (!year || !month || !day) return false;
            const deadlineDay = new Date(year, month - 1, day);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const daysLeft = Math.round((deadlineDay - today) / 86400000);
            return daysLeft >= 0 && daysLeft <= 3;
        }

        return true;
    }

    /* =====================================
       FILTER UKURAN TIM
    ===================================== */

    function matchTeam(cardTeam, selectedTeam) {

        if (!selectedTeam) {
            return true;
        }

        const teamText = normalizeText(cardTeam);

        if (
            selectedTeam === "1" &&
            (
                teamText === "individu" ||
                teamText === "individual" ||
                teamText === "solo"
            )
        ) {
            return true;
        }

        const teamNumber = parseInt(teamText, 10);

        if (Number.isNaN(teamNumber)) {
            return false;
        }

        return teamNumber === Number(selectedTeam);
    }

    /* =====================================
       FILTER KATEGORI
    ===================================== */

    function matchCategory(category) {

        if (selectedCategory === "Semua") {
            return true;
        }

        return normalizeText(category) ===
               normalizeText(selectedCategory);
    }

    /* =====================================
       SORTING LOMBA
    ===================================== */

    function sortCompetitions() {

        const sortValue = sortFilter
            ? sortFilter.value
            : "soon";

        const sortedCards = [...competitionCards];

        sortedCards.sort(function (cardA, cardB) {

            const titleA = normalizeText(
                cardA.dataset.title
            );

            const titleB = normalizeText(
                cardB.dataset.title
            );

            const deadlineA =
                cardA.dataset.deadline || "";

            const deadlineB =
                cardB.dataset.deadline || "";

            // DEADLINE TERJAUH
            if (sortValue === "latest") {
                return deadlineB.localeCompare(deadlineA);
            }

            // NAMA A-Z
            if (sortValue === "name") {
                return titleA.localeCompare(
                    titleB,
                    "id"
                );
            }

            // DEADLINE TERDEKAT
            return deadlineA.localeCompare(deadlineB);
        });

        sortedCards.forEach(function (card) {
            competitionGrid.appendChild(card);
        });
    }

    /* =====================================
       FILTER SEMUA LOMBA
    ===================================== */

    function filterCompetitions() {

        const searchValue = normalizeText(
            searchInput ? searchInput.value : ""
        );

        const deadlineValue = deadlineFilter
            ? deadlineFilter.value
            : "";

        const organizerValue = normalizeText(
            organizerFilter ? organizerFilter.value : ""
        );

        const teamValue = teamFilter
            ? teamFilter.value
            : "";

        let visibleCount = 0;

        competitionCards.forEach(function (card) {

            const title = normalizeText(
                card.dataset.title
            );

            const category = normalizeText(
                card.dataset.category
            );

            const organizer = normalizeText(
                card.dataset.organizer
            );

            const skills = normalizeText(
                card.dataset.skills
            );

            const description = normalizeText(
                card.querySelector(".description")
                    ?.textContent
            );

            const team = card.dataset.team || "";

            /* SEARCH */

            const searchableText = [
                title,
                category,
                organizer,
                skills,
                description
            ].join(" ");

            const matchesSearch =
                searchValue === "" ||
                searchableText.includes(searchValue);

            /* DEADLINE */

            const matchesDeadline = matchDeadline(
                card,
                deadlineValue
            );

            /* ORGANIZER */

            const matchesOrganizer =
                organizerValue === "" ||
                organizer === organizerValue;

            /* TEAM */

            const matchesTeam = matchTeam(
                team,
                teamValue
            );

            /* CATEGORY */

            const matchesCategory = matchCategory(
                category
            );

            /* HASIL AKHIR */

            const isVisible =
                matchesSearch &&
                matchesDeadline &&
                matchesOrganizer &&
                matchesTeam &&
                matchesCategory;

            card.hidden = !isVisible;

            if (isVisible) {
                visibleCount++;
            }
        });

        /* UPDATE JUMLAH LOMBA */

        if (resultCount) {
            resultCount.textContent =
                `Menampilkan ${visibleCount} dari ${competitionCards.length} ajang lomba`;
        }

        /* EMPTY STATE */

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }

        /* SORTING */

        sortCompetitions();
    }

    /* =====================================
       EVENT SEARCH
    ===================================== */

    if (searchInput) {
        searchInput.addEventListener(
            "input",
            filterCompetitions
        );
    }

    /* =====================================
       EVENT DEADLINE
    ===================================== */

    if (deadlineFilter) {
        deadlineFilter.addEventListener(
            "change",
            filterCompetitions
        );
    }

    /* =====================================
       EVENT ORGANIZER
    ===================================== */

    if (organizerFilter) {
        organizerFilter.addEventListener(
            "change",
            filterCompetitions
        );
    }

    /* =====================================
       EVENT UKURAN TIM
    ===================================== */

    if (teamFilter) {
        teamFilter.addEventListener(
            "change",
            filterCompetitions
        );
    }

    /* =====================================
       EVENT SORTING
    ===================================== */

    if (sortFilter) {
        sortFilter.addEventListener(
            "change",
            sortCompetitions
        );
    }

    /* =====================================
       EVENT KATEGORI
    ===================================== */

    categoryButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            selectedCategory =
                button.dataset.category || "Semua";

            categoryButtons.forEach(function (item) {

                const isSelected = item === button;

                item.classList.toggle(
                    "selected",
                    isSelected
                );

                item.setAttribute(
                    "aria-pressed",
                    String(isSelected)
                );
            });

            filterCompetitions();
        });
    });

    /* =====================================
       JALANKAN SAAT HALAMAN DIBUKA
    ===================================== */

    filterCompetitions();


    /* =====================================
       PARALLAX DUA ARAH - CARI LOMBA
       Tetap aktif saat scroll naik/turun.
       Efek hanya pada elemen visual, bukan filter.
    ===================================== */

    const introSection = document.querySelector(".intro");
    const filterPanel = document.querySelector(".filter-panel");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    const posterImages = competitionCards.map(card => ({
        card,
        image: card.querySelector(".poster-wrap img")
    }));

    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
    let parallaxQueued = false;

    // Progress bernilai 0 (di bawah viewport) hingga 1 (di atas).
    function sectionProgress(rect) {
        return clamp(
            (window.innerHeight - rect.top) / (window.innerHeight + rect.height),
            0,
            1
        );
    }

    function updateParallax() {
        parallaxQueued = false;
        if (reduceMotion.matches) return;

        const mobile = window.innerWidth <= 650;
        const viewHeight = window.innerHeight;

        if (introSection) {
            const rect = introSection.getBoundingClientRect();
            const progress = sectionProgress(rect);

            // Judul dan jumlah lomba bergerak berlawanan arah.
            introSection.style.setProperty(
                "--cl-title-shift",
                `${((progress - 0.5) * (mobile ? 20 : 42)).toFixed(2)}px`
            );
            introSection.style.setProperty(
                "--cl-count-shift",
                `${((0.5 - progress) * (mobile ? 12 : 24)).toFixed(2)}px`
            );
        }

        if (filterPanel) {
            const progress = sectionProgress(filterPanel.getBoundingClientRect());
            filterPanel.style.setProperty(
                "--cl-filter-shift",
                `${((0.5 - progress) * (mobile ? 10 : 20)).toFixed(2)}px`
            );
        }

        // Poster tetap berada dalam kartu; hanya gambar di dalamnya bergerak.
        posterImages.forEach(({ card, image }) => {
            if (!image || card.hidden) return;
            const rect = card.getBoundingClientRect();
            if (rect.bottom < -60 || rect.top > viewHeight + 60) return;
            const progress = sectionProgress(rect);
            const shift = (progress - 0.5) * (mobile ? 27 : 40);
            image.style.setProperty("--cl-poster-shift", `${shift.toFixed(2)}px`);
        });
    }

    function requestParallax() {
        if (parallaxQueued) return;
        parallaxQueued = true;
        window.requestAnimationFrame(updateParallax);
    }

    window.addEventListener("scroll", requestParallax, { passive: true });
    window.addEventListener("resize", requestParallax);
    reduceMotion.addEventListener?.("change", requestParallax);

    /* =====================================
       SCROLL REVEAL BERULANG
       Menggunakan CSS translate agar hover card tetap normal.
    ===================================== */
    const revealElements = [
        ...document.querySelectorAll(
            ".intro > div, .intro > .result-count, " +
            ".filter-panel, .competition-card, " +
            ".fys-footer-content > div"
        )
    ];

    if ("IntersectionObserver" in window && !reduceMotion.matches) {
        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.target.hidden) return;
                entry.target.classList.toggle(
                    "cl-is-visible",
                    entry.isIntersecting
                );
            });
        }, {
            threshold: 0.06,
            rootMargin: "0px 0px -4% 0px"
        });

        revealElements.forEach((element, index) => {
            element.classList.add("cl-scroll-reveal");
            if (element.classList.contains("competition-card")) {
                element.style.setProperty(
                    "--cl-reveal-delay",
                    `${(competitionCards.indexOf(element) % 4) * 65}ms`
                );
            }
            revealObserver.observe(element);
        });
    }

    requestParallax();

});
