@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-gray-800/60 border border-white/[.08] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Filmes</p>
            <div class="w-8 h-8 bg-blue-500/15 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold">{{ $stats['movies'] }}</p>
        <a href="{{ route('admin.movies.index') }}" class="text-xs text-gray-500 hover:text-white mt-1 inline-block">Ver todos →</a>
    </div>
    <div class="bg-gray-800/60 border border-white/[.08] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Séries</p>
            <div class="w-8 h-8 bg-purple-500/15 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold">{{ $stats['series'] }}</p>
        <a href="{{ route('admin.series.index') }}" class="text-xs text-gray-500 hover:text-white mt-1 inline-block">Ver todas →</a>
    </div>
    <div class="bg-gray-800/60 border border-white/[.08] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Episódios</p>
            <div class="w-8 h-8 bg-orange-500/15 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold">{{ $stats['episodes'] }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $stats['local_episodes'] }} locais</p>
    </div>
    <div class="bg-gray-800/60 border border-white/[.08] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Utilizadores</p>
            <div class="w-8 h-8 bg-green-500/15 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
        </div>
        <p class="text-3xl font-bold">{{ $stats['users'] }}</p>
        <a href="{{ route('admin.users.index') }}" class="text-xs text-gray-500 hover:text-white mt-1 inline-block">Gerir →</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Filmes recentes --}}
    <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[.08] flex items-center justify-between">
            <h2 class="font-semibold text-sm">Filmes recentes</h2>
            <a href="{{ route('admin.movies.create') }}" class="text-xs text-red-400 hover:text-red-300">+ Adicionar</a>
        </div>
        @if($recentMovies->isEmpty())
            <p class="text-center text-gray-600 py-10 text-sm">Nenhum filme ainda.</p>
        @else
        <div class="divide-y divide-white/[.05]">
            @foreach($recentMovies as $movie)
            <div class="flex items-center gap-3 px-6 py-3 hover:bg-white/[.02]">
                @if($movie->poster_url)
                    <img src="{{ $movie->poster_url }}" style="width:28px;height:40px;object-fit:cover;border-radius:4px;flex-shrink:0">
                @else
                    <div class="w-8 h-11 bg-gray-700 rounded flex items-center justify-center text-xs flex-shrink-0">🎥</div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ $movie->title }}</p>
                    <p class="text-xs text-gray-500">{{ $movie->year }}{{ $movie->rating ? ' · ★ '.$movie->rating : '' }}</p>
                </div>
                <a href="{{ route('admin.movies.edit', $movie) }}" class="text-xs text-gray-500 hover:text-white flex-shrink-0">Editar</a>
            </div>
            @endforeach
        </div>
        <div class="px-6 py-3 border-t border-white/[.05]">
            <a href="{{ route('admin.movies.index') }}" class="text-xs text-gray-500 hover:text-white">Ver todos os filmes →</a>
        </div>
        @endif
    </div>

    {{-- Séries recentes --}}
    <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[.08] flex items-center justify-between">
            <h2 class="font-semibold text-sm">Séries recentes</h2>
            <a href="{{ route('admin.series.create') }}" class="text-xs text-red-400 hover:text-red-300">+ Adicionar</a>
        </div>
        @if($recentSeries->isEmpty())
            <p class="text-center text-gray-600 py-10 text-sm">Nenhuma série ainda.</p>
        @else
        <div class="divide-y divide-white/[.05]">
            @foreach($recentSeries as $serie)
            <div class="flex items-center gap-3 px-6 py-3 hover:bg-white/[.02]">
                @if($serie->poster_url)
                    <img src="{{ $serie->poster_url }}" style="width:28px;height:40px;object-fit:cover;border-radius:4px;flex-shrink:0">
                @else
                    <div class="w-8 h-11 bg-gray-700 rounded flex items-center justify-center text-xs flex-shrink-0">📺</div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ $serie->title }}</p>
                    <p class="text-xs text-gray-500">{{ $serie->year }} · {{ $serie->episodes->count() }} ep.</p>
                </div>
                <a href="{{ route('admin.series.edit', $serie) }}" class="text-xs text-gray-500 hover:text-white flex-shrink-0">Editar</a>
            </div>
            @endforeach
        </div>
        <div class="px-6 py-3 border-t border-white/[.05]">
            <a href="{{ route('admin.series.index') }}" class="text-xs text-gray-500 hover:text-white">Ver todas as séries →</a>
        </div>
        @endif
    </div>

    {{-- Actividade recente --}}
    <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[.08]">
            <h2 class="font-semibold text-sm">Actividade recente</h2>
        </div>
        @if($recentProgress->isEmpty())
            <p class="text-center text-gray-600 py-10 text-sm">Sem actividade ainda.</p>
        @else
        <div class="divide-y divide-white/[.05]">
            @foreach($recentProgress as $prog)
            <div class="flex items-center gap-3 px-6 py-3">
                <div class="w-7 h-7 rounded-full bg-red-600/20 flex items-center justify-center text-xs font-bold text-red-400 flex-shrink-0">
                    {{ strtoupper(substr($prog->user->name ?? '?', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm truncate">
                        <span class="font-medium">{{ $prog->user->name ?? 'Utilizador' }}</span>
                        <span class="text-gray-500"> viu </span>
                        <span class="text-gray-300">{{ $prog->episode->title ?: 'Ep. '.$prog->episode->episode }}</span>
                    </p>
                    <p class="text-xs text-gray-600">{{ $prog->updated_at->diffForHumans() }}</p>
                </div>
                @if($prog->completed)
                    <span class="text-xs text-green-500 flex-shrink-0">✓ Completo</span>
                @else
                    <span class="text-xs text-gray-600 flex-shrink-0">{{ gmdate('i:s', $prog->position) }}</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Utilizadores --}}
    <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[.08] flex items-center justify-between">
            <h2 class="font-semibold text-sm">Utilizadores</h2>
            <a href="{{ route('admin.users.index') }}" class="text-xs text-gray-500 hover:text-white">Ver todos →</a>
        </div>
        <div class="divide-y divide-white/[.05]">
            @foreach($users as $user)
            <div class="flex items-center gap-3 px-6 py-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($user->is_admin)
                        <span class="text-xs bg-red-600/20 text-red-400 px-2 py-0.5 rounded-full">Admin</span>
                    @endif
                    <span class="text-xs text-gray-600">{{ $user->watch_progress_count ?? 0 }} visto</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
