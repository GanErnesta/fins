
document.addEventListener("DOMContentLoaded", () => {

    // ==========================================
    // MENGAMBIL ELEMEN
    // ==========================================

    const form = document.querySelector("#signupForm");
    const nimInput = document.querySelector("#nim");
    const passwordInput = document.querySelector("#password");
    const confirmPassword = document.querySelector("#confirmPassword");
    const meter = document.querySelector(".password-meter");

    const submitButton = form?.querySelector(
        'button[type="submit"]'
    );

    // ==========================================
    // VALIDASI INPUT NIM
    // ==========================================

    if (nimInput) {
        nimInput.addEventListener("input", () => {
            nimInput.value = nimInput.value
                .replace(/\D/g, "")
                .slice(0, 15);
        });
    }

    // ==========================================
    // ICON MATA SHOW / HIDE PASSWORD
    // ==========================================

    const eyeOpen = `
        <svg viewBox="0 0 24 24"
             width="20" height="20"
             fill="none"
             stroke="currentColor"
             stroke-width="1.8"
             stroke-linecap="round"
             stroke-linejoin="round">
            <path d="M2 12s3.5-7 10-7 10 7
                     10 7-3.5 7-10 7S2 12 2 12z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    `;

    const eyeClosed = `
        <svg viewBox="0 0 24 24"
             width="20" height="20"
             fill="none"
             stroke="currentColor"
             stroke-width="1.8"
             stroke-linecap="round"
             stroke-linejoin="round">
            <path d="M3 3l18 18"/>
            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
            <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5
                     c5 0 8.5 4.5 9 7
                     -.2 1.2-1.1 2.7-2.5 4"/>
            <path d="M6.2 6.2C3.8 7.8 2.3 10.2
                     2 12c.5 2.5 4 7 10 7
                     1 0 2-.2 2.9-.5"/>
        </svg>
    `;

    document.querySelectorAll(".toggle-password")
        .forEach((button) => {

            const targetId = button.dataset.target;
            const target = document.getElementById(targetId);

            if (!target) return;

            // Kondisi awal: password tersembunyi
            button.innerHTML = eyeOpen;
            button.setAttribute("aria-pressed", "false");

            button.addEventListener("click", () => {

                const show = target.type === "password";

                target.type = show ? "text" : "password";

                // Mengubah icon mata
                button.innerHTML = show
                    ? eyeClosed
                    : eyeOpen;

                button.setAttribute(
                    "aria-label",
                    show
                        ? "Sembunyikan password"
                        : "Tampilkan password"
                );

                button.setAttribute(
                    "aria-pressed",
                    String(show)
                );
            });
        });

    // ==========================================
    // INDIKATOR KEKUATAN PASSWORD
    // ==========================================

    function updatePasswordStrength() {
        if (!passwordInput || !meter) return;

        const value = passwordInput.value;
        let score = 0;

        if (value.length >= 8) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[a-z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;
        if (value.length >= 12) score++;

        // Jika password kosong, semua bar tidak aktif
        if (value.length === 0) {
            score = 0;
        }

        const segments = meter.querySelectorAll("span");

        segments.forEach((segment, index) => {
            segment.classList.toggle(
                "active",
                index < score
            );

            segment.classList.remove(
                "weak",
                "medium",
                "strong"
            );

            if (index < score) {
                if (score <= 2) {
                    segment.classList.add("weak");
                } else if (score <= 4) {
                    segment.classList.add("medium");
                } else {
                    segment.classList.add("strong");
                }
            }
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener(
            "input",
            updatePasswordStrength
        );

        updatePasswordStrength();
    }

    // ==========================================
    // VALIDASI FORM
    // ==========================================

    if (form) {
        form.addEventListener("submit", (event) => {

            // Menentukan tahap berdasarkan form Laravel
            const isStep1 = !!form.querySelector("#nama");
            const isStep2 = !!form.querySelector("#email");

            // ==================================
            // VALIDASI STEP 1
            // ==================================

            if (isStep1) {
                const nama = form.querySelector("#nama")
                    ?.value.trim() ?? "";

                const nim = nimInput?.value.trim() ?? "";

                const fakultas = form.querySelector("#fakultas")
                    ?.value ?? "";

                const prodi = form.querySelector("#prodi")
                    ?.value ?? "";

                if (!nama || !nim || !fakultas || !prodi) {
                    event.preventDefault();

                    alert(
                        "Lengkapi semua data diri terlebih dahulu."
                    );

                    return;
                }

                if (!/^\d{10,15}$/.test(nim)) {
                    event.preventDefault();

                    alert(
                        "NIM harus terdiri dari 10–15 angka."
                    );

                    nimInput?.focus();
                    return;
                }
            }

            // ==================================
            // VALIDASI STEP 2
            // ==================================

            if (isStep2) {
                const email = form.querySelector("#email")
                    ?.value.trim().toLowerCase() ?? "";

                const password = passwordInput?.value ?? "";

                const confirm = confirmPassword?.value ?? "";

                const emailPattern =
                    /^[^\s@]+@student\.ub\.ac\.id$/i;

                if (!emailPattern.test(email)) {
                    event.preventDefault();

                    alert(
                        "Gunakan email mahasiswa UB (@student.ub.ac.id)."
                    );

                    form.querySelector("#email")?.focus();
                    return;
                }

                if (password.length < 8) {
                    event.preventDefault();

                    alert(
                        "Password minimal 8 karakter."
                    );

                    passwordInput?.focus();
                    return;
                }

                if (password !== confirm) {
                    event.preventDefault();

                    alert(
                        "Konfirmasi password tidak sesuai."
                    );

                    confirmPassword?.focus();
                    return;
                }
            }

            // ==================================
            // PROSES SUBMIT
            // ==================================

            // Mencegah klik berulang
            if (submitButton) {
                submitButton.disabled = true;

                submitButton.textContent = isStep1
                    ? "Memproses..."
                    : "Mendaftarkan...";
            }
        });
    }
});
