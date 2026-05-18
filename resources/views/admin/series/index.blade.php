@extends('layouts.admin')

@section('title', 'Séries')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Séries ({{ $series->count() }})</h1>
    <a href="{{ route('admin.series.create') }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm font-semibold">
        + Adicionar Série
    </a>
</div>

@if($series->isEmpty())
    <p class="text-gray-400">Nenhuma série adicionada ainda.</p>
@else
<div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead class="text-gray-400 border-b border-gray-700">
            <tr>
                <th class="pb-2 pr-4 w-12"></th>
                <th class="pb-2 pr-4">Título</th>
                <th class="pb-2 pr-4">Ano</th>
                <th class="pb-2 pr-4">Temporadas</th>
                <th class="pb-2 pr-4">Géneros</th>
                <th class="pb-2 pr-4">Nota</th>
                <th class="pb-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            @foreach($series as $serie)
            <tr class="hover:bg-gray-800">
                <td class="py-2 pr-4">
                    @if($serie->poster_url)
                        <img src="{{ $serie->poster_url }}" class="w-8 h-12 object-cover rounded" alt="">
                    @else
                        <div class="w-8 h-12 bg-gray-700 rounded flex items-center justify-center text-xs">📺</div>
                    @endif
                </td>
                <td class="py-2 pr-4 font-medium">{{ $serie->title }}</td>
                <td class="py-2 pr-4 text-gray-400">{{ $serie->year }}</td>
                <td class="py-2 pr-4 text-gray-400">{{ $serie->seasons ? $serie->seasons.'T' : '—' }}</td>
                <td class="py-2 pr-4 text-gray-400">{{ $serie->genres_list }}</td>
                <td class="py-2 pr-4 text-yellow-400">{{ $serie->rating ? '★ '.$serie->rating : '—' }}</td>
                <td class="py-2 text-right">
                    <a href="{{ route('admin.series.edit', $serie) }}" class="text-blue-400 hover:text-blue-300 mr-3">Editar</a>
                    <form method="POST" action="{{ route('admin.series.destroy', $serie) }}" class="inline" onsubmit="return confirm('Remover série?')">
                        @csrf @method('DELETE')
                        <button class="text-red-400 hover:text-red-300">Remover</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
