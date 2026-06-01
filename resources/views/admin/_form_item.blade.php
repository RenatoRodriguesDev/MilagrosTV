@php
    $method    = $method ?? 'POST';
    $isEdit    = !is_null($item);
    $genreStr  = $item ? implode(', ', $item->genres ?? []) : '';
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    {{-- Coluna: Busca TMDB --}}
    <div class="lg:col-span-1">
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="font-semibold mb-3 text-sm text-gray-300">🔍 Buscar no TMDB</h3>
            <div class="flex gap-2 mb-3">
                <input type="text" id="tmdb-query" placeholder="Nome do título..."
                    class="flex-1 bg-gray-700 border border-gray-600 rounded px-3 py-2 text-sm text-white focus:outline-none focus:border-red-500">
                <button type="button" onclick="searchTmdb()"
                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm">
                    Buscar
                </button>
            </div>
            <div id="tmdb-results" class="space-y-2 max-h-96 overflow-y-auto"></div>
        </div>

        {{-- Preview poster --}}
        <div class="mt-4 text-center">
            <div id="poster-preview" class="inline-block">
                @if($item?->poster_url)
                    <img src="{{ $item->poster_url }}" class="w-32 rounded-lg mx-auto" alt="">
                @endif
            </div>
        </div>
    </div>

    {{-- Coluna: Formulário --}}
    <div class="lg:col-span-2">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div class="sm:col-span-2">
                    <label class="block text-sm text-gray-400 mb-1">Título *</label>
                    <input type="text" name="title" id="field-title" required
                        value="{{ old('title', $item?->title) }}"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                    @error('title') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-1">Título Original</label>
                    <input type="text" name="original_title" id="field-original_title"
                        value="{{ old('original_title', $item?->original_title) }}"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-1">Ano</label>
                    <input type="number" name="year" id="field-year" min="1900" max="2099"
                        value="{{ old('year', $item?->year) }}"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-1">Géneros (separados por vírgula)</label>
                    <input type="text" name="genres" id="field-genres"
                        value="{{ old('genres', $genreStr) }}"
                        placeholder="Ação, Drama, Comédia"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-1">Nota (0-10)</label>
                    <input type="number" name="rating" id="field-rating" step="0.1" min="0" max="10"
                        value="{{ old('rating', $item?->rating) }}"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                </div>

                @if($type === 'movie')
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Duração (min)</label>
                    <input type="number" name="duration" id="field-duration"
                        value="{{ old('duration', $item?->duration) }}"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                </div>
                @else
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Nº de Temporadas</label>
                    <input type="number" name="seasons" id="field-seasons" min="1"
                        value="{{ old('seasons', $item?->seasons) }}"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                </div>
                @endif

                @if($type === 'movie')
                <div class="sm:col-span-2">
                    <label class="block text-sm text-gray-400 mb-1">Caminho do vídeo local</label>
                    <input type="text" name="video_path" id="field-video_path"
                        value="{{ old('video_path', $item?->video_path) }}"
                        placeholder="Ex: Filmes/Interstellar.mkv"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                    <p class="text-xs text-gray-600 mt-1">Relativo à pasta <code>videos/</code></p>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm text-gray-400 mb-1">🇪🇸 URL piratahub.to (ESP dublado)</label>
                    <input type="url" name="piratahub_url" id="field-piratahub_url"
                        value="{{ old('piratahub_url', $item?->piratahub_url) }}"
                        placeholder="https://piratahub.to/filme-nome/"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                </div>
                @else
                <div class="sm:col-span-2">
                    <label class="block text-sm text-gray-400 mb-1">🇪🇸 Slug piratahub.to (ESP dublado)</label>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 text-sm flex-shrink-0">piratahub.to/</span>
                        <input type="text" name="piratahub_slug" id="field-piratahub_slug"
                            value="{{ old('piratahub_slug', $item?->piratahub_slug) }}"
                            placeholder="star-city"
                            class="flex-1 bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-500">
                        <span class="text-gray-500 text-sm flex-shrink-0">/capitulo-N/</span>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">Preenche uma vez — todos os episódios ficam disponíveis automaticamente em 🇪🇸</p>
                </div>
                @endif

                <div class="sm:col-span-2">
                    <label class="block text-sm text-gray-400 mb-1">URL do Poster</label>
                    <input type="url" name="poster_url" id="field-poster_url"
                        value="{{ old('poster_url', $item?->poster_url) }}"
                        oninput="updatePosterPreview(this.value)"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm text-gray-400 mb-1">Sinopse</label>
                    <textarea name="synopsis" id="field-synopsis" rows="4"
                        class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-red-500">{{ old('synopsis', $item?->synopsis) }}</textarea>
                </div>

                <input type="hidden" name="tmdb_id" id="field-tmdb_id" value="{{ old('tmdb_id', $item?->tmdb_id) }}">

            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded font-semibold transition">
                    {{ $isEdit ? 'Guardar alterações' : 'Adicionar' }}
                </button>
                <a href="{{ $isEdit ? ($type === 'movie' ? route('admin.movies.index') : route('admin.series.index')) : 'javascript:history.back()' }}"
                   class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-2 rounded transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const searchRoute  = '{{ $searchRoute }}';
const detailsRoute = '{{ $detailsRoute }}';

async function searchTmdb() {
    const query = document.getElementById('tmdb-query').value.trim();
    if (!query) return;
    const res   = await fetch(`${searchRoute}?query=${encodeURIComponent(query)}`);
    const items = await res.json();
    const box   = document.getElementById('tmdb-results');
    box.innerHTML = items.length ? '' : '<p class="text-gray-500 text-xs">Sem resultados.</p>';
    items.forEach(item => {
        const title = item.title || item.name || '';
        const year  = (item.release_date || item.first_air_date || '').substring(0, 4);
        const poster = item.poster_path ? `https://image.tmdb.org/t/p/w92${item.poster_path}` : '';
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-center p-2 bg-gray-700 rounded cursor-pointer hover:bg-gray-600';
        div.innerHTML = `
            ${poster ? `<img src="${poster}" class="w-8 h-12 object-cover rounded flex-shrink-0" alt="">` : '<div class="w-8 h-12 bg-gray-600 rounded flex-shrink-0"></div>'}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">${title}</p>
                <p class="text-xs text-gray-400">${year}</p>
            </div>
        `;
        div.onclick = () => fillFromTmdb(item.id);
        box.appendChild(div);
    });
}

async function fillFromTmdb(tmdbId) {
    const res  = await fetch(`${detailsRoute}?tmdb_id=${tmdbId}`);
    const data = await res.json();
    setField('title', data.title);
    setField('original_title', data.original_title);
    setField('year', data.year);
    setField('genres', (data.genres || []).join(', '));
    setField('synopsis', data.synopsis);
    setField('poster_url', data.poster_url);
    setField('rating', data.rating);
    setField('tmdb_id', data.tmdb_id);
    if (data.duration) setField('duration', data.duration);
    if (data.seasons)  setField('seasons', data.seasons);
    updatePosterPreview(data.poster_url);
}

function setField(name, value) {
    const el = document.getElementById(`field-${name}`);
    if (el && value != null) el.value = value;
}

function updatePosterPreview(url) {
    const box = document.getElementById('poster-preview');
    if (url) {
        box.innerHTML = `<img src="${url}" class="w-32 rounded-lg mx-auto" alt="" onerror="this.remove()">`;
    } else {
        box.innerHTML = '';
    }
}

document.getElementById('tmdb-query').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); searchTmdb(); }
});
</script>
@endpush
