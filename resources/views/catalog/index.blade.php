@extends('layouts.app')

@section('title', 'MilagrosTV')

@section('content')

<div class="bg-gradient-to-b from-black to-transparent px-6 pt-8 pb-4">
    <h1 class="text-3xl font-bold mb-6 text-white">{{ __('catalog.title') }}</h1>

    <form method="GET" action="{{ route('catalog.index') }}" class="flex flex-wrap gap-3 items-center">
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="{{ __('catalog.search_placeholder') }}"
            class="bg-gray-800 border border-gray-600 text-white rounded px-4 py-2 text-sm w-64 focus:outline-none focus:border-red-500"
        >

        <select name="genre" class="bg-gray-800 border border-gray-600 text-white rounded px-4 py-2 text-sm focus:outline-none focus:border-red-500">
            <option value="">{{ __('catalog.all_genres') }}</option>
            @foreach($allGenres as $g)
                <option value="{{ $g }}" @selected($genre === $g)>{{ $g }}</option>
            @endforeach
        </select>

        <div class="flex rounded overflow-hidden border border-gray-600">
            <a href="{{ request()->fullUrlWithQuery(['type' => 'all']) }}"
               class="px-4 py-2 text-sm {{ $type === 'all' ? 'bg-red-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                {{ __('catalog.all') }}
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'movies']) }}"
               class="px-4 py-2 text-sm {{ $type === 'movies' ? 'bg-red-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                {{ __('catalog.movies') }}
            </a>
            <a href="{{ request()->fullUrlWithQuery(['type' => 'series']) }}"
               class="px-4 py-2 text-sm {{ $type === 'series' ? 'bg-red-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                {{ __('catalog.series') }}
            </a>
        </div>

        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm transition">
            {{ __('catalog.filter') }}
        </button>
        @if($search || $genre)
            <a href="{{ route('catalog.index', ['type' => $type]) }}" class="text-gray-400 hover:text-white text-sm">{{ __('catalog.clear') }}</a>
        @endif
    </form>
</div>

<div class="px-6 pb-12">

    @if($type !== 'series' && $movies->count() > 0)
    <section class="mb-12">
        <h2 class="text-xl font-semibold mb-4 text-gray-200">
            🎥 {{ __('catalog.movies') }} <span class="text-gray-500 text-base font-normal">({{ $movies->count() }})</span>
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($movies as $movie)
                @include('catalog._card', ['item' => $movie, 'type' => 'movie'])
            @endforeach
        </div>
    </section>
    @endif

    @if($type !== 'movies' && $series->count() > 0)
    <section class="mb-12">
        <h2 class="text-xl font-semibold mb-4 text-gray-200">
            📺 {{ __('catalog.series') }} <span class="text-gray-500 text-base font-normal">({{ $series->count() }})</span>
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            @foreach($series as $serie)
                @include('catalog._card', ['item' => $serie, 'type' => 'serie'])
            @endforeach
        </div>
    </section>
    @endif

    @if($movies->count() === 0 && $series->count() === 0)
    <div class="text-center py-24 text-gray-500">
        <p class="text-5xl mb-4">{{ __('catalog.no_results_emoji') }}</p>
        <p class="text-lg">{{ __('catalog.no_results') }}</p>
    </div>
    @endif

</div>

@endsection

@push('scripts')
<script>
const watchedIds = @json($watchedIds);

function isWatched(type, id) {
    return watchedIds[type] && watchedIds[type].includes(id);
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
    const badge = btn.closest('.relative').querySelector('.watched-badge');
    const watchedText  = @json(__('card.watched'));
    const unwatchedText = @json(__('card.mark_watched'));
    if (watched) {
        btn.textContent = watchedText;
        btn.classList.replace('bg-gray-700', 'bg-green-700');
        if (badge) badge.classList.remove('hidden');
    } else {
        btn.textContent = unwatchedText;
        btn.classList.replace('bg-green-700', 'bg-gray-700');
        if (badge) badge.classList.add('hidden');
    }
}
</script>
@endpush
