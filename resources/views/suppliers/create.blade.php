@extends('layouts.app')

@section('title', 'Nuevo proveedor')
@section('page_title', 'Nuevo proveedor')

@section('content')
<div class="space-y-4">
    @if (isset($mpsfpProject))
        <div class="mb-4">
            @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        </div>
        <div class="mb-4">
            @include('projects.mpsfp._context', [
                'project' => $mpsfpProject,
                'label' => 'Proveedores',
                'title' => 'MPSFP / Alta de proveedor',
                'subtitle' => 'Da de alta un proveedor de forma clara y deja preparado su identificador para futuras importaciones, mapeos y revisiones.',
            ])
        </div>
    @endif

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_360px]">
        <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.store', $mpsfpProject) : route('suppliers.store') }}" method="POST" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Ficha del proveedor</p>
                <h3 class="mt-2 text-lg font-semibold text-[#555555]">Datos base para empezar a importar</h3>
                <p class="mt-1 text-sm text-gray-500">Completa primero identidad y estado. El mapeo del proveedor lo trabajarás después desde las importaciones.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre visible</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Ej. Yamaha, Fender, Adagio" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Es el nombre que verás en filtros, listados e importaciones.</p>
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700">Slug interno</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required placeholder="yamaha" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Usa minúsculas, sin espacios y sin caracteres raros.</p>
                    @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Código corto</label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" placeholder="YMH" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Opcional. Te sirve como referencia rápida en tablas o flujos internos.</p>
                    @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-[#555555]">Estado operativo</p>
                        <p class="mt-1 text-xs text-gray-500">Si lo marcas activo, aparecerá inmediatamente en las pantallas de importación y filtros del submódulo.</p>
                    </div>
                    <label class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                        <span class="ml-2 text-sm font-medium text-gray-700">Proveedor activo</span>
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-primary">Guardar proveedor</button>
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.index', $mpsfpProject) : route('suppliers.index') }}" class="btn-secondary">Volver al listado</a>
            </div>
        </form>

        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Que significa cada campo</p>
                <div class="mt-4 space-y-3 text-sm text-gray-600">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="font-semibold text-[#555555]">Nombre visible</p>
                        <p class="mt-1">Etiqueta comercial con la que identificarás el proveedor dentro de `MPSFP`.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="font-semibold text-[#555555]">Slug interno</p>
                        <p class="mt-1">Identificador técnico estable para automatizaciones, perfiles de proveedor y reglas futuras.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="font-semibold text-[#555555]">Código corto</p>
                        <p class="mt-1">Atajo opcional para tablas, listados o convenciones internas del equipo.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-[#E6007E]/15 bg-[#FFF7FB] p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#E6007E]">Siguiente paso</p>
                <ol class="mt-3 space-y-2 text-sm text-gray-600">
                    <li>1. Guarda el proveedor.</li>
                    <li>2. Entra en `Importaciones` y sube un archivo CSV, XLSX o XML.</li>
                    <li>3. Revisa preview, mapeo y proceso.</li>
                    <li>4. Después filtra por este proveedor en `Productos generados`.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
