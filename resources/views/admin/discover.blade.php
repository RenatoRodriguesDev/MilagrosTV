@extends('layouts.admin')
@section('title', 'Descobrir conteúdo')

@section('content')

{{-- Filtros --}}
<div class="flex flex-wrap gap-3 mb-6">
    <div class="flex rounded-lg overflow-hidden border border-white/10 text-sm">
        @foreach(['movie' => '🎥 Filmes', 'tv' => '📺 Séries'] as $val => $lbl)
        <a href="{{ request()->fullUrlWithQuery(['type' => $val, 'page' => 1]) }}"
            class="px-4 py-2 font-medium transition {{ $type === $val ? 'bg-red-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10' }}">
            {{ $lbl }}
        </a>
        @endforeach
    </div>
    <div class="flex rounded-lg overflow-hidden border border-white/10 text-sm">
        @foreach(['popular' => 'Popular', 'top_rated' => 'Melhor avaliados', 'trending/week' => 'Em tendência'] as $val => $lbl)
        <a href="{{ request()->fullUrlWithQuery(['category' => $val, 'page' => 1]) }}"
            class="px-4 py-2 font-medium transition {{ $category === $val ? 'bg-white/20 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10' }}">
            {{ $lbl }}
        </a>
        @endforeach
    </div>
</div>

{{-- Bulk actions bar --}}
<div class="flex items-center gap-3 mb-4">
    <button onclick="toggleSelectAll()"
        class="text-sm px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition">
        Seleccionar todos
    </button>
    <button id="import-selected-btn" onclick="importSelected()"
        class="hidden text-sm px-4 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition">
        ↓ Importar seleccionados (<span id="sel-count">0</span>)
    </button>
    <span id="import-progress" class="hidden text-sm text-gray-400"></span>
    <span class="text-xs text-gray-600 ml-auto">{{ number_format($totalResults) }} resultados · página {{ $page }}/{{ $totalPages }}</span>
</div>

{{-- Grid --}}
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 mb-8" id="results-grid">
    @foreach($results as $item)
    @php
        $title    = $item['title'] ?? $item['name'] ?? '—';
        $poster   = $item['poster_path'] ? 'https://image.tmdb.org/t/p/w300'.$item['poster_path'] : null;
        $year     = substr($item['release_date'] ?? $item['first_air_date'] ?? '', 0, 4);
        $rating   = round($item['vote_average'] ?? 0, 1);
        $imported = $item['already_imported'];
        $tmdbType = $item['_type'] === 'movie' ? 'movie' : 'tv';
    @endphp
    <div class="relative group rounded-xl overflow-hidden bg-gray-800 border transition
        {{ $imported ? 'border-green-700/40 opacity-60' : 'border-white/10 hover:border-white/30 cursor-pointer' }}"
         id="card-{{ $item['id'] }}"
         data-tmdb-id="{{ $item['id'] }}"
         data-type="{{ $tmdbType }}"
         data-imported="{{ $imported ? '1' : '0' }}"
         @if(!$imported) onclick="toggleCard(this)" @endif>

        @if($poster)
        <img src="{{ $poster }}" alt="{{ $title }}" class="w-full aspect-[2/3] object-cover pointer-events-none">
        @else
        <div class="w-full aspect-[2/3] bg-gray-700 flex items-center justify-center text-3xl pointer-events-none">
            {{ $tmdbType === 'movie' ? '🎥' : '📺' }}
        </div>
        @endif

        @if($rating > 0)
        <div class="absolute top-2 left-2 bg-black/70 text-yellow-400 text-xs px-1.5 py-0.5 rounded font-bold pointer-events-none">
            ★ {{ $rating }}
        </div>
        @endif

        {{-- Selection indicator --}}
        @if(!$imported)
        <div class="card-check absolute top-2 right-2 w-5 h-5 rounded-full border-2 border-white/50 bg-transparent transition pointer-events-none"></div>
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/10 to-transparent flex flex-col justify-end p-3 pointer-events-none">
            <p class="text-white text-xs font-semibold truncate mb-0.5">{{ $title }}</p>
            <p class="text-gray-400 text-[10px]">{{ $year }}</p>
            @if($imported)
            <span class="text-[10px] text-green-400 mt-1">✓ Já importado</span>
            @endif
        </div>

        {{-- Status overlay --}}
        <div id="status-{{ $item['id'] }}" class="hidden absolute inset-0 bg-black/80 flex items-center justify-center">
            <span class="text-white text-xs font-semibold"></span>
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
@if($totalPages > 1)
<div class="flex items-center justify-center gap-2">
    @if($page > 1)
    <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}"
        class="px-4 py-2 text-sm bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white rounded-lg transition">← Anterior</a>
    @endif
    <span class="text-sm text-gray-500">{{ $page }} / {{ $totalPages }}</span>
    @if($page < $totalPages)
    <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}"
        class="px-4 py-2 text-sm bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white rounded-lg transition">Seguinte →</a>
    @endif
</div>
@endif

@endsection

@push('scripts')
<script>
const IMPORT_URL = '{{ route('admin.discover.import') }}';
const CSRF       = document.querySelector('meta[name="csrf-token"]').content;

function toggleCard(card) {
    if (card.dataset.imported === '1') return;
    const selected = card.classList.toggle('ring-2');
    card.classList.toggle('ring-red-500', selected);
    card.querySelector('.card-check')?.classList.toggle('bg-red-500', selected);
    card.querySelector('.card-check')?.classList.toggle('border-red-500', selected);
    updateSelCount();
}

function updateSelCount() {
    const n = document.querySelectorAll('[data-imported="0"].ring-2').length;
    document.getElementById('sel-count').textContent = n;
    document.getElementById('import-selected-btn').classList.toggle('hidden', n === 0);
}

function toggleSelectAll() {
    const cards = [...document.querySelectorAll('[data-imported="0"]')];
    const allSelected = cards.every(c => c.classList.contains('ring-2'));
    cards.forEach(c => {
        if (allSelected) {
            c.classList.remove('ring-2', 'ring-red-500');
            c.querySelector('.card-check')?.classList.remove('bg-red-500', 'border-red-500');
        } else {
            c.classList.add('ring-2', 'ring-red-500');
            c.querySelector('.card-check')?.classList.add('bg-red-500', 'border-red-500');
        }
    });
    updateSelCount();
}

async function importSelected() {
    const selected = [...document.querySelectorAll('[data-imported="0"].ring-2')];
    if (!selected.length) return;

    const btn      = document.getElementById('import-selected-btn');
    const progress = document.getElementById('import-progress');
    btn.classList.add('hidden');
    progress.classList.remove('hidden');

    let done = 0;
    for (const card of selected) {
        const tmdbId = card.dataset.tmdbId;
        const type   = card.dataset.type;
        const statusEl = document.getElementById(`status-${tmdbId}`);

        statusEl.classList.remove('hidden');
        statusEl.querySelector('span').textContent = 'A importar...';
        progress.textContent = `${done}/${selected.length} importados`;

        try {
            const res  = await fetch(IMPORT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ tmdb_id: parseInt(tmdbId), type }),
            });
            const data = await res.json();

            if (res.ok) {
                const eps = data.episodes ? ` ${data.episodes}ep` : '';
                statusEl.querySelector('span').textContent = `✓${eps}`;
                statusEl.style.background = 'rgba(22,163,74,0.85)';
                card.dataset.imported = '1';
                card.classList.remove('ring-2', 'ring-red-500');
                done++;
            } else {
                statusEl.querySelector('span').textContent = data.error === 'Já importado.' ? '✓' : '✗';
                statusEl.style.background = data.error === 'Já importado.' ? 'rgba(22,163,74,0.85)' : 'rgba(220,38,38,0.85)';
                done++;
            }
        } catch(e) {
            statusEl.querySelector('span').textContent = '✗';
            statusEl.style.background = 'rgba(220,38,38,0.85)';
        }

        await new Promise(r => setTimeout(r, 300)); // small delay between imports
    }

    progress.textContent = `✓ ${done} importados!`;
    setTimeout(() => { progress.classList.add('hidden'); btn.classList.remove('hidden'); }, 3000);
    updateSelCount();
}
</script>
@endpush
