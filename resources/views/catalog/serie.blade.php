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

            <div class="flex flex-wrap gap-2">
                {{-- Torrent search button --}}
                <button onclick="openTorrents('{{ addslashes($serie->original_title ?? $serie->title) }}', 'series')"
                    class="flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    {{ __('torrent.find_streams') }}
                </button>

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

{{-- Torrent modal --}}
<div id="torrent-modal" class="hidden fixed inset-0 z-[999] flex items-center justify-center p-4" style="background:rgba(0,0,0,0.92);">
    <div class="w-full max-w-3xl max-h-[85vh] flex flex-col bg-gray-900 rounded-2xl border border-white/10 shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 flex-shrink-0">
            <div>
                <h3 class="font-bold text-white">{{ __('torrent.find_streams') }}</h3>
                <p id="torrent-query-label" class="text-gray-400 text-xs mt-0.5"></p>
            </div>
            <button onclick="closeTorrents()" class="text-gray-500 hover:text-white transition text-xl">✕</button>
        </div>
        <div class="px-6 py-3 border-b border-white/5 flex-shrink-0">
            <div class="flex gap-2">
                <input type="text" id="torrent-search-input"
                    class="flex-1 bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500"
                    placeholder="{{ __('catalog.search_placeholder') }}">
                <button onclick="doTorrentSearch()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    {{ __('common.search') }}
                </button>
            </div>
            {{-- Language filters --}}
            <div class="flex gap-2 mt-2 flex-wrap">
                <button onclick="setLangFilter(null)" id="filter-all"
                    class="lang-filter px-3 py-1 rounded-full text-xs font-semibold transition bg-white/20 text-white">
                    {{ __('subtitles.lang_all') }}
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
                    <span id="wt-status" class="text-gray-400">{{ __('common.loading') }}</span>
                    <div class="flex items-center gap-3">
                        <button onclick="toggleSubSearch()" class="text-gray-500 hover:text-blue-400 transition">{{ __('subtitles.label') }}</button>
                        <button onclick="stopWebTorrent()" class="text-gray-600 hover:text-red-400 transition">{{ __('torrent.stop') }}</button>
                    </div>
                </div>
                <div class="w-full bg-white/5 rounded-full h-1 mt-1">
                    <div id="wt-progress" class="bg-red-500 h-1 rounded-full transition-all" style="width:0%"></div>
                </div>
                {{-- Subtitle controls (offset + style) --}}
                <div id="sub-offset-bar" class="hidden flex-col gap-1.5 mt-2 px-1">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-gray-500 text-xs shrink-0">{{ __('subtitles.sync') }}</span>
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
                        <button onclick="toggleSubBg()" id="sub-bg-btn" class="sub-ctrl-btn ml-2">{{ __('subtitles.background') }}</button>
                    </div>
                </div>
                {{-- Subtitle search panel --}}
                <div id="sub-search-panel" class="hidden mt-3 bg-white/5 rounded-xl p-3">
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="sub-query" placeholder="Título da série..."
                            class="flex-1 bg-white/5 border border-white/10 text-white rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-blue-500">
                        <button onclick="searchSubtitles()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                            {{ __('common.search') }}
                        </button>
                    </div>
                    <p id="sub-episode-info" class="text-gray-500 text-xs mb-1 hidden"></p>
                    <div class="flex gap-1.5 mb-2">
                                <button onclick="setSubLang('PT')" id="sublang-PT" class="sub-lang-btn px-2.5 py-1 rounded-full text-xs font-semibold transition bg-white/20 text-white">🇵🇹 PT</button>
                        <button onclick="setSubLang('ES')" id="sublang-ES" class="sub-lang-btn px-2.5 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400">🇪🇸 ES</button>
                        <button onclick="setSubLang('EN')" id="sublang-EN" class="sub-lang-btn px-2.5 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400">🇬🇧 EN</button>
                        <button onclick="setSubLang('PT,ES,EN')" id="sublang-PT,ES,EN" class="sub-lang-btn px-2.5 py-1 rounded-full text-xs font-semibold transition bg-white/5 text-gray-400">{{ __('subtitles.lang_all') }}</button>
                    </div>
                    <div id="sub-results" class="space-y-1 max-h-40 overflow-y-auto"></div>
                    <p id="sub-status" class="text-xs text-gray-500 mt-1"></p>
                </div>
            </div>

            <div id="torrent-list" class="space-y-2"></div>
            <p id="torrent-empty" class="hidden text-center text-gray-500 py-12 text-sm">{{ __('torrent.no_results') }}</p>
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
                    {{ __('common.close') }}
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
                @if($ep->video_path && isset($progress[$ep->id]) && $progress[$ep->id]->duration > 0)
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
            @if($ep->video_path)
            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition flex items-center gap-2">
                @if($ep->isExternalUrl())
                <span class="text-blue-400 text-xs font-semibold flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Abrir
                </span>
                @elseif(isset($progress[$ep->id]) && !$progress[$ep->id]->completed && $progress[$ep->id]->position > 30)
                <span class="text-orange-400 text-xs font-semibold">{{ __('player.continue') }}</span>
                @else
                <span class="text-red-500 text-sm font-bold">{{ __('serie.play') }}</span>
                @endif
            </div>
            @else
            <button onclick="event.stopPropagation(); openTorrents('{{ addslashes($serie->original_title ?? $serie->title) }} S{{ str_pad($ep->season,2,'0',STR_PAD_LEFT) }}E{{ str_pad($ep->episode,2,'0',STR_PAD_LEFT) }}', 'series')"
                class="flex-shrink-0 text-gray-500 hover:text-orange-400 text-xs transition flex items-center gap-1">
                🧲 <span>{{ __('torrent.find') }}</span>
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
<style>
.sub-ctrl-btn {
    color: #9ca3af;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 4px;
    background: rgba(255,255,255,0.05);
    transition: background 0.15s, color 0.15s;
}
.sub-ctrl-btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
.sub-ctrl-btn.active { background: rgba(255,255,255,0.2); color: #fff; }
</style>
<script>
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
let torrentPlyr      = null;
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
            ? '{{ __('torrent.no_results_filter') }}'
            : '{{ __('torrent.no_results') }}';
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
                {{ __('torrent.play') }}
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

        // Quality picker — use videoFiles from preload (no extra /info call)
        let fileIndex = '';
        if (info.videoFiles && info.videoFiles.length > 1) {
            const choice = await showQualityPicker(info.videoFiles);
            if (choice !== null) fileIndex = `&fileIndex=${choice}`;
        }

        status.textContent = `A iniciar: ${info.name || '...'} (${info.peers} peers)`;

        const tracks = await getSubtitleTracks(magnet);

        const hevcFile    = info.needsRemux && /\b(HEVC|X265|H\.?265|HEVC10)\b/i.test(info.file || '');
        const needsEncode = hevcFile && !browserSupportsHEVC();
        const baseUrl     = `${STREAM_SERVER}/stream?magnet=${encodeURIComponent(magnet)}${needsEncode ? '&transcode=1' : ''}${fileIndex}`;
        currentStreamUrl  = baseUrl;
        currentMagnet     = magnet;

        function applyDurationOverride(media, dur) {
            if (!dur) return;
            try {
                Object.defineProperty(media, 'duration', { get: () => dur, configurable: true });
                media.dispatchEvent(new Event('durationchange'));
            } catch(_) {}
        }

        function loadStream(player, url, seekPos) {
            applyDurationOverride(player.media, info.duration);
            player.media.addEventListener('loadedmetadata', () => applyDurationOverride(player.media, info.duration), { once: true });
            player.media.addEventListener('canplay',        () => applyDurationOverride(player.media, info.duration), { once: true });

            player.source = { type: 'video', sources: [{ src: url, type: 'video/mp4' }], tracks };
        }

        const player = getTorrentPlyr();
        loadStream(player, baseUrl, 0);

        // Seeking: restart stream at new position using ?ss=
        if (info.canSeek) {
            let isSeeking = false;
            player.media.addEventListener('seeking', function onSeek() {
                if (isSeeking) return; // guard against loop
                const t = Math.floor(player.media.currentTime);
                if (t < 2) return;
                clearTimeout(player._seekT);
                player._seekT = setTimeout(() => {
                    isSeeking = true;
                    const seekUrl = `${baseUrl}&ss=${t}`;
                    player.once('ready', () => {
                        isSeeking = false;
                        player.play().catch(() => {});
                        applyDurationOverride(player.media, info.duration);
                    });
                    player.media.addEventListener('loadedmetadata', () => applyDurationOverride(player.media, info.duration), { once: true });
                    player.source = { type: 'video', sources: [{ src: seekUrl, type: 'video/mp4' }] };
                }, 400);
            });
        }

        player.once('ready', () => {
            player.play().catch(() => {});
            applyDurationOverride(player.media, info.duration);
            progress.style.width = '100%';

            // Poll download speed every 3s
            window._speedInterval = setInterval(async () => {
                try {
                    const r = await fetch(`${STREAM_SERVER}/info?magnet=${encodeURIComponent(magnet)}`);
                    const d = await r.json();
                    const dl = d.downloadSpeed || 0;
                    const speed = dl > 1048576 ? (dl/1048576).toFixed(1)+' MB/s' : dl > 1024 ? (dl/1024).toFixed(0)+' KB/s' : dl+'B/s';
                    status.textContent = `▶ ${speed} ↓ · ${d.peers} peers`;
                } catch(_) {}
            }, 3000);
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

function showQualityPicker(videoFiles) {
    return new Promise(resolve => {
        const fmt = b => b >= 1073741824 ? (b/1073741824).toFixed(1)+' GB' : (b/1048576).toFixed(0)+' MB';
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;';
        modal.innerHTML = `
            <div style="background:#1f2937;border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:20px;max-width:400px;width:100%;">
                <p style="font-size:14px;font-weight:600;color:#fff;margin-bottom:12px;">Escolher qualidade</p>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    ${videoFiles.map(f => `
                        <button onclick="this.closest('[data-modal]').dispatchEvent(new CustomEvent('pick',{detail:${f.index}}))"
                            style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 14px;color:#e5e7eb;text-align:left;cursor:pointer;font-size:13px;">
                            <span style="font-weight:600;">${f.name}</span>
                            <span style="color:#6b7280;margin-left:8px;">${fmt(f.size)}</span>
                        </button>`).join('')}
                </div>
                <button onclick="this.closest('[data-modal]').dispatchEvent(new CustomEvent('pick',{detail:null}))"
                    style="margin-top:10px;width:100%;background:transparent;border:0;color:#6b7280;font-size:13px;cursor:pointer;padding:8px;">
                    Cancelar (usar padrão)
                </button>
            </div>`;
        modal.setAttribute('data-modal', '');
        modal.addEventListener('pick', e => { document.body.removeChild(modal); resolve(e.detail); });
        document.body.appendChild(modal);
    });
}

function stopWebTorrent() {
    if (progressInterval) { clearInterval(progressInterval); progressInterval = null; }
    if (window._speedInterval) { clearInterval(window._speedInterval); window._speedInterval = null; }
    if (torrentPlyr) { torrentPlyr.pause(); }
    if (window._subTick && torrentPlyr) torrentPlyr.media.removeEventListener('timeupdate', window._subTick);
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

document.getElementById('torrent-search-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') doTorrentSearch();
});

document.getElementById('sub-query')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') searchSubtitles();
});

// ── Subtitle search (Subdl) ───────────────────────────────────────────────────
const LANG_LABELS = { pt: '🇵🇹 PT', br_pt: '🇧🇷 PT-BR', es: '🇪🇸 ES', en: '🇬🇧 EN', no: '🇳🇴 NO', fr: '🇫🇷 FR', de: '🇩🇪 DE', it: '🇮🇹 IT', id: '🇮🇩 ID', ro: '🇷🇴 RO' };
let activeSublang  = 'PT';
let allSubResults  = [];
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

    // Filter by active language (Todos = show all)
    const filtered = activeSublang === 'PT,ES,EN'
        ? subs
        : subs.filter(s => {
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
        const epFrom = s.episode_from ?? s.episode;
        const epEnd  = s.episode_end  ?? s.episode;
        const epInfo = s.season
            ? (epFrom && epEnd && epFrom !== epEnd
                ? `E${String(epFrom).padStart(2,'0')}-E${String(epEnd).padStart(2,'0')}`
                : epFrom ? `E${String(epFrom).padStart(2,'0')}` : `S${String(s.season).padStart(2,'0')}`)
            : '';
        return `
        <div class="flex items-center gap-2 bg-white/5 rounded-lg px-3 py-2 text-xs">
            <span class="flex-shrink-0">${langLabel}</span>
            ${epInfo ? `<span class="flex-shrink-0 text-gray-500 font-mono">${epInfo}</span>` : ''}
            <span class="flex-1 text-gray-300 truncate">${s.name}</span>
            ${s.file_id === activeSubFileId
                ? `<span class="flex-shrink-0 bg-green-600 text-white px-2 py-1 rounded font-semibold text-xs">✓ Em uso</span>`
                : `<button onclick="loadExternalSubtitle(${s.file_id})"
                class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded font-semibold transition">
                ✓ Usar
            </button>`}
        </div>`;
    }).join('');
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
            const info = document.getElementById('sub-episode-info');
            info.textContent = `A filtrar: S${String(subSeason).padStart(2,'0')}E${String(subEpisode).padStart(2,'0')} — pesquisa só pelo título acima`;
            info.classList.remove('hidden');
        } else {
            document.getElementById('sub-query').value = q;
            subSeason = null; subEpisode = null;
            document.getElementById('sub-episode-info').classList.add('hidden');
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
        allSubResults = subs;

        if (!subs.length) {
            if (lang !== 'PT,ES,EN') {
                status.textContent = '{{ __('subtitles.no_results') }}';
                setSubLang('PT,ES,EN');
                await searchSubtitles();
                return;
            }
            // No episode-specific subtitle found — offer season pack
            if (subEpisode) {
                list.innerHTML = `
                <div class="text-center py-4">
                    <p class="text-gray-400 text-xs mb-3">Sem legenda específica para este episódio no OpenSubtitles.</p>
                    <button onclick="subEpisode=null; document.getElementById('sub-episode-info').textContent='A mostrar legendas da temporada ' + subSeason; searchSubtitles()"
                        class="bg-white/10 hover:bg-white/20 text-white text-xs px-4 py-2 rounded-lg transition">
                        Ver legendas da temporada completa
                    </button>
                </div>`;
                status.textContent = '';
            } else {
                status.textContent = '{{ __('subtitles.no_results') }}';
            }
            return;
        }

        renderSubResults(subs);
    } catch(e) {
        status.textContent = 'Erro ao pesquisar legendas.';
    }
}

// ── Custom subtitle overlay ───────────────────────────────────────────────────
let subtitleCues    = [];
let subtitleOverlay = null;
let subtitleOffset  = 0;
let subtitleSize    = 18;   // px, default slightly larger
let subtitleBg      = false; // default: no background

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
    const btn = document.getElementById('sub-bg-btn');
    btn.classList.toggle('active', subtitleBg);
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
    const bg  = subtitleBg ? 'background:rgba(0,0,0,.82);padding:4px 12px;border-radius:4px;' : '';
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
        if (!res.ok) throw new Error('Falha ao descarregar');
        const vttText = await res.text();
        subtitleCues = parseVtt(vttText);

        ensureSubtitleOverlay();
        const video = getTorrentPlyr().media;
        if (window._subTick) video.removeEventListener('timeupdate', window._subTick);
        window._subTick = () => tickSubtitles(video.currentTime);
        video.addEventListener('timeupdate', window._subTick);

        activeSubFileId = fileId;
        if (allSubResults.length) renderSubResults(allSubResults);

        // Reset controls and show bar
        subtitleOffset = 0;
        subtitleSize   = 18;
        subtitleBg     = false;
        document.getElementById('sub-offset-display').textContent = '0.0s';
        document.getElementById('sub-size-display').textContent   = '18';
        document.getElementById('sub-bg-btn').classList.remove('active');
        document.getElementById('sub-offset-bar').classList.remove('hidden');
        document.getElementById('sub-offset-bar').classList.add('flex');

        status.textContent = `✓ ${subtitleCues.length} cues carregados!`;
        setTimeout(() => document.getElementById('sub-search-panel').classList.add('hidden'), 1500);
    } catch(e) {
        status.textContent = 'Erro ao carregar legenda.';
    }
}
</script>
@endpush
