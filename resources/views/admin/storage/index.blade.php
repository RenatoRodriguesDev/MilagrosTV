@extends('layouts.admin')
@section('title', 'Gestão de Espaço')

@section('content')

@php
    $linked   = $files->where('linked', true);
    $unlinked = $files->where('linked', false);
    $fmt = fn($b) => $b >= 1073741824 ? round($b/1073741824,2).' GB' : ($b >= 1048576 ? round($b/1048576,1).' MB' : round($b/1024,0).' KB');
@endphp

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4">
        <p class="text-xs text-gray-500 mb-1">Total</p>
        <p class="text-xl font-bold">{{ $fmt($totalSize) }}</p>
    </div>
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4">
        <p class="text-xs text-gray-500 mb-1">Ficheiros</p>
        <p class="text-xl font-bold">{{ $files->count() }}</p>
    </div>
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4">
        <p class="text-xs text-gray-500 mb-1">Ligados</p>
        <p class="text-xl font-bold text-green-400">{{ $linked->count() }}</p>
    </div>
    <div class="bg-gray-800/60 border border-white/[.08] rounded-xl p-4">
        <p class="text-xs text-gray-500 mb-1">Sem episódio</p>
        <p class="text-xl font-bold text-yellow-400">{{ $unlinked->count() }}</p>
        <p class="text-xs text-gray-500 mt-0.5">{{ $fmt($unlinked->sum('size')) }}</p>
    </div>
</div>

@if($files->isEmpty())
<div class="bg-gray-800/40 border border-white/[.08] rounded-2xl text-center py-16 text-gray-600">
    <p class="text-3xl mb-3">📁</p>
    <p>Nenhum vídeo encontrado na pasta de vídeos.</p>
</div>
@else

{{-- Unlinked files (actionable) --}}
@if($unlinked->isNotEmpty())
<div class="bg-yellow-900/10 border border-yellow-800/30 rounded-2xl overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-yellow-800/20 flex items-center justify-between">
        <div>
            <h2 class="font-semibold text-sm text-yellow-300">Ficheiros sem episódio associado</h2>
            <p class="text-xs text-yellow-700 mt-0.5">{{ $unlinked->count() }} ficheiros · {{ $fmt($unlinked->sum('size')) }}</p>
        </div>
    </div>
    <div class="divide-y divide-white/[.04]">
        @foreach($unlinked->take(20) as $file)
        <div class="flex items-center gap-3 px-5 py-3">
            <div class="flex-1 min-w-0">
                <p class="text-sm text-white truncate">{{ $file['name'] }}</p>
                <p class="text-xs text-gray-500 truncate">{{ $file['folder'] }}</p>
            </div>
            <span class="text-xs text-yellow-400 flex-shrink-0 font-mono">{{ $fmt($file['size']) }}</span>
        </div>
        @endforeach
        @if($unlinked->count() > 20)
        <p class="text-xs text-gray-600 px-5 py-3">+ {{ $unlinked->count() - 20 }} mais...</p>
        @endif
    </div>
</div>
@endif

{{-- All files sorted by size --}}
<div class="bg-gray-800/40 border border-white/[.08] rounded-2xl overflow-hidden">
    <div class="px-5 py-4 border-b border-white/[.08]">
        <h2 class="font-semibold text-sm">Todos os vídeos (por tamanho)</h2>
    </div>

    {{-- Mobile: cards --}}
    <div class="md:hidden divide-y divide-white/[.05]">
        @foreach($files as $file)
        <div class="flex items-center gap-3 px-4 py-3">
            <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $file['linked'] ? 'bg-green-500' : 'bg-yellow-500' }}"></div>
            <div class="flex-1 min-w-0">
                <p class="text-sm truncate">{{ $file['name'] }}</p>
                <p class="text-xs text-gray-500">{{ $file['folder'] }} · {{ $fmt($file['size']) }}</p>
            </div>
            <button onclick="deleteFile('{{ addslashes($file['path']) }}', '{{ addslashes($file['name']) }}', this)"
                class="text-xs px-2.5 py-1.5 rounded-lg bg-red-900/20 text-red-400 hover:bg-red-900/40 transition flex-shrink-0">
                Apagar
            </button>
        </div>
        @endforeach
    </div>

    {{-- Desktop: table --}}
    <table class="hidden md:table w-full text-sm">
        <thead>
            <tr class="border-b border-white/[.08] text-gray-500 text-xs font-semibold uppercase tracking-wide">
                <th class="text-left px-5 py-3 w-8"></th>
                <th class="text-left px-5 py-3">Ficheiro</th>
                <th class="text-left px-5 py-3">Pasta</th>
                <th class="text-right px-5 py-3">Tamanho</th>
                <th class="text-left px-5 py-3">Estado</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-white/[.05]">
            @foreach($files as $i => $file)
            <tr class="hover:bg-white/[.02] group" id="row-{{ $i }}">
                <td class="px-5 py-3 text-gray-600 text-xs">{{ $i+1 }}</td>
                <td class="px-5 py-3 font-mono text-xs text-gray-200 max-w-xs truncate" title="{{ $file['path'] }}">{{ $file['name'] }}</td>
                <td class="px-5 py-3 text-gray-500 text-xs">{{ $file['folder'] }}</td>
                <td class="px-5 py-3 text-right font-mono text-xs text-gray-300">{{ $fmt($file['size']) }}</td>
                <td class="px-5 py-3">
                    @if($file['linked'])
                        <span class="text-xs bg-green-600/20 text-green-400 px-2 py-0.5 rounded-full">Ligado</span>
                    @else
                        <span class="text-xs bg-yellow-600/20 text-yellow-400 px-2 py-0.5 rounded-full">Sem episódio</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <button onclick="deleteFile('{{ addslashes($file['path']) }}', '{{ addslashes($file['name']) }}', this)"
                        class="opacity-0 group-hover:opacity-100 text-xs px-2.5 py-1.5 rounded-lg bg-red-900/20 text-red-400 hover:bg-red-900/40 transition">
                        Apagar
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection

@push('scripts')
<script>
async function deleteFile(path, name, btn) {
    if (!confirm(`Apagar "${name}" do disco?\n\nEsta acção é irreversível.`)) return;

    btn.disabled = true;
    btn.textContent = '...';

    const res = await fetch('{{ route('admin.storage.destroy') }}', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ path }),
    });

    if (res.ok) {
        btn.closest('tr, .flex')?.remove();
    } else {
        const data = await res.json().catch(() => ({}));
        alert('Erro: ' + (data.error || 'Não foi possível apagar o ficheiro.'));
        btn.disabled = false;
        btn.textContent = 'Apagar';
    }
}
</script>
@endpush
