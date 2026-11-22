<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset Password | FINS</title>
    <link rel="stylesheet" href="/css/supabase-auth-reset.css?v=3">
    <script src="/js/supabase-reset-password.js?v=3" defer></script>
</head>
<body>
<main class="auth-shell">
    <a class="brand" href="{{ route('login', [], false) }}">F<span>UB</span> </a>
    <section class="auth-box">
        <div class="icon">🔒</div>
        <p class="eyebrow">AMANKAN AKUNMU</p>
        <h1>Buat Password Baru</h1>
        <p class="subtitle">Gunakan password baru minimal 8 karakter dan jangan bagikan kepada orang lain.</p>
        <div class="alert bad" id="recoveryMessage" role="alert" hidden></div>
        @if($errors->any())
            <div class="alert bad" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form id="recoveryForm" method="post" action="{{ route('password.update', [], false) }}" hidden>
            @csrf
            <input type="hidden" name="access_token" id="recoveryToken">
            <label for="password">Password Baru</label>
            <input type="password" name="password" id="password" minlength="8" autocomplete="new-password" required>
            <label for="password_confirmation">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   minlength="8" autocomplete="new-password" required>
            <button type="submit">Simpan Password Baru <span>→</span></button>
        </form>
        <a class="back" href="{{ route('password.request', [], false) }}">← Minta link reset baru</a>
    </section>
    <p class="footer">FINS · Universitas Brawijaya</p>
</main>
</body>
</html>
