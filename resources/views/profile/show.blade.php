@extends('layouts.app')
@section('title', $user->name . ' — Perfil')

@section('content')
<div class="max-w-3xl mx-auto px-6 pt-28 pb-20">

    {{-- Header --}}
    <div class="flex items-center gap-5 mb-10">
        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center text-3xl font-black text-white flex-shrink-0">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h1 class="text-2xl font-black text-white">{{ $user->name }}</h1>
            <p class="text-gray-500 text-sm mt-0.5">{{ $user->email }}</p>
            @if($user->is_admin)
            <span class="text-xs bg-red-600/20 text-red-400 px-2 py-0.5 rounded-full mt-1 inline-block">Admin</span>
            @endif
            <p class="text-xs text-gray-600 mt-1">Membro desde {{ $user->created_at->format('M Y') }}</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-10">
        @php
        $statCards = [
            ['label' => 'Ep. iniciados',   'value' => $stats['episodes_started'],   'color' => 'text-white'],
            ['label' => 'Ep. completos',   'value' => $stats['episodes_completed'], 'color' => 'text-green-400'],
            ['label' => 'Horas vistas',    'value' => $stats['hours_watched'].'h',  'color' => 'text-blue-400'],
            ['label' => 'Filmes vistos',   'value' => $stats['movies_watched'],     'color' => 'text-purple-400'],
            ['label' => 'Séries vistas',   'value' => $stats['series_watched'],     'color' => 'text-orange-400'],
        ];
        @endphp
        @foreach($statCards as $card)
        <div class="bg-gray-800/60 border border-white/[.08] rounded-2xl p-4 text-center">
            <p class="text-2xl font-bold {{ $card['color'] }}">{{ $card['value'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Actividade recente --}}
    @if($recentProgress->isNotEmpty())
    <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-white/[.08]">
            <h2 class="font-semibold text-sm">Visto recentemente</h2>
        </div>
        <div class="divide-y divide-white/[.05]">
            @foreach($recentProgress as $prog)
            @php $ep = $prog->episode; $serie = $ep?->serie; @endphp
            <a href="{{ $serie ? route('catalog.serie', $serie) : '#' }}" class="flex items-center gap-3 px-5 py-3 hover:bg-white/[.02] transition">
                @if($serie?->poster_url)
                    <img src="{{ $serie->poster_url }}" style="width:28px;height:40px;object-fit:cover;border-radius:4px;flex-shrink:0">
                @else
                    <div style="width:28px;height:40px;background:#374151;border-radius:4px;flex-shrink:0"></div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ $serie?->localTitle() ?? '—' }}</p>
                    <p class="text-xs text-gray-500">T{{ $ep?->season }}E{{ $ep?->episode }}{{ $ep?->title ? ' · '.$ep->title : '' }}</p>
                </div>
                <div class="flex-shrink-0 text-right">
                    @if($prog->completed)
                        <span class="text-xs text-green-500">✓</span>
                    @elseif($prog->duration > 0)
                        <div class="w-16 h-1 bg-white/10 rounded-full overflow-hidden">
                            <div class="h-full bg-red-500 rounded-full" style="width:{{ $prog->percent }}%"></div>
                        </div>
                    @endif
                    <p class="text-[10px] text-gray-600 mt-1">{{ $prog->updated_at->diffForHumans() }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Edit profile --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        {{-- Name & Email --}}
        <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl p-6">
            <h2 class="font-semibold text-sm mb-4">Editar perfil</h2>

            @if(session('success'))
            <div class="mb-4 text-xs text-green-400 bg-green-900/30 border border-green-700/30 rounded-lg px-3 py-2">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-3">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Nome</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 transition">
                    @error('name')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 transition">
                    @error('email')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-lg text-sm transition mt-2">
                    Guardar
                </button>
            </form>
        </div>

        {{-- Change password --}}
        <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl p-6">
            <h2 class="font-semibold text-sm mb-4">Alterar password</h2>
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-3">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Password actual</label>
                    <input type="password" name="current_password" required
                        class="w-full bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 transition">
                    @error('current_password')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Nova password</label>
                    <input type="password" name="password" required
                        class="w-full bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 transition">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1.5">Confirmar password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full bg-white/5 border border-white/10 text-white rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-red-500 transition">
                </div>
                <button type="submit" class="w-full bg-gray-700 hover:bg-gray-600 text-white font-semibold py-2.5 rounded-lg text-sm transition mt-2">
                    Alterar password
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
