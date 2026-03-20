@extends('layouts.app')

@section('title', 'Proveedor: ' . $supplier->name)
@section('page_title', 'Proveedor: ' . $supplier->name)

@section('content')
@php
    $canEditSupplier = isset($mpsfpProject)
        ? (bool) data_get($mpsfpAccess ?? [], 'sections.proveedores.actions.edit', false)
        : auth()->user()->can('suppliers.edit');
    $canViewMappings = isset($mpsfpProject)
        ? (bool) data_get($mpsfpAccess ?? [], 'sections.proveedores.view', false)
        : auth()->user()->can('mappings.view');
    $canDeleteSupplier = $canEditSupplier;
@endphp
@php
    $yearlyImports = collect(data_get($catalogHistory ?? [], 'yearly_imports', []));
    $yearComparison = data_get($catalogHistory ?? [], 'comparison');
@endphp
<div class="space-y-4">
    @if (isset($mpsfpProject))
        @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
        @include('projects.mpsfp._context', [
            'project' => $mpsfpProject,
            'label' => 'Proveedores',
            'title' => 'MPSFP / Ficha de proveedor',
            'subtitle' => 'Proveedor actual: ' . $supplier->name,
        ])
    @endif

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-gray-500">Nombre</dt><dd class="text-sm text-[#555555]">{{ $supplier->name }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Slug</dt><dd class="text-sm text-[#555555]">{{ $supplier->slug }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Código</dt><dd class="text-sm text-[#555555]">{{ $supplier->code ?? '—' }}</dd></div>
            <div><dt class="text-sm font-medium text-gray-500">Estado</dt><dd class="text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $supplier->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $supplier->is_active ? 'Activo' : 'Inactivo' }}</span></dd></div>
        </dl>
        @if ($canEditSupplier)
            <div class="mt-4">
                <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.edit', [$mpsfpProject, $supplier]) : route('suppliers.edit', $supplier) }}" class="btn-secondary">Editar</a>
                @if ($canDeleteSupplier)
                    <form action="{{ isset($mpsfpProject) ? route('projects.mpsfp.suppliers.destroy', [$mpsfpProject, $supplier]) : route('suppliers.destroy', $supplier) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Se eliminará el proveedor y todas sus importaciones asociadas. ¿Continuar?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-link-muted">Eliminar proveedor</button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    @if ($yearlyImports->isNotEmpty())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($yearlyImports->take(4) as $yearEntry)
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Catálogo {{ $yearEntry['catalog_year'] }}</p>
                    <p class="mt-2 text-3xl font-black text-[#2f3441]">{{ number_format($yearEntry['normalized_products_count']) }}</p>
                    <p class="mt-2 text-sm text-gray-500">{{ $yearEntry['imports_count'] }} importación(es) · última carga {{ \Illuminate\Support\Carbon::parse($yearEntry['imported_at'])->format('d/m/Y') }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($yearComparison)
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-700">Comparativa anual más reciente</h2>
            <p class="mt-2 text-sm text-blue-900">Comparando {{ $yearComparison['current_import']->catalog_year }} contra {{ $yearComparison['previous_import']->catalog_year }} por referencia de proveedor, para detectar cambios de código o precio entre campañas.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-xl border border-white/80 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase text-gray-400">Referencias cruzadas</p>
                    <p class="mt-1 text-2xl font-black text-[#2f3441]">{{ number_format($yearComparison['shared_references']) }}</p>
                </div>
                <div class="rounded-xl border border-white/80 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase text-gray-400">Código cambiado</p>
                    <p class="mt-1 text-2xl font-black text-amber-700">{{ number_format($yearComparison['changed_code_count']) }}</p>
                </div>
                <div class="rounded-xl border border-white/80 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase text-gray-400">Venta cambiada</p>
                    <p class="mt-1 text-2xl font-black text-emerald-700">{{ number_format($yearComparison['changed_sale_price_count']) }}</p>
                </div>
                <div class="rounded-xl border border-white/80 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase text-gray-400">Compra cambiada</p>
                    <p class="mt-1 text-2xl font-black text-fuchsia-700">{{ number_format($yearComparison['changed_cost_price_count']) }}</p>
                </div>
                <div class="rounded-xl border border-white/80 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase text-gray-400">Altas / Bajas</p>
                    <p class="mt-1 text-lg font-black text-[#2f3441]">+{{ number_format($yearComparison['added_references_count']) }} / -{{ number_format($yearComparison['removed_references_count']) }}</p>
                </div>
            </div>
        </div>
    @endif

    <h2 class="text-sm font-semibold text-[#555555]">Últimas importaciones</h2>
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Archivo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Año</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Fecha</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($supplier->supplierImports as $import)
                    <tr>
                        <td class="px-4 py-3 text-sm text-[#555555]">{{ $import->filename_original }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $import->catalog_year ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm"><span class="rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">{{ $import->status }}</span></td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $import->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ isset($mpsfpProject) ? route('projects.mpsfp.imports.show', [$mpsfpProject, $import]) : route('imports.show', $import) }}" class="btn-link">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Sin importaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="text-sm font-semibold text-[#555555] mt-6">Perfiles de mapeo</h2>
    @if ($canViewMappings)
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Campo destino</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Columna origen</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Transform</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Prioridad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($supplier->supplierFieldMappings ?? [] as $m)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $m->target_field }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $m->source_key }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $m->transform ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $m->priority ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $m->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $m->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Sin perfiles de mapeo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="text-sm text-gray-600 rounded-lg border border-gray-200 bg-gray-50 p-4">
            No tienes permisos para ver perfiles de mapeo.
        </div>
    @endif
</div>
@endsection
