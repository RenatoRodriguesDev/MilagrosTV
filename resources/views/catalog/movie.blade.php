@extends('layouts.app')

@section('title', $movie->localTitle() . ' — MilagrosTV')

@section('content')

{{-- Hero --}}
<div class="relative min-h-[50vh] flex items-end overflow-hidden">
    @if($movie->poster_url)
    <div class="absolute inset-0">
        <img src="{{ $movie->poster_url }}" alt="" class="w-full h-full object-cover object-top scale-110" style="filter: blur(20px); transform-origin: top center;">
        <div class="absolute inset-0 bg-black/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a0a] via-[#0a0a0a]/40 to-black/20"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#0a0a0a]/80 to-transparent"></div>
    </div>
    @else
    <div class="absolute inset-0 bg-gradient-to-b from-gray-900 to-[#0a0a0a]"></div>
    @endif

    <div class="relative max-w-5xl mx-auto px-6 pt-28 pb-10 w-full flex gap-8 items-end">

        @if($movie->poster_url)
        <div class="hidden sm:block flex-shrink-0">
            <img src="{{ $movie->poster_url }}" alt="{{ $movie->localTitle() }}"
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

            <button onclick="openTorrents('{{ addslashes($movie->original_title ?? $movie->title) }}', 'movie')"
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
                {{-- Subtitle controls (offset + style) --}}
                <div id="sub-offset-bar" class="hidden flex-col gap-1.5 mt-2 px-1">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-gray-500 text-xs shrink-0">Sync:</span>
                        <button onclick="adjustSubOffset(-5)"   class="sub-ctrl-btn">-5s</button>
                        <button onclick="adjustSubOffset(-1)"   class="sub-ctrl-btn">-1s</button>
                        <button onclick="adjustSubOffset(-0.5)" class="sub-ctrl-btn">-0.5s</button>
                        <span id="sub-offset-display" class="text-white text-xs font-mono w-10 text-center shrink-0">0.0s</span>
                        <button onclick="adjustSubOffset(0.5)"  class="sub-ctrl-btn">+0.5s</button>
                        <button onclick="adjustSubOffset(1)"    class="sub-ctrl-btn">+1s</button>
                        <button onclick="adjustSubOffset(5)"    class="sub-ctrl-btn">+5s</button>
                        <button onclick="resetSubOffset()"      class="sub-ctrl-btn text-gray-600 ml-1">Reset</button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-gray-500 text-xs shrink-0">Legenda:</span>
                        <button onclick="adjustSubSize(-2)"  class="sub-ctrl-btn">A−</button>
                        <span id="sub-size-display" class="text-white text-xs font-mono w-8 text-center shrink-0">18</span>
                        <button onclick="adjustSubSize(2)"   class="sub-ctrl-btn">A+</button>
                        <button onclick="toggleSubBg()" id="sub-bg-btn" class="sub-ctrl-btn ml-2">⬛ Fundo</button>
                    </div>
                </div>
                {{-- Subtitle search panel --}}
                <div id="sub-search-panel" class="hidden mt-3 bg-white/5 rounded-xl p-3">
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="sub-query" placeholder="Título do filme..."
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

@endsection

@push('scripts')
<style>
.sub-ctrl-btn {
    color: #9ca3af; font-size: 0.7rem; padding: 2px 6px;
    border-radius: 4px; background: rgba(255,255,255,0.05);
    transition: background 0.15s, color 0.15s;
}
.sub-ctrl-btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
.sub-ctrl-btn.active { background: rgba(255,255,255,0.2); color: #fff; }
</style>
<script>
// ── Plyr ──────────────────────────────────────────────────────────────────────
const PLYR_CONFIG = {
    controls: ['play-large', 'play', 'rewind', 'fast-forward', 'progress', 'current-time', 'duration', 'mute', 'volume', 'captions', 'settings', 'fullscreen'],
    settings: ['captions', 'speed'],
    captions: { active: true, language: 'auto', update: true },
    speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
    keyboard: { focused: true, global: false },
    tooltips: { controls: false, seek: true },
    fullscreen: { enabled: true, fallback: true, iosNative: false },
    i18n: {
        play: 'Reproduzir', pause: 'Pausar',
        rewind: 'Recuar 10s', fastForward: 'Avançar 10s',
        mute: 'Sem som', volume: 'Volume',
        captions: 'Legendas', settings: 'Definições',
        enterFullscreen: 'Ecrã inteiro', exitFullscreen: 'Sair',
        speed: 'Velocidade', normal: 'Normal',
    }
};
let torrentPlyr = null;

function setupOrientationLock(player, onEnter, onExit) {
    ['fullscreenchange', 'webkitfullscreenchange'].forEach(ev => {
        document.addEventListener(ev, () => {
            const isFS = !!(document.fullscreenElement || document.webkitFullscreenElement);
            if (isFS) screen.orientation?.lock?.('landscape').catch(() => {});
            else      screen.orientation?.unlock?.();
        });
    });
    player.on('enterfullscreen', () => {
        if (onEnter) onEnter();
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

function getTorrentPlyr() {
    if (!torrentPlyr) {
        torrentPlyr = new Plyr('#wt-video', PLYR_CONFIG);
        setupOrientationLock(
            torrentPlyr,
            () => { subtitleSize = Math.min(40, subtitleSize + 12); updateSubSizeDisplay(); },
            () => { subtitleSize = Math.max(10, subtitleSize - 12); updateSubSizeDisplay(); }
        );
    }
    return torrentPlyr;
}

function updateSubSizeDisplay() {
    const el = document.getElementById('sub-size-display');
    if (el) el.textContent = subtitleSize;
    if (subtitleOverlay) tickSubtitles(torrentPlyr.currentTime);
}

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

// ── Torrents ──────────────────────────────────────────────────────────────────
let torrentType      = 'movie';
let allResults       = [];
let activeLangFilter = null;

function openTorrents(query, type = 'movie') {
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
    if (active) { active.classList.remove('bg-white/5', 'text-gray-400'); active.classList.add('bg-white/20', 'text-white'); }
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
        empty.textContent = activeLangFilter ? `Sem resultados "${activeLangFilter}". Tenta outro filtro.` : 'Nenhum resultado encontrado.';
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
    list.innerHTML = ''; empty.classList.add('hidden');
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

// ── Torrent streaming ─────────────────────────────────────────────────────────
const STREAM_SERVER = '{{ env("STREAM_SERVER_URL", "/torrent-stream") }}';
let progressInterval = null;
let currentStreamUrl = null;
let currentMagnet    = null;

function browserSupportsHEVC() {
    const v = document.createElement('video');
    return v.canPlayType('video/mp4; codecs="hev1.1.6.L93.B0"') !== ''
        || v.canPlayType('video/mp4; codecs="hvc1"') !== '';
}

async function playWebTorrent(idx) {
    const magnet = window._magnets[idx];
    if (!magnet) return;
    const box      = document.getElementById('wt-player-box');
    const status   = document.getElementById('wt-status');
    const progress = document.getElementById('wt-progress');
    stopWebTorrent();
    box.classList.remove('hidden');
    status.textContent   = 'A procurar peers e carregar metadados... (pode demorar 30s)';
    progress.style.width = '0%';
    let pct = 0;
    progressInterval = setInterval(() => { pct = Math.min(pct + 1.5, 80); progress.style.width = pct + '%'; }, 500);
    try {
        const preloadRes = await fetch(`${STREAM_SERVER}/preload?magnet=${encodeURIComponent(magnet)}`);
        if (!preloadRes.ok) {
            const err = await preloadRes.json().catch(() => ({ error: 'Sem resposta do servidor' }));
            throw new Error(err.error || 'Falha no preload');
        }
        const info = await preloadRes.json();
        clearInterval(progressInterval); progressInterval = null;
        progress.style.width = '90%';
        status.textContent = `A iniciar: ${info.name || '...'} (${info.peers} peers)`;
        const hevcFile = info.needsRemux && /\b(HEVC|X265|H\.?265|HEVC10)\b/i.test(info.file || '');
        const needsEncode = hevcFile && !browserSupportsHEVC();
        currentStreamUrl = `${STREAM_SERVER}/stream?magnet=${encodeURIComponent(magnet)}${needsEncode ? '&transcode=1' : ''}`;
        currentMagnet    = magnet;
        const player = getTorrentPlyr();
        player.source = { type: 'video', sources: [{ src: currentStreamUrl, type: 'video/mp4' }] };
        player.once('ready', () => { player.play().catch(() => {}); status.textContent = '▶ A reproduzir via torrent stream'; progress.style.width = '100%'; });
        player.on('error', () => { status.textContent = '⚠ Erro ao reproduzir — tenta novamente.'; });
    } catch(e) {
        clearInterval(progressInterval); progressInterval = null;
        progress.style.width = '0%';
        status.textContent = `✗ ${e.message}`;
    }
}

function stopWebTorrent() {
    if (progressInterval) { clearInterval(progressInterval); progressInterval = null; }
    if (torrentPlyr) torrentPlyr.pause();
    if (window._subTick && torrentPlyr) torrentPlyr.media?.removeEventListener('timeupdate', window._subTick);
    subtitleCues = []; subtitleOverlay = null; subtitleOffset = 0; subtitleSize = 18; subtitleBg = false; activeSubFileId = null;
    document.getElementById('sub-offset-bar').classList.add('hidden');
    document.getElementById('sub-offset-bar').classList.remove('flex');
    document.getElementById('wt-player-box')?.classList.add('hidden');
    if (currentMagnet) {
        fetch(`${STREAM_SERVER}/stop?magnet=${encodeURIComponent(currentMagnet)}`, { keepalive: true }).catch(() => {});
        currentMagnet = null;
    }
}

function closeTorrents() {
    stopWebTorrent();
    document.getElementById('torrent-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.getElementById('torrent-search-input')?.addEventListener('keydown', e => { if (e.key === 'Enter') doTorrentSearch(); });
document.getElementById('sub-query')?.addEventListener('keydown', e => { if (e.key === 'Enter') searchSubtitles(); });

// ── Subtitle search ───────────────────────────────────────────────────────────
const LANG_LABELS = { pt: '🇵🇹 PT', 'pt-br': '🇧🇷 PT-BR', es: '🇪🇸 ES', en: '🇬🇧 EN', fr: '🇫🇷 FR', de: '🇩🇪 DE', it: '🇮🇹 IT' };
let activeSublang   = 'PT';
let allSubResults   = [];
let activeSubFileId = null;

function setSubLang(lang) {
    activeSublang = lang;
    document.querySelectorAll('.sub-lang-btn').forEach(b => {
        b.classList.remove('bg-white/20', 'text-white');
        b.classList.add('bg-white/5', 'text-gray-400');
    });
    const btn = document.getElementById('sublang-' + lang);
    if (btn) { btn.classList.remove('bg-white/5', 'text-gray-400'); btn.classList.add('bg-white/20', 'text-white'); }
    if (allSubResults.length) renderSubResults(allSubResults);
}

function renderSubResults(subs) {
    const list   = document.getElementById('sub-results');
    const status = document.getElementById('sub-status');
    const filtered = activeSublang === 'PT,ES,EN' ? subs : subs.filter(s => {
        const code = (s.lang_code || '').toLowerCase();
        const sel  = activeSublang.toLowerCase();
        if (sel === 'pt') return code === 'pt' || code === 'pt-br';
        return code === sel;
    });
    status.textContent = `${filtered.length} legenda(s) encontrada(s)`;
    if (!filtered.length) {
        list.innerHTML = `<p class="text-gray-500 text-xs text-center py-3">Sem legendas em ${activeSublang}. Tenta outro idioma.</p>`;
        return;
    }
    list.innerHTML = filtered.slice(0, 20).map(s => {
        const langLabel = LANG_LABELS[s.lang_code] || s.lang || '?';
        return `
        <div class="flex items-center gap-2 bg-white/5 rounded-lg px-3 py-2 text-xs">
            <span class="flex-shrink-0">${langLabel}</span>
            <span class="flex-1 text-gray-300 truncate">${s.name}</span>
            ${s.file_id === activeSubFileId
                ? `<span class="flex-shrink-0 bg-green-600 text-white px-2 py-1 rounded font-semibold">✓ Em uso</span>`
                : `<button onclick="loadExternalSubtitle(${s.file_id})" class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded font-semibold transition">✓ Usar</button>`}
        </div>`;
    }).join('');
}

function toggleSubSearch() {
    const panel = document.getElementById('sub-search-panel');
    panel.classList.toggle('hidden');
    if (!panel.classList.contains('hidden')) {
        document.getElementById('sub-query').value = '{{ addslashes($movie->original_title ?? $movie->title) }}';
        document.getElementById('sub-query').focus();
    }
}

async function searchSubtitles() {
    const query  = document.getElementById('sub-query').value.trim();
    const status = document.getElementById('sub-status');
    const list   = document.getElementById('sub-results');
    if (!query) return;
    status.textContent = 'A pesquisar...';
    list.innerHTML = '';
    try {
        const res  = await fetch(`/subtitles/search?query=${encodeURIComponent(query)}&type=movie`);
        const subs = await res.json();
        allSubResults = subs;
        if (!subs.length) {
            if (activeSublang !== 'PT,ES,EN') {
                status.textContent = `Sem resultados em ${activeSublang}. A pesquisar em todos os idiomas...`;
                setSubLang('PT,ES,EN');
                await searchSubtitles();
                return;
            }
            status.textContent = 'Sem resultados.';
            return;
        }
        renderSubResults(subs);
    } catch(e) { status.textContent = 'Erro ao pesquisar legendas.'; }
}

// ── Custom subtitle overlay ───────────────────────────────────────────────────
let subtitleCues    = [];
let subtitleOverlay = null;
let subtitleOffset  = 0;
let subtitleSize    = 18;
let subtitleBg      = false;

function adjustSubOffset(delta) {
    subtitleOffset += delta;
    document.getElementById('sub-offset-display').textContent =
        (subtitleOffset >= 0 ? '+' : '') + subtitleOffset.toFixed(1) + 's';
    tickSubtitles(getTorrentPlyr().currentTime);
}
function resetSubOffset() {
    subtitleOffset = 0;
    document.getElementById('sub-offset-display').textContent = '0.0s';
}
function adjustSubSize(delta) {
    subtitleSize = Math.min(40, Math.max(10, subtitleSize + delta));
    updateSubSizeDisplay();
}
function toggleSubBg() {
    subtitleBg = !subtitleBg;
    document.getElementById('sub-bg-btn').classList.toggle('active', subtitleBg);
    tickSubtitles(getTorrentPlyr().currentTime);
}

function ensureSubtitleOverlay() {
    if (subtitleOverlay) return subtitleOverlay;
    const container = getTorrentPlyr().elements.container;
    subtitleOverlay = document.createElement('div');
    subtitleOverlay.style.cssText = 'position:absolute;bottom:13%;left:0;right:0;text-align:center;z-index:9;pointer-events:none;padding:0 16px;';
    container.appendChild(subtitleOverlay);
    return subtitleOverlay;
}

function parseVtt(text) {
    const cues = [];
    const blocks = text.replace(/\r\n/g, '\n').split(/\n\n+/);
    for (const block of blocks) {
        const lines  = block.trim().split('\n');
        const tsLine = lines.find(l => l.includes('-->'));
        if (!tsLine) continue;
        const [s, e] = tsLine.split('-->').map(p => vttTime(p.trim().split(' ')[0]));
        const txt = lines.slice(lines.indexOf(tsLine) + 1).join('\n').replace(/<[^>]+>/g, '').trim();
        if (txt) cues.push({ start: s, end: e, text: txt });
    }
    return cues;
}
function vttTime(ts) {
    const p = ts.split(':').map(Number);
    return p.length === 3 ? p[0] * 3600 + p[1] * 60 + p[2] : p[0] * 60 + p[1];
}
function tickSubtitles(currentTime) {
    if (!subtitleOverlay) return;
    const t = currentTime - subtitleOffset;
    const cue = subtitleCues.find(c => t >= c.start && t <= c.end);
    const bg     = subtitleBg ? 'background:rgba(0,0,0,.82);padding:4px 12px;border-radius:4px;' : '';
    const shadow = subtitleBg ? '' : 'text-shadow:1px 1px 3px #000,-1px -1px 3px #000,0 2px 6px rgba(0,0,0,.9);';
    subtitleOverlay.innerHTML = cue
        ? `<span style="${bg}${shadow}color:#fff;font-size:${subtitleSize}px;font-weight:600;line-height:1.5;display:inline-block;max-width:94%;white-space:pre-line;">${cue.text}</span>`
        : '';
}

async function loadExternalSubtitle(fileId) {
    const status = document.getElementById('sub-status');
    status.textContent = 'A descarregar legenda...';
    try {
        const res = await fetch(`/subtitles/download?file_id=${fileId}`);
        if (!res.ok) throw new Error(await res.text());
        const vttText = await res.text();
        subtitleCues = parseVtt(vttText);
        ensureSubtitleOverlay();
        const video = getTorrentPlyr().media;
        if (window._subTick) video.removeEventListener('timeupdate', window._subTick);
        window._subTick = () => tickSubtitles(video.currentTime);
        video.addEventListener('timeupdate', window._subTick);
        activeSubFileId = fileId;
        if (allSubResults.length) renderSubResults(allSubResults);
        subtitleOffset = 0; subtitleSize = 18; subtitleBg = false;
        document.getElementById('sub-offset-display').textContent = '0.0s';
        document.getElementById('sub-size-display').textContent   = '18';
        document.getElementById('sub-bg-btn').classList.remove('active');
        document.getElementById('sub-offset-bar').classList.remove('hidden');
        document.getElementById('sub-offset-bar').classList.add('flex');
        status.textContent = `✓ ${subtitleCues.length} cues carregados!`;
        setTimeout(() => document.getElementById('sub-search-panel').classList.add('hidden'), 1500);
    } catch(e) { status.textContent = 'Erro: ' + e.message; }
}
</script>
@endpush
