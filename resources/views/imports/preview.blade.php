@extends('layouts.app')

@section('title', 'Preview importación')
@section('page_title', 'Preview importación')

@section('content')
<div class="space-y-4">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Importaciones',
            'title' => 'MPSFP / Preview de importación',
            'subtitle' => 'Estás revisando columnas y primeras filas antes del mapeo.',
        ])
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-sm text-[#555555]"><strong>Archivo:</strong> {{ $import->filename_original }} — <strong>Proveedor:</strong> {{ $import->supplier->name ?? '—' }} — <strong>Tipo:</strong> {{ $import->file_type }} — <strong>Año catálogo:</strong> {{ $import->catalog_year ?? '—' }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.mapping', [$mpsfpProject, $import]) : route('imports.mapping', $import) }}" class="btn-primary">Continuar a mapeo</a>
            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.show', [$mpsfpProject, $import]) : route('imports.show', $import) }}" class="btn-secondary">Ver importación</a>
            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.index', $mpsfpProject) : route('imports.index') }}" class="btn-link-muted">Listado</a>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-[#555555]">Columnas detectadas ({{ count($columns) }})</h2>
        <p class="mt-1 text-xs text-gray-500">Estas son las columnas del archivo. En el siguiente paso las mapeará a los campos internos.</p>
        @if (empty($columns))
            <p class="mt-2 text-sm text-amber-700">No se detectaron columnas. Compruebe que el archivo tenga cabecera o estructura válida.</p>
        @else
            <ul class="mt-2 flex flex-wrap gap-2">
                @foreach ($columns as $col)
                    <li class="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $col }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <h2 class="border-b border-gray-200 px-4 py-3 text-sm font-semibold text-[#555555]">Primeras filas (preview)</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">#</th>
                    @foreach ($columns as $col)
                        <th class="max-w-[200px] truncate px-3 py-2 text-left text-xs font-medium uppercase text-gray-500" title="{{ $col }}">{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($previewRows as $idx => $row)
                    <tr>
                        <td class="whitespace-nowrap px-3 py-2 text-xs text-gray-500">{{ $idx + 1 }}</td>
                        @foreach ($columns as $col)
                            <td class="max-w-[200px] truncate px-3 py-2 text-xs text-gray-700" title="{{ $row[$col] ?? '' }}">{{ $row[$col] ?? '—' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ max(1, count($columns) + 1) }}" class="px-4 py-6 text-center text-gray-500">No hay filas de datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
