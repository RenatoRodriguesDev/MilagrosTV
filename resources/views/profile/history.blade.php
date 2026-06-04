@extends('layouts.app')
@section('title', __('history.title'))

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 pt-28 pb-20">

    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('profile.show') }}" class="text-gray-400 hover:text-white transition text-sm">{{ __('history.back') }}</a>
        <h1 class="text-2xl font-black text-white">{{ __('history.title') }}</h1>
        <span class="bg-white/10 text-gray-400 text-sm px-2.5 py-0.5 rounded-full">{{ $history->total() }}</span>
    </div>

    @if($history->isEmpty())
    <div class="text-center py-20 text-gray-600">
        <p class="text-4xl mb-3">📺</p>
        <p>{{ __('history.empty') }}</p>
    </div>
    @else

    {{-- Group by date --}}
    @php
    $grouped = $history->getCollection()->groupBy(fn($p) => $p['updated_at']->format('Y-m-d'));
    @endphp

    @foreach($grouped as $date => $items)
    <div class="mb-6">
        <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-3">
            {{ \Carbon\Carbon::parse($date)->isToday() ? __('history.today') : (\Carbon\Carbon::parse($date)->isYesterday() ? __('history.yesterday') : \Carbon\Carbon::parse($date)->translatedFormat('d \d\e F')) }}
        </p>
        <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden divide-y divide-white/[.05]">
            @foreach($items as $item)
            @if(!$item['title']) @continue @endif
            <a href="{{ $item['link'] ?? '#' }}" class="flex items-center gap-3 px-4 py-3 hover:bg-white/[.02] transition">
                <div class="flex-shrink-0 rounded overflow-hidden" style="width:32px;height:46px">
                    @if($item['poster'])
                        <img src="{{ $item['poster'] }}" style="width:32px;height:46px;object-fit:cover">
                    @else
                        <div class="w-full h-full bg-gray-700 flex items-center justify-center text-sm">
                            {{ $item['type'] === 'movie' ? '🎬' : '📺' }}
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate flex items-center gap-1.5">
                        {{ $item['title'] }}
                        <span class="text-[10px] {{ $item['type'] === 'movie' ? 'text-blue-400' : 'text-purple-400' }} font-normal flex-shrink-0">
                            {{ $item['type'] === 'movie' ? __('common.movie') : __('common.serie') }}
                        </span>
                    </p>
                    @if($item['subtitle'])
                    <p class="text-xs text-gray-500">{{ $item['subtitle'] }}</p>
                    @endif
                </div>
                <div class="flex-shrink-0 flex flex-col items-end gap-1">
                    @if($item['completed'])
                        <span class="text-xs bg-green-600/20 text-green-400 px-2 py-0.5 rounded-full">{{ __('history.completed') }}</span>
                    @elseif($item['duration'] > 0)
                        <div class="w-16 h-1 bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full bg-red-500 rounded-full" style="width:{{ $item['percent'] }}%"></div>
                        </div>
                        <span class="text-[10px] text-gray-500">{{ gmdate($item['position'] >= 3600 ? 'H:i:s' : 'i:s', $item['position']) }}</span>
                    @endif
                    <time class="text-[10px] text-gray-600 local-time" datetime="{{ $item['updated_at']->toIso8601String() }}"></time>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endforeach

    @if($history->hasPages())
    <div class="flex justify-center mt-6">{{ $history->links('catalog._pagination') }}</div>
    @endif

    @endif
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('time.local-time').forEach(el => {
    const dt = new Date(el.getAttribute('datetime'));
    el.textContent = dt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
});
</script>
@endpush
