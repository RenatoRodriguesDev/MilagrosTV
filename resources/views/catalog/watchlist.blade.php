@extends('layouts.app')
@section('title', __('catalog.my_list') . ' — MilagrosTV')

@section('content')
@php
$watchedIds   = auth()->check()
    ? \App\Models\WatchedItem::where('user_id', auth()->id())->get()->groupBy('item_type')->map(fn($i) => $i->pluck('item_id')->toArray())->toArray()
    : [];
$watchlistIds = ['movie' => $movies->pluck('id')->toArray(), 'serie' => $series->pluck('id')->toArray()];
@endphp
<div class="max-w-7xl mx-auto px-6 pt-28 pb-20">

    <div class="flex items-center gap-3 mb-8">
        <span class="text-yellow-500 text-2xl">🔖</span>
        <h1 class="text-2xl font-black text-white">{{ __('catalog.my_list') }}</h1>
        <span class="bg-white/10 text-gray-400 text-sm px-2.5 py-0.5 rounded-full">{{ $movies->count() + $series->count() }}</span>
    </div>

    @if($movies->isEmpty() && $series->isEmpty())
    <div class="text-center py-32 text-gray-600">
        <p class="text-6xl mb-4">🔖</p>
        <p class="text-lg">A tua lista está vazia.</p>
        <p class="text-sm mt-2">Clica em <span class="text-yellow-400">＋ Minha lista</span> em qualquer filme ou série.</p>
        <a href="{{ route('catalog.index') }}" class="mt-6 inline-block text-red-500 hover:text-red-400 text-sm transition">← Voltar ao catálogo</a>
    </div>
    @else

    @if($series->isNotEmpty())
    <section class="mb-12">
        <div class="flex items-center gap-3 mb-5">
            <span class="text-red-500">📺</span>
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

    @if($movies->isNotEmpty())
    <section class="mb-12">
        <div class="flex items-center gap-3 mb-5">
            <span class="text-red-500">🎥</span>
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

    @endif
</div>
@endsection

@push('scripts')
<script>
const watchedIds   = @json($watchedIds);
const watchlistIds = @json($watchlistIds);

async function toggleWatched(btn, type, id) {
    const res = await fetch('{{ route("catalog.watched") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ item_type: type, item_id: id }),
    });
    const data = await res.json();
    const badge = btn.closest('.relative')?.querySelector('.watched-badge');
    const watchedText   = @json(__('card.watched'));
    const unwatchedText = @json(__('card.mark_watched'));
    if (data.watched) {
        btn.textContent = watchedText;
        btn.classList.replace('bg-white/10', 'bg-green-600');
        if (badge) badge.classList.remove('hidden');
    } else {
        btn.textContent = unwatchedText;
        btn.classList.replace('bg-green-600', 'bg-white/10');
        if (badge) badge.classList.add('hidden');
    }
}

async function toggleWatchlist(btn, type, id) {
    const res = await fetch('{{ route("watchlist.toggle") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ item_type: type, item_id: id }),
    });
    const data = await res.json();
    if (!data.in_watchlist) {
        // Remove card from watchlist page
        btn.closest('.card-item')?.remove();
    }
    btn.textContent = data.in_watchlist ? '🔖' : '＋';
}
</script>
@endpush
