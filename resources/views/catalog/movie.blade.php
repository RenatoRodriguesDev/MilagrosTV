@extends('layouts.app')

@section('title', $movie->localTitle() . ' — MilagrosTV')

@section('content')

{{-- Hero --}}
<div class="relative min-h-[50vh] flex items-end overflow-hidden">
    @if($movie->localPosterUrl())
    <div class="absolute inset-0">
        <img src="{{ $movie->localPosterUrl() }}" alt="" class="w-full h-full object-cover object-top scale-110" style="filter: blur(20px); transform-origin: top center;">
        <div class="absolute inset-0 bg-black/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a0a] via-[#0a0a0a]/40 to-black/20"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#0a0a0a]/80 to-transparent"></div>
    </div>
    @else
    <div class="absolute inset-0 bg-gradient-to-b from-gray-900 to-[#0a0a0a]"></div>
    @endif

    <div class="relative max-w-5xl mx-auto px-6 pt-28 pb-10 w-full flex gap-8 items-end">

        @if($movie->localPosterUrl())
        <div class="hidden sm:block flex-shrink-0">
            <img src="{{ $movie->localPosterUrl() }}" alt="{{ $movie->localTitle() }}"
                 class="w-36 rounded-xl shadow-2xl border border-white/10">
        </div>
        @endif

        <div class="flex-1 pb-2">
            <a href="{{ route('catalog.index', ['type' => 'movies']) }}"
               class="inline-flex items-center gap-1 text-gray-400 hover:text-white text-xs font-medium transition mb-4 group">
                <span class="group-hover:-translate-x-0.5 transition-transform">←</span>
                {{ __('serie.back') }}
            </a>

            <h1 class="text-3xl sm:text-4xl font-black text-white mb-2 leading-tight">{{ $movie->localTitle() }}</h1>

            @if($movie->original_title && $movie->original_title !== $movie->title)
            <p class="text-gray-400 text-sm mb-3 font-medium">{{ $movie->original_title }}</p>
            @endif

            <div class="flex flex-wrap items-center gap-3 text-sm mb-4">
                @if($movie->year)
                <span class="text-gray-300 font-medium">{{ $movie->year }}</span>
                @endif
                @if($movie->duration)
                <span class="text-gray-500">·</span>
                <span class="text-gray-300">{{ $movie->duration }} min</span>
                @endif
                @if($movie->rating)
                <span class="text-gray-500">·</span>
                <span class="flex items-center gap-1 text-yellow-400 font-bold">
                    ★ {{ number_format($movie->rating, 1) }}
                </span>
                @endif
            </div>

            @if(!empty($movie->localGenres()))
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($movie->localGenres() as $genre)
                <span class="bg-white/10 text-gray-200 text-xs px-3 py-1 rounded-full border border-white/10 font-medium">
                    {{ $genre }}
                </span>
                @endforeach
            </div>
            @endif

            @if($movie->synopsis)
            <p class="text-gray-300 text-sm leading-relaxed max-w-2xl line-clamp-3 mb-4">{{ $movie->localSynopsis() }}</p>
            @endif

            <div class="flex flex-wrap gap-2">
                @if($movie->video_path && $movie->hasVideo())
                <button onclick="playLocalMovie()"
                    class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
                    ▶ {{ __('serie.play') }}
                </button>
                @endif

                @if($movie->tmdb_id)
                <button onclick="playMovieOnline()"
                    class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Online
                </button>
                @endif

                <button onclick="playMoviePiratahub()"
                    class="flex items-center gap-2 bg-yellow-600/80 hover:bg-yellow-600 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> ESP
                </button>

                @if($movie->trailer_url)
                <button id="trailer-btn"
                    class="flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🎬 Trailer
                </button>
                @endif

                <button onclick="toggleWatchlistDetail(this)"
                    data-type="movie" data-id="{{ $movie->id }}"
                    class="flex items-center gap-2 border text-sm px-4 py-2 rounded-lg font-semibold transition
                        {{ $inWatchlist ? 'bg-yellow-600/20 border-yellow-600/40 text-yellow-400' : 'bg-white/10 border-white/20 text-white hover:bg-white/20' }}">
                    {{ $inWatchlist ? '🔖 ' . __('catalog.my_list') : '＋ ' . __('catalog.my_list') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Local movie player modal --}}
@if($movie->video_path && $movie->hasVideo())
<div id="movie-player-modal" class="hidden fixed inset-0 z-[999] flex items-center justify-center p-4" style="background:#000;">
    <div class="w-full max-w-5xl">
        <div class="flex items-center justify-between mb-3">
            <p class="text-gray-300 text-sm font-medium">{{ $movie->localTitle() }}</p>
            <button onclick="closeMoviePlayer()" class="text-gray-400 hover:text-white transition text-sm">{{ __('common.close') }}</button>
        </div>
        <div id="movie-plyr-wrap" style="display:block;">
            <video id="movie-video-player" controls playsinline style="width:100%;border-radius:12px;"></video>
        </div>
    </div>
</div>
@endif

{{-- Online player modal --}}
@if($movie->tmdb_id)
<div id="online-modal" class="hidden fixed inset-0 z-[9997] flex items-center justify-center p-4" style="background:#000;">
    <div class="w-full max-w-5xl">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <p class="text-gray-300 text-sm font-medium">{{ $movie->localTitle() }} · 🌐 Online</p>
                <div class="flex items-center gap-1.5">
                    <button id="msrc-0" onclick="switchMovieSource(0)" class="text-[10px] px-2 py-0.5 rounded bg-red-600 text-white font-semibold">1</button>
                    @if($movie->cinemacity_id)
                    <button id="msrc-cc" onclick="switchMovieSource('cc')" class="text-[10px] px-2 py-0.5 rounded bg-white/10 text-gray-400 hover:bg-white/20 font-semibold">ESP CC</button>
                    @endif
                </div>
            </div>
            <button onclick="closeOnlineModal()" class="text-gray-400 hover:text-white text-sm">✕ Fechar</button>
        </div>
        <div style="background:#000;border-radius:12px;line-height:0;">
            <video id="movie-hls-player" controls playsinline style="width:100%;height:75vh;display:none;border-radius:12px;background:#000;"></video>
            <iframe id="online-iframe" frameborder="0" allowfullscreen
                allow="autoplay; fullscreen"
                style="width:100%;height:75vh;border:none;border-radius:12px;"></iframe>
        </div>
    </div>
</div>
@endif

{{-- Trailer modal --}}
@if($movie->trailer_url)
<div id="trailer-modal" class="hidden fixed inset-0 z-[9998] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.95);">
    <div class="w-full max-w-3xl">
        <div class="flex justify-end mb-2">
            <button onclick="document.getElementById('trailer-modal').classList.add('hidden'); document.getElementById('trailer-frame').src=''"
                class="text-gray-400 hover:text-white text-sm">✕ Fechar</button>
        </div>
        <div class="aspect-video rounded-xl overflow-hidden bg-black">
            <iframe id="trailer-frame" src="" allow="autoplay; fullscreen" allowfullscreen class="w-full h-full"></iframe>
        </div>
    </div>
</div>
@endif

{{-- Similar content --}}
@if($similar->isNotEmpty())
<div class="max-w-5xl mx-auto px-4 sm:px-6 pb-20 mt-8">
    <h2 class="text-lg font-bold text-white mb-4">{{ __('catalog.similar') }}</h2>
    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
        @foreach($similar as $item)
        @php $isMovie = $item instanceof \App\Models\Movie; @endphp
        <a href="{{ $isMovie ? route('catalog.movie', $item) : route('catalog.serie', $item) }}"
           class="group block">
            <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-gray-800 mb-1.5">
                @if($item->localPosterUrl())
                <img src="{{ $item->localPosterUrl() }}" alt="{{ $item->localTitle() }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                @else
                <div class="w-full h-full flex items-center justify-center text-2xl text-gray-600">
                    {{ $isMovie ? '🎬' : '📺' }}
                </div>
                @endif
                @if($item->rating)
                <div class="absolute top-1.5 left-1.5 bg-black/70 text-yellow-400 text-[10px] font-bold px-1.5 py-0.5 rounded">
                    ★ {{ number_format($item->rating, 1) }}
                </div>
                @endif
            </div>
            <p class="text-xs text-gray-300 font-medium truncate group-hover:text-white transition">{{ $item->localTitle() }}</p>
            <p class="text-[10px] text-gray-600">{{ $item->year }}</p>
        </a>
        @endforeach
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
@if($movie->video_path && $movie->hasVideo())
// Local movie player with progress tracking
const MOVIE_ID   = {{ $movie->id }};
const MOVIE_CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
let moviePlyr = null;
let movieProgressInterval = null;

function saveMovieProgress(position, duration, completed) {
    fetch(`/progress/movie/${MOVIE_ID}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': MOVIE_CSRF },
        body: JSON.stringify({ position: Math.floor(position), duration: Math.floor(duration), completed }),
        keepalive: true,
    }).catch(() => {});
}

async function playLocalMovie() {
    const modal = document.getElementById('movie-player-modal');
    if (!moviePlyr) {
        document.getElementById('movie-video-player').style.display = 'block';
        moviePlyr = new Plyr('#movie-video-player', {
            controls: ['play-large','play','rewind','fast-forward','progress','current-time','duration','mute','volume','settings','fullscreen'],
            settings: ['speed'],
            speed: { selected: 1, options: [0.75, 1, 1.25, 1.5, 2] },
            fullscreen: { enabled: true, fallback: true, iosNative: false },
        });
        moviePlyr.on('ended', () => saveMovieProgress(moviePlyr.duration, moviePlyr.duration, true));
    }
    // Fetch existing progress to resume
    let resumePos = 0;
    try {
        const r = await fetch(`/progress/movie/${MOVIE_ID}`);
        const p = await r.json();
        if (p.position > 30 && !p.completed) resumePos = p.position;
    } catch (_) {}

    moviePlyr.source = {
        type: 'video',
        sources: [{ src: '{{ route('video.movie', $movie) }}', type: 'video/mp4' }]
    };
    moviePlyr.once('ready', () => {
        if (resumePos > 0) {
            moviePlyr.media.addEventListener('loadedmetadata', () => { moviePlyr.currentTime = resumePos; }, { once: true });
        }
        moviePlyr.play();
        movieProgressInterval = setInterval(() => {
            if (!moviePlyr.paused && moviePlyr.duration > 0) {
                saveMovieProgress(moviePlyr.currentTime, moviePlyr.duration, false);
            }
        }, 15000);
    });
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeMoviePlayer() {
    if (moviePlyr) {
        const pos = Math.floor(moviePlyr.currentTime || 0);
        const dur = Math.floor(moviePlyr.duration || 0);
        if (pos > 5) saveMovieProgress(pos, dur, false);
        moviePlyr.pause();
    }
    clearInterval(movieProgressInterval);
    movieProgressInterval = null;
    document.getElementById('movie-player-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.getElementById('movie-player-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeMoviePlayer();
});
@endif

// HLS helpers — defined unconditionally so closeOnlineModal can always call them
let _movieHls = null;
function _destroyMovieHls() {
    if (_movieHls) { try { _movieHls.destroy(); } catch(_) {} _movieHls = null; }
}

// Piratahub ESP player (auto-slug from Spanish title)
@php
    $movieTranslations = is_array($movie->translations) ? $movie->translations : json_decode($movie->translations ?? '{}', true);
    $movieEsTitle      = $movieTranslations['es']['title'] ?? $movie->original_title ?? $movie->title;
    $moviePiratahubSlug = $movie->piratahub_url ? null : \Illuminate\Support\Str::slug($movieEsTitle);
@endphp
async function playMoviePiratahub() {
    const modal  = document.getElementById('online-modal');
    const iframe = document.getElementById('online-iframe');
    const titleEl = modal.querySelector('p.text-gray-300');

    iframe.src = 'about:blank';
    if (titleEl) titleEl.textContent = '{{ addslashes($movie->localTitle()) }} · 🇪🇸 ESP (a carregar...)';
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.querySelectorAll('[id^="msrc-"]').forEach(b => b.style.display = 'none');

    try {
        @if($movie->piratahub_url)
        const scrapeUrl = '/scrape?url=' + encodeURIComponent('{{ $movie->piratahub_url }}');
        @else
        const scrapeUrl = '/scrape/find-movie?tmdb_id={{ $movie->tmdb_id }}&slug={{ $moviePiratahubSlug }}&es_slug={{ \Illuminate\Support\Str::slug($movieEsTitle) }}&title=' + encodeURIComponent('{{ addslashes($movieEsTitle) }}');
        @endif

        const res  = await fetch(scrapeUrl);
        const data = await res.json();
        if (data.url) {
            iframe.src = data.url;
            if (titleEl) titleEl.textContent = '{{ addslashes($movie->localTitle()) }} · 🇪🇸 ESP';
        } else {
            closeOnlineModal();
            alert('Filme não disponível em espanhol.\n' + (data.error || ''));
        }
    } catch (e) {
        closeOnlineModal();
        alert('Erro ao carregar a fonte ESP.');
    }
}

@if($movie->tmdb_id)
// Online movie player
const MOVIE_TMDB_ID = '{{ $movie->tmdb_id }}';
const MOVIE_SOURCES = [
    { label: 'Fonte 1', url: () => `https://vidsrc.to/embed/movie/${MOVIE_TMDB_ID}` },
];

@php $mId = $movie->id; @endphp
let _movieOnlineStart = null, _movieOnlineBase = 0;

function playMovieOnline() {
    _movieOnlineStart = Date.now();
    _movieOnlineBase  = 0;
    fetch(`/progress/movie/{{ $mId }}`).then(r => r.json()).then(p => { _movieOnlineBase = p.position || 0; }).catch(() => {});
    document.getElementById('online-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    switchMovieSource(0);
}

function switchMovieSource(idx) {
    const iframe = document.getElementById('online-iframe');
    const video  = document.getElementById('movie-hls-player');

    // Update button styles
    [0,'cc'].forEach(i => {
        const btn = document.getElementById(`msrc-${i}`);
        if (btn) btn.className = `text-[10px] px-2 py-0.5 rounded font-semibold transition ${i===idx?'bg-red-600 text-white':'bg-white/10 text-gray-400 hover:bg-white/20'}`;
    });

    if (idx === 'cc') {
        // CinemaCity HLS source
        iframe.style.display = 'none';
        iframe.src = '';
        _destroyMovieHls();
        if (video) {
            video.style.display = 'block';
            video.innerHTML = '<p style="color:#aaa;padding:2rem;text-align:center">A carregar ESP CC…</p>';
        }

        const _ccAbort = new AbortController();
        const _ccTimer = setTimeout(() => _ccAbort.abort(), 90000);
        fetch('/cinemacity/movie/{{ $movie->id }}', { signal: _ccAbort.signal })
            .then(r => { clearTimeout(_ccTimer); return r.json(); })
            .then(data => {
                if (!data.url) throw new Error(data.error || 'not_found');
                if (video) video.innerHTML = '';
                if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                    _movieHls = new Hls({ autoStartLoad: true });
                    _movieHls.loadSource(data.url);
                    _movieHls.attachMedia(video);
                    _movieHls.on(Hls.Events.AUDIO_TRACKS_UPDATED, () => {
                        const tracks = _movieHls.audioTracks;
                        const isLatam = t => { const l = (t.lang||'').toLowerCase(), n = (t.name||'').toLowerCase(); return l==='es-419'||l==='es-la'||l.startsWith('es-mx')||l.startsWith('es-ar')||n.includes('latin')||n.includes('latino'); };
                        const isEs   = t => { const l = (t.lang||'').toLowerCase(), n = (t.name||'').toLowerCase(); return l.startsWith('es')||n.includes('espa'); };
                        const idx = tracks.findIndex(isLatam) >= 0 ? tracks.findIndex(isLatam) : tracks.findIndex(isEs);
                        if (idx >= 0) _movieHls.audioTrack = idx;
                    });
                    _movieHls.on(Hls.Events.MANIFEST_PARSED, () => video.play().catch(() => {}));
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = data.url;
                    video.play().catch(() => {});
                } else {
                    video.innerHTML = '<p style="color:#f87171;padding:1rem;text-align:center">HLS não suportado neste browser.</p>';
                }
            })
            .catch(() => {
                if (video) video.innerHTML = '<p style="color:#f87171;padding:1rem;text-align:center">Filme não disponível em ESP CC.</p>';
            });
    } else {
        _destroyMovieHls();
        if (video) video.style.display = 'none';
        iframe.style.display = 'block';
        iframe.src = MOVIE_SOURCES[idx].url();
    }
}

function closeOnlineModal() {
    if (_movieOnlineStart) {
        const video    = document.getElementById('movie-hls-player');
        const usingHls = _movieHls && video && video.style.display !== 'none';

        let pos, dur;
        if (usingHls) {
            pos = Math.floor(video.currentTime || 0);
            dur = Math.floor(video.duration || 7200);
        } else {
            const elapsed = Math.floor((Date.now() - _movieOnlineStart) / 1000);
            pos = Math.min(_movieOnlineBase + elapsed, 3600 * 4);
            dur = 7200;
        }

        if (pos > 10) {
            fetch(`/progress/movie/{{ $mId }}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ position: pos, duration: dur, completed: dur > 0 && pos > dur * 0.9 }),
                keepalive: true,
            }).catch(() => {});
        }
        _movieOnlineStart = null;
    }
    _destroyMovieHls();
    const hlsVid = document.getElementById('movie-hls-player');
    if (hlsVid) { hlsVid.pause(); hlsVid.src = ''; hlsVid.style.display = 'none'; }
    document.getElementById('online-modal').classList.add('hidden');
    document.getElementById('online-iframe').src = '';
    document.body.style.overflow = '';
}

document.getElementById('online-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeOnlineModal();
});
@endif

// Watchlist toggle (detail page)
async function toggleWatchlistDetail(btn) {
    const type = btn.dataset.type, id = parseInt(btn.dataset.id);
    const res  = await fetch('{{ route("watchlist.toggle") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ item_type: type, item_id: id }),
    });
    const data = await res.json();
    if (data.in_watchlist) {
        btn.textContent = '🔖 Na minha lista';
        btn.className = btn.className.replace('bg-white/10 border-white/20 text-white hover:bg-white/20', 'bg-yellow-600/20 border-yellow-600/40 text-yellow-400');
    } else {
        btn.textContent = '＋ Minha lista';
        btn.className = btn.className.replace('bg-yellow-600/20 border-yellow-600/40 text-yellow-400', 'bg-white/10 border-white/20 text-white hover:bg-white/20');
    }
}

// Trailer
document.getElementById('trailer-btn')?.addEventListener('click', function() {
    document.getElementById('trailer-frame').src = '{{ $movie->trailer_url ?? '' }}';
    document.getElementById('trailer-modal').classList.remove('hidden');
});
document.getElementById('trailer-modal')?.addEventListener('click', function(e) {
    if (e.target === this) { this.classList.add('hidden'); document.getElementById('trailer-frame').src = ''; }
});
</script>
@endpush
