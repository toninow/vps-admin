@extends('layouts.app')

@section('title', 'MPSFP · Cruce proveedores')
@section('page_title', 'MPSFP · Cruce proveedores')

@section('content')
@php
    $filterAction = route('projects.mpsfp.cross-suppliers.index', $mpsfpProject);
@endphp

<div class="space-y-4">
    @include('projects.mpsfp._nav', ['project' => $mpsfpProject, 'sections' => $mpsfpSections])
    @include('projects.mpsfp._context', [
        'project' => $mpsfpProject,
        'label' => 'Cruce proveedores',
        'title' => 'MPSFP / Mismo producto en varios proveedores',
        'subtitle' => 'Agrupa productos por EAN cuando aparecen en más de un proveedor. Te ayuda a detectar el mismo producto comercial vendido por distintas fuentes.',
    ])

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Grupos detectados</p>
            <p class="mt-2 text-3xl font-bold text-[#555555]">{{ number_format($groups->total()) }}</p>
            <p class="mt-2 text-sm text-gray-500">EAN con más de un proveedor distinto.</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Base de detección</p>
            <p class="mt-2 text-sm text-gray-600">Se considera el mismo producto cuando varias filas comparten el mismo `EAN13` y pertenecen a proveedores distintos.</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Uso práctico</p>
            <p class="mt-2 text-sm text-gray-600">Compara nombre, precio, stock y referencia por proveedor antes de unificar o decidir proveedor principal.</p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-4 alert alert-info">
            Esta pantalla solo encuentra productos repetidos entre proveedores cuando comparten el mismo <strong>EAN13</strong>.
            Si buscas una referencia o nombre que solo existe en un proveedor, no aparecerá aquí.
            <a href="{{ route('projects.mpsfp.normalized.index', [$mpsfpProject, 'search' => request('search')]) }}" class="btn-link ml-2">
                Buscarlo en Normalizados
            </a>
        </div>

        <form method="GET" action="{{ $filterAction }}" class="grid gap-3 lg:grid-cols-4 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label for="search" class="mb-1 block text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Buscar</label>
                <input id="search" type="text" name="search" value="{{ request('search') }}" placeholder="EAN, nombre, marca o ref. proveedor" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-[#E6007E] focus:ring-[#E6007E] sm:text-sm">
            </div>
            <div>
                <label for="supplier_id" class="mb-1 block text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">Proveedor</label>
                <select id="supplier_id" name="supplier_id" class="w-full rounded-xl border-gray-300 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="action-stack xl:col-span-6">
                <button type="submit" class="btn-primary">Aplicar filtros</button>
                <a href="{{ $filterAction }}" class="btn-secondary">Quitar filtros</a>
            </div>
        </form>
    </div>

    <div class="space-y-4">
        @forelse ($groups as $group)
            @php
                $groupProducts = collect($productsByEan[$group->ean13] ?? []);
            @endphp
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-[#555555]">EAN {{ $group->ean13 }}</h3>
                            <span class="status-badge status-pink">{{ (int) $group->suppliers_count }} proveedores</span>
                            <span class="status-badge status-blue">{{ (int) $group->rows_count }} filas</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">{{ $group->supplier_names }}</p>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Proveedor</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Producto</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Ref. proveedor</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Stock</th>
                                <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-500">Precio</th>
                                <th class="px-3 py-2 text-right text-xs font-medium uppercase text-gray-500">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($groupProducts as $product)
                                <tr>
                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $product->supplier->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-[#555555]">{{ $product->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $product->supplier_reference ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $product->quantity ?? 0 }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $product->price_tax_incl ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <a href="{{ route('projects.mpsfp.normalized.show', [$mpsfpProject, $product]) }}" class="btn-link">Ver ficha</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <p class="text-sm font-semibold text-[#555555]">No hay grupos de producto compartido para los filtros actuales.</p>
                <p class="mt-2 text-sm text-gray-500">Prueba otra búsqueda o quita el filtro de proveedor.</p>
            </div>
        @endforelse
    </div>

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <p class="text-sm text-gray-500">
            Mostrando {{ $groups->firstItem() ?? 0 }}-{{ $groups->lastItem() ?? 0 }} de {{ number_format($groups->total()) }} grupos detectados.
        </p>
        <div class="flex justify-center">{{ $groups->withQueryString()->links() }}</div>
    </div>
</div>
@endsection
