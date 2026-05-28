@extends('layouts.admin')
@section('title', 'Utilizadores')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <form method="GET" class="flex items-center gap-2 flex-1">
        <input type="text" name="search" value="{{ $search }}" placeholder="Pesquisar..."
            class="flex-1 bg-gray-800 border border-white/10 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500">
        @if($search)
        <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-white text-sm">✕</a>
        @endif
    </form>
</div>

@if($users->isEmpty())
<div class="bg-gray-800/40 border border-white/[.08] rounded-2xl text-center py-16 text-gray-600">
    <p class="text-4xl mb-3">👤</p>
    <p>Nenhum utilizador encontrado.</p>
</div>
@else

{{-- Mobile: cards --}}
<div class="md:hidden space-y-2">
    @foreach($users as $user)
    <div class="bg-gray-800/40 border border-white/[.08] rounded-xl px-4 py-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0
                {{ $user->is_admin ? 'bg-red-600' : 'bg-gray-700' }}">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="font-medium text-sm truncate">{{ $user->name }}</p>
                    @if($user->is_admin)
                        <span class="bg-red-600/20 text-red-400 text-[10px] px-1.5 py-0.5 rounded-full font-semibold flex-shrink-0">Admin</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                <p class="text-xs text-gray-600 mt-0.5">{{ $user->watch_progress_count }} ep. vistos · {{ $user->created_at->format('d/m/Y') }}</p>
            </div>
        </div>
        @if($user->id !== auth()->id())
        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-white/[.05]">
            <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}" class="flex-1">
                @csrf
                <button type="submit" class="w-full text-xs py-1.5 rounded-lg border transition
                    {{ $user->is_admin ? 'border-red-700/50 text-red-400' : 'border-white/10 text-gray-400' }}">
                    {{ $user->is_admin ? 'Remover admin' : 'Tornar admin' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                onsubmit="return confirm('Eliminar {{ addslashes($user->name) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-900/40 text-red-500">
                    Eliminar
                </button>
            </form>
        </div>
        @else
        <p class="text-xs text-gray-600 mt-2 text-center">(a tua conta)</p>
        @endif
    </div>
    @endforeach
    <p class="text-xs text-gray-600 px-1 pt-1">{{ $users->count() }} utilizador(es)</p>
</div>

{{-- Desktop: tabela --}}
<div class="hidden md:block bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
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
                    @if($user->id !== auth()->id())
                    <div class="flex items-center gap-2 justify-end">
                        <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}">
                            @csrf
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border transition
                                {{ $user->is_admin ? 'border-red-700/50 text-red-400 hover:bg-red-900/20' : 'border-white/10 text-gray-400 hover:bg-white/5 hover:text-white' }}">
                                {{ $user->is_admin ? 'Remover admin' : 'Tornar admin' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                            onsubmit="return confirm('Eliminar {{ addslashes($user->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-red-900/40 text-red-500 hover:bg-red-900/20 transition">
                                Eliminar
                            </button>
                        </form>
                    </div>
                    @else
                    <span class="text-xs text-gray-600">(tu)</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-3 border-t border-white/[.05] text-xs text-gray-600">
        {{ $users->count() }} utilizador(es)
    </div>
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
