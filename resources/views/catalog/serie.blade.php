@extends('layouts.app')

@section('title', $serie->title . ' — MilagrosTV')

@section('content')

{{-- Hero --}}
<div class="relative bg-gradient-to-b from-gray-900 to-black px-6 pt-10 pb-8">
    <div class="max-w-5xl mx-auto flex gap-8 items-start">
        @if($serie->poster_url)
        <img src="{{ $serie->poster_url }}" alt="{{ $serie->title }}"
             class="w-40 rounded-lg shadow-2xl flex-shrink-0 hidden sm:block">
        @endif
        <div class="flex-1">
            <a href="{{ route('catalog.index', ['type' => 'series']) }}" class="text-gray-400 hover:text-white text-sm mb-3 inline-block">{{ __('serie.back') }}</a>
            <h1 class="text-3xl font-bold text-white mb-1">{{ $serie->localTitle() }}</h1>
            @if($serie->original_title && $serie->original_title !== $serie->title)
                <p class="text-gray-400 text-sm mb-2">{{ $serie->original_title }}</p>
            @endif
            <div class="flex flex-wrap gap-3 text-sm text-gray-400 mb-3">
                @if($serie->year) <span>{{ $serie->year }}</span> @endif
                @if($serie->seasons) <span>· {{ $serie->seasons }} {{ __('serie.seasons') }}</span> @endif
                @if($serie->rating) <span class="text-yellow-400">★ {{ number_format($serie->rating, 1) }}</span> @endif
            </div>
            @if(!empty($serie->localGenres()))
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($serie->localGenres() as $genre)
                        <span class="bg-gray-700 text-gray-200 text-xs px-2 py-1 rounded">{{ $genre }}</span>
                    @endforeach
                </div>
            @endif
            @if($serie->synopsis)
                <p class="text-gray-300 text-sm leading-relaxed max-w-2xl">{{ $serie->localSynopsis() }}</p>
            @endif
        </div>
    </div>
</div>

{{-- Player + Episódios --}}
<div class="max-w-5xl mx-auto px-6 pb-16 mt-8">

    @if($episodes->isEmpty())
        <div class="text-center py-16 text-gray-500">
            <p class="text-4xl mb-3">📭</p>
            <p>{{ __('serie.no_episodes') }}</p>
        </div>
    @else

    {{-- Player --}}
    <div id="player-section" class="mb-8 hidden">
        <div class="bg-black rounded-xl overflow-hidden shadow-2xl">
            <video id="video-player" controls class="w-full max-h-[520px] bg-black" preload="metadata">
                <p class="text-gray-400 p-4">O teu browser não suporta reprodução de vídeo.</p>
            </video>
        </div>
        <p id="player-label" class="text-gray-300 text-sm mt-2 px-1"></p>
    </div>

    {{-- Tabs de temporada --}}
    <div class="flex gap-2 mb-6 flex-wrap">
        @foreach($episodes->keys() as $season)
            <button onclick="showSeason({{ $season }})"
                id="tab-{{ $season }}"
                class="season-tab px-4 py-2 rounded text-sm font-medium transition
                       {{ $loop->first ? 'bg-red-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}">
                {{ __('serie.season') }} {{ $season }}
            </button>
        @endforeach
    </div>

    {{-- Listas de episódios --}}
    @foreach($episodes as $season => $eps)
    <div id="season-{{ $season }}" class="season-list {{ !$loop->first ? 'hidden' : '' }}">
        <div class="grid gap-2">
            @foreach($eps as $ep)
            <div class="flex items-center gap-4 bg-gray-800 hover:bg-gray-700 rounded-lg px-4 py-3 transition">
                <div class="w-16 text-center flex-shrink-0">
                    <span class="text-red-500 font-bold text-sm">T{{ $ep->season }}E{{ $ep->episode }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium truncate">
                        {{ $ep->title ?: __('serie.episode') . ' ' . $ep->episode }}
                    </p>
                </div>
                @if($ep->video_path)
                <button onclick="playEpisode({{ $ep->id }}, '{{ addslashes($ep->label) }}')"
                    class="flex-shrink-0 bg-red-600 hover:bg-red-700 text-white text-xs px-4 py-2 rounded font-semibold transition">
                    {{ __('serie.play') }}
                </button>
                @else
                <span class="flex-shrink-0 text-gray-500 text-xs px-4 py-2">{{ __('serie.no_video') }}</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    @endif
</div>

@endsection

@push('scripts')
<script>
function showSeason(season) {
    document.querySelectorAll('.season-list').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.season-tab').forEach(el => {
        el.classList.remove('bg-red-600', 'text-white');
        el.classList.add('bg-gray-800', 'text-gray-300');
    });
    document.getElementById('season-' + season).classList.remove('hidden');
    const tab = document.getElementById('tab-' + season);
    tab.classList.add('bg-red-600', 'text-white');
    tab.classList.remove('bg-gray-800', 'text-gray-300');
}

function playEpisode(episodeId, label) {
    const player  = document.getElementById('video-player');
    const section = document.getElementById('player-section');
    const lbl     = document.getElementById('player-label');
    player.src    = '/video/episode/' + episodeId;
    lbl.textContent = label;
    section.classList.remove('hidden');
    section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    player.play();
}
</script>
@endpush
