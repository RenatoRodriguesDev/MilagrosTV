@php
    $watched     = isset($watchedIds[$type])   && in_array($item->id, $watchedIds[$type]);
    $inWatchlist = isset($watchlistIds[$type]) && in_array($item->id, $watchlistIds[$type]);
@endphp

<div class="card-item relative cursor-pointer group"
     @if($type === 'serie') onclick="window.location='{{ route('catalog.serie', $item) }}'"
     @elseif($type === 'movie') onclick="window.location='{{ route('catalog.movie', $item) }}'"
     @endif>

    {{-- Poster --}}
    <div class="relative aspect-[2/3] rounded-xl overflow-hidden bg-gray-900">

        @if($item->poster_url)
            <img src="{{ $item->poster_url }}"
                 alt="{{ $item->title }}"
                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                 loading="lazy"
                 onerror="this.parentElement.classList.add('poster-placeholder'); this.remove()">
        @else
            <div class="poster-placeholder w-full h-full flex items-center justify-center">
                <span class="text-5xl opacity-30">{{ $type === 'movie' ? '🎥' : '📺' }}</span>
            </div>
        @endif

        {{-- Always-on gradient --}}
        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/80 to-transparent"></div>

        {{-- Rating badge --}}
        @if($item->rating)
        <div class="absolute top-2 left-2 flex items-center gap-1 bg-black/60 backdrop-blur-sm text-yellow-400 text-xs font-bold px-2 py-1 rounded-md">
            ★ {{ number_format($item->rating, 1) }}
        </div>
        @endif

        {{-- Watched badge --}}
        <div class="watched-badge absolute top-2 right-2 {{ $watched ? '' : 'hidden' }} bg-green-500/90 text-white text-xs font-bold px-2 py-1 rounded-md">
            ✓
        </div>

        {{-- Hover overlay: play icon + synopsis (desktop only effect) --}}
        <div class="card-overlay absolute inset-0 opacity-0 group-hover:opacity-100 flex flex-col justify-center items-center p-3 pointer-events-none">
            @if($type === 'serie' || $type === 'movie')
            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-xl">
                <span class="text-black text-sm ml-0.5">▶</span>
            </div>
            @endif
            @if($item->synopsis)
            <p class="text-xs text-gray-200 line-clamp-2 mt-2 leading-relaxed text-center">{{ $item->localSynopsis() }}</p>
            @endif
        </div>

        {{-- Action buttons — always visible, bottom of poster --}}
        <div class="absolute bottom-0 inset-x-0 p-2 flex gap-1.5" onclick="event.stopPropagation()">
            <button
                onclick="toggleWatched(this, '{{ $type }}', {{ $item->id }})"
                class="flex-1 text-xs py-1.5 rounded-lg font-semibold transition {{ $watched ? 'bg-green-600' : 'bg-black/60 hover:bg-white/20' }} backdrop-blur-sm text-white border border-white/10">
                {{ $watched ? __('card.watched') : __('card.mark_watched') }}
            </button>
            <button
                onclick="toggleWatchlist(this, '{{ $type }}', {{ $item->id }})"
                title="{{ $inWatchlist ? __('catalog.my_list') : __('catalog.my_list') }}"
                class="text-xs px-2.5 py-1.5 rounded-lg bg-black/60 hover:bg-white/20 backdrop-blur-sm text-white border border-white/10 transition">
                {{ $inWatchlist ? '🔖' : '＋' }}
            </button>
        </div>
    </div>

    {{-- Info --}}
    <div class="mt-2.5 px-0.5">
        <p class="text-sm font-semibold text-white truncate leading-tight">{{ $item->localTitle() }}</p>
        <p class="text-xs text-gray-500 mt-0.5">
            {{ $item->year }}
            @if($type === 'serie' && $item->seasons)
                · {{ $item->seasons }} {{ __('card.seasons') }}
            @elseif($type === 'movie' && $item->duration)
                · {{ $item->duration }}min
            @endif
        </p>
        @php $genres = $item->localGenres(); @endphp
        @if(!empty($genres))
        <p class="text-xs text-gray-600 truncate mt-0.5">{{ implode(' · ', array_slice($genres, 0, 2)) }}</p>
        @endif
    </div>
</div>
