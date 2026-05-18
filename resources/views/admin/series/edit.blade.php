@extends('layouts.admin')

@section('title', 'Editar Série')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.series.index') }}" class="text-gray-400 hover:text-white">← Voltar</a>
    <h1 class="text-2xl font-bold">Editar: {{ $serie->title }}</h1>
</div>

@include('admin._form_item', [
    'action'       => route('admin.series.update', $serie),
    'searchRoute'  => route('admin.series.tmdb-search'),
    'detailsRoute' => route('admin.series.tmdb-details'),
    'item'         => $serie,
    'type'         => 'serie',
    'method'       => 'PUT',
])

{{-- Episódios --}}
<div class="mt-10">
    <h2 class="text-xl font-bold mb-4">🎬 Episódios</h2>

    @if(session('success'))
        <div class="bg-green-800 text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Scanner automático --}}
    <div class="bg-gray-800 rounded-lg p-4 mb-4">
        <h3 class="text-sm font-semibold text-gray-300 mb-3">📂 Detectar ficheiros automaticamente</h3>
        <div class="flex gap-2 mb-3">
            <input type="text" id="scan-folder" placeholder="Ex: From  ou  From/Season1"
                class="flex-1 bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-red-500">
            <button type="button" onclick="scanFolder()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-semibold transition">
                Detectar
            </button>
        </div>
        <p class="text-xs text-gray-500 mb-3">Nome da pasta dentro de <code class="text-gray-400">C:\Users\Renato\Downloads\Series\</code></p>

        <div id="scan-results" class="hidden">
            <div class="flex items-center justify-between mb-2">
                <p id="scan-count" class="text-sm text-gray-300"></p>
                <button type="button" onclick="importAll()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-semibold transition">
                    ✓ Importar todos
                </button>
            </div>
            <div id="scan-list" class="space-y-1 max-h-64 overflow-y-auto"></div>
        </div>
        <p id="scan-error" class="text-red-400 text-sm hidden"></p>
    </div>

    {{-- Adicionar episódio manual --}}
    <details class="bg-gray-800 rounded-lg mb-6">
        <summary class="px-4 py-3 text-sm font-semibold text-gray-400 cursor-pointer hover:text-white">+ Adicionar episódio manualmente</summary>
        <div class="px-4 pb-4">
        <form method="POST" action="{{ route('admin.series.episodes.store', $serie) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
            @csrf
            <div>
                <label class="block text-xs text-gray-400 mb-1">Temporada *</label>
                <input type="number" name="season" min="1" required value="{{ old('season', 1) }}"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Episódio *</label>
                <input type="number" name="episode" min="1" required value="{{ old('episode', 1) }}"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Título</label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="Opcional"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">Caminho do vídeo</label>
                <input type="text" name="video_path" value="{{ old('video_path') }}" placeholder="From/Season1/ep.mkv"
                    class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white text-sm focus:outline-none focus:border-red-500">
            </div>
            <div class="sm:col-span-4">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded text-sm font-semibold transition">
                    Guardar episódio
                </button>
            </div>
        </form>
        </div>
    </details>

    {{-- Lista de episódios --}}
    @php $grouped = $serie->episodes->groupBy('season'); @endphp
    @if($grouped->isEmpty())
        <p class="text-gray-500 text-sm">Ainda não há episódios.</p>
    @else
        @foreach($grouped as $season => $eps)
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-400 mb-2">Temporada {{ $season }}</h4>
            <div class="space-y-1">
                @foreach($eps as $ep)
                <div class="flex items-center gap-3 bg-gray-800 rounded px-4 py-2 text-sm">
                    <span class="text-red-500 font-bold w-14 flex-shrink-0">T{{ $ep->season }}E{{ $ep->episode }}</span>
                    <span class="flex-1 text-gray-200 truncate">{{ $ep->title ?: '—' }}</span>
                    <span class="text-gray-500 text-xs truncate max-w-xs">{{ $ep->video_path ?: 'sem vídeo' }}</span>
                    @if($ep->video_path)
                        <span class="text-xs {{ $ep->hasVideo() ? 'text-green-400' : 'text-red-400' }}">
                            {{ $ep->hasVideo() ? '✓' : '✗ ficheiro não encontrado' }}
                        </span>
                    @endif
                    <form method="POST" action="{{ route('admin.episodes.destroy', $ep) }}" onsubmit="return confirm('Remover?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-500 hover:text-red-400 text-xs transition">Remover</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    @endif
</div>
@endsection

@push('scripts')
<script>
const scanRoute   = '{{ route('admin.episodes.scan') }}';
const importRoute = '{{ route('admin.series.episodes.import', $serie) }}';
const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

let detectedEpisodes = [];

async function scanFolder() {
    const folder = document.getElementById('scan-folder').value.trim();
    if (!folder) return;

    const errEl = document.getElementById('scan-error');
    const resEl = document.getElementById('scan-results');
    errEl.classList.add('hidden');
    resEl.classList.add('hidden');

    const res  = await fetch(`${scanRoute}?folder=${encodeURIComponent(folder)}`);
    const data = await res.json();

    if (!res.ok) {
        errEl.textContent = data.error || 'Erro ao escanear pasta.';
        errEl.classList.remove('hidden');
        return;
    }

    detectedEpisodes = data;
    const list  = document.getElementById('scan-list');
    const count = document.getElementById('scan-count');

    if (data.length === 0) {
        errEl.textContent = 'Nenhum ficheiro de vídeo detectado com padrão S01E08.';
        errEl.classList.remove('hidden');
        return;
    }

    count.textContent = `${data.length} ficheiro(s) detectado(s)`;
    list.innerHTML = '';
    data.forEach(ep => {
        const row = document.createElement('div');
        row.className = 'flex items-center gap-3 bg-gray-700 rounded px-3 py-2 text-sm';
        row.innerHTML = `
            <span class="text-red-400 font-bold w-14 flex-shrink-0">T${ep.season}E${ep.episode}</span>
            <span class="flex-1 text-gray-300 truncate text-xs">${ep.filename}</span>
        `;
        list.appendChild(row);
    });

    resEl.classList.remove('hidden');
}

async function importAll() {
    const btn = event.target;
    btn.disabled  = true;
    btn.textContent = 'A importar...';

    const res  = await fetch(importRoute, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ episodes: detectedEpisodes }),
    });
    const data = await res.json();

    btn.textContent = `✓ ${data.imported} importados!`;
    btn.classList.replace('bg-green-600', 'bg-gray-600');
    setTimeout(() => location.reload(), 1000);
}
</script>
@endpush
