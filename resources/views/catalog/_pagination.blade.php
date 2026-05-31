@if($paginator->hasPages())
<nav class="flex items-center gap-1 flex-wrap justify-center">
    {{-- Previous --}}
    @if($paginator->onFirstPage())
        <span class="px-3 py-1.5 text-sm text-gray-700 rounded-lg cursor-not-allowed">←</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-lg transition">←</a>
    @endif

    {{-- Current page / total (compact on mobile) --}}
    <span class="px-3 py-1.5 text-sm text-gray-500">
        {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
    </span>

    {{-- Show nearby pages only on desktop --}}
    <div class="hidden sm:flex items-center gap-1">
        @foreach($elements as $element)
            @if(is_string($element))
                <span class="px-2 py-1.5 text-sm text-gray-600">{{ $element }}</span>
            @endif
            @if(is_array($element))
                @foreach($element as $page => $url)
                    @if($page == $paginator->currentPage())
                        <span class="px-3 py-1.5 text-sm font-semibold bg-red-600 text-white rounded-lg">{{ $page }}</span>
                    @elseif(abs($page - $paginator->currentPage()) <= 1 || $page == 1 || $page == $paginator->lastPage())
                        <a href="{{ $url }}" class="px-3 py-1.5 text-sm text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-lg transition">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>

    {{-- Next --}}
    @if($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-lg transition">→</a>
    @else
        <span class="px-3 py-1.5 text-sm text-gray-700 rounded-lg cursor-not-allowed">→</span>
    @endif
</nav>
@endif
