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
            <p class="text-gray-300 text-sm leading-relaxed max-w-2xl line-clamp-3">{{ $serie->localSynopsis() }}</p>
            @endif
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
                <video id="video-player" controls style="width:100%;max-height:70vh;display:none;border-radius:12px;"></video>
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
            <span class="flex-shrink-0 text-gray-700 text-xs">{{ __('serie.no_video') }}</span>
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
    const video  = document.getElementById('video-player');
    const iframe = document.getElementById('iframe-player');
    const modal  = document.getElementById('player-modal');
    const lbl    = document.getElementById('player-label');

    video.pause();
    video.src = '';
    video.style.display = 'none';
    iframe.src = '';
    iframe.style.display = 'none';

    if (embedUrl) {
        iframe.src = embedUrl;
        iframe.style.display = 'block';
    } else {
        video.src = '/video/episode/' + episodeId;
        video.style.display = 'block';
        setTimeout(() => video.play(), 300);
    }

    lbl.textContent = label;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePlayer() {
    const video  = document.getElementById('video-player');
    const iframe = document.getElementById('iframe-player');
    const modal  = document.getElementById('player-modal');
    video.pause();
    video.src = '';
    video.style.display = 'none';
    iframe.src = '';
    iframe.style.display = 'none';
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// Fechar ao clicar fora
document.getElementById('player-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closePlayer();
});
</script>
@endpush
