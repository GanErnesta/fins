{{-- Navbar bersama untuk semua halaman FINS --}}
<header class="fins-header">
    <div class="fins-header__inner">
        <a href="{{ route('home') }}" class="fins-header__brand" aria-label="FINS - Beranda">
            <span class="fins-header__brand-f">F</span>
            <span class="fins-header__brand-ub">UB</span>
        </a>

        <nav class="fins-header__nav" aria-label="Navigasi utama">
            <a href="{{ route('home') }}"
               class="{{ request()->routeIs('home') ? 'active' : '' }}"
               @if(request()->routeIs('home')) aria-current="page" @endif>
                Beranda
            </a>
            <a href="{{ route('cari-lomba') }}"
               class="{{ request()->routeIs('cari-lomba', 'cari_lomba.show') ? 'active' : '' }}"
               @if(request()->routeIs('cari-lomba', 'cari_lomba.show')) aria-current="page" @endif>
                Lomba
            </a>
            <a href="{{ route('cari-partner') }}"
               class="{{ request()->routeIs('cari-partner') ? 'active' : '' }}"
               @if(request()->routeIs('cari-partner')) aria-current="page" @endif>
                Cari Partner
            </a>
            <a href="{{ route('lomba-saya') }}"
               class="{{ request()->routeIs('lomba-saya') ? 'active' : '' }}"
               @if(request()->routeIs('lomba-saya')) aria-current="page" @endif>
                Lomba Saya
            </a>
            <a href="{{ route('lomba-saya') }}#tim-saya">Tim Saya</a>
        </nav>

        <div class="fins-header__actions">
            <a href="{{ route('chat') }}" class="fins-header__icon" aria-label="Buka chat">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z" />
                    <path d="M8 10h8M8 14h5" />
                </svg>
            </a>

            <details class="fins-header__notification">
                <summary class="fins-header__icon" aria-label="Buka notifikasi">
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
                    </svg>
                </summary>
                <div class="fins-header__dropdown">
                    <strong>Notifikasi</strong>
                    <p>Belum ada notifikasi.</p>
                </div>
            </details>

            <a href="{{ route('profile') }}" class="fins-header__profile" aria-label="Buka profil">
                <img src="{{ asset('assets/images/Group4.png') }}" alt="Foto profil">
            </a>
        </div>
    </div>
</header>
