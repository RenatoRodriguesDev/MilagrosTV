<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — MilagrosTV</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">

    <nav class="bg-gray-800 px-6 py-3 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-6">
            <span class="text-red-500 font-black text-xl">🎬 Admin</span>
            <a href="{{ route('admin.movies.index') }}" class="text-gray-300 hover:text-white text-sm">Filmes</a>
            <a href="{{ route('admin.series.index') }}" class="text-gray-300 hover:text-white text-sm">Séries</a>
            <a href="{{ route('catalog.index') }}" class="text-gray-300 hover:text-white text-sm" target="_blank">Ver Site</a>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="text-gray-400 hover:text-white text-sm">Sair</button>
        </form>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @if(session('success'))
            <div class="mb-4 bg-green-700 text-white px-4 py-2 rounded">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
