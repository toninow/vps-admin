@extends('layouts.app')

@section('title', $project->name)
@section('page_title', $project->name)

@section('content')
<div class="space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-2">
                @if ($project->color)
                    <span class="inline-block h-6 w-6 rounded-full" style="background-color: {{ $project->color }}"></span>
                @endif
                <h2 class="text-lg font-semibold text-[#555555]">{{ $project->name }}</h2>
                <span class="rounded bg-gray-100 px-2 py-0.5 text-sm text-gray-600">{{ $project->status }}</span>
                @if ($project->has_api)<span class="rounded bg-blue-50 px-2 py-0.5 text-xs text-blue-700">API</span>@endif
                @if ($project->has_mobile_app)<span class="rounded bg-green-50 px-2 py-0.5 text-xs text-green-700">App móvil</span>@endif
            </div>
            @can('projects.edit')
                <a href="{{ route('projects.edit', $project) }}" class="btn-primary text-sm py-2 px-3">Editar</a>
            @endcan
        </div>
        @if ($project->description)
            <p class="mt-3 text-sm text-gray-600">{{ $project->description }}</p>
        @endif
        <dl class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <dt class="text-sm text-gray-500">URL pública</dt><dd class="text-sm">@if ($project->public_url)<a href="{{ $project->public_url }}" target="_blank" class="text-[#E6007E] hover:underline">{{ $project->public_url }}</a>@else — @endif</dd>
            <dt class="text-sm text-gray-500">URL admin</dt><dd class="text-sm">@if ($project->admin_url)<a href="{{ $project->admin_url }}" target="_blank" class="text-[#E6007E] hover:underline">{{ $project->admin_url }}</a>@else — @endif</dd>
            <dt class="text-sm text-gray-500">Framework</dt><dd class="text-sm text-[#555555]">{{ $project->framework ?? '—' }}</dd>
            <dt class="text-sm text-gray-500">Versión backend</dt><dd class="text-sm text-[#555555]">{{ $project->backend_version ?? '—' }}</dd>
            <dt class="text-sm text-gray-500">Versión app</dt><dd class="text-sm text-[#555555]">{{ $project->mobile_app_version ?? '—' }}</dd>
            <dt class="text-sm text-gray-500">Sincronización</dt><dd class="text-sm text-[#555555]">{{ $project->sync_status }}</dd>
        </dl>
    </div>

    @if ($project->slug === 'mpsfp')
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Submódulo del proyecto</p>
                    <h3 class="mt-2 text-base font-semibold text-[#555555]">MPSFP se gestiona desde su panel propio</h3>
                    <p class="mt-1 max-w-3xl text-sm text-gray-500">Esta pantalla es solo la ficha/configuración del proyecto dentro de `Proyectos`. El trabajo operativo de proveedores, importaciones, productos generados, maestros y exportación vive exclusivamente dentro del submódulo `MPSFP`.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Sync: {{ $project->sync_status ?? '—' }}</span>
                    @if ($project->backend_version)
                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Backend: {{ $project->backend_version }}</span>
                    @endif
                </div>
            </div>
            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Aquí sí</p>
                    <p class="mt-2 text-sm font-semibold text-[#555555]">Ficha del proyecto</p>
                    <p class="mt-1 text-sm text-gray-600">URLs, framework, usuarios asignados, integraciones móviles y configuración general del proyecto.</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Aquí no</p>
                    <p class="mt-2 text-sm font-semibold text-[#555555]">Trabajo operativo diario</p>
                    <p class="mt-1 text-sm text-gray-600">Proveedores, importaciones, normalización, catálogo maestro, incidencias y exportación no se gestionan ya en esta ficha.</p>
                </div>
                <div class="rounded-xl border border-[#E6007E]/20 bg-[#FFF7FB] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#E6007E]">Entrada correcta</p>
                    <p class="mt-2 text-sm font-semibold text-[#555555]">Usa el submódulo MPSFP</p>
                    <p class="mt-1 text-sm text-gray-600">Desde ahí verás la navegación propia del módulo y todas sus secciones operativas.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('projects.mpsfp.dashboard', $project) }}" class="btn-primary">Entrar al submódulo MPSFP</a>
                <a href="{{ route('projects.mpsfp.normalized.index', $project) }}" class="btn-secondary">Ver productos generados</a>
                <a href="{{ route('projects.mpsfp.imports.index', $project) }}" class="btn-secondary">Ver importaciones</a>
            </div>
            <p class="mt-3 text-sm text-gray-500">
                La ficha del proyecto y el submódulo tienen propósitos distintos. Desde aquí solo entras y configuras; dentro de `MPSFP` trabajas.
            </p>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-[#555555] mb-3">Usuarios con acceso</h3>
        @if ($project->users->isEmpty())
            <p class="text-sm text-gray-500">Ningún usuario asignado.</p>
        @else
            <ul class="space-y-2 text-sm">
                @foreach ($project->users as $u)
                    <li class="flex items-center justify-between">
                        <span>{{ $u->name }} ({{ $u->email }})</span>
                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{{ $u->pivot->access_level }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
        @can('projects.edit')
            <a href="{{ route('projects.edit', $project) }}#users" class="mt-2 inline-block text-sm text-[#E6007E] hover:underline">Gestionar usuarios</a>
        @endcan
    </div>

    @if ($project->mobileIntegrations->isNotEmpty())
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-[#555555] mb-3">Integraciones móviles</h3>
            <ul class="space-y-2 text-sm">
                @foreach ($project->mobileIntegrations as $mi)
                    <li><a href="{{ route('mobile-integrations.show', $mi) }}" class="text-[#E6007E] hover:underline">{{ $mi->app_name }}</a> — {{ $mi->platform }} ({{ $mi->current_version }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    <a href="{{ route('projects.index') }}" class="inline-block text-sm text-[#555555] hover:underline">← Volver a proyectos</a>
</div>
@endsection
