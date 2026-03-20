@extends('layouts.app')

@section('title', 'Editar proveedor')
@section('page_title', 'Editar proveedor')

@section('content')
<div class="space-y-4">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Proveedores',
            'title' => 'MPSFP / Editar proveedor',
            'subtitle' => 'Ajusta identidad, código y estado operativo del proveedor ' . $supplier->name . ' sin perder de vista su actividad reciente dentro del catálogo.',
        ])
    @endif

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_360px]">
        <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.update', [$mpsfpProject, $supplier]) : route('suppliers.update', $supplier) }}" method="POST" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Ficha del proveedor</p>
                <h3 class="mt-2 text-lg font-semibold text-[#555555]">Editar identidad y estado operativo</h3>
                <p class="mt-1 text-sm text-gray-500">Mantén consistencia entre nombre visible, slug técnico y código corto. Estos datos impactan importaciones, filtros y reglas del pipeline.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nombre visible</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $supplier->name) }}" required placeholder="Ej. Yamaha, Fender, Adagio" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Es el nombre comercial que usarás en filtros, listados e importaciones.</p>
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700">Slug interno</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $supplier->slug) }}" required placeholder="yamaha" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Identificador técnico estable para perfiles, reglas y automatizaciones.</p>
                    @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Código corto</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $supplier->code) }}" placeholder="YMH" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Atajo opcional para tablas, nomenclaturas internas y revisiones rápidas.</p>
                    @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-[#555555]">Estado operativo</p>
                        <p class="mt-1 text-xs text-gray-500">Al dejarlo activo, seguirá apareciendo en pantallas de importación, filtros y procesos automáticos.</p>
                    </div>
                    <label class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supplier->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-[#E6007E] focus:ring-[#E6007E]">
                        <span class="ml-2 text-sm font-medium text-gray-700">Proveedor activo</span>
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-primary">Guardar cambios</button>
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.show', [$mpsfpProject, $supplier]) : route('suppliers.show', $supplier) }}" class="btn-secondary">Volver a la ficha</a>
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.index', $mpsfpProject) : route('suppliers.index') }}" class="btn-secondary">Listado</a>
            </div>
        </form>

        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Resumen actual</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Importaciones</p>
                        <p class="mt-2 text-2xl font-black text-[#2f3441]">{{ number_format($supplier->supplier_imports_count ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Normalizados</p>
                        <p class="mt-2 text-2xl font-black text-[#2f3441]">{{ number_format($supplier->normalized_products_count ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400">Estado</p>
                        <p class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold {{ $supplier->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $supplier->is_active ? 'ACTIVO' : 'INACTIVO' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Últimas importaciones</p>
                <div class="mt-4 space-y-3">
                    @forelse (($supplier->supplierImports ?? collect())->take(3) as $import)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="text-sm font-semibold text-[#555555]">{{ $import->filename_original }}</p>
                            <p class="mt-1 text-xs text-gray-500">Año {{ $import->catalog_year ?? '—' }} · {{ $import->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400">{{ $import->status }}</p>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-4 text-sm text-gray-500">
                            Este proveedor todavía no tiene importaciones recientes.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-[#E6007E]/15 bg-[#FFF7FB] p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#E6007E]">Consejo operativo</p>
                <p class="mt-3 text-sm text-gray-600">Si cambias el `slug`, asegúrate de que siga alineado con las reglas automáticas del proveedor. Si solo cambias nombre visible o código corto, el impacto es mucho más seguro.</p>
            </div>
        </div>
    </div>
</div>
@endsection
