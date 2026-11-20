
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

        if (selectedTeam === "4") {
            return teamNumber >= 4;
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

});
