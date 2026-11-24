<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <title>Reset Password | FINS</title>

    {{-- CSS DENGAN VERSI OTOMATIS --}}
    <link rel="stylesheet"
          href="/css/supabase-auth-reset.css?v={{ filemtime(public_path('css/supabase-auth-reset.css')) }}">

    {{-- JAVASCRIPT DENGAN VERSI OTOMATIS --}}
    <script
        src="/js/supabase-reset-password.js?v={{ filemtime(public_path('js/supabase-reset-password.js')) }}"
        defer>
    </script>

</head>

<body>

<main class="auth-shell">

    <!-- LOGO FINS -->
    <a class="brand" href="{{ route('login', [], false) }}">
        F<span>UB</span>
    </a>

    <section class="auth-box">

        <div class="icon">🔒</div>

        <p class="eyebrow">
            AMANKAN AKUNMU
        </p>

        <h1>Buat Password Baru</h1>

        <p class="subtitle">
            Gunakan password baru minimal 8 karakter
            dan jangan bagikan kepada orang lain.
        </p>

        <!-- PESAN RECOVERY SUPABASE -->
        <div
            class="alert bad"
            id="recoveryMessage"
            role="alert"
            hidden>
        </div>

        <!-- PESAN ERROR VALIDASI LARAVEL -->
        @if($errors->any())

            <div class="alert bad" role="alert">

                @foreach($errors->all() as $error)

                    <p>{{ $error }}</p>

                @endforeach

            </div>

        @endif

        <!-- FORM RESET PASSWORD -->
        <form
            id="recoveryForm"
            method="post"
            action="{{ route('password.update', [], false) }}"
            hidden
        >

            @csrf

            <!-- RECOVERY TOKEN SUPABASE -->
            <input
                type="hidden"
                name="access_token"
                id="recoveryToken"
            >

            <!-- PASSWORD BARU -->
            <label for="password">
                Password Baru
            </label>

            <input
                type="password"
                name="password"
                id="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <!-- KONFIRMASI PASSWORD -->
            <label for="password_confirmation">
                Konfirmasi Password
            </label>

            <input
                type="password"
                name="password_confirmation"
                id="password_confirmation"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <!-- TOMBOL SIMPAN -->
            <button type="submit">
                Simpan Password Baru <span>→</span>
            </button>

        </form>

        <!-- MINTA LINK RESET BARU -->
        <a
            class="back"
            href="{{ route('password.request', [], false) }}"
        >
            ← Minta link reset baru
        </a>

    </section>

    <!-- FOOTER -->
    <p class="footer">
        FINS · Universitas Brawijaya
    </p>

</main>

</body>
</html>