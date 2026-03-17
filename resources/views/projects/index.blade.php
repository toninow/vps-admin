@extends('layouts.app')

@section('title', 'Proyectos')
@section('page_title', 'Proyectos')

@section('content')
<div class="space-y-4">
    @can('projects.create')
        <div class="flex justify-end">
            <a href="{{ route('projects.create') }}" class="rounded-md bg-[#E6007E] px-4 py-2 text-sm font-medium text-white hover:bg-pink-700">Nuevo proyecto</a>
        </div>
    @endcan

    <form method="GET" action="{{ route('projects.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar..." class="rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
        <select name="status" class="rounded-md border-gray-300 sm:text-sm">
            <option value="">Todos</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
            <option value="development" {{ request('status') === 'development' ? 'selected' : '' }}>Desarrollo</option>
            <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Pausado</option>
            <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archivado</option>
        </select>
        <button type="submit" class="rounded-md bg-gray-200 px-3 py-2 text-sm font-medium text-[#555555] hover:bg-gray-300">Filtrar</button>
    </form>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($projects as $project)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        @if ($project->color)
                            <span class="inline-block h-4 w-4 flex-shrink-0 rounded-full" style="background-color: {{ $project->color }}"></span>
                        @endif
                        <h3 class="font-semibold text-[#555555] truncate">{{ $project->name }}</h3>
                    </div>
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">{{ $project->status }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ $project->description ?? '—' }}</p>
                <div class="mt-2 flex flex-wrap gap-1">
                    @if ($project->has_api)<span class="rounded bg-blue-50 px-1.5 py-0.5 text-xs text-blue-700">API</span>@endif
                    @if ($project->has_mobile_app)<span class="rounded bg-green-50 px-1.5 py-0.5 text-xs text-green-700">App</span>@endif
                </div>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('projects.show', $project) }}" class="btn-link">Ver</a>
                    @can('projects.edit')
                        <a href="{{ route('projects.edit', $project) }}" class="btn-link-muted">Editar</a>
                    @endcan
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500">No hay proyectos.</div>
        @endforelse
    </div>

    <div class="flex justify-center">{{ $projects->withQueryString()->links() }}</div>
</div>
@endsection
