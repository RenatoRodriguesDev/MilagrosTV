@extends('layouts.app')

@section('title', $serie->localTitle() . ' — MilagrosTV')

@section('content')

{{-- Hero --}}
<div class="relative min-h-[50vh] flex items-end overflow-hidden w-full">
    {{-- Blurred background --}}
    @if($serie->localPosterUrl())
    <div class="absolute inset-0">
        <img src="{{ $serie->localPosterUrl() }}" alt="" class="w-full h-full object-cover object-top scale-110" style="filter: blur(20px); transform-origin: top center;">
        <div class="absolute inset-0 bg-black/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a0a] via-[#0a0a0a]/40 to-black/20"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#0a0a0a]/80 to-transparent"></div>
    </div>
    @else
    <div class="absolute inset-0 bg-gradient-to-b from-gray-900 to-[#0a0a0a]"></div>
    @endif

    {{-- Content --}}
    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 pt-28 pb-10 w-full flex gap-4 sm:gap-8 items-end min-w-0">

        {{-- Poster --}}
        @if($serie->localPosterUrl())
        <div class="hidden sm:block flex-shrink-0">
            <img src="{{ $serie->localPosterUrl() }}" alt="{{ $serie->localTitle() }}"
                 class="w-36 rounded-xl shadow-2xl border border-white/10">
        </div>
        @endif

        {{-- Info --}}
        <div class="flex-1 pb-2">
            <a href="{{ route('catalog.index', ['type' => 'series']) }}"
               class="inline-flex items-center gap-1 text-gray-400 hover:text-white text-xs font-medium transition mb-4 group">
                <span class="group-hover:-translate-x-0.5 transition-transform">←</span>
                {{ __('serie.back') }}
            </a>

            <h1 class="text-3xl sm:text-4xl font-black text-white mb-2 leading-tight">{{ $serie->localTitle() }}</h1>

            @if($serie->original_title && $serie->original_title !== $serie->title)
            <p class="text-gray-400 text-sm mb-3 font-medium">{{ $serie->original_title }}</p>
            @endif

            <div class="flex flex-wrap items-center gap-3 text-sm mb-4">
                @if($serie->year)
                <span class="text-gray-300 font-medium">{{ $serie->year }}</span>
                @endif
                @if($serie->seasons)
                <span class="text-gray-500">·</span>
                <span class="text-gray-300">{{ $serie->seasons }} {{ __('serie.seasons') }}</span>
                @endif
                @if($serie->rating)
                <span class="text-gray-500">·</span>
                <span class="flex items-center gap-1 text-yellow-400 font-bold">
                    ★ {{ number_format($serie->rating, 1) }}
                </span>
                @endif
            </div>

            @if(!empty($serie->localGenres()))
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($serie->localGenres() as $genre)
                <span class="bg-white/10 text-gray-200 text-xs px-3 py-1 rounded-full border border-white/10 font-medium">
                    {{ $genre }}
                </span>
                @endforeach
            </div>
            @endif

            @if($serie->synopsis)
            <p class="text-gray-300 text-sm leading-relaxed max-w-2xl line-clamp-3 mb-4">{{ $serie->localSynopsis() }}</p>
            @endif

            <div class="flex flex-wrap gap-2">
                {{-- Trailer --}}
                @if($serie->trailer_url)
                <button onclick="document.getElementById('trailer-modal').classList.remove('hidden')"
                    class="flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🎬 Trailer
                </button>
                @endif

                {{-- Watchlist --}}
                <button onclick="toggleWatchlistDetail(this)"
                    data-type="serie" data-id="{{ $serie->id }}"
                    class="flex items-center gap-2 border text-sm px-4 py-2 rounded-lg font-semibold transition
                        {{ $inWatchlist ? 'bg-yellow-600/20 border-yellow-600/40 text-yellow-400' : 'bg-white/10 border-white/20 text-white hover:bg-white/20' }}">
                    {{ $inWatchlist ? '🔖 ' . __('catalog.my_list') : '＋ ' . __('catalog.my_list') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Trailer modal --}}
@if($serie->trailer_url)
<div id="trailer-modal" class="hidden fixed inset-0 z-[9998] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.95);">
    <div class="w-full max-w-3xl">
        <div class="flex justify-end mb-2">
            <button onclick="document.getElementById('trailer-modal').classList.add('hidden'); document.getElementById('trailer-frame').src=''"
                class="text-gray-400 hover:text-white text-sm">✕ Fechar</button>
        </div>
        <div class="aspect-video rounded-xl overflow-hidden bg-black">
            <iframe id="trailer-frame" src="" allow="autoplay; fullscreen" allowfullscreen class="w-full h-full"
                onload="if(this.src==='') return; this.src='{{ $serie->trailer_url }}'"></iframe>
        </div>
    </div>
</div>
<script>
document.querySelector('[onclick*="trailer-modal"]')?.addEventListener('click', function() {
    document.getElementById('trailer-frame').src = '{{ $serie->trailer_url }}';
});
document.getElementById('trailer-modal')?.addEventListener('click', function(e) {
    if (e.target === this) { this.classList.add('hidden'); document.getElementById('trailer-frame').src = ''; }
});
</script>
@endif

{{-- Main content --}}
<div class="max-w-5xl mx-auto px-4 sm:px-6 pb-20">

    @if($episodes->isEmpty())
    <div class="text-center py-20 text-gray-600">
        <p class="text-5xl mb-4">📭</p>
        <p class="text-lg">{{ __('serie.no_episodes') }}</p>
    </div>
    @else

    {{-- Modal player --}}
    <div id="player-modal" class="hidden fixed inset-0 z-[999] flex items-center justify-center p-4" style="background:#000;">
        <div class="w-full max-w-4xl">
            <div class="flex items-center justify-between mb-3">
                <p id="player-label" class="text-gray-300 text-sm font-medium"></p>
                <div class="flex items-center gap-3">
                    <span id="online-badge" class="hidden text-xs text-green-400">🌐 Online</span>
                    <div id="online-src-switcher" class="hidden flex items-center gap-1"></div>
                    <button onclick="closePlayer()" class="text-gray-400 hover:text-white transition text-sm flex items-center gap-1">
                        {{ __('common.close') }}
                    </button>
                </div>
            </div>
            <div style="background:#000;border-radius:12px;line-height:0;">
                <video id="video-player" controls playsinline style="width:100%;max-height:70vh;display:none;border-radius:12px;"></video>
                <iframe id="iframe-player" frameborder="0" allowfullscreen
                    allow="autoplay; fullscreen"
                    style="width:100%;height:70vh;display:none;border:none;border-radius:12px;"></iframe>
            </div>
        </div>
    </div>

    {{-- Season tabs --}}
    @if($episodes->count() > 1 || $episodes->keys()->count() > 1)
    <div class="flex gap-2 mb-6 mt-8 flex-wrap">
        @foreach($episodes->keys() as $season)
        <button onclick="showSeason({{ $season }})"
            id="tab-{{ $season }}"
            class="season-tab px-5 py-2 rounded-lg text-sm font-semibold transition
                   {{ $loop->first ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white border border-white/10' }}">
            {{ __('serie.season') }} {{ $season }}
        </button>
        @endforeach
    </div>
    @else
    <div class="mt-8"></div>
    @endif

    {{-- Episodes --}}
    @foreach($episodes as $season => $eps)
    <div id="season-{{ $season }}" class="season-list {{ !$loop->first ? 'hidden' : '' }} space-y-2">
        @foreach($eps as $ep)
        @php $embedUrl = $ep->isExternalUrl() ? $ep->embedUrl() : null; @endphp
        <div class="group flex items-center gap-4 bg-white/5 hover:bg-white/8 rounded-xl px-5 py-4 transition border border-white/5 hover:border-white/10 cursor-pointer"
             @if($ep->video_path)
                 @if($ep->isExternalUrl())
                     onclick="window.open('{{ addslashes($ep->video_path) }}', '_blank')"
                 @else
                     onclick="playEpisode({{ $ep->id }}, '{{ addslashes($ep->label) }}')"
                 @endif
             @endif>

            {{-- Episode number --}}
            <div class="w-12 flex-shrink-0 text-center">
                @if($ep->video_path)
                <div class="w-10 h-10 rounded-full bg-white/5 border border-white/10 flex items-center justify-center group-hover:bg-red-600 group-hover:border-red-600 transition mx-auto">
                    <span class="text-gray-400 group-hover:text-white text-xs font-bold transition">{{ $ep->episode }}</span>
                </div>
                @else
                <span class="text-gray-600 text-sm font-bold">{{ $ep->episode }}</span>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-semibold truncate">
                    {{ $ep->title ?: __('serie.episode') . ' ' . $ep->episode }}
                </p>
                <p class="text-gray-500 text-xs mt-0.5">T{{ $ep->season }}E{{ $ep->episode }}</p>
                @if(isset($progress[$ep->id]) && $progress[$ep->id]->duration > 0)
                @php $prog = $progress[$ep->id]; @endphp
                <div class="mt-1.5 flex items-center gap-2">
                    <div class="flex-1 h-1 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $prog->completed ? 'bg-gray-500' : 'bg-red-600' }}"
                             style="width: {{ $prog->percent }}%"></div>
                    </div>
                    @if(!$prog->completed)
                    <span class="text-gray-500 text-xs flex-shrink-0">{{ gmdate($prog->position >= 3600 ? 'H:i:s' : 'i:s', $prog->position) }}</span>
                    @else
                    <span class="text-gray-600 text-xs flex-shrink-0">✓</span>
                    @endif
                </div>
                @endif
            </div>

            {{-- Action --}}
            @php
                $isWatched = isset($progress[$ep->id]) && $progress[$ep->id]->completed;
                $hasContinue = isset($progress[$ep->id]) && !$progress[$ep->id]->completed && $progress[$ep->id]->position > 30;
            @endphp
            <div class="flex-shrink-0 flex items-center gap-1.5">

                @if($ep->video_path)
                    {{-- Local/external play button (handled by row onclick) --}}
                    @if($ep->isExternalUrl())
                    <span class="flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-lg font-semibold bg-blue-500/10 border border-blue-500/20 text-blue-400">
                        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Abrir
                    </span>
                    @elseif($hasContinue)
                    <span class="flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-lg font-semibold bg-orange-500/10 border border-orange-500/20 text-orange-400">
                        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Continuar
                    </span>
                    @else
                    <span class="flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-lg font-semibold bg-red-600/10 border border-red-600/20 text-red-400">
                        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Play
                    </span>
                    @endif
                @else
                    {{-- Online sources --}}
                    @if($serie->tmdb_id)
                    <button onclick="event.stopPropagation(); playOnline({{ $ep->season }}, {{ $ep->episode }}, '{{ addslashes($ep->title ?: 'T'.$ep->season.'E'.$ep->episode) }}', {{ $ep->id }})"
                        class="flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-lg font-semibold bg-white/5 hover:bg-green-600/20 border border-white/10 hover:border-green-500/40 text-gray-400 hover:text-green-400 transition">
                        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Online
                    </button>
                    @endif
                @endif

                {{-- ESP button (always shown) --}}
                <button onclick="event.stopPropagation(); playPiratahub(this)"
                    data-url="{{ $ep->piratahub_url }}"
                    data-episode="{{ $ep->episode }}"
                    data-season="{{ $ep->season }}"
                    data-epid="{{ $ep->id }}"
                    data-label="{{ addslashes($ep->label) }}"
                    class="flex items-center gap-1 text-[11px] px-2.5 py-1 rounded-lg font-semibold bg-white/5 hover:bg-yellow-500/20 border border-white/10 hover:border-yellow-500/40 text-gray-400 hover:text-yellow-400 transition">
                    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> ES
                </button>

                {{-- Watched toggle --}}
                <button onclick="event.stopPropagation(); toggleEpWatched({{ $ep->id }}, this)"
                    title="{{ $isWatched ? __('episode.mark_unwatched') : __('episode.mark_watched') }}"
                    data-watched="{{ $isWatched ? '1' : '0' }}"
                    class="transition p-1 rounded hover:bg-white/10 {{ $isWatched ? 'text-green-400' : 'text-gray-600 hover:text-green-400' }}">
                    @if($isWatched)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    @endif
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endforeach

    @endif
</div>

{{-- Similar content --}}
@if($similar->isNotEmpty())
<div class="max-w-5xl mx-auto px-4 sm:px-6 pb-20 mt-4">
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
const eyeOpen   = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
const eyeClosed = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>`;

async function toggleEpWatched(episodeId, btn) {
    const csrf     = document.querySelector('meta[name="csrf-token"]').content;
    const watched  = btn.dataset.watched === '1';
    const markTitle   = '{{ __('episode.mark_watched') }}';
    const unmarkTitle = '{{ __('episode.mark_unwatched') }}';

    if (watched) {
        // Remove watched
        await fetch(`/progress/${episodeId}/dismiss`, {
            method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf }, keepalive: true,
        }).catch(() => {});
        btn.dataset.watched = '0';
        btn.title = markTitle;
        btn.className = btn.className.replace('text-green-400', 'text-gray-600 hover:text-green-400');
        btn.innerHTML = eyeClosed;
        // Reset progress bar
        const row = btn.closest('.group');
        const bar = row?.querySelector('.bg-gray-500');
        if (bar) { bar.className = bar.className.replace('bg-gray-500', 'bg-red-600'); bar.style.width = '0%'; }
        const timeEl = row?.querySelector('.text-gray-600.text-xs.flex-shrink-0');
        if (timeEl) timeEl.remove();
    } else {
        // Mark as watched
        await fetch(`/progress/${episodeId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ position: 2700, duration: 2700, completed: true }),
            keepalive: true,
        }).catch(() => {});
        btn.dataset.watched = '1';
        btn.title = unmarkTitle;
        btn.className = btn.className.replace('text-gray-600 hover:text-green-400', 'text-green-400');
        btn.innerHTML = eyeOpen;
        // Update progress bar
        const row = btn.closest('.group');
        const bar = row?.querySelector('.bg-red-600, .bg-red-500');
        if (bar) { bar.className = bar.className.replace(/bg-red-[56]00/, 'bg-gray-500'); bar.style.width = '100%'; }
        const timeEl = row?.querySelector('.text-red-400, .text-gray-500.text-xs.flex-shrink-0');
        if (timeEl) timeEl.textContent = '✓';
    }
}

function hideEpisodePlyr() {
    if (episodePlyr) {
        episodePlyr.pause();
        const c = episodePlyr.elements?.container;
        if (c) c.style.display = 'none';
        else document.getElementById('video-player').style.display = 'none';
    } else {
        document.getElementById('video-player').style.display = 'none';
    }
}

// ── ESP source (piratahub.to) ─────────────────────────────────────────────────
@php
    $translations   = is_array($serie->translations) ? $serie->translations : json_decode($serie->translations ?? '{}', true);
    $esTitle        = $translations['es']['title'] ?? $serie->original_title ?? $serie->title;
    $piratahubSlug  = $serie->piratahub_slug ?: \Illuminate\Support\Str::slug($esTitle);
@endphp
const PIRATAHUB_SLUG = '{{ $piratahubSlug }}';

async function playPiratahub(btn) {
    const overrideUrl = btn.dataset.url;
    const episode     = btn.dataset.episode;
    const season      = btn.dataset.season;
    const label       = btn.dataset.label;
    const epId        = btn.dataset.epid || null;

    const iframe = document.getElementById('iframe-player');
    const modal  = document.getElementById('player-modal');
    const lbl    = document.getElementById('player-label');
    const badge  = document.getElementById('online-badge');
    const sw     = document.getElementById('online-src-switcher');

    hideEpisodePlyr();
    iframe.src = 'about:blank';
    iframe.style.display = 'block';
    lbl.textContent = label + ' · 🇪🇸 ESP (a carregar...)';
    if (badge) badge.classList.remove('hidden');
    if (sw) sw.classList.add('hidden');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    try {
        let fetchUrl;
        if (overrideUrl) {
            // Episode has a specific URL override — scrape it directly
            fetchUrl = '/scrape?url=' + encodeURIComponent(overrideUrl);
        } else {
            // Auto-find using slug + season + episode (tries multiple URL patterns)
            fetchUrl = `/scrape/find?slug=${encodeURIComponent(PIRATAHUB_SLUG)}&tmdb_id=${encodeURIComponent(TMDB_ID)}&season=${season}&episode=${episode}`;
        }

        const res  = await fetch(fetchUrl);
        const data = await res.json();
        if (data.url) {
            iframe.src = data.url;
            lbl.textContent = label + ' · 🇪🇸 ESP';
            // Start watch progress tracking (same as playOnline)
            if (epId) {
                _onlineEpId      = epId;
                _onlineStartTime = Date.now();
                _onlineBasePos   = 0;
                fetch(`/progress/${epId}`)
                    .then(r => r.json())
                    .then(p => { _onlineBasePos = p.position || 0; })
                    .catch(() => {});
            }
        } else {
            closePlayer();
            alert('Episódio não disponível em espanhol.\n' + (data.error || ''));
        }
    } catch (e) {
        closePlayer();
        alert('Erro ao carregar a fonte ESP.');
    }
}

// ── Online sources (Vidsrc) ───────────────────────────────────────────────────
const TMDB_ID = '{{ $serie->tmdb_id }}';

let _onlineEpId = null, _onlineStartTime = null, _onlineBasePos = 0;

function playOnline(season, episode, label, episodeId) {
    if (!TMDB_ID) { alert('Esta série não tem TMDB ID configurado.'); return; }

    _onlineEpId      = episodeId || null;
    _onlineStartTime = Date.now();
    _onlineBasePos   = 0;

    // Load existing progress to accumulate correctly (non-blocking)
    if (_onlineEpId) {
        fetch(`/progress/${_onlineEpId}`)
            .then(r => r.json())
            .then(p => { _onlineBasePos = p.position || 0; })
            .catch(() => {});
    }

    const iframe = document.getElementById('iframe-player');
    const modal  = document.getElementById('player-modal');
    const lbl    = document.getElementById('player-label');
    const badge  = document.getElementById('online-badge');

    // Source definitions — user can switch in the modal header
    window._onlineSources = [
        { label: 'Fonte 1', url: `https://vidsrc.to/embed/tv/${TMDB_ID}/${season}/${episode}` },
        @if($serie->cinemacity_id)
        { label: 'ESP CC', url: null, cinemacity: true, season: season, episode: episode },
        @endif
    ];
    window._onlineSeason  = season;
    window._onlineEp      = episode;

    hideEpisodePlyr();
    if (badge) { badge.classList.remove('hidden'); }

    // Build source switcher
    const sw = document.getElementById('online-src-switcher');
    if (sw) {
        sw.innerHTML = window._onlineSources.map((s, i) =>
            `<button onclick="switchOnlineSource(${i})" id="osrc-${i}"
                class="text-[10px] px-2 py-0.5 rounded font-semibold transition ${i===0?'bg-red-600 text-white':'bg-white/10 text-gray-400 hover:bg-white/20'}">${s.label}</button>`
        ).join('');
        sw.classList.remove('hidden');
    }

    const video = document.getElementById('video-player');
    if (video) video.style.display = 'none';
    _destroyHls();

    const firstSrc = window._onlineSources[0];
    if (firstSrc.cinemacity) {
        iframe.style.display = 'none';
        _loadCinemaCityEpisode(iframe, video, firstSrc.season, firstSrc.episode);
    } else {
        iframe.src           = firstSrc.url;
        iframe.style.display = 'block';
    }
    lbl.textContent       = label;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function switchOnlineSource(idx) {
    const src    = window._onlineSources[idx];
    const iframe = document.getElementById('iframe-player');
    const video  = document.getElementById('video-player');

    // Update button styles
    window._onlineSources.forEach((_, i) => {
        const btn = document.getElementById(`osrc-${i}`);
        if (btn) btn.className = `text-[10px] px-2 py-0.5 rounded font-semibold transition ${i===idx?'bg-red-600 text-white':'bg-white/10 text-gray-400 hover:bg-white/20'}`;
    });

    if (src.cinemacity) {
        _loadCinemaCityEpisode(iframe, video, src.season, src.episode);
    } else {
        _destroyHls();
        video.style.display  = 'none';
        iframe.style.display = 'block';
        iframe.src           = src.url;
    }
}

let _hlsInstance = null;

function _destroyHls() {
    if (_hlsInstance) { _hlsInstance.destroy(); _hlsInstance = null; }
}

function _loadCinemaCityEpisode(iframe, video, season, episode) {
    iframe.style.display = 'none';
    iframe.src           = '';
    video.style.display  = 'block';
    video.poster         = '';

    const serieId = '{{ $serie->id }}';
    const url     = `/cinemacity/serie/${serieId}/${season}/${episode}`;

    video.innerHTML = '<p style="color:#aaa;padding:1rem;text-align:center">A carregar ESP CC…</p>';

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.url) throw new Error(data.error || 'not_found');
            video.innerHTML = '';
            _destroyHls();
            if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                _hlsInstance = new Hls({ autoStartLoad: true });
                _hlsInstance.loadSource(data.url);
                _hlsInstance.attachMedia(video);
                _hlsInstance.on(Hls.Events.AUDIO_TRACKS_UPDATED, () => {
                    const tracks = _hlsInstance.audioTracks;
                    const isLatam = t => { const l = (t.lang||'').toLowerCase(), n = (t.name||'').toLowerCase(); return l==='es-419'||l==='es-la'||l.startsWith('es-mx')||l.startsWith('es-ar')||n.includes('latin')||n.includes('latino'); };
                    const isEs   = t => { const l = (t.lang||'').toLowerCase(), n = (t.name||'').toLowerCase(); return l.startsWith('es')||n.includes('espa'); };
                    const idx = tracks.findIndex(isLatam) >= 0 ? tracks.findIndex(isLatam) : tracks.findIndex(isEs);
                    if (idx >= 0) _hlsInstance.audioTrack = idx;
                });
                _hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => video.play().catch(() => {}));
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = data.url;
                video.play().catch(() => {});
            } else {
                video.innerHTML = '<p style="color:#f87171;padding:1rem;text-align:center">HLS não suportado neste browser.</p>';
            }
        })
        .catch(err => {
            video.innerHTML = `<p style="color:#f87171;padding:1rem;text-align:center">Episódio não disponível em ESP CC.</p>`;
        });
}

function saveOnlineProgress() {
    if (!_onlineEpId || !_onlineStartTime) return;
    const elapsed = Math.floor((Date.now() - _onlineStartTime) / 1000);
    if (elapsed < 5) { _onlineEpId = null; _onlineStartTime = null; return; }

    const pos  = Math.min(_onlineBasePos + elapsed, 3600 * 4);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Single POST — no chained fetch
    fetch(`/progress/${_onlineEpId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ position: pos, duration: 2700, completed: pos > 2600 }),
        keepalive: true,
    }).catch(() => {});

    _onlineEpId = null;
    _onlineStartTime = null;
    _onlineBasePos = 0;
}

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

// ── Plyr setup ────────────────────────────────────────────────────────────────
const PLYR_CONFIG = {
    controls: ['play-large', 'play', 'rewind', 'fast-forward', 'progress', 'current-time', 'duration', 'mute', 'volume', 'captions', 'settings', 'fullscreen'],
    settings: ['captions', 'speed'],
    captions: { active: true, language: 'auto', update: true },
    speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
    keyboard: { focused: true, global: false },
    tooltips: { controls: false, seek: true },
    fullscreen: { enabled: true, fallback: true, iosNative: false },
    i18n: {
        play: '{{ __('plyr.play') }}', pause: '{{ __('plyr.pause') }}',
        rewind: '{{ __('plyr.rewind') }}', fastForward: '{{ __('plyr.fast_forward') }}',
        mute: '{{ __('plyr.mute') }}', volume: '{{ __('plyr.volume') }}',
        captions: '{{ __('plyr.captions') }}', settings: '{{ __('plyr.settings') }}',
        enterFullscreen: '{{ __('plyr.fullscreen_enter') }}', exitFullscreen: '{{ __('plyr.fullscreen_exit') }}',
        speed: '{{ __('plyr.speed') }}', normal: '{{ __('plyr.normal') }}',
    }
};
let episodePlyr      = null;
let currentEpisodeId = null;

// Orientation lock: Android via API, iOS via CSS rotation
function setupOrientationLock(player, onEnter, onExit) {
    // Android Chrome: orientation lock via real Fullscreen API event
    ['fullscreenchange', 'webkitfullscreenchange'].forEach(ev => {
        document.addEventListener(ev, () => {
            const isFS = !!(document.fullscreenElement || document.webkitFullscreenElement);
            if (isFS) screen.orientation?.lock?.('landscape').catch(() => {});
            else      screen.orientation?.unlock?.();
        });
    });

    player.on('enterfullscreen', () => {
        if (onEnter) onEnter();
        // iOS fallback: CSS rotation when real fullscreen API is unavailable
        setTimeout(() => {
            const realFS = !!(document.fullscreenElement || document.webkitFullscreenElement);
            if (!realFS && window.innerWidth < window.innerHeight) {
                const c = player.elements?.container;
                if (c) {
                    const scale = window.innerWidth / window.innerHeight;
                    c.style.transform = `rotate(90deg) scale(${scale})`;
                    c.style.transformOrigin = 'center center';
                }
            }
        }, 100);
    });

    player.on('exitfullscreen', () => {
        if (onExit) onExit();
        screen.orientation?.unlock?.();
        const c = player.elements?.container;
        if (c) { c.style.transform = ''; c.style.transformOrigin = ''; }
    });
}

function getEpisodePlyr() {
    if (!episodePlyr) {
        document.getElementById('video-player').style.display = 'block';
        episodePlyr = new Plyr('#video-player', PLYR_CONFIG);
        setupOrientationLock(episodePlyr);
    }
    return episodePlyr;
}

function showSeason(season) {
    document.querySelectorAll('.season-list').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.season-tab').forEach(el => {
        el.className = el.className
            .replace('bg-red-600 text-white shadow-lg shadow-red-600/20', '')
            .replace('bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white border border-white/10', '');
        el.classList.add('bg-white/5', 'text-gray-400', 'hover:bg-white/10', 'hover:text-white', 'border', 'border-white/10');
    });
    document.getElementById('season-' + season).classList.remove('hidden');
    const tab = document.getElementById('tab-' + season);
    tab.classList.remove('bg-white/5', 'text-gray-400', 'hover:bg-white/10', 'hover:text-white', 'border', 'border-white/10');
    tab.classList.add('bg-red-600', 'text-white', 'shadow-lg', 'shadow-red-600/20');
}

async function playEpisode(episodeId, label, embedUrl = null) {
    stopProgressSave();

    const iframe = document.getElementById('iframe-player');
    const modal  = document.getElementById('player-modal');
    const lbl    = document.getElementById('player-label');

    iframe.src = '';
    iframe.style.display = 'none';

    currentEpisodeId = embedUrl ? null : episodeId;

    if (embedUrl) {
        if (episodePlyr) episodePlyr.pause();
        document.getElementById('video-player').style.display = 'none';
        iframe.src = embedUrl;
        iframe.style.display = 'block';
    } else {
        // Fetch progress before setting source so 'ready' handler is registered in time
        let resumePos = 0;
        try {
            const r = await fetch('/progress/' + episodeId);
            const prog = await r.json();
            if (prog.position > 30 && !prog.completed) resumePos = prog.position;
        } catch (_) {}

        const player = getEpisodePlyr();
        player.once('ready', () => {
            if (resumePos > 0) {
                player.media.addEventListener('loadedmetadata', () => {
                    player.currentTime = resumePos;
                    showResumeToast(resumePos);
                }, { once: true });
            }
            player.play();
            startProgressSave(player, episodeId);
        });
        player.on('ended', () => saveProgress(episodeId, Math.floor(player.duration || 0), Math.floor(player.duration || 0), true));
        player.source = {
            type: 'video',
            sources: [{ src: '/video/episode/' + episodeId, type: 'video/mp4' }]
        };
    }

    lbl.textContent = label;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

let watchProgressInterval = null;

function startProgressSave(player, episodeId) {
    watchProgressInterval = setInterval(() => {
        if (!player.paused && player.duration > 0) {
            saveProgress(episodeId, Math.floor(player.currentTime), Math.floor(player.duration), false);
        }
    }, 15000);
}

function stopProgressSave() {
    if (watchProgressInterval) { clearInterval(watchProgressInterval); watchProgressInterval = null; }
}

function saveProgress(episodeId, position, duration, completed) {
    fetch('/progress/' + episodeId, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ position, duration, completed }),
        keepalive: true,
    }).catch(() => {});
}

function showResumeToast(seconds) {
    const existing = document.getElementById('resume-toast');
    if (existing) existing.remove();
    const t = document.createElement('div');
    t.id = 'resume-toast';
    const m = Math.floor(seconds / 60), s = seconds % 60;
    t.textContent = `{{ __('player.resuming_from') }} ${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    t.style.cssText = 'position:absolute;top:12px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.85);color:#fff;font-size:13px;padding:6px 14px;border-radius:20px;z-index:999;pointer-events:none;border:1px solid rgba(255,255,255,0.15);';
    document.getElementById('player-modal').appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

function closePlayer() {
    const iframe = document.getElementById('iframe-player');
    const modal  = document.getElementById('player-modal');

    if (episodePlyr && currentEpisodeId) {
        const pos = Math.floor(episodePlyr.currentTime || 0);
        const dur = Math.floor(episodePlyr.duration || 0);
        if (pos > 5) saveProgress(currentEpisodeId, pos, dur, false);
        episodePlyr.pause();
    }
    stopProgressSave();
    currentEpisodeId = null;

    saveOnlineProgress();
    iframe.src = '';
    iframe.style.display = 'none';
    const video = document.getElementById('video-player');
    if (video) { _destroyHls(); video.pause(); video.src = ''; video.style.display = 'none'; }
    const badge = document.getElementById('online-badge');
    if (badge) badge.classList.add('hidden');
    const sw = document.getElementById('online-src-switcher');
    if (sw) sw.classList.add('hidden');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// Fechar ao clicar fora
document.getElementById('player-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closePlayer();
});
</script>
@endpush
