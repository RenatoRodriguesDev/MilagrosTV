@extends('layouts.app')

@section('title', $serie->localTitle() . ' — MilagrosTV')

@section('content')

{{-- Hero --}}
<div class="relative min-h-[50vh] flex items-end overflow-hidden">
    {{-- Blurred background --}}
    @if($serie->poster_url)
    <div class="absolute inset-0">
        <img src="{{ $serie->poster_url }}" alt="" class="w-full h-full object-cover object-top scale-110" style="filter: blur(20px); transform-origin: top center;">
        <div class="absolute inset-0 bg-black/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a0a] via-[#0a0a0a]/40 to-black/20"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#0a0a0a]/80 to-transparent"></div>
    </div>
    @else
    <div class="absolute inset-0 bg-gradient-to-b from-gray-900 to-[#0a0a0a]"></div>
    @endif

    {{-- Content --}}
    <div class="relative max-w-5xl mx-auto px-6 pt-28 pb-10 w-full flex gap-8 items-end">

        {{-- Poster --}}
        @if($serie->poster_url)
        <div class="hidden sm:block flex-shrink-0">
            <img src="{{ $serie->poster_url }}" alt="{{ $serie->localTitle() }}"
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

            {{-- Torrent search button --}}
            <button onclick="openTorrents('{{ addslashes($serie->localTitle()) }}', 'series')"
                class="flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                🧲 Encontrar streams
            </button>
        </div>
    </div>
</div>

{{-- Torrent modal --}}
<div id="torrent-modal" class="hidden fixed inset-0 z-[999] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.92);">
    <div class="w-full max-w-3xl max-h-[85vh] flex flex-col bg-gray-900 rounded-2xl border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 flex-shrink-0">
            <div>
                <h3 class="font-bold text-white">🧲 Encontrar streams</h3>
                <p id="torrent-query-label" class="text-gray-400 text-xs mt-0.5"></p>
            </div>
            <button onclick="closeTorrents()" class="text-gray-500 hover:text-white transition text-xl">✕</button>
        </div>
        <div class="px-6 py-3 border-b border-white/5 flex-shrink-0">
            <div class="flex gap-2">
                <input type="text" id="torrent-search-input"
                    class="flex-1 bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500"
                    placeholder="Pesquisar...">
                <button onclick="doTorrentSearch()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Pesquisar
                </button>
            </div>
            {{-- Language filters --}}
            <div class="flex gap-2 mt-2 flex-wrap">
                <button onclick="setLangFilter(null)" id="filter-all"
                    class="lang-filter px-3 py-1 rounded-full text-xs font-semibold transition bg-white/20 text-white">
                    Todos
                </button>
                <button onclick="setLangFilter('DUAL')" id="filter-DUAL"
                    class="lang-filter px-3 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400 hover:text-white">
                    🌐 DUAL
                </button>
                <button onclick="setLangFilter('ES')" id="filter-ES"
                    class="lang-filter px-3 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400 hover:text-white">
                    🇪🇸 ES
                </button>
                <button onclick="setLangFilter('PT')" id="filter-PT"
                    class="lang-filter px-3 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400 hover:text-white">
                    🇵🇹 PT
                </button>
                <button onclick="setLangFilter('EN')" id="filter-EN"
                    class="lang-filter px-3 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400 hover:text-white">
                    🇬🇧 EN
                </button>
                <span id="filter-count" class="ml-auto text-xs text-gray-500 self-center"></span>
            </div>
        </div>
        <div id="torrent-results" class="overflow-y-auto flex-1 px-4 py-3">
            <div id="torrent-loading" class="hidden text-center py-12">
                <div class="w-8 h-8 border-4 border-white/10 border-t-red-500 rounded-full animate-spin inline-block"></div>
            </div>
            {{-- WebTorrent player inline --}}
            <div id="wt-player-box" class="hidden mb-3">
                <div class="bg-black rounded-xl overflow-hidden">
                    <video id="wt-video" controls playsinline class="w-full" style="max-height:300px;"></video>
                </div>
                <div class="flex items-center justify-between mt-2 text-xs px-1">
                    <span id="wt-status" class="text-gray-400">A carregar...</span>
                    <div class="flex items-center gap-3">
                        <button onclick="toggleSubSearch()" class="text-gray-500 hover:text-blue-400 transition">🔤 Legendas</button>
                        <button onclick="stopWebTorrent()" class="text-gray-600 hover:text-red-400 transition">✕ Parar</button>
                    </div>
                </div>
                <div class="w-full bg-white/5 rounded-full h-1 mt-1">
                    <div id="wt-progress" class="bg-red-500 h-1 rounded-full transition-all" style="width:0%"></div>
                </div>
                {{-- Subtitle search panel --}}
                <div id="sub-search-panel" class="hidden mt-3 bg-white/5 rounded-xl p-3">
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="sub-query" placeholder="Título do episódio..."
                            class="flex-1 bg-white/5 border border-white/10 text-white rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500">
                        <button onclick="searchSubtitles()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                            Pesquisar
                        </button>
                    </div>
                    <div class="flex gap-1.5 mb-2">
                        <button onclick="setSubLang('PT')" id="sublang-PT" class="sub-lang-btn px-2.5 py-1 rounded-full text-xs font-semibold transition bg-white/20 text-white">🇵🇹 PT</button>
                        <button onclick="setSubLang('ES')" id="sublang-ES" class="sub-lang-btn px-2.5 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400">🇪🇸 ES</button>
                        <button onclick="setSubLang('EN')" id="sublang-EN" class="sub-lang-btn px-2.5 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400">🇬🇧 EN</button>
                        <button onclick="setSubLang('PT,ES,EN')" id="sublang-PT,ES,EN" class="sub-lang-btn px-2.5 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400">Todos</button>
                    </div>
                    <div id="sub-results" class="space-y-1 max-h-40 overflow-y-auto"></div>
                    <p id="sub-status" class="text-xs text-gray-500 mt-1"></p>
                </div>
            </div>

            <div id="torrent-list" class="space-y-2"></div>
            <p id="torrent-empty" class="hidden text-center text-gray-500 py-12 text-sm">Nenhum resultado encontrado.</p>
        </div>
    </div>
</div>

{{-- Main content --}}
<div class="max-w-5xl mx-auto px-6 pb-20">

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
                <button onclick="closePlayer()" class="text-gray-400 hover:text-white transition text-sm flex items-center gap-1">
                    ✕ Fechar
                </button>
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
             @if($ep->video_path) onclick="playEpisode({{ $ep->id }}, '{{ addslashes($ep->label) }}', {{ $embedUrl ? "'" . addslashes($embedUrl) . "'" : 'null' }})" @endif>

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
            </div>

            {{-- Action --}}
            @if($ep->video_path)
            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition flex items-center gap-2">
                <span class="text-red-500 text-sm font-bold">{{ __('serie.play') }}</span>
            </div>
            @else
            <button onclick="event.stopPropagation(); openTorrents('{{ addslashes($serie->localTitle()) }} S{{ str_pad($ep->season,2,'0',STR_PAD_LEFT) }}E{{ str_pad($ep->episode,2,'0',STR_PAD_LEFT) }}', 'series')"
                class="flex-shrink-0 text-gray-500 hover:text-orange-400 text-xs transition flex items-center gap-1">
                🧲 <span>Encontrar</span>
            </button>
            @endif
        </div>
        @endforeach
    </div>
    @endforeach

    @endif
</div>

@endsection

@push('scripts')
<script>
// ── Plyr setup ────────────────────────────────────────────────────────────────
const PLYR_CONFIG = {
    controls: ['play-large', 'play', 'rewind', 'fast-forward', 'progress', 'current-time', 'duration', 'mute', 'volume', 'captions', 'settings', 'fullscreen'],
    settings: ['captions', 'speed'],
    captions: { active: true, language: 'auto', update: true },
    speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
    keyboard: { focused: true, global: false },
    tooltips: { controls: false, seek: true },
    i18n: {
        play: 'Reproduzir', pause: 'Pausar',
        rewind: 'Recuar 10s', fastForward: 'Avançar 10s',
        mute: 'Sem som', volume: 'Volume',
        captions: 'Legendas', settings: 'Definições',
        enterFullscreen: 'Ecrã inteiro', exitFullscreen: 'Sair',
        speed: 'Velocidade', normal: 'Normal',
    }
};
let episodePlyr = null;
let torrentPlyr = null;

function getEpisodePlyr() {
    if (!episodePlyr) {
        document.getElementById('video-player').style.display = 'block';
        episodePlyr = new Plyr('#video-player', PLYR_CONFIG);
    }
    return episodePlyr;
}

function getTorrentPlyr() {
    if (!torrentPlyr) {
        torrentPlyr = new Plyr('#wt-video', {
            ...PLYR_CONFIG,
            controls: ['play-large', 'play', 'progress', 'current-time', 'duration', 'mute', 'volume', 'captions', 'fullscreen'],
        });
    }
    return torrentPlyr;
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

function playEpisode(episodeId, label, embedUrl = null) {
    const iframe = document.getElementById('iframe-player');
    const modal  = document.getElementById('player-modal');
    const lbl    = document.getElementById('player-label');

    iframe.src = '';
    iframe.style.display = 'none';

    if (embedUrl) {
        if (episodePlyr) episodePlyr.pause();
        document.getElementById('video-player').style.display = 'none';
        iframe.src = embedUrl;
        iframe.style.display = 'block';
    } else {
        const player = getEpisodePlyr();
        player.source = {
            type: 'video',
            sources: [{ src: '/video/episode/' + episodeId, type: 'video/mp4' }]
        };
        player.once('ready', () => player.play());
    }

    lbl.textContent = label;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePlayer() {
    const iframe = document.getElementById('iframe-player');
    const modal  = document.getElementById('player-modal');
    if (episodePlyr) episodePlyr.pause();
    iframe.src = '';
    iframe.style.display = 'none';
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// Fechar ao clicar fora
document.getElementById('player-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closePlayer();
});

// ── Torrents ──────────────────────────────────────────────────────────────────
let torrentType      = 'series';
let allResults       = [];
let activeLangFilter = null;

function openTorrents(query, type = 'series') {
    torrentType = type;
    document.getElementById('torrent-search-input').value = query;
    document.getElementById('torrent-query-label').textContent = query;
    document.getElementById('torrent-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setLangFilter(null, false);
    doTorrentSearch();
}

function setLangFilter(lang, render = true) {
    activeLangFilter = lang;
    document.querySelectorAll('.lang-filter').forEach(btn => {
        btn.classList.remove('bg-white/20', 'text-white');
        btn.classList.add('bg-white/5', 'text-gray-400');
    });
    const active = document.getElementById('filter-' + (lang ?? 'all'));
    if (active) {
        active.classList.remove('bg-white/5', 'text-gray-400');
        active.classList.add('bg-white/20', 'text-white');
    }
    if (render && allResults.length) renderResults(allResults);
}

function renderResults(results) {
    const list  = document.getElementById('torrent-list');
    const empty = document.getElementById('torrent-empty');
    const count = document.getElementById('filter-count');

    const filtered = activeLangFilter
        ? results.filter(r => detectLangBadges(r.title).some(b => b.label === activeLangFilter))
        : results;

    count.textContent = filtered.length + ' resultado' + (filtered.length !== 1 ? 's' : '');

    if (!filtered.length) {
        list.innerHTML = '';
        empty.textContent = activeLangFilter
            ? `Sem resultados "${activeLangFilter}". Tenta outro filtro.`
            : 'Nenhum resultado encontrado.';
        empty.classList.remove('hidden');
        return;
    }
    empty.classList.add('hidden');

    window._magnets = {};
    list.innerHTML = filtered.map((r, i) => {
        const playId = r.magnet || r.link;
        if (playId) window._magnets[i] = playId;
        const badges = detectLangBadges(r.title);
        return `
        <div class="flex items-center gap-3 bg-white/5 hover:bg-white/8 rounded-xl px-4 py-3 border border-white/5 transition">
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate">${r.title}</p>
                <div class="flex items-center gap-2 mt-1 flex-wrap">
                    <span class="text-gray-500 text-xs">${r.indexer} · ${r.size} · ${r.published || ''}</span>
                    ${badges.map(b => `<span class="text-xs px-1.5 py-0.5 rounded font-bold ${b.cls}">${b.label}</span>`).join('')}
                </div>
            </div>
            <div class="flex items-center gap-1 text-green-400 text-xs flex-shrink-0 mr-1">
                <span>▲</span><span>${r.seeders}</span>
            </div>
            ${playId ? `
            <button onclick="playWebTorrent(${i})"
                class="flex-shrink-0 bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1.5 rounded-lg font-semibold transition">
                ▶ Reproduzir
            </button>
            ${r.magnet ? `<a href="${r.magnet}" class="flex-shrink-0 bg-white/10 hover:bg-white/20 text-white text-xs px-3 py-1.5 rounded-lg transition">🧲</a>` : ''}
            ` : ''}
        </div>`;
    }).join('');
}

async function doTorrentSearch() {
    const query = document.getElementById('torrent-search-input').value.trim();
    if (!query) return;

    const loading = document.getElementById('torrent-loading');
    const list    = document.getElementById('torrent-list');
    const empty   = document.getElementById('torrent-empty');

    loading.classList.remove('hidden');
    list.innerHTML = '';
    empty.classList.add('hidden');
    document.getElementById('filter-count').textContent = '';

    try {
        const res = await fetch(`/torrents/search?query=${encodeURIComponent(query)}&type=${torrentType}`);
        allResults = await res.json();
        loading.classList.add('hidden');
        if (!allResults.length) { empty.classList.remove('hidden'); return; }
        renderResults(allResults);
    } catch(e) {
        loading.classList.add('hidden');
        empty.textContent = 'Erro ao pesquisar. Verifica se o Jackett está activo.';
        empty.classList.remove('hidden');
    }
}

document.getElementById('torrent-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeTorrents();
});

// ── Language badge detection ──────────────────────────────────────────────────
function detectLangBadges(title) {
    const t = title.toUpperCase();
    const badges = [];
    if (/\bDUAL(\s*[-–]?\s*AUDIO)?\b/.test(t))
        badges.push({ label: 'DUAL', cls: 'bg-purple-600/30 text-purple-300 border border-purple-500/30' });
    else if (/\bMULTI(LINGUAL)?\b/.test(t))
        badges.push({ label: 'MULTI', cls: 'bg-purple-600/30 text-purple-300 border border-purple-500/30' });
    else {
        if (/\b(ESP(A[ÑN]OL)?|SPANISH|ESPANHOL|DUBBED)\b/.test(t))
            badges.push({ label: 'ES', cls: 'bg-orange-600/30 text-orange-300 border border-orange-500/30' });
        if (/\b(PT|PTBR|PT[-\s]?BR|PORTUGU[EÊ]S|PORTUGUESE|LEGENDADO)\b/.test(t))
            badges.push({ label: 'PT', cls: 'bg-green-600/30 text-green-300 border border-green-500/30' });
        if (/\b(ENG(LISH)?)\b/.test(t))
            badges.push({ label: 'EN', cls: 'bg-blue-600/30 text-blue-300 border border-blue-500/30' });
    }
    return badges;
}

// ── Subtitle language detection ───────────────────────────────────────────────
function detectSubLang(filename) {
    const f = filename.toLowerCase();
    if (/[\.\-_](pt|pt[\-_]br|ptbr|por|portuguese|portugu)[\.\-_]/.test(f)) return { code: 'pt', label: 'Português' };
    if (/[\.\-_](es|esp|spa|spanish|espanol)[\.\-_]/.test(f))               return { code: 'es', label: 'Español' };
    if (/[\.\-_](en|eng|english)[\.\-_]/.test(f))                           return { code: 'en', label: 'English' };
    if (/[\.\-_](fr|fra|french)[\.\-_]/.test(f))                            return { code: 'fr', label: 'Français' };
    if (/[\.\-_](de|deu|german)[\.\-_]/.test(f))                            return { code: 'de', label: 'Deutsch' };
    if (/[\.\-_](it|ita|italian)[\.\-_]/.test(f))                           return { code: 'it', label: 'Italiano' };
    // Guess from position in filename list
    if (/pt|por|portug/.test(f))  return { code: 'pt', label: 'Português' };
    if (/es|esp|span/.test(f))    return { code: 'es', label: 'Español' };
    return { code: 'und', label: 'Legenda' };
}

async function getSubtitleTracks(magnet) {
    try {
        const res = await fetch(`${STREAM_SERVER}/subtitles?magnet=${encodeURIComponent(magnet)}`);
        if (!res.ok) return [];
        const subs = await res.json();
        if (!subs.length) return [];

        document.getElementById('wt-status').textContent += ` · ${subs.length} legenda(s)`;

        return subs.map((sub, i) => {
            const lang = detectSubLang(sub.name);
            return {
                kind: 'subtitles',
                label: lang.label,
                srclang: lang.code,
                src: `${STREAM_SERVER}/subtitle?magnet=${encodeURIComponent(magnet)}&name=${encodeURIComponent(sub.name)}`,
                default: i === 0,
            };
        });
    } catch(e) { return []; }
}

// ── Torrent streaming via servidor local ──────────────────────────────────────
const STREAM_SERVER = '{{ env("STREAM_SERVER_URL", "/torrent-stream") }}';
let progressInterval = null;

async function playWebTorrent(idx) {
    const magnet = window._magnets[idx];
    if (!magnet) return;

    const box      = document.getElementById('wt-player-box');
    const video    = document.getElementById('wt-video');
    const status   = document.getElementById('wt-status');
    const progress = document.getElementById('wt-progress');

    stopWebTorrent();
    box.classList.remove('hidden');
    status.textContent   = 'A procurar peers e carregar metadados... (pode demorar 30s)';
    progress.style.width = '0%';

    // Animate progress bar while waiting for preload
    let pct = 0;
    progressInterval = setInterval(() => {
        pct = Math.min(pct + 1.5, 80);
        progress.style.width = pct + '%';
    }, 500);

    try {
        // Step 1: preload — waits until torrent is ready on the server
        const preloadRes = await fetch(`${STREAM_SERVER}/preload?magnet=${encodeURIComponent(magnet)}`);
        if (!preloadRes.ok) {
            const err = await preloadRes.json().catch(() => ({ error: 'Sem resposta do servidor' }));
            throw new Error(err.error || 'Falha no preload');
        }
        const info = await preloadRes.json();

        clearInterval(progressInterval);
        progressInterval = null;
        progress.style.width = '90%';
        status.textContent = `A iniciar: ${info.name || '...'} (${info.peers} peers)`;

        // Load subtitles from torrent (if any .srt/.ass files exist)
        const tracks = await getSubtitleTracks(magnet);

        // Step 2: now the torrent is ready — stream immediately
        const streamUrl = `${STREAM_SERVER}/stream?magnet=${encodeURIComponent(magnet)}`;
        const player = getTorrentPlyr();

        player.source = {
            type: 'video',
            sources: [{ src: streamUrl, type: 'video/mp4' }],
            tracks: tracks,
        };

        player.once('ready', () => {
            player.play().catch(() => {});
            status.textContent   = '▶ A reproduzir via torrent stream';
            progress.style.width = '100%';
        });

        player.on('error', () => {
            status.textContent = '⚠ Erro ao reproduzir — tenta novamente.';
        });

    } catch(e) {
        clearInterval(progressInterval);
        progressInterval = null;
        progress.style.width = '0%';
        status.textContent = `✗ ${e.message}`;
    }
}

function stopWebTorrent() {
    if (progressInterval) { clearInterval(progressInterval); progressInterval = null; }
    if (torrentPlyr) { torrentPlyr.pause(); }
    document.getElementById('wt-player-box')?.classList.add('hidden');
}

function closeTorrents() {
    stopWebTorrent();
    document.getElementById('torrent-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.getElementById('torrent-search-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') doTorrentSearch();
});

document.getElementById('sub-query')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') searchSubtitles();
});

// ── Subtitle search (Subdl) ───────────────────────────────────────────────────
const LANG_LABELS = { pt: '🇵🇹 PT', br_pt: '🇧🇷 PT-BR', es: '🇪🇸 ES', en: '🇬🇧 EN', no: '🇳🇴 NO', fr: '🇫🇷 FR', de: '🇩🇪 DE', it: '🇮🇹 IT', id: '🇮🇩 ID', ro: '🇷🇴 RO' };
let activeSublang = 'PT';

function setSubLang(lang) {
    activeSublang = lang;
    document.querySelectorAll('.sub-lang-btn').forEach(b => {
        b.classList.remove('bg-white/20', 'text-white');
        b.classList.add('bg-white/5', 'text-gray-400');
    });
    const btn = document.getElementById('sublang-' + lang);
    if (btn) { btn.classList.remove('bg-white/5', 'text-gray-400'); btn.classList.add('bg-white/20', 'text-white'); }
}

let subSeason = null, subEpisode = null;

function toggleSubSearch() {
    const panel = document.getElementById('sub-search-panel');
    panel.classList.toggle('hidden');
    if (!panel.classList.contains('hidden')) {
        // Extract title and season/episode from torrent query (e.g. "FROM S01E07")
        const q = document.getElementById('torrent-search-input')?.value || '';
        const m = q.match(/^(.*?)\s*[Ss](\d{1,2})[Ee](\d{1,3})/);
        if (m) {
            document.getElementById('sub-query').value = m[1].trim() || q;
            subSeason  = parseInt(m[2]);
            subEpisode = parseInt(m[3]);
        } else {
            document.getElementById('sub-query').value = q;
            subSeason = null; subEpisode = null;
        }
        document.getElementById('sub-query').focus();
    }
}

async function searchSubtitles() {
    const query  = document.getElementById('sub-query').value.trim();
    const lang   = activeSublang;
    const status = document.getElementById('sub-status');
    const list   = document.getElementById('sub-results');

    if (!query) return;
    status.textContent = 'A pesquisar...';
    list.innerHTML = '';

    try {
        let url = `/subtitles/search?query=${encodeURIComponent(query)}&lang=${encodeURIComponent(lang)}&type=tv`;
        if (subSeason)  url += `&season=${subSeason}`;
        if (subEpisode) url += `&episode=${subEpisode}`;
        const res  = await fetch(url);
        const subs = await res.json();

        if (!subs.length) { status.textContent = 'Sem resultados.'; return; }
        status.textContent = `${subs.length} legenda(s) encontrada(s)`;

        list.innerHTML = subs.slice(0, 20).map((s, i) => {
            const langLabel = LANG_LABELS[s.lang_code] || s.lang || '?';
            const epInfo = s.season
                ? (s.episode ? `E${String(s.episode).padStart(2,'0')}` : `S${String(s.season).padStart(2,'0')} completo`)
                : '';
            return `
            <div class="flex items-center gap-2 bg-white/5 rounded-lg px-3 py-2 text-xs">
                <span class="flex-shrink-0">${langLabel}</span>
                ${epInfo ? `<span class="flex-shrink-0 text-gray-500 font-mono">${epInfo}</span>` : ''}
                <span class="flex-1 text-gray-300 truncate">${s.name}</span>
                <button onclick="loadExternalSubtitle('${encodeURIComponent(s.url)}')"
                    class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded font-semibold transition">
                    ✓ Usar
                </button>
            </div>`;
        }).join('');
    } catch(e) {
        status.textContent = 'Erro ao pesquisar legendas.';
    }
}

async function loadExternalSubtitle(encodedUrl) {
    const status = document.getElementById('sub-status');
    status.textContent = 'A descarregar legenda...';

    try {
        const url    = decodeURIComponent(encodedUrl);
        const vttUrl = `/subtitles/download?url=${encodeURIComponent(url)}`;
        const player = getTorrentPlyr();
        const video  = player.media;

        Array.from(video.querySelectorAll('track[data-external]')).forEach(t => t.remove());

        const track = document.createElement('track');
        track.kind    = 'subtitles';
        track.src     = vttUrl;
        track.label   = activeSublang;
        track.srclang = activeSublang.toLowerCase().split(',')[0];
        track.default = true;
        track.dataset.external = '1';
        video.appendChild(track);

        track.addEventListener('load', () => {
            for (const t of video.textTracks) t.mode = 'disabled';
            video.textTracks[video.textTracks.length - 1].mode = 'showing';
        });

        status.textContent = '✓ Legenda carregada!';
        setTimeout(() => document.getElementById('sub-search-panel').classList.add('hidden'), 1500);
    } catch(e) {
        status.textContent = 'Erro ao carregar legenda.';
    }
}
</script>
@endpush
