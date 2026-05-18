<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MilagrosTV')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        netflix: { red: '#E50914', dark: '#141414', card: '#1f1f1f' }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #141414; }
        .card-hover { transition: transform 0.2s, box-shadow 0.2s; }
        .card-hover:hover { transform: scale(1.04); box-shadow: 0 8px 32px rgba(0,0,0,0.7); }
        .poster-placeholder { background: linear-gradient(135deg, #1f1f1f, #2d2d2d); }
        .watched-badge { backdrop-filter: blur(4px); }
    </style>
</head>
<body class="text-white min-h-screen">

    <nav class="bg-black bg-opacity-95 sticky top-0 z-50 px-6 py-3 flex items-center justify-between shadow-lg">
        <a href="{{ route('catalog.index') }}" class="flex items-center gap-3">
            <span class="text-red-600 font-black text-2xl tracking-tight">🎬 MilagrosTV</span>
        </a>
        <div class="flex items-center gap-4">
            <a href="{{ route('catalog.index') }}" class="text-gray-300 hover:text-white text-sm transition">{{ __('nav.home') }}</a>
            <a href="{{ route('catalog.index', ['type' => 'movies']) }}" class="text-gray-300 hover:text-white text-sm transition">{{ __('nav.movies') }}</a>
            <a href="{{ route('catalog.index', ['type' => 'series']) }}" class="text-gray-300 hover:text-white text-sm transition">{{ __('nav.series') }}</a>

            {{-- Seletor de idioma --}}
            <div class="flex items-center gap-1 border border-gray-700 rounded px-2 py-1">
                @foreach(['pt' => 'pt', 'es' => 'es', 'en' => 'gb'] as $lang => $flag)
                    <a href="{{ route('locale.switch', $lang) }}"
                       title="{{ strtoupper($lang) }}"
                       class="transition {{ app()->getLocale() === $lang ? 'opacity-100 ring-1 ring-red-500 rounded' : 'opacity-40 hover:opacity-80' }}">
                        <img src="https://flagcdn.com/20x15/{{ $flag }}.png"
                             width="20" height="15"
                             alt="{{ strtoupper($lang) }}"
                             class="rounded-sm">
                    </a>
                @endforeach
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
