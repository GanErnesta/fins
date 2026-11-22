'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('recoveryForm');
    const tokenInput = document.getElementById('recoveryToken');
    const message = document.getElementById('recoveryMessage');
    const params = new URLSearchParams(window.location.hash.substring(1));
    const token = params.get('access_token');
    const type = params.get('type');
    const error = params.get('error_description') || params.get('error');

    // Fragment dapat berisi token; singkirkan dari address bar secepat mungkin.
    if (window.location.hash) {
        window.history.replaceState(null, '', window.location.pathname);
    }

    if (type === 'recovery' && token && !error) {
        tokenInput.value = token;
        form.hidden = false;
        message.hidden = true;
        return;
    }

    form.hidden = true;
    message.textContent = error
        ? 'Link reset password tidak valid atau sudah kedaluwarsa. Silakan minta link baru.'
        : 'Token recovery tidak ditemukan. Buka halaman ini menggunakan tombol reset dari email terbaru.';
    message.hidden = false;
});
