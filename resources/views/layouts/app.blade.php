<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MilagrosTV')</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icon.svg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MilagrosTV">
    <meta name="theme-color" content="#E50914">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
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

        /* Mobile bottom nav */
        .bottom-nav { padding-bottom: env(safe-area-inset-bottom); }

        /* Plyr customization */
        :root {
            --plyr-color-main: #E50914;
            --plyr-video-background: #000;
            --plyr-font-family: 'Inter', sans-serif;
            --plyr-font-size-base: 13px;
        }
        .plyr--video { border-radius: 12px; overflow: hidden; }
        .plyr__control--overlaid { background: rgba(229,9,20,0.85) !important; }
        .plyr__control--overlaid:hover { background: #E50914 !important; }

        /* Prevent text select on tap */
        nav, .card-item { -webkit-tap-highlight-color: transparent; user-select: none; }

        /* Bigger touch targets on mobile */
        @media (max-width: 640px) {
            .card-item:hover { transform: none; box-shadow: none; }
            .card-item:active { transform: scale(0.97); }
            body { padding-bottom: 64px; }
        }
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
                {{-- Language switcher (hidden on mobile, shown in bottom nav instead) --}}
                <div class="hidden sm:flex items-center gap-1.5 bg-white/5 rounded-lg px-2 py-1.5 border border-white/10">
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

    {{-- Bottom navigation (mobile only) --}}
    <nav class="bottom-nav sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-black/95 backdrop-blur border-t border-white/10 flex">
        @php
            $currentType = request('type', 'all');
            $isHome   = request()->routeIs('catalog.index') && !request('type');
            $isMovies = request('type') === 'movies';
            $isSeries = request('type') === 'series' || request()->routeIs('catalog.serie');
        @endphp
        <a href="{{ route('catalog.index') }}"
           class="flex-1 flex flex-col items-center py-3 gap-1 transition {{ $isHome ? 'text-red-500' : 'text-gray-500' }}">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
            <span class="text-xs font-medium">{{ __('nav.home') }}</span>
        </a>
        <a href="{{ route('catalog.index', ['type' => 'movies']) }}"
           class="flex-1 flex flex-col items-center py-3 gap-1 transition {{ $isMovies ? 'text-red-500' : 'text-gray-500' }}">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18 4l2 4h-3l-2-4h-2l2 4h-3l-2-4H8l2 4H7L5 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4h-4z"/></svg>
            <span class="text-xs font-medium">{{ __('nav.movies') }}</span>
        </a>
        <a href="{{ route('catalog.index', ['type' => 'series']) }}"
           class="flex-1 flex flex-col items-center py-3 gap-1 transition {{ $isSeries ? 'text-red-500' : 'text-gray-500' }}">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>
            <span class="text-xs font-medium">{{ __('nav.series') }}</span>
        </a>
        <div class="flex-1 flex flex-col items-center py-3 gap-1">
            <button onclick="document.getElementById('lang-modal').classList.remove('hidden')"
                class="flex flex-col items-center gap-1 text-gray-500">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg>
                <span class="text-xs font-medium">Idioma</span>
            </button>
        </div>
    </nav>

    {{-- Language modal (mobile) --}}
    <div id="lang-modal" class="hidden fixed inset-0 z-[9999] flex items-end sm:hidden" style="background:rgba(0,0,0,0.7);">
        <div class="w-full bg-gray-900 rounded-t-2xl p-6 border-t border-white/10">
            <h3 class="text-white font-bold mb-4 text-center">Idioma</h3>
            <div class="flex justify-center gap-6">
                @foreach(['pt' => ['flag' => 'pt', 'label' => 'Português'], 'es' => ['flag' => 'es', 'label' => 'Español'], 'en' => ['flag' => 'gb', 'label' => 'English']] as $lang => $info)
                <a href="{{ route('locale.switch', $lang) }}"
                   class="flex flex-col items-center gap-2 p-3 rounded-xl {{ app()->getLocale() === $lang ? 'bg-red-600' : 'bg-white/5' }}">
                    <img src="https://flagcdn.com/40x30/{{ $info['flag'] }}.png" width="40" height="30" class="rounded">
                    <span class="text-xs text-white">{{ $info['label'] }}</span>
                </a>
                @endforeach
            </div>
            <button onclick="document.getElementById('lang-modal').classList.add('hidden')"
                class="w-full mt-4 py-3 text-gray-400 text-sm">Cancelar</button>
        </div>
    </div>

    <script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
    @stack('scripts')

    <script>
        // Service Worker (PWA)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }

        // Navbar scroll
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });

        // Close language modal on backdrop click
        document.getElementById('lang-modal')?.addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });
    </script>
</body>
</html>
