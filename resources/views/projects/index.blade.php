@extends('layouts.app')

@section('title', 'Proyectos')
@section('page_title', 'Proyectos')

@section('content')
@php
    $projectCollection = $projects->getCollection();
    $activeOnPage = $projectCollection->where('status', 'active')->count();
    $mpsfpProject = $projectCollection->firstWhere('slug', 'mpsfp');
@endphp

<div class="space-y-8">
    <section class="mpsfp-shell p-6 lg:p-8">
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium text-secondary">
                        <span>Dashboard</span>
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                        <span class="font-bold text-primary">Proyectos</span>
                    </div>
                    <div class="mt-3">
                        <h2 class="font-headline text-3xl font-extrabold tracking-tight text-[#1b1c1c]">Vista general de proyectos</h2>
                        <p class="mt-2 max-w-4xl text-sm leading-6 text-secondary">Aquí organizas los proyectos del ecosistema Musical Princesa y entras a sus espacios operativos sin cambiar de lenguaje visual ni de navegación.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @can('projects.create')
                        <a href="{{ route('projects.create') }}" class="btn-primary">Nuevo proyecto</a>
                    @endcan
                </div>
            </div>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="mpsfp-kpi">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Total proyectos</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($projects->total()) }}</h3>
                        <span class="material-symbols-outlined text-primary-container opacity-40">folder_open</span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Activos en pantalla</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($activeOnPage) }}</h3>
                        <span class="material-symbols-outlined text-primary-container opacity-40">task_alt</span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Con API</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($projectCollection->where('has_api', true)->count()) }}</h3>
                        <span class="material-symbols-outlined text-primary-container opacity-40">api</span>
                    </div>
                </div>
                <div class="mpsfp-kpi">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.22em] text-secondary">Con app móvil</p>
                    <div class="flex items-end justify-between">
                        <h3 class="font-headline text-3xl font-extrabold text-[#1b1c1c]">{{ number_format($projectCollection->where('has_mobile_app', true)->count()) }}</h3>
                        <span class="material-symbols-outlined text-primary-container opacity-40">smartphone</span>
                    </div>
                </div>
            </section>
        </div>
    </section>

    <section class="mpsfp-soft-panel p-5 lg:p-6">
        <form method="GET" action="{{ route('projects.index') }}" class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="grid flex-1 gap-3 md:grid-cols-3">
                <label class="relative block">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary text-lg">search</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar proyecto..." class="w-full rounded-xl border-none bg-white py-3 pl-10 pr-4 text-sm focus:ring-2 focus:ring-primary/20">
                </label>
                <label class="relative block">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-secondary text-lg">tune</span>
                    <select name="status" class="w-full appearance-none rounded-xl border-none bg-white py-3 pl-10 pr-10 text-sm focus:ring-2 focus:ring-primary/20">
                        <option value="">Todos los estados</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="development" {{ request('status') === 'development' ? 'selected' : '' }}>Desarrollo</option>
                        <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Pausado</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archivado</option>
                    </select>
                    <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-secondary text-lg">expand_more</span>
                </label>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" class="btn-primary flex-1">Aplicar filtros</button>
                    <a href="{{ route('projects.index') }}" class="btn-secondary flex-1">Limpiar</a>
                </div>
            </div>
        </form>
    </section>

    @if ($mpsfpProject)
        <section class="mpsfp-panel overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 bg-primary/5 px-6 py-5">
                <div>
                    <h2 class="font-headline text-lg font-bold text-[#1b1c1c]">Módulo operativo destacado</h2>
                    <p class="mt-1 text-sm text-secondary">Entrada rápida al espacio de proveedores, catálogo y exportación.</p>
                </div>
                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary">Catálogo</span>
            </div>
            <div class="p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <h3 class="font-headline text-2xl font-extrabold tracking-tight text-[#1b1c1c]">{{ $mpsfpProject->name }}</h3>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-secondary">{{ $mpsfpProject->description ?: 'Gestión centralizada de proveedores, importaciones, normalización, categorías, catálogo maestro y exportación.' }}</p>
                    </div>
                    <div class="action-stack">
                        <a href="{{ route('projects.mpsfp.dashboard', $mpsfpProject) }}" class="btn-primary">Abrir módulo</a>
                        @can('projects.edit')
                            <a href="{{ route('projects.edit', $mpsfpProject) }}" class="btn-secondary">Editar proyecto</a>
                        @endcan
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        @forelse ($projects as $project)
            <article class="mpsfp-panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <span class="inline-block h-4 w-4 flex-shrink-0 rounded-full shadow-sm" style="background-color: {{ $project->color ?: '#d1d5db' }}"></span>
                            <h3 class="truncate font-headline text-lg font-bold text-[#1b1c1c]">{{ $project->name }}</h3>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="mpsfp-pill {{ $project->status === 'active' ? 'status-green' : ($project->status === 'development' ? 'status-blue' : ($project->status === 'paused' ? 'status-amber' : 'status-gray')) }}">{{ $project->status }}</span>
                            @if ($project->has_api)
                                <span class="mpsfp-pill status-blue">API</span>
                            @endif
                            @if ($project->has_mobile_app)
                                <span class="mpsfp-pill status-green">App móvil</span>
                            @endif
                        </div>
                    </div>
                    @if ($project->slug === 'mpsfp')
                        <span class="rounded-full bg-primary/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-primary">Operativo</span>
                    @endif
                </div>

                <p class="mt-4 min-h-[3.5rem] text-sm leading-6 text-secondary">{{ $project->description ?: 'Sin descripción configurada todavía.' }}</p>

                <dl class="mt-5 grid grid-cols-1 gap-3 rounded-2xl bg-surface-container-low p-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-[0.18em] text-secondary">Framework</dt>
                        <dd class="mt-1 font-medium text-[#1b1c1c]">{{ $project->framework ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold uppercase tracking-[0.18em] text-secondary">Sincronización</dt>
                        <dd class="mt-1 font-medium text-[#1b1c1c]">{{ $project->sync_status ?: '—' }}</dd>
                    </div>
                </dl>

                <div class="action-stack mt-5">
                    @if ($project->slug === 'mpsfp')
                        <a href="{{ route('projects.mpsfp.dashboard', $project) }}" class="btn-primary">Abrir módulo</a>
                    @else
                        <a href="{{ route('projects.show', $project) }}" class="btn-secondary">Ver ficha</a>
                    @endif
                    @can('projects.edit')
                        <a href="{{ route('projects.edit', $project) }}" class="btn-link">Editar</a>
                    @endcan
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl bg-white p-10 text-center text-sm text-secondary shadow-[0_12px_32px_-4px_rgba(27,28,28,0.06)]">
                No hay proyectos disponibles con los filtros actuales.
            </div>
        @endforelse
    </section>

    <div class="flex justify-center">{{ $projects->withQueryString()->links() }}</div>
</div>
@endsection
