@extends('layouts.app')

@section('title', 'MilagrosTV')

@section('content')

@php
    $featured = $series->first() ?? $movies->first();
@endphp

{{-- Hero banner --}}
@if($featured && !$search && !$genre)
<div class="relative h-[55vh] min-h-[360px] flex items-end overflow-hidden w-full">
    {{-- Background --}}
    @if($featured->poster_url)
    <div class="absolute inset-0">
        <img src="{{ $featured->poster_url }}" alt="" class="w-full h-full object-cover object-top scale-110" style="filter: blur(2px);">
        <div class="absolute inset-0 bg-gradient-to-r from-black via-black/60 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a0a] via-transparent to-black/30"></div>
    </div>
    @else
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900 to-black"></div>
    @endif

    {{-- Content --}}
    <div class="relative max-w-7xl mx-auto px-6 pb-12 w-full">
        <p class="text-red-500 text-xs font-bold uppercase tracking-widest mb-2">
            {{ $series->isNotEmpty() ? __('nav.series') : __('nav.movies') }} · {{ __('catalog.featured') ?? 'Destaque' }}
        </p>
        <h1 class="text-4xl sm:text-5xl font-black text-white mb-3 leading-tight max-w-lg">
            {{ $featured->localTitle() }}
        </h1>
        @if($featured->synopsis)
        <p class="text-gray-300 text-sm max-w-md line-clamp-2 mb-5 leading-relaxed">
            {{ $featured->localSynopsis() }}
        </p>
        @endif
        <div class="flex gap-3 flex-wrap">
            @if($featured instanceof \App\Models\Serie)
            <a href="{{ route('catalog.serie', $featured) }}"
               class="flex items-center gap-2 bg-white text-black px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-gray-200 transition">
                ▶ {{ __('serie.play') ?? 'Ver' }}
            </a>
            @endif
            @if($featured->rating)
            <div class="flex items-center gap-1.5 bg-white/10 backdrop-blur px-4 py-2.5 rounded-lg text-sm border border-white/10">
                <span class="text-yellow-400">★</span>
                <span class="font-semibold">{{ number_format($featured->rating, 1) }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
@else
<div class="pt-20"></div>
@endif

{{-- Filter bar — só no modo grelha --}}
@if(empty($carousels))
<div class="sticky top-14 z-40 bg-[#0a0a0a]/95 backdrop-blur border-b border-white/5 px-4 py-3">
    <div class="max-w-7xl mx-auto">
        <form method="GET" action="{{ route('catalog.index') }}" class="flex flex-wrap gap-2 items-center">
            <div class="relative w-full sm:w-52">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs">🔍</span>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="{{ __('catalog.search_placeholder') }}"
                    class="search-input bg-white/5 border border-white/10 text-white rounded-lg pl-8 pr-3 py-2 text-sm w-full focus:outline-none focus:border-red-500/50 transition placeholder-gray-500">
            </div>
            <select name="genre" class="bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500/50 transition w-full sm:w-auto">
                <option value="">{{ __('catalog.all_genres') }}</option>
                @foreach($allGenres as $g)
                    <option value="{{ $g }}" @selected($genre === $g) class="bg-gray-900">{{ $g }}</option>
                @endforeach
            </select>
            <div class="flex rounded-lg overflow-hidden border border-white/10 text-sm flex-shrink-0">
                @foreach(['all' => __('catalog.all'), 'movies' => __('catalog.movies'), 'series' => __('catalog.series')] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['type' => $val]) }}"
                   class="px-4 py-2 font-medium transition {{ $type === $val ? 'bg-red-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
            <select name="sort" onchange="this.form.submit()"
                class="bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500/50 transition flex-shrink-0">
                <option value="title"  @selected($sort==='title')  class="bg-gray-900">A-Z</option>
                <option value="year"   @selected($sort==='year')   class="bg-gray-900">{{ __('catalog.sort_year') ?? 'Ano' }}</option>
                <option value="rating" @selected($sort==='rating') class="bg-gray-900">★ {{ __('catalog.sort_rating') ?? 'Nota' }}</option>
                <option value="added"  @selected($sort==='added')  class="bg-gray-900">{{ __('catalog.sort_added') ?? 'Recentes' }}</option>
            </select>
            <input type="hidden" name="order" value="{{ $order }}">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex-shrink-0">
                {{ __('catalog.filter') }}
            </button>
            @if($search || $genre)
            <a href="{{ route('catalog.index', ['type' => $type]) }}" class="text-gray-500 hover:text-white text-sm transition">✕ {{ __('catalog.clear') }}</a>
            @endif
        </form>
    </div>
</div>
@endif

{{-- Content --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 pb-16">

    {{-- Continuar a ver --}}
    @if($continueWatching->isNotEmpty() && !$search && !$genre)
    <section class="mb-10 fade-in">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-red-500 text-lg">▶</span>
            <h2 class="text-lg font-bold text-white">{{ __('catalog.continue_watching') }}</h2>
        </div>
        <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-none">
            @foreach($continueWatching as $prog)
            @php $ep = $prog->episode; $serie = $ep->serie; @endphp
            <div class="flex-shrink-0 w-44 group relative rounded-xl overflow-hidden bg-gray-800 border border-white/10 hover:border-red-500/50 transition"
                 id="cw-{{ $prog->episode_id }}">
                {{-- Remove button --}}
                <button onclick="event.preventDefault(); dismissProgress({{ $prog->episode_id }})"
                    class="absolute top-2 right-2 z-10 w-6 h-6 rounded-full bg-black/70 text-white text-xs flex items-center justify-center transition hover:bg-red-600">✕</button>

                <a href="{{ route('catalog.serie', $serie) }}" class="block">
                    @if($serie->poster_url)
                    <img src="{{ $serie->poster_url }}" class="w-full h-64 object-cover">
                    @else
                    <div class="w-full h-64 bg-gray-700 flex items-center justify-center text-3xl">📺</div>
                    @endif
                    {{-- Progress bar --}}
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-black/60">
                        <div class="h-full bg-red-500" style="width: {{ $prog->percent }}%"></div>
                    </div>
                    {{-- Overlay --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent flex flex-col justify-end p-3">
                        <p class="text-white text-xs font-semibold truncate">{{ $serie->localTitle() }}</p>
                        <p class="text-gray-400 text-[10px]">T{{ $ep->season }}E{{ $ep->episode }}{{ $ep->title ? ' · '.Str::limit($ep->title, 20) : '' }}</p>
                        <p class="text-red-400 text-[10px] font-medium mt-0.5">{{ gmdate($prog->position >= 3600 ? 'H:i:s' : 'i:s', $prog->position) }} restantes</p>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                        <div class="w-12 h-12 rounded-full bg-red-600/90 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Watchlist --}}
    @if(isset($watchlistSection) && $watchlistSection->isNotEmpty() && !$search && !$genre)
    <section class="mb-10 fade-in">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-yellow-500 text-lg">🔖</span>
            <h2 class="text-lg font-bold text-white">{{ __('catalog.my_list') }}</h2>
        </div>
    </section>
    @endif

    {{-- Carrosséis por género (vista padrão sem filtros) --}}
    @if(!empty($carousels))
        @foreach($carousels as $ci => $carousel)
        <section class="mb-8 fade-in">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    @if($carousel['icon'])<span>{{ $carousel['icon'] }}</span>@endif
                    @if(!empty($carousel['link']))
                    <a href="{{ route('catalog.index') }}{{ $carousel['link'] }}"
                       class="text-lg font-bold text-white hover:text-red-400 transition">{{ $carousel['title'] }}</a>
                    @else
                    <h2 class="text-lg font-bold text-white">{{ $carousel['title'] }}</h2>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if(!empty($carousel['link']))
                    <a href="{{ route('catalog.index') }}{{ $carousel['link'] }}"
                       class="text-xs text-gray-500 hover:text-white transition hidden sm:block">
                        {{ __('catalog.see_all') }} →
                    </a>
                    @endif
                    <button class="swiper-prev-{{ $ci }} w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition text-lg disabled:opacity-30">‹</button>
                    <button class="swiper-next-{{ $ci }} w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition text-lg disabled:opacity-30">›</button>
                </div>
            </div>
            <div class="swiper swiper-carousel-{{ $ci }}">
                <div class="swiper-wrapper">
                    @foreach($carousel['items'] as $item)
                    @php
                        $isMovie  = $item instanceof \App\Models\Movie;
                        $itemType = $isMovie ? 'movie' : 'serie';
                    @endphp
                    <div class="swiper-slide">
                        @include('catalog._card', ['item' => $item, 'type' => $itemType])
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endforeach

    {{-- Vista de grelha (com filtros activos) --}}
    @else

    @if($type !== 'series' && $movies->total() > 0)
    <section class="mb-12 fade-in">
        <div class="flex items-center gap-3 mb-5">
            <span class="text-red-500 text-lg">🎥</span>
            <h2 class="text-lg font-bold text-white">{{ __('catalog.movies') }}</h2>
            <span class="bg-white/10 text-gray-400 text-xs px-2 py-0.5 rounded-full">{{ $movies->total() }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($movies as $movie)
                @include('catalog._card', ['item' => $movie, 'type' => 'movie'])
            @endforeach
        </div>
        @if($movies->hasPages())
        <div class="flex justify-center mt-6">{{ $movies->links('catalog._pagination') }}</div>
        @endif
    </section>
    @endif

    @if($type !== 'movies' && $series->total() > 0)
    <section class="mb-12 fade-in">
        <div class="flex items-center gap-3 mb-5">
            <span class="text-red-500 text-lg">📺</span>
            <h2 class="text-lg font-bold text-white">{{ __('catalog.series') }}</h2>
            <span class="bg-white/10 text-gray-400 text-xs px-2 py-0.5 rounded-full">{{ $series->total() }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($series as $serie)
                @include('catalog._card', ['item' => $serie, 'type' => 'serie'])
            @endforeach
        </div>
        @if($series->hasPages())
        <div class="flex justify-center mt-6">{{ $series->links('catalog._pagination') }}</div>
        @endif
    </section>
    @endif

    @if($movies->total() === 0 && $series->total() === 0)
    <div class="text-center py-32 fade-in">
        <p class="text-6xl mb-4 opacity-50">🔍</p>
        <p class="text-gray-500 text-lg">{{ __('catalog.no_results') }}</p>
        @if($search || $genre)
        <a href="{{ route('catalog.index', ['type' => $type]) }}" class="mt-4 inline-block text-red-500 hover:text-red-400 text-sm transition">
            {{ __('catalog.clear') }} →
        </a>
        @endif
    </div>
    @endif
    @endif

</div>

{{-- Floating request button --}}
<button onclick="document.getElementById('request-modal').classList.remove('hidden')"
    class="fixed bottom-20 sm:bottom-6 right-4 sm:right-6 z-50 flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-3 rounded-full shadow-lg shadow-red-900/40 font-semibold transition-all hover:scale-105 active:scale-95">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
    Pedir conteúdo
</button>

{{-- Request modal --}}
<div id="request-modal" class="hidden fixed inset-0 z-[9999] flex items-end sm:items-start sm:justify-center sm:pt-20" style="background:rgba(0,0,0,0.75);">
    <div class="w-full sm:max-w-lg bg-[#111] border border-white/10 sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/[.08]">
            <h2 class="font-bold text-white text-sm">📬 Pedir conteúdo</h2>
            <button onclick="closeRequestModal()" class="text-gray-500 hover:text-white transition p-1">✕</button>
        </div>
        <div class="p-4">
            <div class="flex flex-col gap-2 mb-4">
                <input id="req-query" type="text" placeholder="Nome do filme ou série..."
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-white text-sm focus:outline-none focus:border-red-500 placeholder-gray-500"
                    oninput="reqDebounce()" onkeydown="if(event.key==='Enter') searchRequest()">
                <button onclick="searchRequest()"
                    class="w-full bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg text-sm font-semibold transition">
                    Pesquisar
                </button>
            </div>
            <div id="req-results" class="space-y-2 max-h-64 overflow-y-auto"></div>
            <p id="req-msg" class="text-sm text-center mt-3 hidden"></p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Inicializar Swiper para cada carrossel
@if(!$search && !$genre && $type === 'all' && !empty($carousels))
@foreach($carousels as $ci => $carousel)
new Swiper('.swiper-carousel-{{ $ci }}', {
    slidesPerView: 2.3,
    spaceBetween: 12,
    grabCursor: true,
    lazy: { loadPrevNext: true, loadPrevNextAmount: 2 },
    navigation: {
        nextEl: '.swiper-next-{{ $ci }}',
        prevEl: '.swiper-prev-{{ $ci }}',
    },
    breakpoints: {
        480:  { slidesPerView: 3.3, spaceBetween: 12 },
        640:  { slidesPerView: 3.5, spaceBetween: 14 },
        768:  { slidesPerView: 4.3, spaceBetween: 14 },
        1024: { slidesPerView: 5.3, spaceBetween: 16 },
        1280: { slidesPerView: 6.3, spaceBetween: 16 },
    },
});
@endforeach
@endif

const watchedIds    = @json($watchedIds);
const watchlistIds  = @json($watchlistIds);

async function toggleWatchlist(btn, type, id) {
    const res = await fetch('{{ route("watchlist.toggle") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ item_type: type, item_id: id }),
    });
    const data = await res.json();
    if (data.in_watchlist) {
        btn.textContent = '🔖';
        btn.title = 'Remover da lista';
        if (!watchlistIds[type]) watchlistIds[type] = [];
        watchlistIds[type].push(id);
    } else {
        btn.textContent = '＋';
        btn.title = 'Adicionar à lista';
        watchlistIds[type] = (watchlistIds[type] || []).filter(i => i !== id);
    }
}

async function toggleWatched(btn, type, id) {
    const res = await fetch('{{ route("catalog.watched") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ item_type: type, item_id: id }),
    });
    const data = await res.json();
    updateBtn(btn, data.watched);
    if (data.watched) {
        if (!watchedIds[type]) watchedIds[type] = [];
        watchedIds[type].push(id);
    } else {
        watchedIds[type] = watchedIds[type].filter(i => i !== id);
    }
}

function updateBtn(btn, watched) {
    const badge = btn.closest('.relative')?.querySelector('.watched-badge');
    const watchedText   = @json(__('card.watched'));
    const unwatchedText = @json(__('card.mark_watched'));
    if (watched) {
        btn.textContent = watchedText;
        btn.classList.replace('bg-white/10', 'bg-green-600');
        if (badge) badge.classList.remove('hidden');
    } else {
        btn.textContent = unwatchedText;
        btn.classList.replace('bg-green-600', 'bg-white/10');
        if (badge) badge.classList.add('hidden');
    }
}

async function dismissProgress(episodeId) {
    await fetch(`/progress/${episodeId}/dismiss`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    }).catch(() => {});
    const card = document.getElementById(`cw-${episodeId}`);
    if (card) card.remove();
}
</script>
<script>
// ── Content request widget ────────────────────────────────────────────────────
let reqTimer = null;
function reqDebounce() { clearTimeout(reqTimer); reqTimer = setTimeout(searchRequest, 500); }

function closeRequestModal() {
    document.getElementById('request-modal').classList.add('hidden');
    document.getElementById('req-results').innerHTML = '';
    document.getElementById('req-query').value = '';
    document.getElementById('req-msg').classList.add('hidden');
}
document.getElementById('request-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeRequestModal();
});

const TMDB_IMG = 'https://image.tmdb.org/t/p/w92';

async function searchRequest() {
    const q = document.getElementById('req-query').value.trim();
    if (!q) return;
    const res = document.getElementById('req-results');
    res.innerHTML = '<p class="text-gray-500 text-xs text-center py-4">A pesquisar...</p>';

    const [movies, series] = await Promise.all([
        fetch(`/admin/movies/tmdb-search?query=${encodeURIComponent(q)}`).then(r => r.json()).catch(() => []),
        fetch(`/admin/series/tmdb-search?query=${encodeURIComponent(q)}`).then(r => r.json()).catch(() => []),
    ]);

    // Normalise raw TMDB fields
    const normalise = (item, type) => ({
        tmdb_id:        item.id,
        type,
        title:          item.title || item.name || '',
        original_title: item.original_title || item.original_name || null,
        poster_url:     item.poster_path ? TMDB_IMG + item.poster_path : null,
        year:           (item.release_date || item.first_air_date || '').substring(0, 4) || null,
        popularity:     item.popularity || 0,
    });

    const items = [
        ...movies.map(m => normalise(m, 'movie')),
        ...series.map(s => normalise(s, 'tv')),
    ].sort((a, b) => b.popularity - a.popularity).slice(0, 8);

    if (!items.length) { res.innerHTML = '<p class="text-gray-500 text-xs text-center py-4">Sem resultados.</p>'; return; }

    res.innerHTML = items.map((item, i) => `
        <div class="flex items-center gap-3 bg-gray-800/60 hover:bg-gray-800 rounded-xl px-3 py-2.5 transition">
            ${item.poster_url
                ? `<img src="${item.poster_url}" class="w-10 h-14 object-cover rounded flex-shrink-0" loading="lazy">`
                : '<div class="w-10 h-14 bg-gray-700 rounded flex-shrink-0 flex items-center justify-center text-gray-600 text-lg">🎬</div>'}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate">${item.title}</p>
                <p class="text-xs text-gray-500">${item.type === 'movie' ? 'Filme' : 'Série'}${item.year ? ' · ' + item.year : ''}</p>
            </div>
            <button onclick="submitRequest(reqItems[${i}])"
                class="flex-shrink-0 text-xs bg-white/10 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg font-semibold transition">
                Pedir
            </button>
        </div>
    `).join('');

    window.reqItems = items;
}

async function submitRequest(item) {
    const msg = document.getElementById('req-msg');
    msg.className = 'text-sm text-center mt-3 text-gray-400';
    msg.textContent = 'A enviar...';
    msg.classList.remove('hidden');

    const res = await fetch('{{ route("content-requests.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({
            tmdb_id:        String(item.tmdb_id),
            type:           item.type,
            title:          item.title,
            original_title: item.original_title || null,
            poster_url:     item.poster_url || null,
            year:           item.year ? parseInt(item.year) : null,
        }),
    });
    const data = await res.json();
    msg.textContent = data.message;
    msg.className = 'text-sm text-center mt-3 ' + (data.ok ? 'text-green-400' : 'text-yellow-400');
}
</script>
@endpush
