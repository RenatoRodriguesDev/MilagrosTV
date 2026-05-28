@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
    @php
    $statCards = [
        ['label' => 'Filmes',      'value' => $stats['movies'],         'icon' => 'M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z', 'color' => 'blue',   'route' => 'admin.movies.index'],
        ['label' => 'Séries',      'value' => $stats['series'],         'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'color' => 'purple', 'route' => 'admin.series.index'],
        ['label' => 'Episódios',   'value' => $stats['episodes'],       'icon' => 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'orange', 'route' => null],
        ['label' => 'Locais',      'value' => $stats['local_episodes'], 'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z', 'color' => 'green',  'route' => null],
        ['label' => 'Utilizadores','value' => $stats['users'],          'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'teal', 'route' => 'admin.users.index'],
        ['label' => 'Hoje',        'value' => $stats['views_today'],    'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', 'color' => 'red', 'route' => null],
    ];
    $colorMap = ['blue'=>'text-blue-400 bg-blue-500/15','purple'=>'text-purple-400 bg-purple-500/15','orange'=>'text-orange-400 bg-orange-500/15','green'=>'text-green-400 bg-green-500/15','teal'=>'text-teal-400 bg-teal-500/15','red'=>'text-red-400 bg-red-500/15'];
    @endphp

    @foreach($statCards as $card)
    <div class="bg-gray-800/60 border border-white/[.08] rounded-2xl p-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-[10px] text-gray-500 font-semibold uppercase tracking-wide">{{ $card['label'] }}</p>
            <div class="w-7 h-7 rounded-lg flex items-center justify-center {{ $colorMap[$card['color']] }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
            </div>
        </div>
        <p class="text-2xl font-bold">{{ $card['value'] }}</p>
        @if($card['route'])
        <a href="{{ route($card['route']) }}" class="text-[10px] text-gray-600 hover:text-white mt-0.5 inline-block">Ver todos →</a>
        @endif
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

    {{-- Chart: views per day --}}
    <div class="xl:col-span-2 bg-gray-800/40 border border-white/[.08] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-sm">Visualizações (últimos 14 dias)</h2>
        </div>
        <canvas id="viewsChart" height="100"></canvas>
    </div>

    {{-- Top episodes --}}
    <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-white/[.08]">
            <h2 class="font-semibold text-sm">Episódios mais vistos</h2>
        </div>
        @if($topEpisodes->isEmpty())
            <p class="text-center text-gray-600 py-8 text-sm">Sem dados.</p>
        @else
        <div class="divide-y divide-white/[.05]">
            @foreach($topEpisodes as $i => $prog)
            @php $ep = $prog->episode; @endphp
            <div class="flex items-center gap-3 px-5 py-3">
                <span class="text-gray-600 text-xs font-bold w-4 flex-shrink-0">{{ $i+1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm truncate">{{ $ep?->serie?->title ?? '—' }}</p>
                    <p class="text-xs text-gray-500">T{{ $ep?->season }}E{{ $ep?->episode }}</p>
                </div>
                <span class="text-xs text-gray-400 flex-shrink-0">{{ $prog->views }}×</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Recent content --}}
    <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[.08] flex items-center justify-between">
            <h2 class="font-semibold text-sm">Conteúdo recente</h2>
            <div class="flex gap-2 text-xs">
                <a href="{{ route('admin.movies.create') }}" class="text-red-400 hover:text-red-300">+ Filme</a>
                <a href="{{ route('admin.series.create') }}" class="text-red-400 hover:text-red-300 ml-2">+ Série</a>
            </div>
        </div>
        @if($recentMovies->isEmpty() && $recentSeries->isEmpty())
            <p class="text-center text-gray-600 py-10 text-sm">Sem conteúdo ainda.</p>
        @else
        <div class="divide-y divide-white/[.05]">
            @foreach($recentSeries->take(3) as $s)
            <div class="flex items-center gap-3 px-5 py-3">
                <img src="{{ $s->poster_url }}" style="width:24px;height:34px;object-fit:cover;border-radius:3px;flex-shrink:0" onerror="this.style.display='none'">
                <div class="flex-1 min-w-0">
                    <p class="text-sm truncate">{{ $s->title }}</p>
                    <p class="text-xs text-gray-500">Série · {{ $s->year }}</p>
                </div>
                <a href="{{ route('admin.series.edit', $s) }}" class="text-xs text-gray-500 hover:text-white">Editar</a>
            </div>
            @endforeach
            @foreach($recentMovies->take(3) as $m)
            <div class="flex items-center gap-3 px-5 py-3">
                <img src="{{ $m->poster_url }}" style="width:24px;height:34px;object-fit:cover;border-radius:3px;flex-shrink:0" onerror="this.style.display='none'">
                <div class="flex-1 min-w-0">
                    <p class="text-sm truncate">{{ $m->title }}</p>
                    <p class="text-xs text-gray-500">Filme · {{ $m->year }}</p>
                </div>
                <a href="{{ route('admin.movies.edit', $m) }}" class="text-xs text-gray-500 hover:text-white">Editar</a>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Top users + recent activity --}}
    <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[.08] flex items-center justify-between">
            <h2 class="font-semibold text-sm">Utilizadores mais activos</h2>
            <a href="{{ route('admin.users.index') }}" class="text-xs text-gray-500 hover:text-white">Ver todos →</a>
        </div>
        <div class="divide-y divide-white/[.05]">
            @foreach($topUsers as $user)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 {{ $user->is_admin ? 'bg-red-600' : 'bg-gray-700' }}">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm truncate">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->watch_progress_count }} ep. vistos</p>
                </div>
                <a href="{{ route('admin.users.activity', $user) }}" class="text-xs text-gray-500 hover:text-white flex-shrink-0">Actividade</a>
            </div>
            @endforeach
        </div>

        {{-- File detection shortcut --}}
        <div class="px-5 py-4 border-t border-white/[.08] bg-white/[.01]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium">Ficheiros novos</p>
                    <p class="text-xs text-gray-500 mt-0.5">Detectar vídeos não ligados</p>
                </div>
                <a href="#" onclick="event.preventDefault(); loadUnlinkedFiles()" class="text-xs bg-blue-600/20 text-blue-400 hover:bg-blue-600/30 px-3 py-1.5 rounded-lg transition">
                    Detectar
                </a>
            </div>
            <div id="unlinked-results" class="hidden mt-3 max-h-48 overflow-y-auto space-y-1"></div>
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="lg:col-span-2 bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[.08]">
            <h2 class="font-semibold text-sm">Actividade recente</h2>
        </div>
        @if($recentProgress->isEmpty())
            <p class="text-center text-gray-600 py-10 text-sm">Sem actividade ainda.</p>
        @else
        <div class="divide-y divide-white/[.05]">
            @foreach($recentProgress as $prog)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="w-7 h-7 rounded-full bg-red-600/20 flex items-center justify-center text-xs font-bold text-red-400 flex-shrink-0">
                    {{ strtoupper(substr($prog->user->name ?? '?', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm truncate">
                        <span class="font-medium">{{ $prog->user->name ?? '—' }}</span>
                        <span class="text-gray-500"> · </span>
                        <span class="text-gray-300">{{ $prog->episode?->serie?->title ?? '—' }}</span>
                        <span class="text-gray-500"> T{{ $prog->episode?->season }}E{{ $prog->episode?->episode }}</span>
                    </p>
                    <p class="text-xs text-gray-600">{{ $prog->updated_at->diffForHumans() }}</p>
                </div>
                <span class="text-xs flex-shrink-0 {{ $prog->completed ? 'text-green-500' : 'text-gray-600' }}">
                    {{ $prog->completed ? '✓' : gmdate('i:s', $prog->position) }}
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
// Views chart
const ctx   = document.getElementById('viewsChart').getContext('2d');
const days  = @json(array_keys($days->toArray()));
const views = @json(array_values($days->toArray()));

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: days.map(d => { const [,m,day] = d.split('-'); return `${day}/${m}`; }),
        datasets: [{
            label: 'Visualizações',
            data: views,
            backgroundColor: 'rgba(220,38,38,0.5)',
            borderColor: 'rgba(220,38,38,0.8)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9ca3af', font: { size: 11 } } },
            y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9ca3af', font: { size: 11 }, stepSize: 1 }, beginAtZero: true },
        }
    }
});

// File detection
async function loadUnlinkedFiles() {
    const box = document.getElementById('unlinked-results');
    box.classList.remove('hidden');
    box.innerHTML = '<p class="text-xs text-gray-500">A detectar...</p>';
    const res  = await fetch('{{ route("admin.files.scan") }}');
    const data = await res.json();
    if (data.error) { box.innerHTML = `<p class="text-xs text-red-400">${data.error}</p>`; return; }
    if (!data.length) { box.innerHTML = '<p class="text-xs text-gray-500">Nenhum ficheiro novo encontrado.</p>'; return; }
    box.innerHTML = data.slice(0, 10).map(f => `
        <div class="flex items-center gap-2 bg-white/5 rounded-lg px-3 py-2">
            <div class="flex-1 min-w-0">
                <p class="text-xs text-white truncate">${f.filename}</p>
                <p class="text-[10px] text-gray-500">${f.folder} · ${f.size}${f.season ? ` · T${f.season}E${f.episode}` : ''}</p>
            </div>
        </div>`).join('');
    if (data.length > 10) box.innerHTML += `<p class="text-[10px] text-gray-600 px-1">+ ${data.length-10} mais...</p>`;
}
</script>
@endpush
