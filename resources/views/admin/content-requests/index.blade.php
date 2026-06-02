@extends('layouts.admin')
@section('title', 'Pedidos de conteúdo')
@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-lg font-bold">📬 Pedidos de conteúdo</h1>
        @if($pendingCount > 0)
        <p class="text-sm text-yellow-400 mt-0.5">{{ $pendingCount }} pedido(s) pendente(s)</p>
        @endif
    </div>
</div>

@if(session('success'))
<div class="bg-green-900/30 border border-green-700/40 text-green-400 rounded-xl px-4 py-3 mb-4 text-sm">
    {{ session('success') }}
</div>
@endif

@if($requests->isEmpty())
<div class="text-center py-20 text-gray-600">
    <p class="text-4xl mb-3">📭</p>
    <p>Sem pedidos ainda.</p>
</div>
@else
<div class="space-y-3">
    @foreach($requests as $req)
    <div class="flex items-center gap-4 bg-gray-800/60 border {{ $req->isPending() ? 'border-yellow-600/30' : 'border-white/[.06]' }} rounded-xl px-4 py-3">

        {{-- Poster --}}
        <div class="flex-shrink-0">
            @if($req->poster_url)
            <img src="{{ $req->poster_url }}" alt="" class="w-12 h-16 object-cover rounded-lg">
            @else
            <div class="w-12 h-16 bg-gray-700 rounded-lg flex items-center justify-center text-gray-500 text-xl">🎬</div>
            @endif
        </div>

        {{-- Info --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <p class="font-semibold text-sm text-white truncate">{{ $req->title }}</p>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold {{ $req->type === 'movie' ? 'bg-blue-900/40 text-blue-400' : 'bg-purple-900/40 text-purple-400' }}">
                    {{ $req->type === 'movie' ? 'Filme' : 'Série' }}
                </span>
                @if($req->year)
                <span class="text-xs text-gray-500">{{ $req->year }}</span>
                @endif
            </div>
            @if($req->original_title && $req->original_title !== $req->title)
            <p class="text-xs text-gray-500 truncate">{{ $req->original_title }}</p>
            @endif
            <p class="text-xs text-gray-600 mt-1">
                Pedido por <span class="text-gray-400">{{ $req->user->name }}</span>
                · {{ $req->created_at->diffForHumans() }}
            </p>
        </div>

        {{-- Status & actions --}}
        <div class="flex-shrink-0 flex items-center gap-2">
            @if($req->isPending())
            <form method="POST" action="{{ route('admin.content-requests.import', $req) }}">
                @csrf
                <button type="submit"
                    class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg font-semibold transition">
                    ↓ Importar
                </button>
            </form>
            <form method="POST" action="{{ route('admin.content-requests.reject', $req) }}">
                @csrf
                <button type="submit"
                    class="text-xs bg-red-900/40 hover:bg-red-900/60 text-red-400 px-3 py-1.5 rounded-lg font-semibold transition">
                    Rejeitar
                </button>
            </form>
            @elseif($req->isImported())
            <span class="text-xs text-green-500 font-semibold">✓ Importado</span>
            @else
            <span class="text-xs text-red-400 font-semibold">✗ Rejeitado</span>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
