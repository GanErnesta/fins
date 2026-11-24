@php
    $detail = $competition ?? $lomba ?? null;

    abort_if(!$detail, 404);

    $title = data_get($detail, 'title', 'Detail Lomba');
    $subtitle = data_get($detail, 'subtitle');
    $category = data_get($detail, 'category', '-');
    $organizer = data_get($detail, 'organizer', '-');
    $description = data_get(
        $detail,
        'description',
        'Belum ada deskripsi lomba.'
    );

    $team = data_get($detail, 'team', 1);
    $image = data_get($detail, 'image');
    $prize = data_get($detail, 'prize');
    $guide = data_get($detail, 'guide_url');

    $deadline = \Illuminate\Support\Carbon::parse(
        data_get($detail, 'deadline')
    );

    $skills = data_get($detail, 'skills', []);

    if (is_string($skills)) {
        $skills = json_decode($skills, true) ?: [];
    }

    if ($skills instanceof \Illuminate\Support\Collection) {
        $skills = $skills->all();
    }

    $skills = is_array($skills) ? $skills : [];

    $timeline = data_get($detail, 'timeline', []);

    if (is_string($timeline)) {
        $timeline = json_decode($timeline, true) ?: [];
    }

    $timeline = is_array($timeline) ? $timeline : [];

    $posterUrl = null;

    if ($image) {
        $posterUrl = \Illuminate\Support\Str::startsWith(
            $image,
            ['http://', 'https://']
        ) ? $image : '/' . ltrim($image, '/');
    }
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title }} | FINS</title>

    {{-- CSS dengan versi otomatis --}}
    <link rel="stylesheet"
          href="/css/cari_lomba_detail.css?v={{ filemtime(public_path('css/cari_lomba_detail.css')) }}">

    {{-- JavaScript dengan versi otomatis --}}
    <script
        src="/js/cari_lomba_detail.js?v={{ filemtime(public_path('js/cari_lomba_detail.js')) }}"
        defer>
    </script>
</head>

<body>

<div class="detail-overlay" id="detailOverlay">

    <div class="detail-modal" role="dialog"
         aria-modal="true"
         aria-labelledby="detailTitle">

        <div class="detail-header">

            @if($posterUrl)
                <img src="{{ $posterUrl }}"
                     alt="Poster {{ $title }}">
            @endif

            <div class="header-shade"></div>

            <a href="/cari-lomba"
               class="close-detail"
               id="closeDetail"
               aria-label="Tutup detail">
                &times;
            </a>

            <div class="header-content">

                <div class="detail-category">
                    {{ $category }}

                    @if($subtitle && $subtitle !== $category)
                        &nbsp;·&nbsp; {{ $subtitle }}
                    @endif
                </div>

                <h1 class="detail-title" id="detailTitle">
                    {{ $title }}
                </h1>

            </div>

        </div>

        <div class="detail-scroll">

            <div class="detail-info-grid">

                <div class="info-box">

                    <div class="info-label">
                        <span class="info-icon">♙</span>
                        Penyelenggara
                    </div>

                    <div class="info-value">
                        {{ $organizer }}
                    </div>

                </div>

                <div class="info-box">

                    <div class="info-label">
                        <span class="info-icon">▦</span>
                        Batas Akhir
                    </div>

                    <div class="info-value">
                        {{ $deadline->format('Y-m-d') }}
                    </div>

                </div>

                <div class="info-box">

                    <div class="info-label">
                        <span class="info-icon">♧</span>
                        Formasi Tim
                    </div>

                    <div class="info-value">
                        {{ $team == 1 ? 'Individu' : $team . ' Mahasiswa' }}
                    </div>

                </div>

                <div class="info-box">

                    <div class="info-label">
                        <span class="info-icon">♕</span>
                        Hadiah / Dana
                    </div>

                    <div class="info-value prize">
                        {{ $prize ?: 'Belum tersedia' }}
                    </div>

                </div>

            </div>

            <section class="detail-section">

                <h3>Tentang Kompetisi</h3>

                <p>{{ $description }}</p>

            </section>

            <section class="detail-section">

                <h3>Kompetensi &amp; Keahlian yang Relevan</h3>

                <div class="detail-skills">

                    @forelse($skills as $skill)

                        <span>{{ $skill }}</span>

                    @empty

                        <p>Belum ada informasi skill.</p>

                    @endforelse

                </div>

            </section>

            <section class="detail-section">

                <h3>Timeline Penting</h3>

                <div class="timeline-list">

                    @forelse($timeline as $event)

                        <div class="timeline-item">

                            <div class="timeline-left">

                                <span class="timeline-icon">⊙</span>

                                <span class="timeline-name">
                                    {{ data_get($event, 'name', 'Kegiatan') }}
                                </span>

                            </div>

                            <span class="timeline-date">
                                {{ data_get($event, 'date', '-') }}
                            </span>

                        </div>

                    @empty

                        <div class="timeline-item">

                            <div class="timeline-left">

                                <span class="timeline-icon">⊙</span>

                                <span class="timeline-name">
                                    Batas akhir pendaftaran
                                </span>

                            </div>

                            <span class="timeline-date">
                                {{ $deadline->translatedFormat('d M Y') }}
                            </span>

                        </div>

                    @endforelse

                </div>

            </section>

        </div>

        <div class="detail-footer">

            @if($guide)

                <a href="{{ $guide }}"
                   class="guide-link"
                   target="_blank"
                   rel="noopener noreferrer">

                    <span>▤</span>
                    Unduh Panduan Resmi

                </a>

            @endif

            <div class="footer-actions">

                <a href="/cari-partner"
                   class="btn-partner">

                    Cari Partner

                </a>

                <a href="/tim-saya"
                   class="btn-team">

                    Buat Tim Baru &nbsp; →

                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>