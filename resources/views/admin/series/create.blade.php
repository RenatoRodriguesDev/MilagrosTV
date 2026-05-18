@extends('layouts.admin')

@section('title', 'Adicionar Série')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.series.index') }}" class="text-gray-400 hover:text-white">← Voltar</a>
    <h1 class="text-2xl font-bold">Adicionar Série</h1>
</div>

@include('admin._form_item', [
    'action'       => route('admin.series.store'),
    'searchRoute'  => route('admin.series.tmdb-search'),
    'detailsRoute' => route('admin.series.tmdb-details'),
    'item'         => null,
    'type'         => 'serie',
])
@endsection
