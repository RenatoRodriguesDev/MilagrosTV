@extends('layouts.admin')

@section('title', 'Editar: ' . $serie->title)

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.series.index') }}" class="text-gray-400 hover:text-white flex-shrink-0">←</a>
    <h1 class="text-lg font-bold truncate">{{ $serie->title }}</h1>
</div>

@include('admin._form_item', [
    'action'       => route('admin.series.update', $serie),
    'searchRoute'  => route('admin.series.tmdb-search'),
    'detailsRoute' => route('admin.series.tmdb-details'),
    'item'         => $serie,
    'type'         => 'serie',
    'method'       => 'PUT',
])

{{-- Sincronizar episódios do TMDB --}}
@if($serie->tmdb_id)
<div class="bg-blue-900/20 border border-blue-700/30 rounded-xl px-5 py-4 mb-6 flex items-center justify-between flex-wrap gap-3">
    <div>
        <p class="text-sm font-semibold text-blue-300">Sincronizar episódios com TMDB</p>
        <p class="text-xs text-blue-500 mt-0.5">Importa/actualiza todos os episódios de todas as temporadas automaticamente.</p>
    </div>
    <button onclick="syncEpisodes()" id="sync-btn"
        class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition flex-shrink-0">
        ↻ Sincronizar
    </button>
</div>
@endif

{{-- Episódios --}}
<div class="mt-10">
    <h2 class="text-base font-bold mb-4">🎬 Episódios</h2>

    {{-- Importar do TMDB --}}
    @if($serie->tmdb_id)
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4 mb-4">
        <h3 class="text-sm font-semibold text-gray-300 mb-3">Importar do TMDB</h3>
        <div class="flex flex-wrap gap-2 mb-2">
            <select id="tmdb-season" class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none">
                @for($s = 1; $s <= ($serie->seasons ?? 5); $s++)
                <option value="{{ $s }}">Temporada {{ $s }}</option>
                @endfor
            </select>
            <button type="button" onclick="importFromTmdb()"
                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                ↓ Importar
            </button>
        </div>
        <p class="text-xs text-gray-500">Cria episódios com títulos do TMDB (sem vídeo).</p>
        <div id="tmdb-results" class="hidden mt-3">
            <p id="tmdb-count" class="text-sm text-green-400 mb-2"></p>
            <div id="tmdb-list" class="space-y-1 max-h-48 overflow-y-auto"></div>
        </div>
        <p id="tmdb-error" class="text-red-400 text-sm hidden mt-2"></p>
    </div>
    @else
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4 mb-4 text-gray-500 text-sm">
        ℹ️ Para importar do TMDB, associa primeiro a série a um TMDB ID acima.
    </div>
    @endif

    {{-- Scanner automático --}}
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4 mb-4">
        <h3 class="text-sm font-semibold text-gray-300 mb-3">📂 Detectar ficheiros</h3>
        <div class="flex gap-2 mb-2">
            <input type="text" id="scan-folder" placeholder="Ex: From  ou  From/Season1"
                class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-red-500 min-w-0">
            <button type="button" onclick="scanFolder()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex-shrink-0">
                Detectar
            </button>
        </div>
        <p class="text-xs text-gray-500 mb-3">Pasta dentro de <code class="text-gray-400">videos/</code></p>
        <div id="scan-results" class="hidden">
            <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
                <p id="scan-count" class="text-sm text-gray-300"></p>
                <button type="button" onclick="importAll()"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    ✓ Importar todos
                </button>
            </div>
            <div id="scan-list" class="space-y-1 max-h-64 overflow-y-auto"></div>
        </div>
        <p id="scan-error" class="text-red-400 text-sm hidden"></p>
    </div>

    {{-- Adicionar episódio manual --}}
    <details class="bg-gray-800/60 border border-white/[.08] rounded-xl mb-6">
        <summary class="px-4 py-3 text-sm font-semibold text-gray-400 cursor-pointer hover:text-white">+ Adicionar manualmente</summary>
        <div class="px-4 pb-4">
            <form method="POST" action="{{ route('admin.series.episodes.store', $serie) }}" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Temporada *</label>
                    <input type="number" name="season" min="1" required value="{{ old('season', 1) }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Episódio *</label>
                    <input type="number" name="episode" min="1" required value="{{ old('episode', 1) }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Título</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Opcional"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Caminho do vídeo</label>
                    <input type="text" name="video_path" value="{{ old('video_path') }}" placeholder="From/S1/ep.mkv"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none">
                </div>
                <div class="col-span-2 sm:col-span-4">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">
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

    {{-- Bulk actions bar --}}
    <div id="bulk-bar" class="hidden bg-blue-900/30 border border-blue-700/40 rounded-xl px-4 py-3 mb-3 flex items-center gap-3">
        <span id="bulk-count" class="text-sm text-blue-300 font-medium"></span>
        <button onclick="bulkSave()" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg transition">Guardar alterações</button>
        <button onclick="bulkDelete()" class="text-xs bg-red-900/40 hover:bg-red-900/60 text-red-400 px-3 py-1.5 rounded-lg transition">Eliminar seleccionados</button>
        <button onclick="clearSelection()" class="text-xs text-gray-500 hover:text-white ml-auto">Cancelar</button>
    </div>

        @foreach($grouped as $season => $eps)
        <div class="mb-4">
            <div class="flex items-center gap-2 mb-2 px-1">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Temporada {{ $season }}</h4>
                <button onclick="selectSeason({{ $season }})" class="text-[10px] text-gray-600 hover:text-gray-400">Seleccionar todos</button>
            </div>
            <div class="space-y-1" data-season="{{ $season }}">
                @foreach($eps as $ep)
                <div class="bg-gray-800/60 border border-white/[.05] rounded-xl px-3 py-2.5 episode-row" data-id="{{ $ep->id }}" data-season="{{ $ep->season }}">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" class="ep-check rounded flex-shrink-0" data-id="{{ $ep->id }}"
                            onchange="onCheckChange()">
                        <span class="text-red-400 font-bold text-xs flex-shrink-0 w-12">T{{ $ep->season }}E{{ $ep->episode }}</span>
                        <input type="text" value="{{ $ep->title }}" placeholder="Título"
                            class="ep-title flex-1 bg-transparent border-b border-transparent hover:border-white/20 focus:border-blue-500 text-sm text-gray-200 focus:outline-none px-1 py-0.5 min-w-0 transition"
                            data-id="{{ $ep->id }}" data-field="title">
                        <input type="text" value="{{ $ep->video_path }}" placeholder="Caminho do vídeo"
                            class="ep-path flex-1 bg-transparent border-b border-transparent hover:border-white/20 focus:border-blue-500 text-xs text-gray-500 focus:outline-none px-1 py-0.5 min-w-0 transition hidden md:block"
                            data-id="{{ $ep->id }}" data-field="video_path">
                        @if($ep->video_path && !$ep->isExternalUrl())
                        <span class="text-xs flex-shrink-0 {{ $ep->hasVideo() ? 'text-green-500' : 'text-red-400' }}">
                            {{ $ep->hasVideo() ? '✓' : '✗' }}
                        </span>
                        @endif
                        <button onclick="togglePiratahub(this)" class="text-[10px] flex-shrink-0 px-1 {{ $ep->piratahub_url ? 'text-yellow-400' : 'text-gray-600 hover:text-yellow-500' }} transition" title="URL piratahub.to">🇪🇸</button>
                        <button onclick="deleteEp({{ $ep->id }}, this)" class="text-gray-600 hover:text-red-400 text-xs transition flex-shrink-0 px-1">✕</button>
                    </div>
                    <div class="piratahub-row mt-1.5 {{ $ep->piratahub_url ? '' : 'hidden' }}">
                        <input type="text" value="{{ $ep->piratahub_url }}" placeholder="https://piratahub.to/..."
                            class="ep-piratahub w-full bg-transparent border-b border-yellow-500/30 hover:border-yellow-500/50 focus:border-yellow-500 text-xs text-yellow-300/70 focus:outline-none px-1 py-0.5 transition"
                            data-id="{{ $ep->id }}" data-field="piratahub_url">
                    </div>
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
// Bulk episode editing
const bulkUpdateRoute  = '{{ route('admin.series.episodes.bulk-update', $serie) }}';
const bulkDestroyRoute = '{{ route('admin.series.episodes.bulk-destroy', $serie) }}';

function onCheckChange() {
    const checked = document.querySelectorAll('.ep-check:checked');
    const bar = document.getElementById('bulk-bar');
    document.getElementById('bulk-count').textContent = `${checked.length} seleccionado(s)`;
    bar.style.display = checked.length > 0 ? 'flex' : 'none';
}

function selectSeason(season) {
    document.querySelectorAll(`.ep-check`).forEach(cb => {
        const row = cb.closest('.episode-row');
        if (row?.dataset.season == season) { cb.checked = true; }
    });
    onCheckChange();
}

function clearSelection() {
    document.querySelectorAll('.ep-check').forEach(cb => cb.checked = false);
    onCheckChange();
}

function togglePiratahub(btn) {
    const row = btn.closest('.episode-row').querySelector('.piratahub-row');
    row.classList.toggle('hidden');
}

async function bulkSave() {
    const episodes = {};
    document.querySelectorAll('.ep-check:checked').forEach(cb => {
        const id  = cb.dataset.id;
        const row = cb.closest('.episode-row');
        episodes[id] = {
            title:         row.querySelector('.ep-title')?.value || '',
            video_path:    row.querySelector('.ep-path')?.value || '',
            piratahub_url: row.querySelector('.ep-piratahub')?.value || '',
        };
    });
    const res = await fetch(bulkUpdateRoute, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ episodes }),
    });
    const data = await res.json();
    alert(`✓ ${data.updated} episódio(s) guardado(s).`);
    clearSelection();
}

async function bulkDelete() {
    const ids = [...document.querySelectorAll('.ep-check:checked')].map(cb => cb.dataset.id);
    if (!confirm(`Eliminar ${ids.length} episódio(s)?`)) return;
    const res = await fetch(bulkDestroyRoute, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ ids }),
    });
    const data = await res.json();
    ids.forEach(id => document.querySelector(`.ep-check[data-id="${id}"]`)?.closest('.episode-row')?.remove());
    clearSelection();
    alert(`✓ ${data.deleted} episódio(s) eliminado(s).`);
}

async function deleteEp(id, btn) {
    if (!confirm('Remover este episódio?')) return;
    const res = await fetch(`/admin/episodes/${id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ _method: 'DELETE' }),
    });
    if (res.ok || res.redirected) {
        btn.closest('.episode-row').remove();
    }
}

@if($serie->tmdb_id)
const syncRoute = '{{ route('admin.series.sync-episodes', $serie) }}';
async function syncEpisodes() {
    const btn = document.getElementById('sync-btn');
    btn.textContent = 'A sincronizar...';
    btn.disabled = true;
    const res  = await fetch(syncRoute, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    });
    const data = await res.json();
    if (res.ok) {
        btn.textContent = `✓ ${data.message}`;
        btn.className = btn.className.replace('bg-blue-600 hover:bg-blue-700', 'bg-green-600');
        setTimeout(() => location.reload(), 1500);
    } else {
        btn.textContent = data.error || 'Erro';
        btn.disabled = false;
    }
}
@endif

const scanRoute   = '{{ route('admin.episodes.scan') }}';
const importRoute = '{{ route('admin.series.episodes.import', $serie) }}';
@if($serie->tmdb_id)
const tmdbRoute   = '{{ route('admin.series.episodes.tmdb-season', $serie) }}';
@endif
const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;

let detectedEpisodes = [];

async function scanFolder() {
    const folder = document.getElementById('scan-folder').value.trim();
    if (!folder) return;
    const errEl = document.getElementById('scan-error');
    const resEl = document.getElementById('scan-results');
    errEl.classList.add('hidden');
    resEl.classList.add('hidden');
    const res  = await fetch(`${scanRoute}?folder=${encodeURIComponent(folder)}`, { credentials: 'same-origin' });
    const data = await res.json();
    if (!res.ok) { errEl.textContent = data.error || 'Erro.'; errEl.classList.remove('hidden'); return; }
    detectedEpisodes = data;
    const list  = document.getElementById('scan-list');
    const count = document.getElementById('scan-count');
    if (!data.length) { errEl.textContent = 'Nenhum ficheiro detectado.'; errEl.classList.remove('hidden'); return; }
    count.textContent = `${data.length} ficheiro(s) detectado(s)`;
    list.innerHTML = data.map(ep => `
        <div class="flex items-center gap-3 bg-gray-700 rounded-lg px-3 py-2 text-sm">
            <span class="text-red-400 font-bold w-14 flex-shrink-0 text-xs">T${ep.season}E${ep.episode}</span>
            <span class="flex-1 text-gray-300 truncate text-xs">${ep.filename}</span>
        </div>`).join('');
    resEl.classList.remove('hidden');
}

async function importFromTmdb() {
    const season = document.getElementById('tmdb-season').value;
    const errEl  = document.getElementById('tmdb-error');
    const resEl  = document.getElementById('tmdb-results');
    errEl.classList.add('hidden');
    resEl.classList.add('hidden');
    const btn = event.target;
    btn.disabled = true; btn.textContent = 'A importar...';
    const res  = await fetch(`${tmdbRoute}?season=${season}`, { credentials: 'same-origin' });
    const data = await res.json();
    btn.disabled = false; btn.textContent = '↓ Importar';
    if (!res.ok) { errEl.textContent = data.error || 'Erro.'; errEl.classList.remove('hidden'); return; }
    document.getElementById('tmdb-count').textContent = `✓ ${data.imported} episódio(s) importados da T${season}`;
    document.getElementById('tmdb-list').innerHTML = data.episodes.map(ep => `
        <div class="flex items-center gap-3 bg-gray-700 rounded-lg px-3 py-2 text-sm">
            <span class="text-purple-400 font-bold w-10 flex-shrink-0 text-xs">E${ep.episode_number}</span>
            <span class="flex-1 text-gray-300 truncate text-xs">${ep.name || '—'}</span>
        </div>`).join('');
    resEl.classList.remove('hidden');
    setTimeout(() => location.reload(), 1800);
}

async function importAll() {
    const btn = event.target;
    btn.disabled = true; btn.textContent = 'A importar...';
    const res  = await fetch(importRoute, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ episodes: detectedEpisodes }),
    });
    const data = await res.json();
    btn.textContent = `✓ ${data.imported} importados!`;
    setTimeout(() => location.reload(), 1000);
}
</script>
@endpush
