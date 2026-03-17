<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Musical Princesa - @yield('title', 'Panel')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { brand: '#E6007E', neutral: '#555555' } } }
        }
    </script>
    <style>
        .sidebar-transition { transition: transform 0.25s ease, width 0.25s ease, margin 0.25s ease, opacity 0.2s ease; }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
        .animate-slide-up { animation: slideUp 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.05); }
        .btn-primary { @apply inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white bg-[#E6007E] shadow-lg shadow-[#E6007E]/25 transition hover:bg-[#d1006f] hover:shadow-[#E6007E]/30 focus:outline-none focus:ring-2 focus:ring-[#E6007E] focus:ring-offset-2; }
        .btn-secondary { @apply inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#E6007E] focus:ring-offset-2; }
        .btn-link { @apply text-sm text-[#E6007E] hover:underline; }
        .btn-link-muted { @apply text-sm text-gray-500 hover:text-gray-700 hover:underline; }
        input[type="checkbox"]:checked { background-color: #E6007E; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100 text-[#555555] min-h-screen antialiased">

<div class="flex min-h-screen" x-data="{ sidebarOpen: false, sidebarCollapsed: false }" x-cloak>
    <!-- Overlay móvil -->
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:leave="transition-opacity ease-in duration-150"
         class="fixed inset-0 z-20 bg-black/50 lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

    <!-- Sidebar: móvil oculto por defecto; desktop se oculta con sidebarCollapsed -->
    <aside :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'lg:-translate-x-full lg:absolute' : 'lg:translate-x-0 lg:static'
    ]"
           class="sidebar-transition fixed inset-y-0 left-0 z-30 w-64 flex-shrink-0 bg-white border-r border-gray-200 shadow-sm lg:static">
        <div class="flex h-full w-64 flex-col">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4">
                <div class="flex items-center min-w-0">
                    <img src="/assets/logo.png" alt="Musical Princesa" class="h-10 w-auto object-contain">
                </div>
                <button type="button" class="lg:hidden flex-shrink-0 rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700" @click="sidebarOpen = false" aria-label="Cerrar menú">✕</button>
            </div>
            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
                <a href="{{ route('dashboard') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-[#E6007E] text-white shadow-md' : 'text-gray-700 hover:bg-gray-100' }}">Dashboard</a>
                @can('projects.view')
                    <a href="{{ route('projects.index') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('projects.*') ? 'bg-[#E6007E] text-white shadow-md' : 'text-gray-700 hover:bg-gray-100' }}">Proyectos</a>
                @endcan
                @can('users.view')
                    <a href="{{ route('users.index') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('users.*') ? 'bg-[#E6007E] text-white shadow-md' : 'text-gray-700 hover:bg-gray-100' }}">Usuarios</a>
                @endcan
                @can('mobile_integrations.view')
                    <a href="{{ route('mobile-integrations.index') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('mobile-integrations.*') ? 'bg-[#E6007E] text-white shadow-md' : 'text-gray-700 hover:bg-gray-100' }}">Integraciones móviles</a>
                @endcan
                @can('logs.view')
                    <a href="{{ route('activity-logs.index') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('activity-logs.*') ? 'bg-[#E6007E] text-white shadow-md' : 'text-gray-700 hover:bg-gray-100' }}">Logs</a>
                @endcan
            </nav>
        </div>
    </aside>

    <div class="flex flex-1 flex-col min-w-0 transition-all duration-250" :class="sidebarCollapsed ? 'lg:ml-0' : ''">
        <!-- Topbar -->
        <header class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white/95 backdrop-blur shadow-sm">
            <div class="flex items-center gap-2 px-4 py-3">
                <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition text-sm" aria-label="Menú">
                    <span class="lg:hidden" @click="sidebarOpen = true">☰</span>
                    <span class="hidden lg:inline" @click="sidebarCollapsed = !sidebarCollapsed" x-text="sidebarCollapsed ? '☰' : '‹'" title="Ocultar menú">☰</span>
                </button>
                <h1 class="text-lg font-semibold text-[#555555]">@yield('page_title', 'Panel')</h1>
            </div>
            <div class="flex items-center gap-3 px-4 py-3">
                <div class="hidden sm:block text-right">
                    <div class="text-sm font-medium text-[#555555]">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-500">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-primary text-xs py-2 px-3">Cerrar sesión</button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-6">
            @if (session('status'))
                <div class="mb-4 animate-fade-in rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sidebarCollapsed', () => ({ collapsed: false }));
    });
</script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@stack('scripts')
</body>
</html>
