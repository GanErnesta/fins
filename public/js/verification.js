
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#verificationForm");
    const digits = Array.from(document.querySelectorAll(".otp-digit"));
    const hiddenOtp = document.querySelector("#otpValue");
    const verifyButton = document.querySelector("#verifyButton");
    const resendButton = document.querySelector("#resendButton");
    const resendForm = document.querySelector("#resendForm");
    const countdown = document.querySelector("#countdown");

    // Menggabungkan seluruh digit OTP
    const syncOtp = () => {
        if (hiddenOtp) {
            hiddenOtp.value = digits.map(input => input.value).join("");
        }
    };

    // Mengatur input enam digit OTP
    digits.forEach((input, index) => {
        input.addEventListener("input", () => {
            input.value = input.value.replace(/\D/g, "").slice(-1);
            syncOtp();

            if (input.value && index < digits.length - 1) {
                digits[index + 1].focus();
            }
        });

        input.addEventListener("keydown", (event) => {
            if (event.key === "Backspace" && !input.value && index > 0) {
                digits[index - 1].focus();
            }

            if (event.key === "ArrowLeft" && index > 0) {
                event.preventDefault();
                digits[index - 1].focus();
            }

            if (event.key === "ArrowRight" && index < digits.length - 1) {
                event.preventDefault();
                digits[index + 1].focus();
            }
        });

        // Paste OTP langsung ke enam kotak
        input.addEventListener("paste", (event) => {
            event.preventDefault();

            const pasted = event.clipboardData
                .getData("text")
                .replace(/\D/g, "")
                .slice(0, 6);

            if (!pasted) return;

            const numbers = pasted.split("");

            digits.forEach((digit, i) => {
                digit.value = numbers[i] || "";
            });

            syncOtp();

            digits[Math.min(numbers.length - 1, 5)].focus();
        });
    });

    // Validasi sebelum mengirim OTP
    if (form) {
        form.addEventListener("submit", (event) => {
            syncOtp();

            if (!hiddenOtp || !/^\d{6}$/.test(hiddenOtp.value)) {
                event.preventDefault();

                alert("Masukkan semua 6 digit kode verifikasi.");

                const emptyInput = digits.find(input => !input.value);

                (emptyInput || digits[0])?.focus();
                return;
            }

            if (verifyButton) {
                verifyButton.disabled = true;
                verifyButton.textContent = "Memeriksa kode...";
            }
        });
    }

    // Countdown kirim ulang OTP
    if (resendButton && countdown) {
        let remaining = 58;

        const updateCountdown = () => {
            const minutes = String(
                Math.floor(remaining / 60)
            ).padStart(2, "0");

            const seconds = String(
                remaining % 60
            ).padStart(2, "0");

            countdown.textContent = `${minutes}:${seconds}`;

            if (remaining <= 0) {
                resendButton.disabled = false;
                resendButton.textContent = "Kirim ulang kode";
                return;
            }

            remaining--;

            window.setTimeout(updateCountdown, 1000);
        };

        updateCountdown();

        // Kirim ulang melalui route Laravel
        resendButton.addEventListener("click", () => {
            if (resendButton.disabled || !resendForm) return;

            resendButton.disabled = true;
            resendButton.textContent = "Mengirim ulang...";

            resendForm.requestSubmit();
        });
    }
});
