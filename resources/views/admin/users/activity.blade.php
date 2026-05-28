@extends('layouts.admin')
@section('title', 'Actividade — ' . $user->name)

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-white flex-shrink-0">←</a>
    <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold flex-shrink-0 {{ $user->is_admin ? 'bg-red-600' : 'bg-gray-700' }}">
        {{ strtoupper(substr($user->name, 0, 1)) }}
    </div>
    <div class="min-w-0">
        <h1 class="font-bold truncate">{{ $user->name }}</h1>
        <p class="text-xs text-gray-500">{{ $user->email }}</p>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4 text-center">
        <p class="text-2xl font-bold">{{ $progress->count() }}</p>
        <p class="text-xs text-gray-500 mt-1">Episódios</p>
    </div>
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4 text-center">
        <p class="text-2xl font-bold">{{ $progress->where('completed', true)->count() }}</p>
        <p class="text-xs text-gray-500 mt-1">Completos</p>
    </div>
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4 text-center">
        <p class="text-2xl font-bold">{{ $progress->where('completed', false)->where('position', '>', 30)->count() }}</p>
        <p class="text-xs text-gray-500 mt-1">Em progresso</p>
    </div>
</div>

<div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
    @if($progress->isEmpty())
        <p class="text-center text-gray-600 py-16 text-sm">Nenhuma actividade ainda.</p>
    @else

    {{-- Mobile: cards --}}
    <div class="md:hidden divide-y divide-white/[.05]">
        @foreach($progress as $prog)
        @php $ep = $prog->episode; $serie = $ep?->serie; @endphp
        <div class="flex items-center gap-3 px-4 py-3">
            @if($serie?->poster_url)
                <img src="{{ $serie->poster_url }}" style="width:28px;height:40px;object-fit:cover;border-radius:4px;flex-shrink:0">
            @else
                <div style="width:28px;height:40px;background:#374151;border-radius:4px;flex-shrink:0"></div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ $serie?->title ?? '—' }}</p>
                <p class="text-xs text-gray-500">T{{ $ep?->season }}E{{ $ep?->episode }}{{ $ep?->title ? ' · '.$ep->title : '' }}</p>
                <p class="text-[10px] text-gray-600 mt-0.5">{{ $prog->updated_at->diffForHumans() }}</p>
            </div>
            <div class="flex-shrink-0 text-right">
                @if($prog->completed)
                    <span class="text-xs text-green-500">✓ Completo</span>
                @else
                    <span class="text-xs text-gray-400">{{ gmdate('i:s', $prog->position) }}</span>
                    @if($prog->duration > 0)
                    <div class="w-16 h-1 bg-white/10 rounded-full overflow-hidden mt-1">
                        <div class="h-full bg-red-500 rounded-full" style="width: {{ $prog->percent }}%"></div>
                    </div>
                    @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Desktop: tabela --}}
    <table class="hidden md:table w-full text-sm">
        <thead>
            <tr class="border-b border-white/[.08] text-gray-500 text-xs font-semibold uppercase tracking-wide">
                <th class="text-left px-6 py-3 w-12"></th>
                <th class="text-left px-6 py-3">Série</th>
                <th class="text-left px-6 py-3">Episódio</th>
                <th class="text-left px-6 py-3">Progresso</th>
                <th class="text-left px-6 py-3">Última vez</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/[.05]">
            @foreach($progress as $prog)
            @php $ep = $prog->episode; $serie = $ep?->serie; @endphp
            <tr class="hover:bg-white/[.02]">
                <td class="px-6 py-3">
                    @if($serie?->poster_url)
                        <img src="{{ $serie->poster_url }}" style="width:24px;height:34px;object-fit:cover;border-radius:3px">
                    @endif
                </td>
                <td class="px-6 py-3 font-medium">{{ $serie?->title ?? '—' }}</td>
                <td class="px-6 py-3 text-gray-400">
                    T{{ $ep?->season }}E{{ $ep?->episode }}
                    @if($ep?->title)<span class="text-gray-600 text-xs ml-1">{{ Str::limit($ep->title, 30) }}</span>@endif
                </td>
                <td class="px-6 py-3">
                    @if($prog->completed)
                        <span class="text-xs bg-green-600/20 text-green-400 px-2 py-0.5 rounded-full">Completo</span>
                    @else
                        <div class="flex items-center gap-2">
                            <div class="w-24 h-1.5 bg-white/10 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500 rounded-full" style="width: {{ $prog->percent }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500">{{ gmdate('i:s', $prog->position) }}</span>
                        </div>
                    @endif
                </td>
                <td class="px-6 py-3 text-gray-500 text-xs">{{ $prog->updated_at->diffForHumans() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
