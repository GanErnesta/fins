
document.addEventListener("DOMContentLoaded", function () {
    let activeOverlay = null;
    let previousFocus = null;
    let oldOverflow = "";
    let loading = false;

    document.addEventListener("click", async function (event) {
        const button = event.target.closest("[data-detail-link]");

        if (!button) return;

        if (
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey ||
            event.altKey ||
            event.button !== 0
        ) {
            return;
        }

        event.preventDefault();

        if (loading || activeOverlay) return;

        loading = true;
        previousFocus = button;

        try {
            const response = await fetch(button.href);

            if (!response.ok) {
                throw new Error("Gagal mengambil detail lomba");
            }

            const html = await response.text();

            const parsed = new DOMParser().parseFromString(
                html,
                "text/html"
            );

            const overlay = parsed.querySelector(".detail-overlay");

            if (!overlay) {
                throw new Error("Popup detail tidak ditemukan");
            }

            activeOverlay = document.importNode(overlay, true);
            document.body.appendChild(activeOverlay);

            oldOverflow = document.body.style.overflow;
            document.body.style.overflow = "hidden";

            const modal = activeOverlay.querySelector(".detail-modal");

            if (modal) {
                modal.setAttribute("tabindex", "-1");
                modal.focus();
            }

        } catch (error) {
            console.error(error);
            window.location.href = button.href;
        } finally {
            loading = false;
        }
    });

    function closeDetail() {
        if (!activeOverlay) return;

        activeOverlay.remove();
        activeOverlay = null;

        document.body.style.overflow = oldOverflow;

        if (previousFocus) {
            previousFocus.focus();
        }
    }

    document.addEventListener("click", function (event) {
        const closeButton = event.target.closest(
            ".close-detail"
        );

        if (closeButton && activeOverlay) {
            event.preventDefault();
            closeDetail();
        }

        if (activeOverlay && event.target === activeOverlay) {
            closeDetail();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && activeOverlay) {
            closeDetail();
        }
    });
});
