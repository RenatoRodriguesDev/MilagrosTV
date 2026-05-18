@php
    $watched = isset($watchedIds[$type]) && in_array($item->id, $watchedIds[$type]);
@endphp
<div class="relative card-hover group cursor-pointer" @if($type === 'serie') onclick="window.location='{{ route('catalog.serie', $item) }}'" @endif>
    {{-- Poster --}}
    <div class="relative aspect-[2/3] rounded-lg overflow-hidden bg-gray-800">
        @if($item->poster_url)
            <img src="{{ $item->poster_url }}" alt="{{ $item->title }}"
                 class="w-full h-full object-cover"
                 loading="lazy"
                 onerror="this.parentElement.classList.add('poster-placeholder'); this.remove()">
        @else
            <div class="poster-placeholder w-full h-full flex items-center justify-center">
                <span class="text-4xl">{{ $type === 'movie' ? '🎥' : '📺' }}</span>
            </div>
        @endif

        <div class="watched-badge absolute top-2 right-2 bg-green-600 bg-opacity-90 text-white text-xs font-bold px-2 py-1 rounded {{ $watched ? '' : 'hidden' }}">
            {{ __('card.watched') }}
        </div>

        @if($item->rating)
        <div class="absolute top-2 left-2 bg-black bg-opacity-70 text-yellow-400 text-xs font-bold px-2 py-1 rounded">
            ★ {{ number_format($item->rating, 1) }}
        </div>
        @endif

        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition-all duration-200 flex flex-col justify-end p-3 opacity-0 group-hover:opacity-100">
            @if($item->synopsis)
            <p class="text-xs text-gray-200 line-clamp-3 mb-2">{{ $item->localSynopsis() }}</p>
            @endif
            <button
                onclick="toggleWatched(this, '{{ $type }}', {{ $item->id }})"
                class="w-full text-xs py-1.5 rounded font-semibold transition {{ $watched ? 'bg-green-700' : 'bg-gray-700' }} hover:opacity-90 text-white">
                {{ $watched ? __('card.watched') : __('card.mark_watched') }}
            </button>
        </div>
    </div>

    <div class="mt-2 px-0.5">
        <p class="text-sm font-semibold text-white truncate">{{ $item->localTitle() }}</p>
        <p class="text-xs text-gray-400">
            {{ $item->year }}
            @if($type === 'serie' && $item->seasons)
                · {{ $item->seasons }} {{ __('card.seasons') }}
            @elseif($type === 'movie' && $item->duration)
                · {{ $item->duration }}min
            @endif
        </p>
        @php $genres = $item->localGenres(); @endphp
        @if(!empty($genres))
        <p class="text-xs text-gray-500 truncate">{{ implode(', ', array_slice($genres, 0, 2)) }}</p>
        @endif
    </div>
</div>
