<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — MilagrosTV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter','sans-serif'] } } } }</script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0d0d0d; }
        .nav-link { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:10px; font-size:14px; font-weight:500; transition:all .15s; color:#9ca3af; }
        .nav-link:hover { background:rgba(255,255,255,.06); color:#fff; }
        .nav-link.active { background:#dc2626; color:#fff; }
        .nav-badge { margin-left:auto; background:rgba(255,255,255,.1); color:#d1d5db; font-size:11px; padding:1px 7px; border-radius:20px; }
        #sidebar { transition: transform .25s cubic-bezier(.4,0,.2,1); }
        @media (max-width: 767px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="text-white min-h-screen flex">

    {{-- Sidebar overlay (mobile) --}}
    <div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/60 z-30 md:hidden" onclick="closeSidebar()"></div>

    {{-- Sidebar --}}
    <aside id="sidebar" class="w-56 flex-shrink-0 bg-gray-900 border-r border-white/[.08] flex flex-col fixed top-0 left-0 h-screen z-40">
        <div class="px-5 py-5 border-b border-white/[.08] flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center font-black text-sm">M</div>
                <div>
                    <p class="font-bold text-sm leading-none">MilagrosTV</p>
                    <p class="text-[11px] text-gray-500 mt-0.5">Administração</p>
                </div>
            </div>
            <button onclick="closeSidebar()" class="md:hidden text-gray-500 hover:text-white p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
            <p class="text-[10px] text-gray-600 font-semibold uppercase tracking-widest px-3 pt-3 pb-1.5">Conteúdo</p>
            <a href="{{ route('admin.dashboard') }}" onclick="closeSidebar()" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke-width="2"/></svg>
                Dashboard
            </a>
            <a href="{{ route('admin.discover.index') }}" onclick="closeSidebar()" class="nav-link {{ request()->routeIs('admin.discover.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Descobrir
            </a>
            <a href="{{ route('admin.movies.index') }}" onclick="closeSidebar()" class="nav-link {{ request()->routeIs('admin.movies.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                Filmes
                <span class="nav-badge">{{ \App\Models\Movie::count() }}</span>
            </a>
            <a href="{{ route('admin.series.index') }}" onclick="closeSidebar()" class="nav-link {{ request()->routeIs('admin.series.*') || request()->routeIs('admin.episodes.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Séries
                <span class="nav-badge">{{ \App\Models\Serie::count() }}</span>
            </a>

            @php $pendingRequests = \App\Models\ContentRequest::where('status','pending')->count(); @endphp
            <a href="{{ route('admin.content-requests.index') }}" onclick="closeSidebar()" class="nav-link {{ request()->routeIs('admin.content-requests.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Pedidos
                @if($pendingRequests > 0)
                <span class="nav-badge" style="background:#dc2626;color:#fff;">{{ $pendingRequests }}</span>
                @endif
            </a>

            <p class="text-[10px] text-gray-600 font-semibold uppercase tracking-widest px-3 pt-4 pb-1.5">Pessoas</p>
            <a href="{{ route('admin.users.index') }}" onclick="closeSidebar()" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Utilizadores
                <span class="nav-badge">{{ \App\Models\User::count() }}</span>
            </a>

            <p class="text-[10px] text-gray-600 font-semibold uppercase tracking-widest px-3 pt-4 pb-1.5">Sistema</p>
            <a href="{{ route('admin.monitor.index') }}" onclick="closeSidebar()" class="nav-link {{ request()->routeIs('admin.monitor.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                Monitor
            </a>
            <a href="{{ route('admin.logs.index') }}" onclick="closeSidebar()" class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Logs
            </a>
            <a href="{{ route('admin.storage.index') }}" onclick="closeSidebar()" class="nav-link {{ request()->routeIs('admin.storage.*') ? 'active' : '' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7c0-2 1-3 3-3h10c2 0 3 1 3 3M4 7h16M9 11h6"/></svg>
                Espaço
            </a>
        </nav>

        <div class="p-3 border-t border-white/[.08] space-y-0.5">
            <a href="{{ route('catalog.index') }}" target="_blank" class="nav-link">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Ver Site
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link w-full text-left text-red-400 hover:text-red-300">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Sair
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex-1 md:ml-56 flex flex-col min-h-screen">
        <header class="sticky top-0 z-30 bg-gray-900/90 backdrop-blur border-b border-white/[.08] px-4 md:px-8 py-4 flex items-center justify-between">
            {{-- Hamburger (mobile only) --}}
            <button onclick="openSidebar()" class="md:hidden text-gray-400 hover:text-white mr-3 p-1 -ml-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="font-semibold text-sm md:text-base truncate">@yield('title', 'Dashboard')</h1>
            <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                <div class="w-7 h-7 rounded-full bg-red-600 flex items-center justify-center text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span class="text-sm text-gray-300 hidden sm:block max-w-[100px] truncate">{{ auth()->user()->name }}</span>
            </div>
        </header>

        <main class="flex-1 px-4 md:px-8 py-6 md:py-8">
            @if(session('success'))
                <div class="mb-6 bg-green-900/40 border border-green-700/40 text-green-300 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="mb-6 bg-yellow-900/40 border border-yellow-700/40 text-yellow-300 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
                    ⚠️ {{ session('warning') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-900/40 border border-red-700/40 text-red-300 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>
            @endif
            @yield('content')
        </main>
    </div>

    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('sidebar-overlay').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.add('hidden');
            document.body.style.overflow = '';
        }
    </script>
    @stack('scripts')
</body>
</html>
