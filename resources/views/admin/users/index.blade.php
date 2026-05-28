@extends('layouts.admin')
@section('title', 'Utilizadores')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-gray-500 text-sm mt-0.5">{{ $users->count() }} utilizador(es)</p>
    </div>
    {{-- Search --}}
    <form method="GET" class="flex items-center gap-2">
        <input type="text" name="search" value="{{ $search }}" placeholder="Pesquisar..."
            class="bg-gray-800 border border-white/10 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500 w-56">
        <button type="submit" class="bg-gray-800 border border-white/10 hover:bg-gray-700 text-gray-300 px-3 py-2 rounded-lg text-sm transition">
            Pesquisar
        </button>
        @if($search)
        <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-white text-sm">Limpar</a>
        @endif
    </form>
</div>

<div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
    @if($users->isEmpty())
        <p class="text-center text-gray-600 py-16 text-sm">Nenhum utilizador encontrado.</p>
    @else
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-white/[.08] text-gray-500 text-xs font-semibold uppercase tracking-wide">
                <th class="text-left px-6 py-3">Utilizador</th>
                <th class="text-left px-6 py-3">Email</th>
                <th class="text-left px-6 py-3">Função</th>
                <th class="text-left px-6 py-3">Visto</th>
                <th class="text-left px-6 py-3">Registado</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/[.05]">
            @foreach($users as $user)
            <tr class="hover:bg-white/[.02]">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0
                            {{ $user->is_admin ? 'bg-red-600' : 'bg-gray-700' }}">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <span class="font-medium">{{ $user->name }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-400">{{ $user->email }}</td>
                <td class="px-6 py-4">
                    @if($user->is_admin)
                        <span class="bg-red-600/20 text-red-400 text-xs px-2.5 py-1 rounded-full font-semibold">Admin</span>
                    @else
                        <span class="bg-white/5 text-gray-400 text-xs px-2.5 py-1 rounded-full">Utilizador</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-gray-400">{{ $user->watch_progress_count }} ep.</td>
                <td class="px-6 py-4 text-gray-500 text-xs">{{ $user->created_at->format('d/m/Y') }}</td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2 justify-end">
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}">
                            @csrf
                            <button type="submit"
                                class="text-xs px-3 py-1.5 rounded-lg border transition
                                {{ $user->is_admin
                                    ? 'border-red-700/50 text-red-400 hover:bg-red-900/20'
                                    : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white' }}">
                                {{ $user->is_admin ? 'Remover admin' : 'Tornar admin' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                            onsubmit="return confirm('Eliminar {{ addslashes($user->name) }}? Esta acção é irreversível.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-900/40 text-red-500 hover:bg-red-900/20 transition">
                                Eliminar
                            </button>
                        </form>
                        @else
                        <span class="text-xs text-gray-600">(tu)</span>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
