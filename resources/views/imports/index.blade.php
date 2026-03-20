@extends('layouts.app')

@section('title', 'Importaciones')
@section('page_title', 'Importaciones')

@section('content')
@php
    $canCreateImport = isset($mpsfpProject)
        ? (bool) data_get($mpsfpAccess ?? [], 'sections.importaciones.actions.create', false)
        : auth()->user()->can('imports.create');
    $canDeleteImport = isset($mpsfpProject)
        ? (bool) data_get($mpsfpAccess ?? [], 'sections.importaciones.actions.process', false)
        : auth()->user()->can('imports.process');
@endphp
<div class="space-y-6">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Importaciones',
            'title' => 'MPSFP / Importaciones',
            'subtitle' => 'Aquí ves todas las importaciones del módulo y puedes subir nuevos archivos de proveedor.',
        ])
    @endif

    @if ($canCreateImport)
        <div class="flex justify-end">
            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.create', $mpsfpProject) : route('imports.create') }}" class="btn-primary w-full sm:w-auto">Nueva importación</a>
        </div>
    @endif

    <form method="GET" action="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.index', $mpsfpProject) : route('imports.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <select name="supplier_id" class="w-full rounded-md border-gray-300 sm:text-sm">
            <option value="">Todos los proveedores</option>
            @foreach ($suppliers as $s)
                <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        <select name="status" class="w-full rounded-md border-gray-300 sm:text-sm">
            <option value="">Todos los estados</option>
            <option value="uploaded" {{ request('status') === 'uploaded' ? 'selected' : '' }}>Subido</option>
            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Procesando</option>
            <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>Procesado</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallido</option>
        </select>
        <input type="number" name="catalog_year" min="2020" max="{{ now()->year + 2 }}" value="{{ request('catalog_year') }}" placeholder="Año catálogo" class="w-full rounded-md border-gray-300 sm:text-sm">
        <input type="date" name="imported_from" value="{{ request('imported_from') }}" class="w-full rounded-md border-gray-300 sm:text-sm">
        <input type="date" name="imported_to" value="{{ request('imported_to') }}" class="w-full rounded-md border-gray-300 sm:text-sm">
        <button type="submit" class="btn-secondary xl:col-span-1">Filtrar</button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Archivo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Proveedor</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Año</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Filas</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Fecha</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($imports as $import)
                    <tr>
                        <td class="px-4 py-3 text-sm text-[#555555]">{{ $import->filename_original }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $import->supplier->name ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">{{ $import->status }}</span></td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $import->catalog_year ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $import->processed_rows ?? 0 }}/{{ $import->total_rows ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $import->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.show', [$mpsfpProject, $import]) : route('imports.show', $import) }}" class="btn-link">Ver</a>
                            @if ($canDeleteImport)
                                <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.destroy', [$mpsfpProject, $import]) : route('imports.destroy', $import) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Se eliminará la importación y sus productos normalizados asociados. ¿Continuar?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-link-muted">Eliminar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No hay importaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-center">{{ $imports->withQueryString()->links() }}</div>
</div>
@endsection
