@extends('layouts.admin')
@section('title', 'Monitor do Servidor')

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8" id="stats-grid">
    {{-- CPU --}}
    <div class="bg-gray-800/60 border border-white/[.08] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">CPU Load</p>
            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
        </div>
        <p class="text-3xl font-bold" id="cpu-load">—</p>
        <p class="text-xs text-gray-500 mt-1">1 min · <span id="cpu-5">—</span> 5min · <span id="cpu-15">—</span> 15min</p>
    </div>

    {{-- RAM --}}
    <div class="bg-gray-800/60 border border-white/[.08] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Memória RAM</p>
            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="text-3xl font-bold" id="mem-used">—</p>
        <div class="mt-2 h-1.5 bg-white/10 rounded-full overflow-hidden">
            <div id="mem-bar" class="h-full bg-purple-500 rounded-full transition-all" style="width:0%"></div>
        </div>
        <p class="text-xs text-gray-500 mt-1"><span id="mem-pct">—</span>% · <span id="mem-total">—</span> total</p>
    </div>

    {{-- Disco --}}
    <div class="bg-gray-800/60 border border-white/[.08] rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Disco</p>
            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16M9 11h6"/></svg>
        </div>
        <p class="text-3xl font-bold" id="disk-free">—</p>
        <div class="mt-2 h-1.5 bg-white/10 rounded-full overflow-hidden">
            <div id="disk-bar" class="h-full bg-orange-500 rounded-full transition-all" style="width:0%"></div>
        </div>
        <p class="text-xs text-gray-500 mt-1"><span id="disk-pct">—</span>% usado · <span id="disk-total">—</span> total</p>
    </div>
</div>

<div class="bg-gray-800/40 border border-white/[.08] rounded-2xl p-5">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-sm">Histórico CPU (último minuto)</h2>
        <span id="refresh-indicator" class="text-xs text-gray-600">A actualizar...</span>
    </div>
    <canvas id="cpu-chart" height="60"></canvas>
</div>

@endsection

@push('scripts')
<script src="/js/chart.min.js"></script>
<script>
const fmt = b => b >= 1073741824 ? (b/1073741824).toFixed(1)+' GB' : b >= 1048576 ? (b/1048576).toFixed(0)+' MB' : (b/1024).toFixed(0)+' KB';

const cpuHistory = Array(30).fill(0);
const cpuChart   = new Chart(document.getElementById('cpu-chart').getContext('2d'), {
    type: 'line',
    data: {
        labels: cpuHistory.map(() => ''),
        datasets: [{
            data: cpuHistory,
            borderColor: 'rgba(96,165,250,0.8)',
            backgroundColor: 'rgba(96,165,250,0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 0,
        }]
    },
    options: {
        responsive: true,
        animation: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { display: false },
            y: { min: 0, grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9ca3af', font: { size: 10 } } }
        }
    }
});

async function refresh() {
    try {
        const r = await fetch('{{ route('admin.monitor.stats') }}');
        const d = await r.json();

        // CPU
        document.getElementById('cpu-load').textContent = d.cpu.load1;
        document.getElementById('cpu-5').textContent   = d.cpu.load5;
        document.getElementById('cpu-15').textContent  = d.cpu.load15;
        cpuHistory.shift(); cpuHistory.push(d.cpu.load1);
        cpuChart.data.datasets[0].data = [...cpuHistory];
        cpuChart.update();

        // RAM
        document.getElementById('mem-used').textContent  = fmt(d.memory.used);
        document.getElementById('mem-total').textContent = fmt(d.memory.total);
        document.getElementById('mem-pct').textContent   = d.memory.percent;
        document.getElementById('mem-bar').style.width   = d.memory.percent + '%';

        // Disk
        document.getElementById('disk-free').textContent  = fmt(d.disk.free) + ' livre';
        document.getElementById('disk-total').textContent = fmt(d.disk.total);
        document.getElementById('disk-pct').textContent   = d.disk.percent;
        document.getElementById('disk-bar').style.width   = d.disk.percent + '%';

        document.getElementById('refresh-indicator').textContent = 'Actualizado ' + new Date().toLocaleTimeString();
    } catch(e) {
        document.getElementById('refresh-indicator').textContent = 'Erro ao carregar stats';
    }
}

refresh();
setInterval(refresh, 5000);
</script>
@endpush
