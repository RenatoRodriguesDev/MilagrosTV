@extends('layouts.app')

@section('title', 'MilagrosTV')

@section('content')

@php
    $featured = $series->first() ?? $movies->first();
@endphp

{{-- Hero banner --}}
@if($featured && !$search && !$genre)
<div class="relative h-[55vh] min-h-[360px] flex items-end overflow-hidden">
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

{{-- Filter bar --}}
<div class="sticky top-14 z-40 bg-[#0a0a0a]/95 backdrop-blur border-b border-white/5 px-6 py-3">
    <div class="max-w-7xl mx-auto">
        <form method="GET" action="{{ route('catalog.index') }}" class="flex flex-wrap gap-2 items-center">

            {{-- Search --}}
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-xs">🔍</span>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="{{ __('catalog.search_placeholder') }}"
                    class="search-input bg-white/5 border border-white/10 text-white rounded-lg pl-8 pr-4 py-2 text-sm w-52 focus:outline-none focus:border-red-500/50 transition placeholder-gray-500">
            </div>

            {{-- Genre --}}
            <select name="genre" class="bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500/50 transition">
                <option value="">{{ __('catalog.all_genres') }}</option>
                @foreach($allGenres as $g)
                    <option value="{{ $g }}" @selected($genre === $g) class="bg-gray-900">{{ $g }}</option>
                @endforeach
            </select>

            {{-- Type tabs --}}
            <div class="flex rounded-lg overflow-hidden border border-white/10 text-sm">
                @foreach(['all' => __('catalog.all'), 'movies' => __('catalog.movies'), 'series' => __('catalog.series')] as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['type' => $val]) }}"
                   class="px-4 py-2 font-medium transition {{ $type === $val ? 'bg-red-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>

            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                {{ __('catalog.filter') }}
            </button>
            @if($search || $genre)
            <a href="{{ route('catalog.index', ['type' => $type]) }}" class="text-gray-500 hover:text-white text-sm transition">✕ {{ __('catalog.clear') }}</a>
            @endif
        </form>
    </div>
</div>

{{-- Content --}}
<div class="max-w-7xl mx-auto px-6 py-8 pb-16">

    @if($type !== 'series' && $movies->count() > 0)
    <section class="mb-12 fade-in">
        <div class="flex items-center gap-3 mb-5">
            <span class="text-red-500 text-lg">🎥</span>
            <h2 class="text-lg font-bold text-white">{{ __('catalog.movies') }}</h2>
            <span class="bg-white/10 text-gray-400 text-xs px-2 py-0.5 rounded-full">{{ $movies->count() }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($movies as $movie)
                @include('catalog._card', ['item' => $movie, 'type' => 'movie'])
            @endforeach
        </div>
    </section>
    @endif

    @if($type !== 'movies' && $series->count() > 0)
    <section class="mb-12 fade-in">
        <div class="flex items-center gap-3 mb-5">
            <span class="text-red-500 text-lg">📺</span>
            <h2 class="text-lg font-bold text-white">{{ __('catalog.series') }}</h2>
            <span class="bg-white/10 text-gray-400 text-xs px-2 py-0.5 rounded-full">{{ $series->count() }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($series as $serie)
                @include('catalog._card', ['item' => $serie, 'type' => 'serie'])
            @endforeach
        </div>
    </section>
    @endif

    @if($movies->count() === 0 && $series->count() === 0)
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

</div>

@endsection

@push('scripts')
<script>
const watchedIds = @json($watchedIds);

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
</script>
@endpush
