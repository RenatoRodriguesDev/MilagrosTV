@extends('layouts.admin')
@section('title', 'Filmes')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <form method="GET" class="flex items-center gap-2 flex-1">
        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Pesquisar filmes..."
            class="flex-1 bg-gray-800 border border-white/10 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500">
        @if($search ?? false)
        <a href="{{ route('admin.movies.index') }}" class="text-gray-500 hover:text-white text-sm">✕</a>
        @endif
    </form>
    <a href="{{ route('admin.movies.create') }}" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm font-semibold transition flex-shrink-0">
        + Filme
    </a>
</div>

@if($movies->isEmpty())
<div class="bg-gray-800/40 border border-white/[.08] rounded-2xl text-center py-16 text-gray-600">
    <p class="text-4xl mb-3">🎬</p>
    <p>Nenhum filme adicionado ainda.</p>
    <a href="{{ route('admin.movies.create') }}" class="text-red-400 hover:text-red-300 text-sm mt-2 inline-block">Adicionar o primeiro →</a>
</div>
@else

{{-- Mobile: cards --}}
<div class="md:hidden space-y-2">
    @foreach($movies as $movie)
    <div class="bg-gray-800/40 border border-white/[.08] rounded-xl px-4 py-3 flex items-center gap-3">
        <div class="flex-shrink-0 rounded overflow-hidden" style="width:32px;height:46px">
            @if($movie->poster_url)
                <img src="{{ $movie->poster_url }}" style="width:32px;height:46px;object-fit:cover">
            @else
                <div class="w-full h-full bg-gray-700 flex items-center justify-center text-xs">🎥</div>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-medium text-sm truncate">{{ $movie->title }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $movie->year }}{{ $movie->rating ? ' · ★ '.$movie->rating : '' }}</p>
        </div>
        <div class="flex items-center gap-1.5 flex-shrink-0">
            <a href="{{ route('admin.movies.edit', $movie) }}" class="text-xs px-2.5 py-1.5 rounded-lg bg-blue-600/20 text-blue-400">Editar</a>
            <form method="POST" action="{{ route('admin.movies.destroy', $movie) }}"
                onsubmit="return confirm('Remover {{ addslashes($movie->title) }}?')">
                @csrf @method('DELETE')
                <button class="text-xs px-2.5 py-1.5 rounded-lg bg-red-900/20 text-red-400">✕</button>
            </form>
        </div>
    </div>
    @endforeach
    <p class="text-xs text-gray-600 px-1 pt-1">{{ $movies->count() }} filme(s)</p>
</div>

{{-- Desktop: tabela --}}
<div class="hidden md:block bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/[.08] text-gray-500 text-xs font-semibold uppercase tracking-wide">
                <th class="text-left px-6 py-3 w-12"></th>
                <th class="text-left px-6 py-3">Título</th>
                <th class="text-left px-6 py-3">Ano</th>
                <th class="text-left px-6 py-3 hidden lg:table-cell">Géneros</th>
                <th class="text-left px-6 py-3">Nota</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/[.05]">
            @foreach($movies as $movie)
            <tr class="hover:bg-white/[.02] group">
                <td class="px-6 py-3">
                    <div class="flex-shrink-0 rounded overflow-hidden" style="width:28px;height:40px">
                        @if($movie->poster_url)
                            <img src="{{ $movie->poster_url }}" style="width:28px;height:40px;object-fit:cover">
                        @else
                            <div class="w-full h-full bg-gray-700 flex items-center justify-center text-xs">🎥</div>
                        @endif
                    </div>
                </td>
                <td class="px-6 py-3 font-medium max-w-[200px] truncate">{{ $movie->title }}</td>
                <td class="px-6 py-3 text-gray-500">{{ $movie->year }}</td>
                <td class="px-6 py-3 text-gray-500 hidden lg:table-cell text-xs">{{ implode(', ', array_slice($movie->localGenres(), 0, 2)) }}</td>
                <td class="px-6 py-3 text-yellow-400">{{ $movie->rating ? '★ '.$movie->rating : '—' }}</td>
                <td class="px-6 py-3">
                    <div class="flex items-center gap-2 justify-end opacity-0 group-hover:opacity-100 transition">
                        <a href="{{ route('catalog.movie', $movie) }}" target="_blank" class="text-xs px-2.5 py-1.5 rounded-lg bg-white/5 text-gray-400 hover:text-white transition">Ver</a>
                        <a href="{{ route('admin.movies.edit', $movie) }}" class="text-xs px-2.5 py-1.5 rounded-lg bg-blue-600/20 text-blue-400 hover:bg-blue-600/30 transition">Editar</a>
                        <form method="POST" action="{{ route('admin.movies.destroy', $movie) }}"
                            onsubmit="return confirm('Remover {{ addslashes($movie->title) }}?')" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-xs px-2.5 py-1.5 rounded-lg bg-red-900/20 text-red-400 hover:bg-red-900/40 transition">Remover</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-3 border-t border-white/[.05] text-xs text-gray-600">{{ $movies->count() }} filme(s)</div>
</div>
@endif

@endsection

@push('scripts')
<script>
const si = document.querySelector('input[name="search"]');
if (si) si.addEventListener('input', function() {
    clearTimeout(this._t);
    this._t = setTimeout(() => this.form.submit(), 400);
});
</script>
@endpush
