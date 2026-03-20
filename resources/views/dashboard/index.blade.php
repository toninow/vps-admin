@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
@php
    $mpsfpProject = $projects->firstWhere('slug', 'mpsfp');
@endphp

<div class="space-y-8">
    <section class="mpsfp-shell p-6 lg:p-8">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-secondary">
                        <span>Dashboard</span>
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                        <span class="font-bold text-primary">Panel general</span>
                    </div>
                    <div class="mt-3">
                        <h2 class="font-headline text-3xl font-extrabold tracking-tight text-[#1b1c1c]">Centro de control Musical Princesa</h2>
                        <p class="mt-2 max-w-4xl text-sm leading-6 text-secondary">Vista global del administrador: usuarios, proyectos, accesos recientes y entrada rápida a los módulos operativos. Desde aquí supervisas el sistema y entras a `MPSFP` cuando toca trabajar el catálogo.</p>
                    </div>
                </div>

                <div class="flex w-full flex-col gap-3 xl:w-auto xl:min-w-[28rem]">
                    <div class="relative w-full">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary text-lg">search</span>
                        <input type="text" placeholder="Buscar proyecto, usuario o actividad..." class="w-full rounded-full border-none bg-surface-container-low py-2.5 pl-10 pr-4 text-sm focus:bg-white focus:ring-2 focus:ring-primary/20">
                    </div>
                </div>
            </div>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @can('users.view')
                    <a href="{{ route('users.index') }}" class="mpsfp-kpi block text-[#555555] no-underline hover:text-[#555555]">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Usuarios</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($totalUsers) }}</h3>
                                <p class="mt-1 text-xs text-secondary">{{ number_format($activeUsers) }} activos</p>
                            </div>
                            <span class="material-symbols-outlined text-primary-container opacity-40">groups</span>
                        </div>
                    </a>
                @else
                    <div class="mpsfp-kpi">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Usuarios</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($totalUsers) }}</h3>
                                <p class="mt-1 text-xs text-secondary">{{ number_format($activeUsers) }} activos</p>
                            </div>
                            <span class="material-symbols-outlined text-primary-container opacity-40">groups</span>
                        </div>
                    </div>
                @endcan

                @can('projects.view')
                    <a href="{{ route('projects.index') }}" class="mpsfp-kpi block text-[#555555] no-underline hover:text-[#555555]">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Proyectos</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($totalProjects) }}</h3>
                                <p class="mt-1 text-xs text-secondary">{{ number_format($activeProjects) }} activos</p>
                            </div>
                            <span class="material-symbols-outlined text-primary-container opacity-40">folder_open</span>
                        </div>
                    </a>
                @else
                    <div class="mpsfp-kpi">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Proyectos</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($totalProjects) }}</h3>
                                <p class="mt-1 text-xs text-secondary">{{ number_format($activeProjects) }} activos</p>
                            </div>
                            <span class="material-symbols-outlined text-primary-container opacity-40">folder_open</span>
                        </div>
                    </div>
                @endcan

                @can('logs.view')
                    <a href="{{ route('activity-logs.index') }}" class="mpsfp-kpi block text-[#555555] no-underline hover:text-[#555555]">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Accesos</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($recentLogins->count()) }}</h3>
                                <p class="mt-1 text-xs text-secondary">Últimos logins</p>
                            </div>
                            <span class="material-symbols-outlined text-primary-container opacity-40">login</span>
                        </div>
                    </a>
                @else
                    <div class="mpsfp-kpi">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Accesos</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($recentLogins->count()) }}</h3>
                                <p class="mt-1 text-xs text-secondary">Últimos logins</p>
                            </div>
                            <span class="material-symbols-outlined text-primary-container opacity-40">login</span>
                        </div>
                    </div>
                @endcan

                @can('logs.view')
                    <a href="{{ route('activity-logs.index') }}" class="mpsfp-kpi block text-[#555555] no-underline hover:text-[#555555]">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Actividad</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($recentActivities->count()) }}</h3>
                                <p class="mt-1 text-xs text-secondary">Últimas acciones</p>
                            </div>
                            <span class="material-symbols-outlined text-primary-container opacity-40">history</span>
                        </div>
                    </a>
                @else
                    <div class="mpsfp-kpi">
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Actividad</p>
                        <div class="flex items-end justify-between">
                            <div>
                                <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($recentActivities->count()) }}</h3>
                                <p class="mt-1 text-xs text-secondary">Últimas acciones</p>
                            </div>
                            <span class="material-symbols-outlined text-primary-container opacity-40">history</span>
                        </div>
                    </div>
                @endcan
            </section>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            @if ($mpsfpProject)
                <section class="mpsfp-panel overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-container bg-primary/5 px-6 py-5">
                        <div>
                            <h2 class="font-headline text-lg font-bold text-[#1b1c1c]">Submódulo destacado</h2>
                            <p class="mt-1 text-sm text-secondary">Acceso directo al espacio de trabajo de proveedores y catálogo.</p>
                        </div>
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">MPSFP</span>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <h3 class="font-headline text-2xl font-extrabold tracking-tight text-[#1b1c1c]">{{ $mpsfpProject->name }}</h3>
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-secondary">Gestión de proveedores, importaciones, normalización, categorías sugeridas, cruce entre proveedores, catálogo maestro y exportación.</p>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="mpsfp-pill {{ $mpsfpProject->status === 'active' ? 'status-green' : 'status-gray' }}">{{ $mpsfpProject->status }}</span>
                                    <span class="mpsfp-pill status-blue">{{ $mpsfpProject->framework ?? 'Laravel' }}</span>
                                </div>
                            </div>
                            <div class="action-stack">
                                <a href="{{ route('projects.show', $mpsfpProject) }}" class="btn-secondary">Ficha del proyecto</a>
                                <a href="{{ route('projects.mpsfp.dashboard', $mpsfpProject) }}" class="btn-primary">Entrar a MPSFP</a>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <section class="mpsfp-panel p-6">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="font-headline text-lg font-bold">Proyectos</h2>
                    @can('projects.view')
                        <a href="{{ route('projects.index') }}" class="text-xs font-bold text-primary hover:underline">Ver todos</a>
                    @endcan
                </div>

                @if ($projects->isEmpty())
                    <div class="rounded-xl bg-surface-container-low p-8 text-center text-sm text-secondary">
                        No hay proyectos asignados. Crea uno desde Proyectos.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($projects as $project)
                            <a href="{{ route('projects.show', $project) }}" class="group flex flex-wrap items-center justify-between gap-3 rounded-xl bg-surface-container-lowest p-4 no-underline transition-all hover:bg-surface-container-low hover:text-[#555555]">
                                <div class="flex items-center gap-3">
                                    @if ($project->color)
                                        <span class="h-4 w-4 flex-shrink-0 rounded-full shadow-sm" style="background-color: {{ $project->color }}"></span>
                                    @else
                                        <span class="h-4 w-4 flex-shrink-0 rounded-full bg-gray-300"></span>
                                    @endif
                                    <div>
                                        <span class="block font-medium text-[#555555]">{{ $project->name }}</span>
                                        <span class="mt-0.5 inline-flex rounded-lg bg-surface-container-high px-2 py-0.5 text-xs font-medium text-secondary">{{ $project->status }}</span>
                                    </div>
                                </div>
                                <span class="rounded-lg bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary transition-colors group-hover:bg-primary group-hover:text-white">Ver</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="space-y-8">
            <section class="mpsfp-panel p-6">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="font-headline text-lg font-bold">Actividad reciente</h2>
                    @can('logs.view')
                        <a href="{{ route('activity-logs.index') }}" class="text-xs font-bold text-primary hover:underline">Logs</a>
                    @endcan
                </div>
                @if ($recentActivities->isEmpty())
                    <p class="rounded-xl bg-surface-container-low p-6 text-center text-sm text-secondary">Sin actividad reciente.</p>
                @else
                    <div class="relative space-y-6 before:absolute before:bottom-2 before:left-[11px] before:top-2 before:w-0.5 before:bg-surface-container">
                        @foreach ($recentActivities as $log)
                            <div class="relative flex flex-col gap-1 pl-8">
                                <div class="absolute left-0 top-1 z-10 flex h-6 w-6 items-center justify-center rounded-full border-2 border-primary-container bg-surface-container-lowest">
                                    <div class="h-2 w-2 rounded-full bg-primary"></div>
                                </div>
                                <p class="text-xs font-bold">{{ $log->user?->name ?? 'Sistema' }}</p>
                                <p class="text-[11px] text-secondary">{{ Str::limit($log->description ?? $log->action, 70) }}</p>
                                <span class="text-[10px] text-secondary/60">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection
