<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MilagrosTV')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { brand: { red: '#E50914', dark: '#0a0a0a' } }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background-color: #0a0a0a; }

        /* Navbar scroll effect */
        #navbar { transition: background 0.3s, box-shadow 0.3s; }
        #navbar.scrolled { background: rgba(0,0,0,0.97) !important; box-shadow: 0 2px 20px rgba(0,0,0,0.8); }

        /* Cards */
        .card-item { transition: transform 0.25s cubic-bezier(.25,.46,.45,.94), box-shadow 0.25s; }
        .card-item:hover { transform: scale(1.06) translateY(-4px); box-shadow: 0 20px 60px rgba(0,0,0,0.8); z-index: 10; }
        .card-overlay { transition: opacity 0.25s; }

        /* Poster placeholder */
        .poster-placeholder { background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460); }

        /* Watched badge */
        .watched-badge { backdrop-filter: blur(6px); }

        /* Season tabs */
        .season-tab { transition: all 0.2s; }

        /* Video player */
        video::-webkit-media-controls { background: rgba(0,0,0,0.6); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #111; }
        ::-webkit-scrollbar-thumb { background: #444; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #666; }

        /* Animations */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.4s ease forwards; }

        /* Search input */
        .search-input:focus { box-shadow: 0 0 0 2px rgba(229,9,20,0.4); }
    </style>
</head>
<body class="text-white min-h-screen">

    <nav id="navbar" class="fixed top-0 left-0 right-0 z-50 px-6 py-3 bg-gradient-to-b from-black to-transparent">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            {{-- Logo --}}
            <a href="{{ route('catalog.index') }}" class="flex items-center gap-2 group">
                <div class="w-8 h-8 bg-red-600 rounded flex items-center justify-center font-black text-white text-sm group-hover:bg-red-500 transition">M</div>
                <span class="font-black text-xl tracking-tight text-white">MilagrosTV</span>
            </a>

            {{-- Nav links --}}
            <div class="hidden sm:flex items-center gap-6">
                <a href="{{ route('catalog.index') }}"
                   class="text-sm font-medium transition-colors {{ request()->routeIs('catalog.index') && !request('type') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                    {{ __('nav.home') }}
                </a>
                <a href="{{ route('catalog.index', ['type' => 'movies']) }}"
                   class="text-sm font-medium transition-colors {{ request('type') === 'movies' ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                    {{ __('nav.movies') }}
                </a>
                <a href="{{ route('catalog.index', ['type' => 'series']) }}"
                   class="text-sm font-medium transition-colors {{ request('type') === 'series' ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                    {{ __('nav.series') }}
                </a>
            </div>

            {{-- Right side --}}
            <div class="flex items-center gap-3">
                {{-- Language switcher --}}
                <div class="flex items-center gap-1.5 bg-white/5 rounded-lg px-2 py-1.5 border border-white/10">
                    @foreach(['pt' => 'pt', 'es' => 'es', 'en' => 'gb'] as $lang => $flag)
                        <a href="{{ route('locale.switch', $lang) }}"
                           title="{{ strtoupper($lang) }}"
                           class="transition-all {{ app()->getLocale() === $lang ? 'opacity-100 scale-110' : 'opacity-35 hover:opacity-70' }}">
                            <img src="https://flagcdn.com/20x15/{{ $flag }}.png" width="20" height="15" alt="{{ strtoupper($lang) }}" class="rounded-sm">
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    @stack('scripts')

    <script>
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    </script>
</body>
</html>
