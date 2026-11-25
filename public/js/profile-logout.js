// FINS: popup logout. Tidak mengubah script Profile yang lain.
document.addEventListener('DOMContentLoaded', () => {
    const openButton = document.getElementById('openLogoutModal');
    const overlay = document.getElementById('finsLogoutModal');
    const cancelButton = document.getElementById('cancelLogoutModal');
    const submitButton = document.getElementById('confirmLogoutButton');
    const form = document.getElementById('finsLogoutForm');

    if (!openButton || !overlay || !cancelButton || !submitButton || !form) return;

    let previousFocus = null;

    function openModal() {
        previousFocus = document.activeElement;
        overlay.hidden = false;
        document.body.classList.add('fins-logout-open');
        cancelButton.focus();
    }

    function closeModal() {
        overlay.hidden = true;
        document.body.classList.remove('fins-logout-open');
        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    openButton.addEventListener('click', openModal);
    cancelButton.addEventListener('click', closeModal);
    overlay.addEventListener('click', event => {
        if (event.target === overlay) closeModal();
    });

    document.addEventListener('keydown', event => {
        if (overlay.hidden) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
        }
        // Jaga fokus tetap di tombol dialog.
        if (event.key === 'Tab') {
            const nodes = [cancelButton, submitButton];
            const activeIndex = nodes.indexOf(document.activeElement);
            if (event.shiftKey && activeIndex <= 0) {
                event.preventDefault();
                submitButton.focus();
            } else if (!event.shiftKey && activeIndex === nodes.length - 1) {
                event.preventDefault();
                cancelButton.focus();
            }
        }
    });

    form.addEventListener('submit', () => {
        submitButton.disabled = true;
        submitButton.textContent = 'Mengeluarkan akun...';
    });
});
