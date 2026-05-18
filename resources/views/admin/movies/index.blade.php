@extends('layouts.admin')

@section('title', 'Filmes')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Filmes ({{ $movies->count() }})</h1>
    <a href="{{ route('admin.movies.create') }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm font-semibold">
        + Adicionar Filme
    </a>
</div>

@if($movies->isEmpty())
    <p class="text-gray-400">Nenhum filme adicionado ainda.</p>
@else
<div class="overflow-x-auto">
    <table class="w-full text-sm text-left">
        <thead class="text-gray-400 border-b border-gray-700">
            <tr>
                <th class="pb-2 pr-4 w-12"></th>
                <th class="pb-2 pr-4">Título</th>
                <th class="pb-2 pr-4">Ano</th>
                <th class="pb-2 pr-4">Géneros</th>
                <th class="pb-2 pr-4">Nota</th>
                <th class="pb-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            @foreach($movies as $movie)
            <tr class="hover:bg-gray-800">
                <td class="py-2 pr-4">
                    @if($movie->poster_url)
                        <img src="{{ $movie->poster_url }}" class="w-8 h-12 object-cover rounded" alt="">
                    @else
                        <div class="w-8 h-12 bg-gray-700 rounded flex items-center justify-center text-xs">🎥</div>
                    @endif
                </td>
                <td class="py-2 pr-4 font-medium">{{ $movie->title }}</td>
                <td class="py-2 pr-4 text-gray-400">{{ $movie->year }}</td>
                <td class="py-2 pr-4 text-gray-400">{{ $movie->genres_list }}</td>
                <td class="py-2 pr-4 text-yellow-400">{{ $movie->rating ? '★ '.$movie->rating : '—' }}</td>
                <td class="py-2 text-right">
                    <a href="{{ route('admin.movies.edit', $movie) }}" class="text-blue-400 hover:text-blue-300 mr-3">Editar</a>
                    <form method="POST" action="{{ route('admin.movies.destroy', $movie) }}" class="inline" onsubmit="return confirm('Remover filme?')">
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
