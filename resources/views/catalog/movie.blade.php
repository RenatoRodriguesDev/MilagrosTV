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

            <button onclick="openTorrents('{{ addslashes($movie->localTitle()) }}', 'movie')"
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
                    <video id="wt-video" controls class="w-full" style="max-height:300px;"></video>
                </div>
                <div class="flex items-center justify-between mt-2 text-xs px-1">
                    <span id="wt-status" class="text-gray-400">A carregar...</span>
                    <button onclick="stopWebTorrent()" class="text-gray-600 hover:text-red-400 transition">✕ Parar</button>
                </div>
                <div class="w-full bg-white/5 rounded-full h-1 mt-1">
                    <div id="wt-progress" class="bg-red-500 h-1 rounded-full transition-all" style="width:0%"></div>
                </div>
            </div>
            <div id="torrent-list" class="space-y-2"></div>
            <p id="torrent-empty" class="hidden text-center text-gray-500 py-12 text-sm">Nenhum resultado encontrado.</p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
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
    return { code: 'und', label: 'Legenda' };
}

async function loadSubtitles(video, magnet) {
    Array.from(video.querySelectorAll('track')).forEach(t => t.remove());
    try {
        const res = await fetch(`${STREAM_SERVER}/subtitles?magnet=${encodeURIComponent(magnet)}`);
        if (!res.ok) return;
        const subs = await res.json();
        if (!subs.length) return;
        document.getElementById('wt-status').textContent += ` · ${subs.length} legenda(s)`;
        subs.forEach((sub, i) => {
            const lang = detectSubLang(sub.name);
            const track = document.createElement('track');
            track.kind = 'subtitles'; track.label = lang.label; track.srclang = lang.code;
            track.src = `${STREAM_SERVER}/subtitle?magnet=${encodeURIComponent(magnet)}&name=${encodeURIComponent(sub.name)}`;
            if (i === 0) track.default = true;
            video.appendChild(track);
        });
    } catch(e) {}
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

// ── Torrent streaming ─────────────────────────────────────────────────────────
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
        await loadSubtitles(video, magnet);
        video.src = `${STREAM_SERVER}/stream?magnet=${encodeURIComponent(magnet)}`;
        video.play().catch(() => {});
        video.onloadedmetadata = () => { status.textContent = '▶ A reproduzir'; progress.style.width = '100%'; };
        video.onerror = () => { status.textContent = '⚠ Erro ao reproduzir — tenta novamente.'; };
    } catch(e) {
        clearInterval(progressInterval); progressInterval = null;
        progress.style.width = '0%';
        status.textContent = `✗ ${e.message}`;
    }
}

function stopWebTorrent() {
    if (progressInterval) { clearInterval(progressInterval); progressInterval = null; }
    const video = document.getElementById('wt-video');
    if (video) { video.pause(); video.src = ''; }
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
</script>
@endpush
