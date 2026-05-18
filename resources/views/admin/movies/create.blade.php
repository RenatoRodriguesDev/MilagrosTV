@extends('layouts.admin')

@section('title', 'Adicionar Filme')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.movies.index') }}" class="text-gray-400 hover:text-white">← Voltar</a>
    <h1 class="text-2xl font-bold">Adicionar Filme</h1>
</div>

@include('admin._form_item', [
    'action'     => route('admin.movies.store'),
    'searchRoute' => route('admin.movies.tmdb-search'),
    'detailsRoute' => route('admin.movies.tmdb-details'),
    'item'       => null,
    'type'       => 'movie',
])
@endsection
