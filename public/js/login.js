
document.addEventListener("DOMContentLoaded", function () {

    // ==============================
    // MENGAMBIL ELEMEN
    // ==============================

    const loginForm = document.querySelector(".login-form form");

    const nimInput = document.getElementById("nim");
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");

    const loginButton = document.querySelector(".login-button");


    // ==============================
    // VALIDASI INPUT NIM
    // ==============================

    if (nimInput) {

        nimInput.addEventListener("input", function () {

            // Hanya mengizinkan angka 0-9
            this.value = this.value.replace(/[^0-9]/g, "");

            // Menghapus pesan error setelah diperbaiki
            this.setCustomValidity("");

        });

        nimInput.addEventListener("invalid", function () {

            if (this.validity.valueMissing) {
                this.setCustomValidity("NIM wajib diisi.");
            } else if (this.validity.patternMismatch) {
                this.setCustomValidity("NIM hanya boleh berisi angka.");
            }

        });

    }


    // ==============================
    // TOGGLE SHOW / HIDE PASSWORD
    // ==============================

    if (passwordInput && togglePassword) {

        const eyeOpen = `
            <svg
                viewBox="0 0 24 24"
                width="20"
                height="20"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="M2 12s3.5-7 10-7 10 7
                    10 7-3.5 7-10 7S2 12 2 12z"
                />
                <circle cx="12" cy="12" r="3"/>
            </svg>
        `;

        const eyeClosed = `
            <svg
                viewBox="0 0 24 24"
                width="20"
                height="20"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M3 3l18 18"/>
                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                <path
                    d="M9.9 5.2A10.8 10.8 0 0 1 12 5
                    c5 0 8.5 4.5 9 7-.2 1.2-1.1 2.7-2.5 4"
                />
                <path
                    d="M6.2 6.2C3.8 7.8 2.3 10.2
                    2 12c.5 2.5 4 7 10 7
                    1 0 2-.2 2.9-.5"
                />
            </svg>
        `;

        // Ikon awal
        togglePassword.innerHTML = eyeOpen;

        togglePassword.setAttribute(
            "aria-label",
            "Tampilkan password"
        );

        togglePassword.setAttribute(
            "aria-pressed",
            "false"
        );

        togglePassword.addEventListener("click", function () {

            const isHidden = passwordInput.type === "password";

            // Mengubah tipe input
            passwordInput.type = isHidden ? "text" : "password";

            // Mengubah ikon mata
            togglePassword.innerHTML = isHidden
                ? eyeClosed
                : eyeOpen;

            // Mengubah label aksesibilitas
            togglePassword.setAttribute(
                "aria-label",
                isHidden
                    ? "Sembunyikan password"
                    : "Tampilkan password"
            );

            togglePassword.setAttribute(
                "aria-pressed",
                String(isHidden)
            );

        });

    }


    // ==============================
    // VALIDASI PASSWORD
    // ==============================

    if (passwordInput) {

        passwordInput.addEventListener("input", function () {

            this.setCustomValidity("");

        });

        passwordInput.addEventListener("invalid", function () {

            if (this.validity.valueMissing) {
                this.setCustomValidity("Password wajib diisi.");
            }

        });

    }


    // ==============================
    // PROSES SUBMIT LOGIN
    // ==============================

    if (loginForm) {

        loginForm.addEventListener("submit", function (event) {

            // Validasi NIM kosong
            if (!nimInput || nimInput.value.trim() === "") {

                event.preventDefault();

                if (nimInput) {
                    nimInput.setCustomValidity("NIM wajib diisi.");
                    nimInput.reportValidity();
                    nimInput.focus();
                }

                return;
            }

            // Validasi NIM hanya angka
            if (!/^[0-9]+$/.test(nimInput.value)) {

                event.preventDefault();

                nimInput.setCustomValidity(
                    "NIM hanya boleh berisi angka."
                );

                nimInput.reportValidity();
                nimInput.focus();

                return;
            }

            // Validasi password kosong
            if (!passwordInput || passwordInput.value === "") {

                event.preventDefault();

                if (passwordInput) {
                    passwordInput.setCustomValidity(
                        "Password wajib diisi."
                    );

                    passwordInput.reportValidity();
                    passwordInput.focus();
                }

                return;
            }

            // Mencegah klik login berulang
            if (loginButton) {

                loginButton.disabled = true;
                loginButton.textContent = "Memproses...";

            }

            // Form akan dikirim ke Laravel
            // melalui action="{{ route('login.submit') }}"

        });

    }

});
