@extends('layouts.app')

@section('title', 'Nueva importación')
@section('page_title', 'Nueva importación')

@section('content')
<div class="max-w-xl space-y-4">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Importaciones',
            'title' => 'MPSFP / Nueva importación',
            'subtitle' => 'Sube un CSV, XLSX o XML y continúa al preview y al mapeo.',
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
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
    <p class="text-sm text-gray-600">Seleccione el proveedor y el archivo (CSV, Excel XLSX/XLS o XML). Tamaño máximo 500 MB.</p>
    <p class="mt-1 text-xs text-gray-500">Para probar con CSV: use un archivo con cabecera (ej. name, quantity, ean13, brand) y varias filas separadas por coma o punto y coma.</p>
    <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.store', $mpsfpProject) : route('imports.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
        @csrf
        <div>
            <label for="supplier_id" class="block text-sm font-medium text-gray-700">Proveedor</label>
            <select name="supplier_id" id="supplier_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                <option value="">Seleccione...</option>
                @foreach ($suppliers as $s)
                    <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('supplier_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="file" class="block text-sm font-medium text-gray-700">Archivo</label>
            <input type="file" name="file" id="file" required accept=".csv,.txt,.xlsx,.xls,.xml" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-[#E6007E] file:py-2 file:px-4 file:text-white file:text-sm file:font-medium hover:file:bg-[#d1006f]">
            @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="catalog_year" class="block text-sm font-medium text-gray-700">Año de catálogo</label>
            <input type="number" name="catalog_year" id="catalog_year" min="2020" max="{{ now()->year + 2 }}" value="{{ old('catalog_year', now()->year) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            <p class="mt-1 text-xs text-gray-500">Sirve para comparar campañas anuales del mismo proveedor: cambios de precio, EAN o referencia entre 2026, 2027, etc.</p>
            @error('catalog_year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Subir y continuar</button>
            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.index', $mpsfpProject) : route('imports.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>
@endsection
