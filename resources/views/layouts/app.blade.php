<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Musical Princesa - @yield('title', 'Panel')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&family=Material+Symbols+Outlined:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#E6007E',
                        neutral: '#555555',
                        mpbg: '#fbf9f8',
                        mpsurface: '#ffffff',
                        mpmuted: '#f5f3f3',
                        mpborder: '#e4e2e2',
                        mptext: '#1b1c1c',
                        mpsubtle: '#646464',
                        mpgreen: '#008a18',
                        mpblue: '#1d4ed8',
                        mpamber: '#b45309',
                        mpred: '#ba1a1a',
                    },
                    fontFamily: {
                        body: ['Inter', 'sans-serif'],
                        headline: ['Manrope', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --mp-primary: #E6007E;
            --mp-primary-dark: #c7006d;
            --mp-bg: #fbf9f8;
            --mp-surface: #ffffff;
            --mp-surface-soft: #f5f3f3;
            --mp-border: #e4e2e2;
            --mp-text: #1b1c1c;
            --mp-subtle: #646464;
        }
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Inter', sans-serif;
            background: #fbf9f8;
        }
        .font-headline { font-family: 'Manrope', sans-serif; }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined', sans-serif;
            font-weight: normal;
            font-style: normal;
            font-size: 20px;
            line-height: 1;
            display: inline-block;
            text-transform: none;
            letter-spacing: normal;
            word-wrap: normal;
            white-space: nowrap;
            direction: ltr;
            -webkit-font-smoothing: antialiased;
            font-variation-settings: 'FILL' 0, 'wght' 450, 'GRAD' 0, 'opsz' 24;
        }
        .sidebar-transition { transition: transform 0.25s ease, width 0.25s ease, margin 0.25s ease, opacity 0.2s ease; }
        .animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
        .animate-slide-up { animation: slideUp 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.05); }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            padding: 0.7rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(135deg, #b90064 0%, #e6007e 100%);
            box-shadow: 0 12px 32px -10px rgba(185, 0, 100, 0.28);
            transition: opacity 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        .btn-primary:hover {
            opacity: 0.96;
            box-shadow: 0 14px 34px -12px rgba(185, 0, 100, 0.34);
            transform: translateY(-1px);
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            border: none;
            background: #e4e2e2;
            padding: 0.7rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #1b1c1c;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }
        .btn-secondary:hover {
            background: #dbdad9;
            color: #1b1c1c;
            transform: translateY(-1px);
        }
        .btn-link {
            font-size: 0.875rem;
            font-weight: 700;
            color: #b90064;
            text-decoration: none;
        }
        .btn-link:hover { text-decoration: underline; }
        .btn-link-muted {
            font-size: 0.875rem;
            color: #6b7280;
            text-decoration: none;
        }
        .btn-link-muted:hover {
            color: #374151;
            text-decoration: underline;
        }
        .btn-disabled {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            border: none;
            background: #f1efef;
            padding: 0.7rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #9ca3af;
            cursor: not-allowed;
        }
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            border-radius: 1rem;
            border: 1px solid transparent;
            padding: 1rem 1.125rem;
            font-size: 0.875rem;
            box-shadow: 0 10px 20px -16px rgba(15, 23, 42, 0.35);
        }
        .alert::before {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .alert-success {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }
        .alert-success::before {
            content: "OK";
            background: #dcfce7;
            color: #166534;
        }
        .alert-error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }
        .alert-error::before {
            content: "!";
            background: #fee2e2;
            color: #b91c1c;
        }
        .alert-warn {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }
        .alert-warn::before {
            content: "!";
            background: #fef3c7;
            color: #92400e;
        }
        .alert-info {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }
        .alert-info::before {
            content: "i";
            background: #dbeafe;
            color: #1d4ed8;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 9999px;
            padding: 0.25rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .status-badge::before {
            content: "";
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 9999px;
            background: currentColor;
            opacity: 0.8;
        }
        .status-pink { background: rgba(230, 0, 126, 0.10); color: #E6007E; }
        /* Success / OK */
        .status-green { background: #008a18; color: #ffffff; }
        /* Blue is used for informational/scores; keep existing tonal mapping */
        .status-blue { background: #eff6ff; color: #1d4ed8; }
        /* Warning stays as a soft amber tint */
        .status-amber { background: #fffbeb; color: #b45309; }
        /* Error / Failed */
        .status-red { background: #ffdad6; color: #93000a; }
        /* Pending */
        .status-gray { background: #c8c6c6; color: #1b1c1c; }
        .mpsfp-shell {
            background: transparent;
            border-radius: 1.25rem;
            box-shadow: none;
        }
        .mpsfp-panel {
            background: rgba(255,255,255,0.96);
            border-radius: 1rem;
            box-shadow: 0 12px 32px -4px rgba(27, 28, 28, 0.06);
        }
        .mpsfp-soft-panel {
            background: #eae8e7;
            border-radius: 1rem;
        }
        .mpsfp-kpi {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            background: #ffffff;
            padding: 1.25rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .mpsfp-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px -10px rgba(27, 28, 28, 0.08);
        }
        .mpsfp-kpi::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 5rem;
            height: 5rem;
            background: radial-gradient(circle, rgba(230,0,126,0.06), transparent 70%);
            transform: translate(35%, -35%);
        }
        .mpsfp-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 9999px;
            padding: 0.375rem 0.8rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .mpsfp-pill::before {
            content: "";
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 9999px;
            background: currentColor;
            opacity: 0.75;
        }
        .mpsfp-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            border-radius: 0.5rem;
            padding: 0.8rem 1rem;
            font-size: 0.875rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .mpsfp-tab:hover { transform: translateY(-1px); }
        .mpsfp-subtab {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 9999px;
            padding: 0.55rem 0.9rem;
            font-size: 0.75rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .mpsfp-step {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.7rem;
            min-width: 5.5rem;
            z-index: 1;
        }
        .mpsfp-step-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 9999px;
            font-weight: 800;
            font-size: 0.95rem;
            border: 2px solid rgba(228, 226, 226, 0.95);
            background: #fff;
        }
        .mpsfp-data-table th {
            background: transparent;
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #7a7a7a;
        }
        .mpsfp-data-table {
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }
        .mpsfp-data-table tbody td {
            background: #ffffff;
        }
        .mpsfp-data-table tbody tr:nth-child(even) td {
            background: #f5f3f3;
        }
        .mpsfp-data-table tbody td:first-child {
            border-top-left-radius: 0.75rem;
            border-bottom-left-radius: 0.75rem;
        }
        .mpsfp-data-table tbody td:last-child {
            border-top-right-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
        }
        .mpsfp-data-table tbody tr:hover td {
            background: #eae8e7;
        }
        input[type="checkbox"]:checked { background-color: #E6007E; }
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 0.75rem;
            padding: 0.75rem 0.875rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #555555;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }
        .sidebar-nav-link:hover {
            background: #eae8e7;
            color: #1b1c1c;
        }
        .sidebar-nav-link.is-active {
            background: #ffffff;
            color: #1b1c1c;
            box-shadow: 0 10px 24px -16px rgba(27, 28, 28, 0.25);
        }
        .sidebar-sub-link {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            border-radius: 0.75rem;
            padding: 0.625rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #555555;
            transition: color 0.2s ease, background-color 0.2s ease;
        }
        .sidebar-sub-link:hover {
            background: #efeded;
            color: #1b1c1c;
        }
        .sidebar-sub-link.is-active {
            color: #e6007e;
        }
        .sidebar-section-title {
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #8e6f77;
        }
        .action-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            width: 100%;
        }
        .action-stack > * {
            flex: 1 1 100%;
            min-width: 0;
        }
        .compact-toolbar {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .compact-toolbar > * {
            min-width: 0;
        }
        .table-footer {
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }
        .table-footer > :last-child {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        @media (min-width: 640px) {
            .action-stack {
                width: auto;
            }
            .action-stack > * {
                flex: 0 1 auto;
                width: auto;
            }
        }
        @media (min-width: 768px) {
            .compact-toolbar {
                align-items: center;
                flex-direction: row;
                justify-content: space-between;
            }
            .table-footer {
                align-items: center;
                flex-direction: row;
                justify-content: space-between;
            }
            .table-footer > :last-child {
                justify-content: flex-end;
                width: auto;
            }
        }
        @media (max-width: 639px) {
            .btn-primary,
            .btn-secondary,
            .btn-disabled {
                width: 100%;
            }
            .mpsfp-shell,
            .mpsfp-panel,
            .mpsfp-soft-panel,
            .mpsfp-kpi {
                border-radius: 0.875rem;
            }
            .mpsfp-tab,
            .mpsfp-subtab {
                white-space: nowrap;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#fbf9f8] text-[#555555] min-h-screen antialiased">

@php
    $currentProjectParam = request()->route('project');
    $currentProject = $currentProjectParam instanceof \App\Models\Project ? $currentProjectParam : null;
    $currentImport = request()->route('import');
    $onDashboard = request()->routeIs('dashboard');
    $onProjects = request()->routeIs('projects.*');
    $onMpsfp = request()->routeIs('projects.mpsfp.*') || request()->routeIs('projects.mpfsp.*');
    $projectsMenuOpen = $onProjects || $onMpsfp;
    $workflowMenuOpen = $onMpsfp;
    $mappingUrl = $currentProject
        ? ($currentImport
            ? route('projects.mpsfp.imports.mapping', [$currentProject, $currentImport])
            : route('projects.mpsfp.imports.index', $currentProject))
        : null;
@endphp

<div class="flex min-h-screen" x-data="{ sidebarOpen: false, sidebarCollapsed: false, projectsMenuOpen: {{ $projectsMenuOpen ? 'true' : 'false' }}, workflowMenuOpen: {{ $workflowMenuOpen ? 'true' : 'false' }} }" x-cloak>
    <!-- Overlay móvil -->
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-out duration-200" x-transition:leave="transition-opacity ease-in duration-150"
         class="fixed inset-0 z-20 bg-black/50 lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

    <!-- Sidebar: móvil oculto por defecto; desktop se oculta con sidebarCollapsed -->
    <aside :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'lg:-translate-x-full lg:absolute' : 'lg:translate-x-0 lg:static'
    ]"
           class="sidebar-transition fixed inset-y-0 left-0 z-30 w-64 flex-shrink-0 bg-[#f5f3f3] shadow-sm lg:static">
        <div class="flex h-full w-64 flex-col">
            <div class="px-5 py-5">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <img src="/assets/logo.png" alt="Musical Princesa" class="h-11 w-auto object-contain">
                    </div>
                    <button type="button" class="lg:hidden flex-shrink-0 rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700" @click="sidebarOpen = false" aria-label="Cerrar menú">✕</button>
                </div>
            </div>
            <nav class="flex-1 space-y-5 overflow-y-auto px-4 pb-5">
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="sidebar-nav-link {{ $onDashboard ? 'is-active' : '' }}">
                        <span class="material-symbols-outlined {{ $onDashboard ? 'text-[#e6007e]' : 'text-[#646464]' }}">space_dashboard</span>
                        <span>Dashboard</span>
                    </a>

                    @can('viewAny', \App\Models\Project::class)
                        <div class="rounded-2xl bg-[#efeded]/70 p-1.5">
                            <button type="button" class="sidebar-nav-link w-full justify-between {{ $onProjects || $onMpsfp ? 'is-active' : '' }}" @click="projectsMenuOpen = !projectsMenuOpen">
                                <span class="flex items-center gap-3">
                                    <span class="material-symbols-outlined {{ $onProjects || $onMpsfp ? 'text-[#e6007e]' : 'text-[#646464]' }}">folder_open</span>
                                    <span>Proyectos</span>
                                </span>
                                <span class="material-symbols-outlined text-lg text-[#646464]" x-text="projectsMenuOpen ? 'expand_more' : 'chevron_right'"></span>
                            </button>

                            <div x-show="projectsMenuOpen" x-transition class="space-y-1 px-2 pb-2 pt-1" style="display: none;">
                                <a href="{{ route('projects.index') }}" class="sidebar-sub-link {{ request()->routeIs('projects.index') ? 'is-active' : '' }}">
                                    <span class="material-symbols-outlined text-[18px]">grid_view</span>
                                    <span>Todos los proyectos</span>
                                </a>

                                @if ($currentProject)
                                    <a href="{{ route('projects.show', $currentProject) }}" class="sidebar-sub-link {{ request()->routeIs('projects.show') || $onMpsfp ? 'is-active' : '' }}">
                                        <span class="material-symbols-outlined text-[18px]">workspaces</span>
                                        <span class="truncate">{{ $currentProject->name }}</span>
                                    </a>

                                    @if ($onMpsfp)
                                        <div class="mt-2 rounded-2xl bg-[#eae8e7] px-3 py-3">
                                            <button type="button" class="flex w-full items-center justify-between text-left text-sm font-bold tracking-tight text-[#e6007e]" @click="workflowMenuOpen = !workflowMenuOpen">
                                                <span class="flex items-center gap-3">
                                                    <span class="material-symbols-outlined text-xl">folder_open</span>
                                                    <span>MPSFP Workflow</span>
                                                </span>
                                                <span class="material-symbols-outlined text-lg" x-text="workflowMenuOpen ? 'expand_more' : 'chevron_right'"></span>
                                            </button>

                                            <div class="mt-2 space-y-1 pl-1" x-show="workflowMenuOpen" x-transition style="display: none;">
                                                <a href="{{ route('projects.mpsfp.suppliers.index', $currentProject) }}" class="sidebar-sub-link {{ request()->routeIs('projects.mpsfp.suppliers.*') ? 'is-active' : '' }}">
                                                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                                                    <span>Proveedores</span>
                                                </a>
                                                <a href="{{ route('projects.mpsfp.imports.index', $currentProject) }}" class="sidebar-sub-link {{ request()->routeIs('projects.mpsfp.imports.index') || request()->routeIs('projects.mpsfp.imports.create') || request()->routeIs('projects.mpsfp.imports.show') || request()->routeIs('projects.mpsfp.imports.preview') ? 'is-active' : '' }}">
                                                    <span class="material-symbols-outlined text-[18px]">upload_file</span>
                                                    <span>Importaciones</span>
                                                </a>
                                                @if ($mappingUrl)
                                                    <a href="{{ $mappingUrl }}" class="sidebar-sub-link {{ request()->routeIs('projects.mpsfp.imports.mapping*') ? 'is-active' : '' }}">
                                                        <span class="material-symbols-outlined text-[18px]">account_tree</span>
                                                        <span>Mapeo</span>
                                                    </a>
                                                @endif
                                                <a href="{{ route('projects.mpsfp.normalized.index', $currentProject) }}" class="sidebar-sub-link {{ request()->routeIs('projects.mpsfp.normalized.*') ? 'is-active' : '' }}">
                                                    <span class="material-symbols-outlined text-[18px]">rule</span>
                                                    <span>Normalizados</span>
                                                </a>
                                                <a href="{{ route('projects.mpsfp.master.index', $currentProject) }}" class="sidebar-sub-link {{ request()->routeIs('projects.mpsfp.master.*') ? 'is-active' : '' }}">
                                                    <span class="material-symbols-outlined text-[18px]">database</span>
                                                    <span>Maestro</span>
                                                </a>
                                                <a href="{{ route('projects.mpsfp.export.index', $currentProject) }}" class="sidebar-sub-link {{ request()->routeIs('projects.mpsfp.export.*') ? 'is-active' : '' }}">
                                                    <span class="material-symbols-outlined text-[18px]">ios_share</span>
                                                    <span>Exportación</span>
                                                </a>
                                            </div>
                                        </div>

                                        <a href="{{ route('projects.mpsfp.cross-suppliers.index', $currentProject) }}" class="sidebar-sub-link mt-2 {{ request()->routeIs('projects.mpsfp.cross-suppliers.*') ? 'is-active' : '' }}">
                                            <span class="material-symbols-outlined text-[18px]">compare_arrows</span>
                                            <span>Cruce</span>
                                        </a>
                                        <a href="{{ route('projects.mpsfp.categories.review', $currentProject) }}" class="sidebar-sub-link {{ request()->routeIs('projects.mpsfp.categories.*') ? 'is-active' : '' }}">
                                            <span class="material-symbols-outlined text-[18px]">category</span>
                                            <span>Categorías</span>
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endcan
                </div>

                <div class="space-y-1">
                    <div class="sidebar-section-title px-3">Administración</div>
                    @can('users.view')
                        <a href="{{ route('users.index') }}" class="sidebar-nav-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('users.*') ? 'text-[#e6007e]' : 'text-[#646464]' }}">groups</span>
                            <span>Usuarios</span>
                        </a>
                    @endcan
                    @can('mobile_integrations.view')
                        <a href="{{ route('mobile-integrations.index') }}" class="sidebar-nav-link {{ request()->routeIs('mobile-integrations.*') ? 'is-active' : '' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('mobile-integrations.*') ? 'text-[#e6007e]' : 'text-[#646464]' }}">smartphone</span>
                            <span>Integraciones móviles</span>
                        </a>
                    @endcan
                    @can('logs.view')
                        <a href="{{ route('activity-logs.index') }}" class="sidebar-nav-link {{ request()->routeIs('activity-logs.*') ? 'is-active' : '' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('activity-logs.*') ? 'text-[#e6007e]' : 'text-[#646464]' }}">history</span>
                            <span>Logs</span>
                        </a>
                    @endcan
                    @can('settings.view')
                        <a href="{{ route('settings.index') }}" class="sidebar-nav-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('settings.*') ? 'text-[#e6007e]' : 'text-[#646464]' }}">settings</span>
                            <span>Configuración</span>
                        </a>
                    @endcan
                </div>
            </nav>

            <div class="space-y-3 px-4 pb-5">
                @if ($currentProject && $onMpsfp)
                    <a href="{{ route('projects.show', $currentProject) }}" class="btn-secondary w-full">Admin General</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-primary w-full">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex flex-1 flex-col min-w-0 transition-all duration-250" :class="sidebarCollapsed ? 'lg:ml-0' : ''">
        <!-- Topbar -->
        <header class="sticky top-0 z-10 flex flex-wrap items-center justify-between gap-2 bg-[#fbf9f8]/85 backdrop-blur">
            <div class="flex min-w-0 flex-1 items-center gap-2 px-4 py-3">
                <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition text-sm" aria-label="Menú">
                    <span class="lg:hidden" @click="sidebarOpen = true">☰</span>
                    <span class="hidden lg:inline" @click="sidebarCollapsed = !sidebarCollapsed" x-text="sidebarCollapsed ? '☰' : '‹'" title="Ocultar menú">☰</span>
                </button>
                <h1 class="truncate text-lg font-semibold text-[#555555]">@yield('page_title', 'Panel')</h1>
            </div>
            <div class="ml-auto flex flex-wrap items-center justify-end gap-3 px-4 py-3">
                <div class="hidden sm:block text-right">
                    <div class="text-sm font-medium text-[#555555]">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-500">{{ auth()->user()->email }}</div>
                </div>
                @can('logs.view')
                    <a href="{{ route('activity-logs.index', ['user_id' => auth()->id()]) }}" class="hidden md:inline-flex btn-secondary text-xs py-2 px-3">Mi actividad</a>
                @endcan
                @can('users.view')
                    <a href="{{ route('users.show', auth()->user()) }}" class="hidden md:inline-flex btn-secondary text-xs py-2 px-3">Mi perfil</a>
                @endcan
            </div>
        </header>

        <main class="flex-1 p-4 md:p-6">
            @if (session('status'))
                <div class="mb-4 animate-fade-in alert alert-success">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

@stack('pre_alpine_scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sidebarCollapsed', () => ({ collapsed: false }));
    });
</script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@stack('scripts')
</body>
</html>
