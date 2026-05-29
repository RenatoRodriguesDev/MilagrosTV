@extends('layouts.admin')
@section('title', 'Logs de Erros')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    @php
    $levelConfig = [
        'error'   => ['label' => 'Erros',    'color' => 'red'],
        'warning' => ['label' => 'Avisos',   'color' => 'yellow'],
        'info'    => ['label' => 'Info',     'color' => 'blue'],
        'debug'   => ['label' => 'Debug',    'color' => 'gray'],
    ];
    $colorMap = ['red' => 'text-red-400 bg-red-500/10', 'yellow' => 'text-yellow-400 bg-yellow-500/10', 'blue' => 'text-blue-400 bg-blue-500/10', 'gray' => 'text-gray-400 bg-white/5'];
    @endphp
    @foreach($levelConfig as $key => $cfg)
    <a href="{{ route('admin.logs.index', ['level' => $key]) }}"
        class="bg-gray-800/60 border rounded-xl p-4 transition {{ $level === $key ? 'border-white/20' : 'border-white/[.08] hover:border-white/15' }}">
        <p class="text-xs text-gray-500 mb-1">{{ $cfg['label'] }}</p>
        <p class="text-2xl font-bold {{ $colorMap[$cfg['color']] }} px-0">{{ $stats[$key] ?? 0 }}</p>
    </a>
    @endforeach
</div>

{{-- Filters + actions --}}
<div class="flex flex-wrap items-center gap-3 mb-4">
    <form method="GET" class="flex gap-2 flex-1 min-w-0">
        <input type="hidden" name="level" value="{{ $level }}">
        <input type="text" name="search" value="{{ $search }}" placeholder="Filtrar mensagens..."
            class="flex-1 bg-gray-800 border border-white/10 text-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500 min-w-0">
        <button type="submit" class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-2 rounded-lg text-sm transition flex-shrink-0">Filtrar</button>
    </form>
    <div class="flex gap-2 flex-shrink-0">
        @foreach(['all' => 'Todos', 'error' => 'Erros', 'warning' => 'Avisos', 'info' => 'Info'] as $val => $lbl)
        <a href="{{ route('admin.logs.index', ['level' => $val, 'search' => $search]) }}"
            class="text-xs px-3 py-1.5 rounded-lg transition {{ $level === $val ? 'bg-red-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10' }}">
            {{ $lbl }}
        </a>
        @endforeach
    </div>
    <form method="POST" action="{{ route('admin.logs.clear') }}" onsubmit="return confirm('Limpar todo o log?')">
        @csrf
        <button type="submit" class="text-xs px-3 py-1.5 rounded-lg bg-red-900/20 text-red-400 hover:bg-red-900/40 transition flex-shrink-0">
            Limpar log
        </button>
    </form>
</div>

@if(isset($stats['size']))
<p class="text-xs text-gray-600 mb-4">Tamanho do ficheiro: {{ round($stats['size'] / 1024, 1) }} KB · A mostrar as últimas {{ count($lines) }} entradas</p>
@endif

{{-- Log entries --}}
@if(empty($lines))
    <div class="bg-gray-800/40 border border-white/[.08] rounded-2xl text-center py-16 text-gray-600">
        <p class="text-3xl mb-3">✓</p>
        <p>Nenhuma entrada encontrada.</p>
    </div>
@else
<div class="space-y-1.5">
    @foreach($lines as $entry)
    @php
    $colors = [
        'error'     => 'border-red-800/50 bg-red-950/20',
        'warning'   => 'border-yellow-800/50 bg-yellow-950/20',
        'critical'  => 'border-red-600/60 bg-red-900/30',
        'emergency' => 'border-red-600/60 bg-red-900/30',
        'info'      => 'border-blue-800/30 bg-blue-950/10',
        'debug'     => 'border-white/5 bg-white/[.02]',
    ];
    $badges = [
        'error'     => 'bg-red-600/20 text-red-400',
        'warning'   => 'bg-yellow-600/20 text-yellow-400',
        'critical'  => 'bg-red-600/30 text-red-300',
        'emergency' => 'bg-red-600/30 text-red-300',
        'info'      => 'bg-blue-600/20 text-blue-400',
        'debug'     => 'bg-white/5 text-gray-500',
    ];
    $color = $colors[$entry['level']] ?? 'border-white/5 bg-white/[.02]';
    $badge = $badges[$entry['level']] ?? 'bg-white/5 text-gray-500';
    @endphp
    <details class="border {{ $color }} rounded-xl group">
        <summary class="flex items-start gap-3 px-4 py-3 cursor-pointer list-none">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full flex-shrink-0 mt-0.5 {{ $badge }}">{{ strtoupper($entry['level']) }}</span>
            <span class="text-gray-400 text-xs flex-shrink-0 mt-0.5 hidden sm:block">{{ $entry['date'] }}</span>
            <p class="text-sm text-gray-200 truncate flex-1 min-w-0 font-mono">{{ Str::limit(explode("\n", $entry['message'])[0], 120) }}</p>
            <svg class="w-4 h-4 text-gray-600 flex-shrink-0 mt-0.5 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="px-4 pb-4 pt-1">
            <p class="text-xs text-gray-500 mb-2 sm:hidden">{{ $entry['date'] }}</p>
            <pre class="text-xs text-gray-300 font-mono whitespace-pre-wrap break-all bg-black/30 rounded-lg p-3 max-h-64 overflow-y-auto">{{ $entry['full'] }}</pre>
        </div>
    </details>
    @endforeach
</div>
@endif

@endsection
