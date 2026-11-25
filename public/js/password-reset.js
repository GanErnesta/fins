'use strict';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fins-password-toggle[data-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            if (!input) return;
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'Sembunyikan' : 'Lihat';
            button.setAttribute('aria-label', (visible ? 'Sembunyikan' : 'Tampilkan') + ' password');
            button.setAttribute('aria-pressed', String(visible));
        });
    });
});
