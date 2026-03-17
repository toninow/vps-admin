@extends('layouts.app')

@section('title', $mobileIntegration->app_name)
@section('page_title', $mobileIntegration->app_name)

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <dt class="text-sm font-medium text-gray-500">Proyecto</dt>
            <dd class="text-sm"><a href="{{ route('projects.show', $mobileIntegration->project) }}" class="text-[#E6007E] hover:underline">{{ $mobileIntegration->project->name }}</a></dd>
            <dt class="text-sm font-medium text-gray-500">Plataforma</dt><dd class="text-sm text-[#555555]">{{ $mobileIntegration->platform }}</dd>
            <dt class="text-sm font-medium text-gray-500">Versión actual</dt><dd class="text-sm text-[#555555]">{{ $mobileIntegration->current_version }}</dd>
            <dt class="text-sm font-medium text-gray-500">Versión mínima</dt><dd class="text-sm text-[#555555]">{{ $mobileIntegration->min_supported_version ?? '—' }}</dd>
            <dt class="text-sm font-medium text-gray-500">URL API</dt><dd class="text-sm text-[#555555]">{{ $mobileIntegration->api_url ?? '—' }}</dd>
            <dt class="text-sm font-medium text-gray-500">Estado</dt><dd class="text-sm text-[#555555]">{{ $mobileIntegration->connection_status }}</dd>
            <dt class="text-sm font-medium text-gray-500">Última sincronización</dt><dd class="text-sm text-[#555555]">{{ $mobileIntegration->last_synced_at?->format('d/m/Y H:i') ?? '—' }}</dd>
        </dl>
        @if ($mobileIntegration->notes)
            <p class="mt-3 text-sm text-gray-600">{{ $mobileIntegration->notes }}</p>
        @endif
        <div class="mt-4 flex gap-2">
            @can('mobile_integrations.edit')
                <a href="{{ route('mobile-integrations.edit', $mobileIntegration) }}" class="btn-primary text-sm py-2 px-3">Editar</a>
            @endcan
            <a href="{{ route('mobile-integrations.index') }}" class="btn-secondary text-sm py-2 px-3">Volver</a>
        </div>
    </div>
</div>
@endsection
