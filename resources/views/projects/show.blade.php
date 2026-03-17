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
